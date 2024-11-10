<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Laravel\Lumen\Testing\TestCase;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Libraries\RestClientFile;

class RestFileClientTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    function testCall():void
    {
        $startTime = time();
        $client = new RestClientFile();
        $body = [
            'owner' => 'Renne',
            'privilege' => '755'
        ];
        $client->call(
            $body, 
            "https://i.pinimg.com/236x/07/a7/85/07a785bbde12c4146e68bab5c323b1a8.jpg", 
            "https://jsonplaceholder.typicode.com", 
            "/posts"
        );
        $duration = time() - $startTime;
        $this->assertTrue($duration > 0); // more than 1 second
    }
}
