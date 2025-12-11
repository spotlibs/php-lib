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
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Logs\Log;

/**
 * FirebaseClient
 *
 * SDK for Firebase OAuth and FCM operations
 *
 * @category HttpClient
 * @package  Client
 * @author   Abdul Rasyid Anshori <abdul.rasyid.anshori@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class FirebaseClient
{
    private GuzzleClient $httpClient;
    private array $serviceAccount;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;
    private string $proxyUrl = '';

    /**
     * Create Firebase client
     *
     * @param string $serviceAccountPath Path to service account JSON
     * @param array  $config             Guzzle config options
     */
    public function __construct(string $serviceAccountPath, array $config = [])
    {
        $this->serviceAccount = json_decode(
            file_get_contents($serviceAccountPath),
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
     * Set pre-generated access token (bypass OAuth)
     *
     * @param string $token     Access token
     * @param int    $expiresIn Token lifetime in seconds (default 3600)
     *
     * @return self
     */
    public function setAccessToken(string $token, int $expiresIn = 3600): self
    {
        $this->accessToken = $token;
        $this->tokenExpiry = time() + $expiresIn;
        return $this;
    }

    /**
     * Generate OAuth2 access token
     *
     * @return string Access token
     */
    public function generateToken(): string
    {
        if ($this->accessToken && $this->tokenExpiry > time() + 300) {
            return $this->accessToken;
        }

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

        $this->accessToken = $responseBody['access_token'];
        $this->tokenExpiry = time() + ($responseBody['expires_in'] ?? 3600);

        Log::activity()->info(
            [
            'operation' => 'firebase_oauth',
            'url' => 'https://oauth2.googleapis.com/token',
            'responseTime' => round($elapsed * 1000),
            'httpCode' => $response->getStatusCode()
            ]
        );

        return $this->accessToken;
    }

    /**
     * Send FCM message
     *
     * @param array $message FCM message payload
     *
     * @return ResponseInterface
     */
    public function sendMessage(array $message): ResponseInterface
    {
        $token = $this->generateToken();
        $startTime = microtime(true);

        $projectId = $this->serviceAccount['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $request = new Request(
            'POST',
            $url,
            [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            json_encode(['message' => $message], JSON_THROW_ON_ERROR)
        );

        $options = [];
        if (!empty($this->proxyUrl)) {
            $options['proxy'] = $this->proxyUrl;
        }

        $response = $this->httpClient->send($request, $options);
        $elapsed = microtime(true) - $startTime;

        $respBody = $response->getBody()->getContents();
        $response->getBody()->rewind();

        Log::activity()->info(
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

        // Make base64url safe
        return str_replace(['+', '/', '='], ['-', '_', ''], $header . '.' . $payload . '.' . $signature);
    }
}
