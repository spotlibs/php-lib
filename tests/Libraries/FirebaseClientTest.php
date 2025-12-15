<?php

/**
 * PHP version 8
 *
 * @category Tests
 * @package  Tests
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
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

class FirebaseClientTest extends TestCase
{
    // Real valid RSA private key for testing (2048-bit)
    private string $validPrivateKey = "-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAyPFw8D7OUFNJ8u7v7F3aZ0Xy7b9F1dF8F9F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0IDAQAB
AoIBABx9F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0AoGBAP
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0AoGBAN
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0AoGAF
0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0AoGBAP
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0AoGAF
0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0
F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0F0=
-----END RSA PRIVATE KEY-----";

    private array $mockServiceAccount = [
        'type' => 'service_account',
        'project_id' => 'test-project',
        'private_key_id' => 'key123',
        'private_key' => '',
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
        'client_id' => '12345',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockServiceAccount['private_key'] = $this->validPrivateKey;
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testSendMessage(): void
    {
        $this->setupAppToken();

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
    /** @runInSeparateProcess */
    public function testSendMessageRetryOn401(): void
    {
        $this->setupAppToken();

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
    /** @runInSeparateProcess */
    public function testSendMulticast(): void
    {
        $this->setupAppToken();

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
    /** @runInSeparateProcess */
    public function testSendMulticastWithFailures(): void
    {
        $this->setupAppToken();

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
    /** @runInSeparateProcess */
    public function testSetProxy(): void
    {
        $guzzleMock = Mockery::mock(GuzzleClient::class);
        $client = $this->createMockedClient($guzzleMock);

        $result = $client->setProxy('http://proxy.example.com:8080');

        $this->assertInstanceOf(FirebaseClient::class, $result);
    }

    private function setupAppToken(): void
    {
        $tokenData = [
            'token' => 'mock_token_12345',
            'expiry' => time() + 3600
        ];

        $this->app->singleton('firebase.token', function() use ($tokenData) {
            return $tokenData;
        });
    }

    private function createMockedClient($guzzleMock): FirebaseClient
    {
        $reflection = new \ReflectionClass(FirebaseClient::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($instance, $guzzleMock);

        $serviceAccountProperty = $reflection->getProperty('serviceAccount');
        $serviceAccountProperty->setAccessible(true);
        $serviceAccountProperty->setValue($instance, $this->mockServiceAccount);

        $proxyProperty = $reflection->getProperty('proxyUrl');
        $proxyProperty->setAccessible(true);
        $proxyProperty->setValue($instance, '');

        return $instance;
    }
}