<?php

declare(strict_types=1);

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\TestCase;
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
        $client->call(null, "https://reqres.in", "/api/users?delay=2", "GET");
        $duration = time() - $startTime;
        $this->assertTrue($duration > 1); // more than 1 second
    }
    
    function testCallAsync():void
    {
        $startTime = time();
        $client = new RestClient();
        $client->callAsync(null, "https://reqres.in", "/api/users?delay=3", "GET");
        $client->callAsync(null, "https://reqres.in", "/api/users?delay=5", "GET");
        $duration = time() - $startTime;
        $this->assertTrue($duration < 1); // less than 1 second
    }
}