<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Redis;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\ClientExternal;

class ClientExternalTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testCallEksternal1(): void
    {
        putenv('APP_ENV=production');
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            ['content-type' => 'application/json'],
            json_encode(['message' => 'hello world'])
        );
        $client = new ClientExternal();
        $response = $client->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512);
        $this->assertEquals('hello world', $contents_arr['message']);
        putenv('APP_ENV=local');
    }

    public function testCallExternalMultipartSuccess(): void
    {
        Redis::shouldReceive('get')->andReturn(json_encode([
            'id' => 1,
            'target_url' => 'https://jsonplaceholder.typicode.com/posts',
            'mock_url' => 'https://jsonplaceholder.typicode.com/posts',
            'flag' => true,
        ]));
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [],
            new MultipartStream([
                [
                    'name' => 'file',
                    'contents' => Utils::tryFopen('public/docs/hello.txt', 'r')
                ]
            ])
        );
        $client = new ClientExternal();
        $resp = $client
            ->injectRequestHeader(['X-Unit-Test' => ['clover']])
            ->injectResponseHeader(['X-Unit-Test-Response' => ['clover-response']])
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
        $this->assertEquals('clover-response', $resp->getHeader('X-Unit-Test-Response')[0]);
    }

    public function testCallExternalMultipartError(): void
    {
        $this->expectException(GuzzleException::class);
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            ['content-type' => 'application/json'],
            new MultipartStream([
                [
                    'name' => 'file',
                    'contents' => fopen('public/docs/hello.txt', 'r')
                ]
            ])
        );
        $client = new ClientExternal();
        $client->call($request);
    }
}