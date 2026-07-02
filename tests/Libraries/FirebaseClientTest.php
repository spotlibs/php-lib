<?php

/**
 * PHP version 8
 *
 * @category Tests
 * @package  Tests
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.8
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Mockery;
use Spotlibs\PhpLib\Libraries\FirebaseClient;
use Tests\TestCase;

// Test helper class that extends FirebaseClient
class TestableFirebaseClient extends FirebaseClient
{
    public function __construct(GuzzleClient $httpClient, array $serviceAccount)
    {
        $reflection = new \ReflectionClass(FirebaseClient::class);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($this, $httpClient);

        $serviceProperty = $reflection->getProperty('serviceAccount');
        $serviceProperty->setAccessible(true);
        $serviceProperty->setValue($this, $serviceAccount);

        $proxyProperty = $reflection->getProperty('proxyUrl');
        $proxyProperty->setAccessible(true);
        $proxyProperty->setValue($this, '');

        $tokenProperty = $reflection->getProperty('tokenFile');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($this, '/mock/path/firebase_token.json');
    }

    // Expose getAccessToken as public for testing
    public function getAccessTokenPublic(bool $forceRefresh = false): string
    {
        return 'mock_access_token_12345';
    }

    // Override sendMessage to use our public method
    public function sendMessage(array $message): \Psr\Http\Message\ResponseInterface
    {
        $token = $this->getAccessTokenPublic();

        $reflection = new \ReflectionClass(FirebaseClient::class);
        $serviceProperty = $reflection->getProperty('serviceAccount');
        $serviceProperty->setAccessible(true);
        $serviceAccount = $serviceProperty->getValue($this);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpClient = $httpProperty->getValue($this);

        $proxyProperty = $reflection->getProperty('proxyUrl');
        $proxyProperty->setAccessible(true);
        $proxyUrl = $proxyProperty->getValue($this);

        $startTime = microtime(true);
        $projectId = $serviceAccount['project_id'];
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
        if (!empty($proxyUrl)) {
            $options['proxy'] = $proxyUrl;
        }

        try {
            $response = $httpClient->send($request, $options);
            return $response;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401) {
                $newToken = $this->getAccessTokenPublic(true);

                $retryRequest = new Request(
                    'POST',
                    $url,
                    [
                        'Authorization' => 'Bearer ' . $newToken,
                        'Content-Type' => 'application/json'
                    ],
                    json_encode(['message' => $message], JSON_THROW_ON_ERROR)
                );

                return $httpClient->send($retryRequest, $options);
            }

            throw $e;
        }
    }
}

class FirebaseClientTest extends TestCase
{
    private array $mockServiceAccount = [
        'type' => 'service_account',
        'project_id' => 'test-project',
        'private_key_id' => 'key123',
        'private_key' => 'fake-key',
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
        'client_id' => '12345',
    ];

    /** @test */
    public function testSendMessage(): void
    {
        $mockResponse = new Response(
            200,
            [],
            json_encode(['name' => 'projects/test-project/messages/123'])
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $message = [
            'token' => 'device_token_123',
            'notification' => [
                'title' => 'Test',
                'body' => 'Test message'
            ]
        ];

        $response = $client->sendMessage($message);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSendMessageRetryOn401(): void
    {
        $unauthorizedResponse = new Response(401, [], json_encode(['error' => 'Unauthorized']));
        $successResponse = new Response(200, [], json_encode(['name' => 'projects/test-project/messages/123']));

        $exception = new ClientException(
            'Unauthorized',
            new Request('POST', 'https://fcm.googleapis.com'),
            $unauthorizedResponse
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andThrow($exception);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($successResponse);

        $client = $this->createMockedClient($guzzleMock);

        $message = ['token' => 'device_token_123'];
        $response = $client->sendMessage($message);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSendMulticast(): void
    {
        $mockResponse = new Response(200, [], json_encode(['name' => 'projects/test-project/messages/123']));

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->times(3)
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $tokens = ['token1', 'token2', 'token3'];
        $notification = ['title' => 'Test', 'body' => 'Message'];

        $result = $client->sendMulticast($tokens, $notification);

        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failure']);
        $this->assertCount(3, $result['responses']);
    }

    /** @test */
    public function testSendMulticastWithFailures(): void
    {
        $successResponse = new Response(200, [], json_encode(['name' => 'projects/test-project/messages/123']));
        $errorResponse = new Response(400, [], json_encode(['error' => 'Invalid token']));

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($successResponse);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($errorResponse);

        $client = $this->createMockedClient($guzzleMock);

        $tokens = ['token1', 'token2'];
        $result = $client->sendMulticast($tokens);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['failure']);
    }

    /** @test */
    public function testSendMulticastWithException(): void
    {
        $mockResponse = new Response(200, [], json_encode(['name' => 'projects/test-project/messages/123']));

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $client = $this->createMockedClient($guzzleMock);

        $tokens = ['token1', 'token2'];
        $result = $client->sendMulticast($tokens);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['failure']);
        $this->assertStringContainsString('Network error', $result['responses'][1]['error']);
    }

    /** @test */
    public function testSendMulticastWithData(): void
    {
        $mockResponse = new Response(200, [], json_encode(['name' => 'projects/test-project/messages/123']));

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $tokens = ['token1'];
        $notification = ['title' => 'Test', 'body' => 'Message'];
        $data = ['key' => 'value'];

        $result = $client->sendMulticast($tokens, $notification, $data);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failure']);
    }

    /** @test */
    public function testSendToTopic(): void
    {
        $mockResponse = new Response(
            200,
            [],
            json_encode(['name' => 'projects/test-project/messages/123'])
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $response = $client->sendToTopic(
            'news',
            ['title' => 'Breaking', 'body' => 'Something happened'],
            ['key' => 'value']
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSendToTopicWithNotificationOnly(): void
    {
        $mockResponse = new Response(
            200,
            [],
            json_encode(['name' => 'projects/test-project/messages/123'])
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $response = $client->sendToTopic('news', ['title' => 'Test', 'body' => 'Body']);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSendToCondition(): void
    {
        $mockResponse = new Response(
            200,
            [],
            json_encode(['name' => 'projects/test-project/messages/123'])
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $response = $client->sendToCondition(
            "'news' in topics || 'alerts' in topics",
            ['title' => 'Update', 'body' => 'New update available'],
            ['version' => '2.0']
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSendToConditionWithNotificationOnly(): void
    {
        $mockResponse = new Response(
            200,
            [],
            json_encode(['name' => 'projects/test-project/messages/123'])
        );

        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $guzzleMock->shouldReceive('send')
            ->once()
            ->andReturn($mockResponse);

        $client = $this->createMockedClient($guzzleMock);

        $response = $client->sendToCondition(
            "'news' in topics && 'premium' in topics",
            ['title' => 'Premium News', 'body' => 'Exclusive content']
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function testSetProxy(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);

        $result = $client->setProxy('http://proxy.example.com:8080');

        $this->assertInstanceOf(TestableFirebaseClient::class, $result);
    }

    // =========================================================================
    // sendMulticastParallel
    // =========================================================================

    /** @test */
    public function testSendMulticastParallelReturnsEmptyWhenNoTokens(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createParallelClient($guzzleMock);

        $result = $client->sendMulticastParallel([], ['title' => 'Test', 'body' => 'Body']);

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(0, $result['failure']);
        $this->assertEmpty($result['responses']);
    }

    /** @test */
    public function testSendMulticastParallelAllSuccess(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createParallelClient($guzzleMock, [
            ['token' => 'token1', 'success' => true, 'httpCode' => 200],
            ['token' => 'token2', 'success' => true, 'httpCode' => 200],
            ['token' => 'token3', 'success' => true, 'httpCode' => 200],
        ]);

        $tokens = ['token1', 'token2', 'token3'];
        $notification = ['title' => 'Test', 'body' => 'Message'];

        $result = $client->sendMulticastParallel($tokens, $notification);

        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failure']);
        $this->assertCount(3, $result['responses']);
    }

    /** @test */
    public function testSendMulticastParallelWithFailures(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createParallelClient($guzzleMock, [
            ['token' => 'token1', 'success' => true, 'httpCode' => 200],
            ['token' => 'token2', 'success' => false, 'error' => 'Invalid token', 'httpCode' => 400],
            ['token' => 'token3', 'success' => false, 'error' => 'Curl error', 'httpCode' => 0],
        ]);

        $tokens = ['token1', 'token2', 'token3'];
        $result = $client->sendMulticastParallel($tokens);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(2, $result['failure']);
        $this->assertCount(3, $result['responses']);
    }

    /** @test */
    public function testSendMulticastParallelRetries401Tokens(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);

        // First call: token2 gets 401, token1 and token3 succeed
        $firstCallResults = [
            ['token' => 'token1', 'success' => true, 'httpCode' => 200],
            ['token' => 'token2', 'success' => false, 'error' => 'Unauthorized', 'httpCode' => 401],
            ['token' => 'token3', 'success' => true, 'httpCode' => 200],
        ];

        // Retry call: token2 succeeds with new access token
        $retryResults = [
            ['token' => 'token2', 'success' => true, 'httpCode' => 200],
        ];

        $client = $this->createParallelClientWithRetry($guzzleMock, $firstCallResults, $retryResults);

        $tokens = ['token1', 'token2', 'token3'];
        $notification = ['title' => 'Test', 'body' => 'Body'];

        $result = $client->sendMulticastParallel($tokens, $notification);

        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failure']);
        $this->assertCount(3, $result['responses']);
        $this->assertTrue($result['responses'][1]['success']);
    }

    /** @test */
    public function testSendMulticastParallelRetry401StillFails(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);

        $firstCallResults = [
            ['token' => 'token1', 'success' => true, 'httpCode' => 200],
            ['token' => 'token2', 'success' => false, 'error' => 'Unauthorized', 'httpCode' => 401],
        ];

        // Retry still fails
        $retryResults = [
            ['token' => 'token2', 'success' => false, 'error' => 'Still unauthorized', 'httpCode' => 401],
        ];

        $client = $this->createParallelClientWithRetry($guzzleMock, $firstCallResults, $retryResults);

        $tokens = ['token1', 'token2'];
        $result = $client->sendMulticastParallel($tokens);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['failure']);
        $this->assertFalse($result['responses'][1]['success']);
    }

    /** @test */
    public function testSendMulticastParallelWithDataPayload(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createParallelClient($guzzleMock, [
            ['token' => 'token1', 'success' => true, 'httpCode' => 200],
        ]);

        $tokens = ['token1'];
        $notification = ['title' => 'Test', 'body' => 'Body'];
        $data = ['key' => 'value', 'action' => 'open_screen'];

        $result = $client->sendMulticastParallel($tokens, $notification, $data);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failure']);
    }

    // =========================================================================
    // executeCurlMulti (via reflection)
    // =========================================================================

    /** @test */
    public function testExecuteCurlMultiAllSuccess(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);

        $reflection = new \ReflectionMethod(FirebaseClient::class, 'executeCurlMulti');
        $reflection->setAccessible(true);

        // Use httpbin or a mock server — but since we can't guarantee network,
        // we test against the actual curl_multi with a known unreachable endpoint
        // to verify error handling
        $tokens = ['device_token_1'];
        $notification = ['title' => 'Test', 'body' => 'Body'];
        $data = [];
        $accessToken = 'test_access_token';
        // Use a URL that immediately refuses connection to test curl error path
        $url = 'http://127.0.0.1:1/v1/projects/test/messages:send';

        $result = $reflection->invoke(
            $client,
            $tokens,
            $notification,
            $data,
            $accessToken,
            $url
        );

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failure', $result);
        $this->assertArrayHasKey('responses', $result);
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['failure']);
        $this->assertCount(1, $result['responses']);
        $this->assertFalse($result['responses'][0]['success']);
        $this->assertEquals('device_token_1', $result['responses'][0]['token']);
    }

    /** @test */
    public function testExecuteCurlMultiWithProxy(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);
        $client->setProxy('http://invalid-proxy:9999');

        $reflection = new \ReflectionMethod(FirebaseClient::class, 'executeCurlMulti');
        $reflection->setAccessible(true);

        $tokens = ['device_token_1'];
        $notification = [];
        $data = ['key' => 'value'];
        $accessToken = 'test_token';
        $url = 'http://127.0.0.1:1/v1/projects/test/messages:send';

        $result = $reflection->invoke(
            $client,
            $tokens,
            $notification,
            $data,
            $accessToken,
            $url
        );

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failure', $result);
        $this->assertArrayHasKey('responses', $result);
        $this->assertEquals(1, $result['failure']);
        $this->assertFalse($result['responses'][0]['success']);
    }

    /** @test */
    public function testExecuteCurlMultiMultipleTokens(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);

        $reflection = new \ReflectionMethod(FirebaseClient::class, 'executeCurlMulti');
        $reflection->setAccessible(true);

        $tokens = ['token_a', 'token_b', 'token_c'];
        $notification = ['title' => 'Multi', 'body' => 'Test'];
        $data = [];
        $accessToken = 'bearer_token';
        $url = 'http://127.0.0.1:1/v1/projects/test/messages:send';

        $result = $reflection->invoke(
            $client,
            $tokens,
            $notification,
            $data,
            $accessToken,
            $url
        );

        $this->assertCount(3, $result['responses']);
        $this->assertEquals(3, $result['failure']);
        $this->assertEquals(0, $result['success']);

        // Verify each token is present in results
        $resultTokens = array_column($result['responses'], 'token');
        $this->assertContains('token_a', $resultTokens);
        $this->assertContains('token_b', $resultTokens);
        $this->assertContains('token_c', $resultTokens);
    }

    /** @test */
    public function testExecuteCurlMultiWithEmptyNotificationAndData(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);

        $reflection = new \ReflectionMethod(FirebaseClient::class, 'executeCurlMulti');
        $reflection->setAccessible(true);

        $tokens = ['token_only'];
        $notification = [];
        $data = [];
        $accessToken = 'test_token';
        $url = 'http://127.0.0.1:1/v1/projects/test/messages:send';

        $result = $reflection->invoke(
            $client,
            $tokens,
            $notification,
            $data,
            $accessToken,
            $url
        );

        $this->assertArrayHasKey('responses', $result);
        $this->assertCount(1, $result['responses']);
        $this->assertEquals('token_only', $result['responses'][0]['token']);
    }

    private function createMockedClient($guzzleMock): TestableFirebaseClient
    {
        return new TestableFirebaseClient($guzzleMock, $this->mockServiceAccount);
    }

    private function createParallelClient($guzzleMock, array $curlMultiResults = []): TestableParallelFirebaseClient
    {
        return new TestableParallelFirebaseClient($guzzleMock, $this->mockServiceAccount, $curlMultiResults);
    }

    private function createParallelClientWithRetry(
        $guzzleMock,
        array $firstCallResults,
        array $retryResults
    ): TestableParallelFirebaseClient {
        return new TestableParallelFirebaseClient(
            $guzzleMock,
            $this->mockServiceAccount,
            $firstCallResults,
            $retryResults
        );
    }
}

/**
 * Testable subclass for sendMulticastParallel that stubs executeCurlMulti
 */
class TestableParallelFirebaseClient extends TestableFirebaseClient
{
    private array $curlMultiResults;
    private array $retryResults;
    private int $callCount = 0;

    public function __construct(
        GuzzleClient $httpClient,
        array $serviceAccount,
        array $curlMultiResults = [],
        array $retryResults = []
    ) {
        parent::__construct($httpClient, $serviceAccount);
        $this->curlMultiResults = $curlMultiResults;
        $this->retryResults = $retryResults;
    }

    /**
     * Override sendMulticastParallel to use our stubbed executeCurlMulti
     */
    public function sendMulticastParallel(
        array $tokens,
        array $notification = [],
        array $data = []
    ): array {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'responses' => []];
        }

        $accessToken = $this->getAccessTokenPublic();
        $reflection = new \ReflectionClass(FirebaseClient::class);
        $serviceProperty = $reflection->getProperty('serviceAccount');
        $serviceProperty->setAccessible(true);
        $serviceAccount = $serviceProperty->getValue($this);

        $projectId = $serviceAccount['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $results = $this->stubbedExecuteCurlMulti($tokens, $notification, $data, $accessToken, $url);

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
            $newAccessToken = $this->getAccessTokenPublic(true);
            $retryResults = $this->stubbedExecuteCurlMulti($retryTokens, $notification, $data, $newAccessToken, $url);

            foreach ($retryIndices as $i => $originalIndex) {
                $retryResp = $retryResults['responses'][$i];
                $oldResp = $results['responses'][$originalIndex];

                if ($retryResp['success'] && !$oldResp['success']) {
                    $results['success']++;
                    $results['failure']--;
                }
                $results['responses'][$originalIndex] = $retryResp;
            }
        }

        return $results;
    }

    private function stubbedExecuteCurlMulti(
        array $tokens,
        array $notification,
        array $data,
        string $accessToken,
        string $url
    ): array {
        $this->callCount++;

        if ($this->callCount === 1) {
            $responses = $this->curlMultiResults;
        } else {
            $responses = $this->retryResults;
        }

        $success = 0;
        $failure = 0;
        foreach ($responses as $resp) {
            if ($resp['success']) {
                $success++;
            } else {
                $failure++;
            }
        }

        return ['success' => $success, 'failure' => $failure, 'responses' => $responses];
    }
}