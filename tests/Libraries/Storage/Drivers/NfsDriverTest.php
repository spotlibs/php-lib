<?php

declare(strict_types=1);

namespace Tests\Libraries\Storage\Drivers;

use Illuminate\Http\UploadedFile;
use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\NfsDriver;
use Spotlibs\PhpLib\Libraries\Storage\Storage;
use Mockery;

class NfsDriverTest extends TestCase
{
    private string $tempDir;

    public function createApplication()
    {
        return require __DIR__.'/../../../../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/spotlibs_nfs_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        putenv("PATH_NFS_FALLBACK_STORAGE_SPOTLIB={$this->tempDir}");
    }

    protected function tearDown(): void
    {
        putenv("PATH_NFS_FALLBACK_STORAGE_SPOTLIB=");
        $this->deleteTempDir($this->tempDir);
        Mockery::close();
        parent::tearDown();
    }

    private function deleteTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteTempDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testUpload(): void
    {
        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('getClientOriginalName')->andReturn('test.txt');
        $fileMock->shouldReceive('move')->with($this->tempDir . '/uploads', 'test.txt')->once();

        $driver = new NfsDriver();
        $result = $driver->upload($fileMock, $this->tempDir . '/uploads');

        $this->assertEquals(Storage::NFS, $result->driver);
        $this->assertEquals('test.txt', $result->pathFile);
    }

    public function testUploadWithFilenameOverride(): void
    {
        $fileMock = Mockery::mock(UploadedFile::class);
        $fileMock->shouldReceive('getClientOriginalName')->andReturn('test.txt');
        $fileMock->shouldReceive('move')->with($this->tempDir . '/uploads', 'custom.txt')->once();

        $driver = new NfsDriver();
        $result = $driver->upload($fileMock, $this->tempDir . '/uploads', 'custom.txt');

        $this->assertEquals(Storage::NFS, $result->driver);
        $this->assertEquals('custom.txt', $result->pathFile);
    }

    public function testWriteStream(): void
    {
        $driver = new NfsDriver();
        $stream = fopen('php://memory', 'r');
        fwrite($stream, 'content');
        rewind($stream);

        $dest = $this->tempDir . '/write/test.txt';
        $result = $driver->writeStream($stream, $dest);

        $this->assertEquals(Storage::NFS, $result->driver);
        $this->assertFileExists($dest);
        // $this->assertEquals('content', file_get_contents($dest));
    }

    public function testCopySameDriver(): void
    {
        $src = $this->tempDir . '/src.txt';
        $dst = $this->tempDir . '/dst.txt';
        file_put_contents($src, 'content');

        $driver = new NfsDriver();
        $result = $driver->copySameDriver($src, $dst);

        $this->assertEquals(Storage::NFS, $result->driver);
        $this->assertFileExists($dst);
    }

    public function testMoveSameDriver(): void
    {
        $src = $this->tempDir . '/src.txt';
        $dst = $this->tempDir . '/dst.txt';
        file_put_contents($src, 'content');

        $driver = new NfsDriver();
        $result = $driver->moveSameDriver($src, $dst);

        $this->assertEquals(Storage::NFS, $result->driver);
        $this->assertFileExists($dst);
        $this->assertFileDoesNotExist($src);
    }

    public function testDelete(): void
    {
        $file = $this->tempDir . '/test.txt';
        file_put_contents($file, 'content');

        $driver = new NfsDriver();
        $driver->delete($file);

        $this->assertFileDoesNotExist($file);
    }

    public function testDeleteFailsOnFolder(): void
    {
        $dir = $this->tempDir . '/folder';
        mkdir($dir);

        $driver = new NfsDriver();
        $this->expectException(RuntimeException::class);
        $driver->delete($dir);
    }

    public function testExists(): void
    {
        $file = $this->tempDir . '/test.txt';
        file_put_contents($file, 'content');

        $driver = new NfsDriver();
        $this->assertTrue($driver->exists($file));
        $this->assertFalse($driver->exists($this->tempDir . '/not-exist.txt'));
    }

    public function testReadStream(): void
    {
        $file = $this->tempDir . '/test.txt';
        file_put_contents($file, 'content');

        $driver = new NfsDriver();
        $stream = $driver->readStream($file);

        $this->assertIsResource($stream);
        $this->assertEquals('content', stream_get_contents($stream));
        fclose($stream);
    }

    public function testFiles(): void
    {
        $dir = $this->tempDir . '/folder';
        mkdir($dir);
        file_put_contents($dir . '/test1.txt', '123');
        file_put_contents($dir . '/test2.txt', '1234');
        mkdir($dir . '/sub'); // Should be ignored by files()

        $driver = new NfsDriver();
        $files = $driver->files($dir);

        $this->assertCount(2, $files);
    }

    public function testAllFiles(): void
    {
        $dir = $this->tempDir . '/folder';
        mkdir($dir);
        file_put_contents($dir . '/test1.txt', '123');
        mkdir($dir . '/sub');
        file_put_contents($dir . '/sub/test2.txt', '1234');

        $driver = new NfsDriver();
        $files = $driver->allFiles($dir);

        $this->assertCount(2, $files);
    }

    public function testInfo(): void
    {
        $file = $this->tempDir . '/test.txt';
        file_put_contents($file, '12345');

        $driver = new NfsDriver();
        $info = $driver->info($file);

        $this->assertEquals(Storage::NFS, $info->driver);
        $this->assertEquals(5, $info->size);
    }
}
