<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\FirebaseClient;

class FirebaseClientTest extends TestCase
{
    private string $testServiceAccountPath;

    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock service account file - key doesn't need to be valid since we'll mock everything
        $this->testServiceAccountPath = sys_get_temp_dir() . '/test-service-account.json';
        file_put_contents($this->testServiceAccountPath, json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'test-key-id',
            'private_key' => 'mock-private-key',
            'client_email' => 'test@test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testServiceAccountPath)) {
            unlink($this->testServiceAccountPath);
        }
        parent::tearDown();
    }

    public function testSetAccessToken(): void
    {
        $client = new FirebaseClient($this->testServiceAccountPath);
        $client->setAccessToken('test-token', 3600);

        $this->assertEquals('test-token', $client->generateToken());
    }

    public function testSetProxy(): void
    {
        $client = new FirebaseClient($this->testServiceAccountPath);
        $result = $client->setProxy('http://proxy:1707');

        $this->assertInstanceOf(FirebaseClient::class, $result);
    }

    public function testGenerateToken(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'ya29.test-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer'
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('ya29.test-token', 3600);
        $token = $client->generateToken();

        $this->assertEquals('ya29.test-token', $token);
    }

    public function testGenerateTokenWithProxy(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'ya29.proxy-token',
                'expires_in' => 3600
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        $client->setProxy('http://proxy:1707');
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('ya29.proxy-token', 3600);
        $token = $client->generateToken();

        $this->assertEquals('ya29.proxy-token', $token);
    }

    public function testSendMessage(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'name' => 'projects/test-project/messages/0:123456'
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('ya29.test', 3600);

        $response = $client->sendMessage([
            'token' => 'device-token',
            'notification' => ['title' => 'Test', 'body' => 'Hello']
        ]);

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('projects/test-project/messages', $contents['name']);
    }

    public function testSendMessageWithPreGeneratedToken(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'name' => 'projects/test-project/messages/0:789012'
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        $client->setAccessToken('pre-generated-token');

        $response = $client->sendMessage([
            'token' => 'device-token',
            'notification' => ['title' => 'Test', 'body' => 'Hello']
        ]);

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertStringContainsString('messages', $contents['name']);
    }

    public function testSendMulticast(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['name' => 'msg1'])),
            new Response(200, [], json_encode(['name' => 'msg2'])),
            new Response(404, [], json_encode(['error' => 'not found'])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('ya29.test', 3600);

        $result = $client->sendMulticast(
            ['token1', 'token2', 'token3'],
            ['title' => 'Test', 'body' => 'Hello'],
            ['key' => 'value']
        );

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['failure']);
        $this->assertCount(3, $result['responses']);
    }

    public function testSendMulticastAllSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['name' => 'msg1'])),
            new Response(200, [], json_encode(['name' => 'msg2'])),
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('ya29.test', 3600);

        $result = $client->sendMulticast(
            ['token1', 'token2'],
            ['title' => 'Test'],
            []
        );

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failure']);
    }

    public function testOAuthConnectionError(): void
    {
        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);

        $request = new Request('POST', 'https://fcm.googleapis.com');
        $mock = new MockHandler([
            new ConnectException('Connection failed', $request)
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('token', 3600);

        // Force a connection error by trying to send a message
        $client->sendMessage(['token' => 'test']);
    }

    public function testFCMConnectionError(): void
    {
        $request = new Request('POST', 'https://fcm.googleapis.com');
        $mock = new MockHandler([
            new ConnectException('FCM unavailable', $request)
        ]);
        $handlerStack = HandlerStack::create($mock);

        $client = new FirebaseClient($this->testServiceAccountPath, ['handler' => $handlerStack]);
        // Pre-set token to bypass JWT creation
        $client->setAccessToken('token', 3600);

        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        $client->sendMessage(['token' => 'test']);
    }

    public function testTokenCaching(): void
    {
        $client = new FirebaseClient($this->testServiceAccountPath);
        // Pre-set token to test caching
        $client->setAccessToken('cached-token', 3600);

        $token1 = $client->generateToken();
        $token2 = $client->generateToken(); // Should return cached token

        $this->assertEquals($token1, $token2);
        $this->assertEquals('cached-token', $token2);
    }
}