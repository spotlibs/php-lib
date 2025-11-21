<?php

declare(strict_types=1);

namespace Tests\Libraries;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\TestCase;
use Mockery;
use Spotlibs\PhpLib\Exceptions\DataNotFoundException;
use Spotlibs\PhpLib\Libraries\Client;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

class ClientTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testCallY(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'well done'])),
        ]);
        $meta = new Metadata();
        $meta->authorization = 'Bearer 123';
        $meta->user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3';
        $meta->cache_control = 'no-cache';
        $meta->api_key = '1234567890abcdef';
        $meta->forwarded_for = '';
        $meta->request_from = '';
        $meta->device_id = '1234567890abcdef';
        $meta->app = 'test_app';
        $meta->version_app = '1.0.0';
        $meta->req_id = '1234567890abcdef';
        $meta->task_id = '1234567890abcdef';
        $meta->req_tags = 'test_tag';
        $meta->req_user = 'test_user';
        $meta->req_nama = 'test_name';
        $meta->req_kode_jabatan = 'test_code';
        $meta->req_nama_jabatan = 'test_name';
        $meta->req_kode_main_uker = 'test_code';
        $meta->req_kode_region = 'test_code';
        $meta->req_jenis_uker = 'test_type';
        $meta->req_kode_uker = 'test_code';
        $meta->req_nama_uker = 'test_name';
        $meta->path_gateway = 'test_path';
        $meta->identifier = 'test_identifier';
        $meta->req_uker_supervised = ['abc', 'def'];
        $meta->req_stell = '123';
        $meta->req_stell_tx = 'abc';
        $meta->req_kostl = '123';
        $meta->req_kostl_tx = 'abc';
        $meta->req_orgeh = '123';
        $meta->req_orgeh_tx = 'abc';
        $meta->req_level_uker = 'X';
        $meta->req_uid = 'abc123';
        $meta->req_role = 'abc';
        /**
         * @var \Mockery\MockInterface $context
         */
        $context = Mockery::mock(Context::class);
        $context->shouldReceive('get')->with(Metadata::class)->andReturn($meta);
        $this->app->instance(Context::class, $context);
        $handlerStack = new HandlerStack($mock);
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client(['handler' => $handlerStack]);
        $response = $client->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512);
        $this->assertEquals('ok', $contents_arr['status']);
    }

    public function testCallX(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'well done'])),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $meta = new Metadata();
        $meta->task_id = 'abcd';
        /**
         * @var \Mockery\MockInterface $context
         */
        $context = Mockery::mock(Context::class);
        $context->shouldReceive('get')->with(Metadata::class)->andReturn($meta);
        $this->app->instance(Context::class, $context);
        $client = new Client(['handler' => $handlerStack]);
        $response = $client->call($request);
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
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'well done'])),
        ]);
        $handlerStack = new HandlerStack($mock);
        $client = new Client(['handler' => $handlerStack]);
        $response = $client
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
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ok', 'message' => 'well done'])),
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client(['handler' => $handlerStack]);
        $response = $client
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
            ->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallMultipartSuccess2(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '101', 'status' => 'OK', 'message' => 'well done'])),
        ]);
        $handlerStack = new HandlerStack($mock);
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
        $client = new Client(['handler' => $handlerStack]);
        $resp = $client
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
        $resp = $client->call($request);
        $r = json_decode($resp->getBody()->getContents());
        $this->assertEquals('101', $r->id);
    }

    public function testCallZA(): void
    {
        $this->expectException(DataNotFoundException::class);
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['responseCode' => '02', 'responseDesc' => 'Not found']))
        ]);
        $handlerStack = new HandlerStack($mock);
        $request = new Request(
            'POST',
            '/123',
            [
                'Content-Type' => 'application/json',
                'Strict-Transport-Security' => ['max-age=31536000', 'includeSubDomains', 'preload']
            ],
            json_encode([
                "status" => "ok",
                "message" => "welcome"
            ])
        );
        $client = new Client(['handler' => $handlerStack]);
        $client
            ->injectRequestHeader(['X-Powered-By' => ['Money']])
            ->injectResponseHeader(['X-Server' => ['tinyurl'], 'X-Overhead' => ['true', 'allowed']])
            ->call($request);
    }
}