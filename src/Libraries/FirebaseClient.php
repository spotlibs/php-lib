<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.8
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
 * SDK for Firebase OAuth and FCM operations with file-based token persistence
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
    private string $tokenFile;

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

        // Set token file path in storage
        $this->tokenFile = storage_path('framework/cache/firebase_token.json');

        // Ensure directory exists
        $dir = dirname($this->tokenFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

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
     * Get or refresh access token from file
     *
     * @param bool $forceRefresh Force token regeneration
     *
     * @return string Access token
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    private function getAccessToken(bool $forceRefresh = false): string
    {
        // Try to read existing token
        if (!$forceRefresh && file_exists($this->tokenFile)) {
            $handle = fopen($this->tokenFile, 'r');
            if ($handle && flock($handle, LOCK_SH)) {
                $content = fread($handle, filesize($this->tokenFile));
                flock($handle, LOCK_UN);
                fclose($handle);

                $tokenData = json_decode($content, true);

                // Check if token is still valid (with 5 min buffer)
                if ($tokenData && isset($tokenData['token'], $tokenData['expiry']) && $tokenData['expiry'] > time() + 300) {
                    return $tokenData['token'];
                }
            } elseif ($handle) {
                fclose($handle);
            }
        }

        Log::runtime()->info(
            [
                'operation' => 'firebase_token_refresh',
                'reason' => $forceRefresh ? 'forced' : (!file_exists($this->tokenFile) ? 'empty' : 'expired')
            ]
        );

        // Generate new token and save to file
        $tokenData = $this->generateToken();
        $this->saveTokenToFile($tokenData);

        return $tokenData['token'];
    }

    /**
     * Save token data to file with lock
     *
     * @param array $tokenData Token data with 'token' and 'expiry' keys
     *
     * @return void
     */
    private function saveTokenToFile(array $tokenData): void
    {
        $handle = fopen($this->tokenFile, 'c');
        if ($handle && flock($handle, LOCK_EX)) {
            ftruncate($handle, 0);
            fwrite($handle, json_encode($tokenData, JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);
        }
        if ($handle) {
            fclose($handle);
        }
    }

    /**
     * Generate OAuth2 access token
     *
     * @return array Array with 'token' and 'expiry' keys
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    private function generateToken(): array
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
        $token = $this->getAccessToken();

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

                $newToken = $this->getAccessToken(true);

                $retryRequest = new Request(
                    'POST',
                    $url,
                    [
                        'Authorization' => 'Bearer ' . $newToken,
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
     * Maximum number of concurrent HTTP requests per curl_multi batch
     *
     * @var int
     */
    private const MAX_PARALLEL_REQUESTS = 20;

    /**
     * Send to multiple tokens using curl_multi for parallel execution
     *
     * Unlike sendMulticast() which sends sequentially, this method fires all
     * HTTP requests concurrently using curl_multi. Tokens are processed in
     * batches of 20 to avoid overwhelming the network stack.
     * For 100 tokens at ~200ms each: sequential = ~20s, parallel = ~1-2s (5 batches).
     *
     * @param array $tokens       FCM registration tokens
     * @param array $notification Notification payload
     * @param array $data         Data payload
     *
     * @return array Results with success/failure counts
     */
    public function sendMulticastParallel(
        array $tokens,
        array $notification = [],
        array $data = []
    ): array {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'responses' => []];
        }

        $accessToken = $this->getAccessToken();
        $projectId = $this->serviceAccount['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $startTime = microtime(true);

        // Process tokens in batches of MAX_PARALLEL_REQUESTS
        $results = ['success' => 0, 'failure' => 0, 'responses' => []];
        $chunks = array_chunk($tokens, self::MAX_PARALLEL_REQUESTS);

        foreach ($chunks as $chunk) {
            $batchResults = $this->executeCurlMulti($chunk, $notification, $data, $accessToken, $url);
            $results['success'] += $batchResults['success'];
            $results['failure'] += $batchResults['failure'];
            foreach ($batchResults['responses'] as $resp) {
                $results['responses'][] = $resp;
            }
        }

        // Retry 401 failures with refreshed token
        $retryTokens = [];
        $retryIndices = [];
        foreach ($results['responses'] as $index => $resp) {
            if (!$resp['success'] && isset($resp['httpCode']) && $resp['httpCode'] === 401) {
                $retryTokens[] = $resp['token'];
                $retryIndices[] = $index;
            }
        }

        if (!empty($retryTokens)) {
            Log::runtime()->warning(
                [
                    'operation' => 'firebase_multicast_parallel_401_retry',
                    'count' => count($retryTokens),
                    'message' => 'Retrying failed tokens with refreshed access token'
                ]
            );

            $newAccessToken = $this->getAccessToken(true);

            // Retry also in batches
            $retryChunks = array_chunk($retryTokens, self::MAX_PARALLEL_REQUESTS);
            $allRetryResponses = [];
            foreach ($retryChunks as $retryChunk) {
                $chunkResults = $this->executeCurlMulti($retryChunk, $notification, $data, $newAccessToken, $url);
                foreach ($chunkResults['responses'] as $resp) {
                    $allRetryResponses[] = $resp;
                }
            }

            foreach ($retryIndices as $i => $originalIndex) {
                $retryResp = $allRetryResponses[$i];
                $oldResp = $results['responses'][$originalIndex];

                if ($retryResp['success'] && !$oldResp['success']) {
                    $results['success']++;
                    $results['failure']--;
                }
                $results['responses'][$originalIndex] = $retryResp;
            }
        }

        $elapsed = microtime(true) - $startTime;

        Log::runtime()->info(
            [
                'operation' => 'firebase_multicast_parallel_complete',
                'totalTokens' => count($tokens),
                'success' => $results['success'],
                'failure' => $results['failure'],
                'responseTime' => round($elapsed * 1000)
            ]
        );

        return $results;
    }

    /**
     * Execute parallel FCM sends using curl_multi
     *
     * @param array  $tokens       FCM device tokens
     * @param array  $notification Notification payload
     * @param array  $data         Data payload
     * @param string $accessToken  OAuth2 bearer token
     * @param string $url          FCM endpoint URL
     *
     * @return array Results with success/failure counts and responses
     */
    private function executeCurlMulti(
        array $tokens,
        array $notification,
        array $data,
        string $accessToken,
        string $url
    ): array {
        $results = ['success' => 0, 'failure' => 0, 'responses' => []];
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($tokens as $index => $deviceToken) {
            $message = ['token' => $deviceToken];
            if (!empty($notification)) {
                $message['notification'] = $notification;
            }
            if (!empty($data)) {
                $message['data'] = $data;
            }

            $payload = json_encode(['message' => $message], JSON_THROW_ON_ERROR);

            $ch = curl_init();
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $accessToken,
                        'Content-Type: application/json',
                    ],
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]
            );

            if (!empty($this->proxyUrl)) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl);
            }

            $curlHandles[$index] = ['handle' => $ch, 'token' => $deviceToken];
            curl_multi_add_handle($multiHandle, $ch);
        }

        // Execute all requests in parallel
        $running = null;
        do {
            $status = curl_multi_exec($multiHandle, $running);
            if ($status > CURLM_OK) {
                break;
            }
            if ($running > 0) {
                curl_multi_select($multiHandle, 1.0);
            }
        } while ($running > 0);

        // Collect results
        foreach ($curlHandles as $index => $item) {
            $ch = $item['handle'];
            $deviceToken = $item['token'];

            $responseBody = curl_multi_getcontent($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            if (!empty($curlError)) {
                $results['failure']++;
                $results['responses'][$index] = [
                    'token' => $deviceToken,
                    'success' => false,
                    'error' => $curlError,
                    'httpCode' => 0
                ];
            } elseif ($httpCode === 200) {
                $results['success']++;
                $results['responses'][$index] = [
                    'token' => $deviceToken,
                    'success' => true,
                    'httpCode' => $httpCode
                ];
            } else {
                $results['failure']++;
                $results['responses'][$index] = [
                    'token' => $deviceToken,
                    'success' => false,
                    'error' => $responseBody,
                    'httpCode' => $httpCode
                ];
            }
        }

        curl_multi_close($multiHandle);

        // Re-index to sequential array
        $results['responses'] = array_values($results['responses']);

        return $results;
    }

    /**
     * Send FCM message to a topic
     *
     * @param string $topic        Topic name (without /topics/ prefix)
     * @param array  $notification Notification payload (title, body)
     * @param array  $data         Data payload
     *
     * @return ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    public function sendToTopic(
        string $topic,
        array $notification = [],
        array $data = []
    ): ResponseInterface {
        $message = ['topic' => $topic];
        if (!empty($notification)) {
            $message['notification'] = $notification;
        }
        if (!empty($data)) {
            $message['data'] = $data;
        }

        return $this->sendMessage($message);
    }

    /**
     * Send FCM message to a topic condition
     *
     * @param string $condition    Topic condition expression (e.g. "'topicA' in topics && 'topicB' in topics")
     * @param array  $notification Notification payload (title, body)
     * @param array  $data         Data payload
     *
     * @return ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException On HTTP error
     */
    public function sendToCondition(
        string $condition,
        array $notification = [],
        array $data = []
    ): ResponseInterface {
        $message = ['condition' => $condition];
        if (!empty($notification)) {
            $message['notification'] = $notification;
        }
        if (!empty($data)) {
            $message['data'] = $data;
        }

        return $this->sendMessage($message);
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
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = '';
        openssl_sign(
            $header . '.' . $payload,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $header . '.' . $payload . '.' . $signature;
    }
}
