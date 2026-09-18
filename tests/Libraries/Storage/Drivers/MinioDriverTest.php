<?php

declare(strict_types=1);

namespace Tests\Libraries\Storage\Drivers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\MinioDriver;
use Spotlibs\PhpLib\Libraries\Storage\Storage as StorageManager;
use Mockery;
use Carbon\Carbon;

class MinioDriverTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../../bootstrap/app.php';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testUploadSuccess(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        
        $diskMock->shouldReceive('put')->with('/tmp/dir/test.txt', 'content')->once()->andReturn(true);

        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('getClientOriginalName')->andReturn('test.txt');
        $fileMock->shouldReceive('getContent')->andReturn('content');

        $driver = new MinioDriver();
        $result = $driver->upload($fileMock, '/tmp/dir');

        $this->assertEquals(StorageManager::MINIO, $result->driver);
        $this->assertEquals('test.txt', $result->pathFile);
        $this->assertEquals('/tmp/dir/test.txt', $result->fullPath);
    }

    public function testUploadFailure(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('put')->once()->andReturn(false);

        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('getClientOriginalName')->andReturn('test.txt');
        $fileMock->shouldReceive('getContent')->andReturn('content');

        $driver = new MinioDriver();
        $this->expectException(RuntimeException::class);
        $driver->upload($fileMock, '/tmp/dir');
    }

    public function testWriteStreamSuccess(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio_brimen')->andReturn($diskMock);
        
        $stream = fopen('php://memory', 'r');
        $diskMock->shouldReceive('writeStream')->with('/tmp/dir/test.txt', $stream)->once()->andReturn(true);

        $driver = new MinioDriver('minio_brimen');
        $result = $driver->writeStream($stream, '/tmp/dir/test.txt');

        $this->assertEquals(StorageManager::MINIO_BRIMEN, $result->driver);
        $this->assertEquals('test.txt', $result->pathFile);
    }

    public function testCopySameDriverSuccess(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('copy')->with('src.txt', 'dst.txt')->once()->andReturn(true);

        $driver = new MinioDriver();
        $result = $driver->copySameDriver('src.txt', 'dst.txt');
        $this->assertEquals('dst.txt', $result->pathFile);
    }

    public function testMoveSameDriverSuccess(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('move')->with('src.txt', 'dst.txt')->once()->andReturn(true);

        $driver = new MinioDriver();
        $result = $driver->moveSameDriver('src.txt', 'dst.txt');
        $this->assertEquals('dst.txt', $result->pathFile);
    }

    public function testDeleteSuccess(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('delete')->with('test.txt')->once()->andReturn(true);

        $driver = new MinioDriver();
        $driver->delete('test.txt');
        $this->assertTrue(true);
    }

    public function testDeleteFolderFails(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);

        $driver = new MinioDriver();
        $this->expectException(RuntimeException::class);
        $driver->delete('folder/');
    }

    public function testExists(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('exists')->with('test.txt')->once()->andReturn(true);

        $driver = new MinioDriver();
        $this->assertTrue($driver->exists('test.txt'));
    }

    public function testReadStream(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        
        $stream = fopen('php://memory', 'r');
        $diskMock->shouldReceive('readStream')->with('test.txt')->once()->andReturn($stream);

        $driver = new MinioDriver();
        $this->assertSame($stream, $driver->readStream('test.txt'));
    }

    public function testFiles(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('files')->with('/tmp')->once()->andReturn(['/tmp/a.txt']);
        $diskMock->shouldReceive('size')->with('/tmp/a.txt')->once()->andReturn(123);

        $driver = new MinioDriver();
        $files = $driver->files('/tmp');
        $this->assertCount(1, $files);
        $this->assertEquals('/tmp/a.txt', $files[0]['path']);
        $this->assertEquals(123, $files[0]['size']);
    }

    public function testAllFiles(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('allFiles')->with('/tmp')->once()->andReturn(['/tmp/a.txt']);
        $diskMock->shouldReceive('size')->with('/tmp/a.txt')->once()->andReturn(123);

        $driver = new MinioDriver();
        $files = $driver->allFiles('/tmp');
        $this->assertCount(1, $files);
        $this->assertEquals('/tmp/a.txt', $files[0]['path']);
        $this->assertEquals(123, $files[0]['size']);
    }

    public function testInfo(): void
    {
        $diskMock = Mockery::mock();
        LaravelStorage::shouldReceive('disk')->with('minio')->andReturn($diskMock);
        $diskMock->shouldReceive('exists')->with('test.txt')->once()->andReturn(true);
        $diskMock->shouldReceive('size')->with('test.txt')->once()->andReturn(123);

        $driver = new MinioDriver();
        $info = $driver->info('test.txt');
        $this->assertEquals(123, $info->size);
    }
}
