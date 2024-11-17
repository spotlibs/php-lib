<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Carbon\Exceptions\InvalidTypeException;
use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\Client;
use Spotlibs\PhpLib\Libraries\ClientHelpers\Multipart;

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
            ]
        );
        $client = new Client();
        $response = $client->setTimeout(5)
            ->injectRequestHeader(['X-Powered-By' => ['Money']])
            ->injectResponseHeader(['X-Server' => ['tinyurl'], 'X-Overhead' => ['true', 'allowed']])
            ->setFormType('json')
            ->setRequestBody([
                "status" => "ok",
                "message" => "welcome"
            ])
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $headers = $response->getHeaders();
        $this->assertEquals('ok', $contents_arr['status']);
        $this->assertStringContainsString('application/json', $headers['Content-Type'][0]);
    }

    public function testCallError(): void
    {
        $this->expectException(Exception::class);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [
                'Content-Type' => 'application/json',
            ],
        );
        $client = new Client();
        $client->setFormType('video')
            ->call($request);
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

    public function testCallMultipartError(): void
    {
        $this->expectException(InvalidTypeException::class);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
        );
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $client = new Client();
        $client->setFormType(RequestOptions::MULTIPART)
            ->setRequestBody([
                [
                    'name' => 'upload',
                    'contents' => Utils::tryFopen('public/docs/hello.txt', 'r')
                ],
                [
                    'name' => 'dir',
                    'contents' => 'public/images'
                ]
            ])
            ->setVerify(true)
            ->call($request);
    }

    public function testCallMultipartSuccess(): void
    {
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
        );
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $client = new Client();
        $resp = $client->setFormType(RequestOptions::MULTIPART)
            ->setRequestBody([
                new Multipart([
                    'name' => 'file',
                    'contents' => Utils::tryFopen('public/docs/hello.txt', 'r')
                ])
            ])
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallMultipartSuccess2(): void
    {
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
        );
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $client = new Client();
        $resp = $client->setFormType(RequestOptions::MULTIPART)
            ->setRequestBody([
                new Multipart([
                    'name' => 'file',
                    'contents' => new \Illuminate\Http\UploadedFile('public/docs/hello.txt', 'hello.txt')
                ])
            ])
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallMultipartSuccess3(): void
    {
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
        );
        $f = fopen('public/docs/hello.txt', 'w');
        fwrite($f, 'hello world');
        fclose($f);
        $f = fopen('public/docs/hello2.txt', 'w');
        fwrite($f, 'spotlibs the php library');
        fclose($f);
        $x = [];
        array_push($x, new \Illuminate\Http\UploadedFile('public/docs/hello.txt', 'hello.txt'));
        array_push($x, new \Illuminate\Http\UploadedFile('public/docs/hello.txt', 'hello2.txt'));
        $client = new Client();
        $resp = $client->setFormType(RequestOptions::MULTIPART)
            ->setRequestBody([
                new Multipart([
                    'name' => 'file[]',
                    'contents' => $x
                ])
            ])
            ->setVerify(true)
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }
}