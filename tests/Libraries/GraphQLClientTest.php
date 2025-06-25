<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\GraphQLClient;
use GuzzleHttp\Handler\MockHandler;

class GraphQLClientTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testBasicQuery(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['user' => ['id' => '1', 'name' => 'John Doe']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query('{ user(id: "1") { id name } }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('John Doe', $contents['data']['user']['name']);
    }

    public function testQueryWithVariables(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['user' => ['id' => '123', 'email' => 'test@example.com']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query(
            'query GetUser($id: ID!) { user(id: $id) { id email } }',
            ['id' => '123']
        );

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('test@example.com', $contents['data']['user']['email']);
    }

    public function testQueryWithOperationName(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['posts' => [['id' => '1', 'title' => 'Test Post']]]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query(
            'query GetPosts { posts { id title } } query GetUsers { users { id name } }',
            null,
            'GetPosts'
        );

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Test Post', $contents['data']['posts'][0]['title']);
    }

    public function testQueryWithVariablesAndOperationName(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['createUser' => ['id' => '456', 'name' => 'Jane Doe']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query(
            'mutation CreateUser($input: UserInput!) { createUser(input: $input) { id name } }',
            ['input' => ['name' => 'Jane Doe', 'email' => 'jane@example.com']],
            'CreateUser'
        );

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Jane Doe', $contents['data']['createUser']['name']);
    }

    public function testBasicAuth(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['authenticated' => true]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $client->setBasicAuth('username', 'password');
        $response = $client->query('{ authenticated }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($contents['data']['authenticated']);
    }

    public function testBearerToken(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['user' => ['role' => 'admin']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $client->setBearerToken('jwt-token-here');
        $response = $client->query('{ user { role } }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('admin', $contents['data']['user']['role']);
    }

    public function testCustomHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['success' => true]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $client->setHeaders(['X-Custom-Header' => 'custom-value', 'X-API-Version' => '2.0']);
        $response = $client->query('{ success }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($contents['data']['success']);
    }

    public function testConnectionError(): void
    {
        $this->expectException(GuzzleException::class);

        $request = new Request('POST', 'https://api.example.com/graphql');
        $mock = new MockHandler([
            new ConnectException('Connection failed', $request)
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $client->query('{ user { id } }');
    }

    public function testHttpError(): void
    {
        $mock = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'errors' => [['message' => 'Internal server error']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query('{ user { id } }');

        $this->assertEquals(500, $response->getStatusCode());
        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Internal server error', $contents['errors'][0]['message']);
    }

    public function testGraphQLErrors(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => null,
                'errors' => [['message' => 'Field "nonexistent" not found']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query('{ nonexistent }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertNull($contents['data']);
        $this->assertEquals('Field "nonexistent" not found', $contents['errors'][0]['message']);
    }

    public function testDebugLogging(): void
    {
        putenv('APP_DEBUG=true');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['user' => ['id' => '1', 'name' => 'Debug User']]
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query('{ user(id: "1") { id name } }', ['debug' => true]);

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Debug User', $contents['data']['user']['name']);

        putenv('APP_DEBUG=false');
    }

    public function testLargeBodyTruncation(): void
    {
        putenv('APP_DEBUG=true');

        // Create large response body (over 5000 characters)
        $largeData = [];
        for ($i = 0; $i < 20; $i++) {
            $largeData["message$i"] = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. ', 10);
        }

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => $largeData
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        // Create large query variables
        $largeVariables = [];
        for ($i = 0; $i < 20; $i++) {
            $largeVariables["input$i"] = str_repeat('This is a very long input string that will make the request body exceed 5000 characters. ', 20);
        }

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client->query(
            'mutation CreateLargeData($input: LargeInput!) { createData(input: $input) { success } }',
            $largeVariables
        );

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $contents);

        putenv('APP_DEBUG=false');
    }

    public function testCustomConfig(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['config' => 'custom']
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', [
            'handler' => $handlerStack,
            'timeout' => 30,
            'verify' => true,
            'connect_timeout' => 5
        ]);

        $response = $client->query('{ config }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('custom', $contents['data']['config']);
    }

    public function testChainedAuthentication(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['authenticated' => true, 'role' => 'admin']
            ])),
        ]);
        $handlerStack = new HandlerStack($mock);

        $client = new GraphQLClient('https://api.example.com/graphql', ['handler' => $handlerStack]);
        $response = $client
            ->setBearerToken('jwt-token')
            ->setHeaders(['X-API-Key' => 'api-key-123'])
            ->query('{ authenticated role }');

        $contents = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($contents['data']['authenticated']);
        $this->assertEquals('admin', $contents['data']['role']);
    }
}