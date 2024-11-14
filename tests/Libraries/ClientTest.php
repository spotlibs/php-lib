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

    public function testCallMultipartError(): void
    {
        $this->expectException(InvalidTypeException::class);
        $request = new Request(
            'POST',
            'https://jsonplaceholder.typicode.com/posts',
        );
        $client = new Client();
        $client->setFormType(RequestOptions::MULTIPART)
            ->setRequestBody([
                [
                    'name' => 'upload',
                    'contents' => Utils::tryFopen('public/docs/tugas I pengantar matematika.docx', 'r')
                ],
                [
                    'name' => 'dir',
                    'contents' => 'public/images'
                ]
            ])
            ->setVerify(true)
            ->call($request);
    }

    // public function testCallLocalX(): void
    // {
    //     $request = new Request(
    //         'GET',
    //         'localhost:8585',
    //     );
    //     $client = new Client();
    //     $response = $client->call($request);
    //     $contents = $response->getBody()->getContents();
    //     print_r($contents);
    // }
    
    // public function testCallMultipartY(): void
    // {
    //     $request = new Request(
    //         'POST',
    //         'https://jsonplaceholder.typicode.com/posts',
    //     );
    //     $multi = new Multipart();
    //     $multi->name = 'upload';
    //     $multi->contents = Utils::tryFopen('public/docs/tugas I pengantar matematika.docx', 'r');
    //     $multi->filename = 'public/docs/tugas I pengantar matematika.docx';
    //     $multi->headers = ['Content-Type' => ['<Content-type header>']];
    //     $client = new Client();
    //     $response = $client->setFormType(RequestOptions::MULTIPART)
    //         ->setRequestBody([
    //             $multi,
    //             new Multipart([
    //                 'name' => 'upload',
    //                 'contents' => Utils::tryFopen('public/images/gatau_males.jpg', 'r'),
    //                 'filename' => 'public/images/gatau_males.jpg',
    //                 'headers' => ['Content-Type' => ['<Content-type header>']]
    //             ])
    //         ])
    //         ->call($request);
    //     print_r($response);
    // }
}