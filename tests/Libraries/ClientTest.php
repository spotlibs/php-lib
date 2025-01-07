<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\Client;

class ClientTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testCallY(): void
    {
        
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client();
        $response = $client
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512);
        $this->assertEquals('ok', $contents_arr['status']);
    }

    public function testCallX(): void
    {
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client();
        $response = $client->setTimeout(5)
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512);
        $this->assertEquals('ok', $contents_arr['status']);
    }

    public function testCallZ(): void
    {
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [
                'Content-Type' => 'application/json',
                'Strict-Transport-Security' => ['max-age=31536000', 'includeSubDomains', 'preload']
            ],
            json_encode([
                "status" => "ok",
                "message" => "welcome"
            ])
        );
        $client = new Client();
        $response = $client->setTimeout(5)
            ->injectRequestHeader(['X-Powered-By' => ['Money']])
            ->injectResponseHeader(['X-Server' => ['tinyurl'], 'X-Overhead' => ['true', 'allowed']])
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $headers = $response->getHeaders();
        $this->assertEquals('ok', $contents_arr['status']);
        $this->assertStringContainsString('application/json', $headers['Content-Type'][0]);
    }

    public function testCallEksternal(): void
    {
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client();
        $response = $client->setTimeout(8)
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $headers = $response->getHeaders();
        $this->assertEquals('ok', $contents_arr['status']);
        $this->assertStringContainsString('application/json', $headers['Content-Type'][0]);
    }

    public function testCallMultipartSuccess(): void
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
                    'contents' => fopen('public/docs/hello.txt', 'r')
                ]
            ])
        );
        $client = new Client();
        $resp = $client
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallMultipartSuccess2(): void
    {
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            ['Content-Type' => 'multipart/form-data'],
            new MultipartStream([
                [
                    'name' => 'file',
                    'contents' => new \Illuminate\Http\UploadedFile('public/docs/hello.txt', 'hello.txt')
                ]
            ])
        );
        $client = new Client();
        $resp = $client
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallXWwwUrlEncoded(): void
    {
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                "status" => "ok",
                "message" => "welcome"
            ])
        );
        $client = new Client();
        $resp = $client
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }
}