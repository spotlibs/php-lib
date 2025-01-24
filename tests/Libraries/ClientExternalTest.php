<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
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
    }

    public function testCallExternalMultipartSuccess(): void
    {
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