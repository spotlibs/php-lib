<?php

declare(strict_types=1);

namespace Tests\Libraries\Storage;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;

class StorageResultTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../bootstrap/app.php';
    }

    public function testStorageResultProperties(): void
    {
        $result = new StorageResult();
        $result->driver = 'MINIO';
        $result->folder = '/tmp/';
        $result->pathFile = 'test.txt';
        $result->fullPath = '/tmp/test.txt';
        $result->size = 1234;

        $this->assertEquals('MINIO', $result->driver);
        $this->assertEquals('/tmp/', $result->folder);
        $this->assertEquals('test.txt', $result->pathFile);
        $this->assertEquals('/tmp/test.txt', $result->fullPath);
        $this->assertEquals(1234, $result->size);
    }
}
