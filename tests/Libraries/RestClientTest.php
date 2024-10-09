<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Laravel\Lumen\Testing\TestCase;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Libraries\RestClient;

class RestClientTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    function testCall():void
    {
        $startTime = time();
        $client = new RestClient();
        $client->call(null, "https://dummyjson.com", "/test?delay=2000", "GET");
        $duration = time() - $startTime;
        $this->assertTrue($duration > 1); // more than 1 second
    }
    
    function testCallAsync():void
    {
        $startTime = time();
        $client = new RestClient();
        $client->callAsync(null, "https://dummyjson.com", "/test?delay=3000", "GET");
        $client->callAsync(null, "https://dummyjson.com", "/test?delay=5000", "GET");
        $duration = time() - $startTime;
        $this->assertTrue($duration < 1); // less than 1 second
    }

    function testCallAsync2():void
    {
        $startTime = microtime(true);
        $client = new RestClient();
        $promise = $client->callAsync(null, "https://dummyjson.com", "/test?delay=2000", "GET");
        $promise2 = $client->callAsync(null, "https://dummyjson.com", "/test?delay=2000", "GET");
        $response = new Response();
        $promise->then(
            function(ResponseInterface $res) use (&$response) {
                $response = new Response(json_decode($res->getBody()->getContents(), true));
            },
            function() {
                // echo "\nFirst promise rejected\n";
            }
        );
        // $promise2->then(
        //     function() {
        //         echo "\nSecond promise fulfilled\n";
        //     },
        //     function() {
        //         echo "\nSecond promise rejected\n";
        //     }
        // );
        $promise->wait();
        $promise2->wait();
        $duration = microtime(true) - $startTime;
        $this->assertTrue($duration > 2);
    }
}