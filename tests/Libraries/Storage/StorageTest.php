<?php

declare(strict_types=1);

namespace Tests\Libraries\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Storage;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;
use Mockery;

class StorageTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        putenv('DEFAULT_DRIVER_STORAGE=' . Storage::MINIO);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUploadExplicitDriver(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('put')->once()->andReturn(true);

        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('getClientOriginalName')->andReturn('test.txt');
        $fileMock->shouldReceive('getContent')->andReturn('content');

        $storage = new Storage();
        $result = $storage->driver(Storage::MINIO)->upload($fileMock, '/tmp');
        $this->assertEquals(Storage::MINIO, $result->driver);
    }

    public function testUploadAutoDetectFails(): void
    {
        $storage = new Storage();
        $fileMock = Mockery::mock(UploadedFile::class);

        $this->expectException(RuntimeException::class);
        $storage->autoDetect()->upload($fileMock, '/tmp');
    }

    public function testExistsAutoDetect(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        LaravelStorage::shouldReceive('disk')->with('minio_brimen')->andReturn($diskMock);
        $diskMock->shouldReceive('exists')->with('test.txt')->andReturn(true);

        $storage = new Storage();
        $this->assertTrue($storage->autoDetect()->exists('test.txt'));
    }

    public function testDeleteAllowed(): void
    {
        putenv('ALLOW_DELETE_STORAGE_SPOTLIB=true');
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('delete')->with('test.txt')->once()->andReturn(true);

        $storage = new Storage();
        $storage->driver(Storage::MINIO)->delete('test.txt');
        $this->assertTrue(true);
    }

    public function testDeleteDisallowed(): void
    {
        putenv('ALLOW_DELETE_STORAGE_SPOTLIB=false');
        $storage = new Storage();
        $this->expectException(RuntimeException::class);
        $storage->driver(Storage::MINIO)->delete('test.txt');
    }

    public function testCopySameDriver(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('copy')->with('a.txt', 'b.txt')->once()->andReturn(true);

        $storage = new Storage();
        $result = $storage->driver(Storage::MINIO)->copy('a.txt', 'b.txt');
        $this->assertEquals('b.txt', $result->pathFile);
    }

    public function testCopyCrossDriver(): void
    {
        $diskMock1 = Mockery::mock();
        $diskMock2 = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock1);
        LaravelStorage::shouldReceive('disk')->with('minio_brimen')->andReturn($diskMock2);
        
        $stream = fopen('php://memory', 'r');
        $diskMock1->shouldReceive('readStream')->with('a.txt')->once()->andReturn($stream);
        $diskMock2->shouldReceive('writeStream')->with('b.txt', $stream)->once()->andReturn(true);

        $storage = new Storage();
        $result = $storage->fromDriver(Storage::MINIO)->toDriver(Storage::MINIO_BRIMEN)->copy('a.txt', 'b.txt');
        $this->assertEquals('b.txt', $result->pathFile);
    }

    public function testMoveSameDriver(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('move')->with('a.txt', 'b.txt')->once()->andReturn(true);

        $storage = new Storage();
        $result = $storage->driver(Storage::MINIO)->move('a.txt', 'b.txt');
        $this->assertEquals('b.txt', $result->pathFile);
    }
}
