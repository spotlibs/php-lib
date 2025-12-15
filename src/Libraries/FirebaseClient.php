<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Logs\Log;

/**
 * FirebaseClient
 *
 * SDK for Firebase OAuth and FCM operations with singleton token support
 *
 * @category HttpClient
 * @package  Client
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class FirebaseClient
{
    private GuzzleClient $httpClient;
    private array $serviceAccount;
    private string $proxyUrl = '';

    /**
     * Create Firebase client
     *
     * @param array $config Guzzle config options
     *
     * @throws RuntimeException When FIREBASE_CREDENTIALS env not set
     */
    public function __construct(array $config = [])
    {
        $serviceAccountPath = env('FIREBASE_CREDENTIALS');
        if (empty($serviceAccountPath)) {
            throw new RuntimeException('FIREBASE_CREDENTIALS environment variable is not set');
        }
        $fullPath = base_path($serviceAccountPath);
        if (!file_exists($fullPath)) {
            throw new RuntimeException("Firebase credentials file not found: {$fullPath}");
        }

        $this->serviceAccount = json_decode(
            file_get_contents($fullPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $defaultConfig = [
            'timeout' => 60,
            'verify' => false,
        ];

        $this->httpClient = new GuzzleClient(array_merge($defaultConfig, $config));
    }

    /**
     * Set proxy URL
     *
     * @param string $proxyUrl Proxy URL (e.g., http://proxy:port)
     *
     * @return self
     */
    public function setProxy(string $proxyUrl): self
    {
        $this->proxyUrl = $proxyUrl;
        return $this;
    }

    /**
     * Generate OAuth2 access token
     *
     * @return array Array with 'token' and 'expiry' keys
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    public function generateToken(): array
    {
        $startTime = microtime(true);
        $now = time();

        $jwt = $this->createJWT(
            [
                'iss' => $this->serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600
            ],
            $this->serviceAccount['private_key']
        );

        $body = http_build_query(
            [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
            ]
        );

        $request = new Request(
            'POST',
            'https://oauth2.googleapis.com/token',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $body
        );

        $options = [];
        if (!empty($this->proxyUrl)) {
            $options['proxy'] = $this->proxyUrl;
        }

        $response = $this->httpClient->send($request, $options);
        $elapsed = microtime(true) - $startTime;

        $responseBody = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $token = $responseBody['access_token'];
        $expiresIn = $responseBody['expires_in'] ?? 3600;
        $expiry = time() + $expiresIn;

        Log::runtime()->info(
            [
            'operation' => 'firebase_oauth',
            'url' => 'https://oauth2.googleapis.com/token',
            'responseTime' => round($elapsed * 1000),
            'httpCode' => $response->getStatusCode()
            ]
        );

        return ['token' => $token, 'expiry' => $expiry];
    }

    /**
     * Send FCM message
     *
     * @param array $message FCM message payload
     *
     * @return ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    public function sendMessage(array $message): ResponseInterface
    {
        // Get token from singleton
        $tokenData = app('firebase.token');

        // Check if empty or expired (with 5 min buffer)
        if (empty($tokenData['token']) || $tokenData['expiry'] <= time() + 300) {
            Log::runtime()->info(
                [
                'operation' => 'firebase_token_refresh',
                'reason' => empty($tokenData['token']) ? 'empty' : 'expired'
                ]
            );

            // Regenerate and update singleton
            app()->forgetInstance('firebase.token');
            $tokenData = app('firebase.token');
        }

        $startTime = microtime(true);
        $projectId = $this->serviceAccount['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $request = new Request(
            'POST',
            $url,
            [
                'Authorization' => 'Bearer ' . $tokenData['token'],
                'Content-Type' => 'application/json'
            ],
            json_encode(['message' => $message], JSON_THROW_ON_ERROR)
        );

        $options = [];
        if (!empty($this->proxyUrl)) {
            $options['proxy'] = $this->proxyUrl;
        }

        try {
            $response = $this->httpClient->send($request, $options);
            $elapsed = microtime(true) - $startTime;

            $respBody = $response->getBody()->getContents();
            $response->getBody()->rewind();

            Log::runtime()->info(
                [
                'operation' => 'firebase_fcm_send',
                'host' => 'fcm.googleapis.com',
                'url' => "/v1/projects/{$projectId}/messages:send",
                'request' => ['body' => $message],
                'response' => [
                    'httpCode' => $response->getStatusCode(),
                    'body' => json_decode($respBody, true)
                ],
                'responseTime' => round($elapsed * 1000)
                ]
            );

            return $response;
        } catch (ClientException $e) {
            // On 401, regenerate token and retry once
            if ($e->getResponse()->getStatusCode() === 401) {
                Log::runtime()->warning(
                    [
                    'operation' => 'firebase_fcm_send_401',
                    'message' => 'Token unauthorized, regenerating and retrying'
                    ]
                );

                // Regenerate and update singleton
                app()->forgetInstance('firebase.token');
                $newTokenData = app('firebase.token');

                $retryRequest = new Request(
                    'POST',
                    $url,
                    [
                        'Authorization' => 'Bearer ' . $newTokenData['token'],
                        'Content-Type' => 'application/json'
                    ],
                    json_encode(['message' => $message], JSON_THROW_ON_ERROR)
                );

                return $this->httpClient->send($retryRequest, $options);
            }

            throw $e;
        }
    }

    /**
     * Send to multiple tokens (multicast)
     *
     * @param array $tokens       FCM registration tokens
     * @param array $notification Notification payload
     * @param array $data         Data payload
     *
     * @return array Results with success/failure counts
     */
    public function sendMulticast(
        array $tokens,
        array $notification = [],
        array $data = []
    ): array {
        $results = ['success' => 0, 'failure' => 0, 'responses' => []];

        foreach ($tokens as $token) {
            $message = ['token' => $token];
            if (!empty($notification)) {
                $message['notification'] = $notification;
            }
            if (!empty($data)) {
                $message['data'] = $data;
            }

            try {
                $response = $this->sendMessage($message);
                if ($response->getStatusCode() === 200) {
                    $results['success']++;
                    $results['responses'][] = [
                        'token' => $token,
                        'success' => true
                    ];
                } else {
                    $results['failure']++;
                    $results['responses'][] = [
                        'token' => $token,
                        'success' => false,
                        'error' => $response->getBody()->getContents()
                    ];
                }
            } catch (\Throwable $e) {
                $results['failure']++;
                $results['responses'][] = [
                    'token' => $token,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Generate JWT manually using OpenSSL
     *
     * @param array  $payload    JWT payload
     * @param string $privateKey RSA private key
     *
     * @return string JWT token
     */
    private function createJWT(array $payload, string $privateKey): string
    {
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));

        $signature = '';
        openssl_sign(
            $header . '.' . $payload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        $signature = base64_encode($signature);

        return str_replace(['+', '/', '='], ['-', '_', ''], $header . '.' . $payload . '.' . $signature);
    }
}
