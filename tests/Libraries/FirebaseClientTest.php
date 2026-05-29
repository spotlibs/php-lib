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

    private function createMockedClient($guzzleMock): TestableFirebaseClient
    {
        return new TestableFirebaseClient($guzzleMock, $this->mockServiceAccount);
    }
}