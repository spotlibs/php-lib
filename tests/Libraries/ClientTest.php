<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\Client;
use Spotlibs\PhpLib\Libraries\ClientHelpers\Multipart;
use Spotlibs\PhpLib\Libraries\TimeoutUnit;
use Spotlibs\PhpLib\Responses\StdResponse;

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
        $json = '{
            "status": "ok",
            "message": "welcome"
        }';
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [
                'Content-Type' => 'application/json'
            ],
            $json
        );
        $client = new Client();
        $response = $client->setTimeout(5)
            ->setFormType('json')
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
        $json = '{
            "status": "ok",
            "message": "welcome"
        }';
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
            [
                'Content-Type' => 'application/json'
            ],
            $json
        );
        $client = new Client();
        $client->setTimeout(1)
            ->setFormType('video')
            ->call($request);
    }
    
    public function testCallEksternal(): void
    {
        $request = new Request(
            'GET',
            'https://dummyjson.com/test',
        );
        $client = new Client();
        $response = $client->setTimeout(1)
            ->call($request);
        $contents = $response->getBody()->getContents();
        $contents_arr = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $headers = $response->getHeaders();
        $this->assertEquals('ok', $contents_arr['status']);
        $this->assertStringContainsString('application/json', $headers['Content-Type'][0]);
    }
}