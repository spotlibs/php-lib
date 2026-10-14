<?php

declare(strict_types=1);

namespace Tests\Libraries\StorageDrivers;

use Mockery;
use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Libraries\StorageDrivers\MinioAdapter;

/**
 * Pins the MinioAdapter::allFiles contract so legacy callers (raw
 * listContents entries) are not changed by storage manager work.
 */
class MinioAdapterTest extends TestCase
{
    private const ENV_KEY = 'MINIO_ALLFILES_PATHS';

    private array $entries = [
        ['type' => 'dir', 'path' => '378/REF/dokumentasi', 'basename' => 'dokumentasi'],
        ['type' => 'file', 'path' => '378/REF/dokumentasi/a.png', 'size' => '10'],
    ];

    protected function tearDown(): void
    {
        putenv(self::ENV_KEY);
        Mockery::close();
        parent::tearDown();
    }

    private function adapter(string $dir, bool $expectedRecursive): MinioAdapter
    {
        $adapter = Mockery::mock(MinioAdapter::class)->makePartial();
        $adapter->shouldReceive('listContents')
            ->with($dir, $expectedRecursive)
            ->once()
            ->andReturn($this->entries);

        return $adapter;
    }

    public function testAllFilesDefaultReturnsRawEntriesNonRecursive(): void
    {
        putenv(self::ENV_KEY);

        $result = $this->adapter('378/REF', false)->allFiles('378/REF');

        $this->assertSame($this->entries, $result);
    }

    public function testAllFilesDefaultHonorsExplicitRecursive(): void
    {
        putenv(self::ENV_KEY);

        $result = $this->adapter('378/REF', true)->allFiles('378/REF', true);

        $this->assertSame($this->entries, $result);
    }

    public function testAllFilesOptInReturnsFilePathsRecursiveByDefault(): void
    {
        putenv(self::ENV_KEY . '=true');

        $result = $this->adapter('378/REF', true)->allFiles('378/REF');

        $this->assertSame(['378/REF/dokumentasi/a.png'], $result);
    }

    public function testAllFilesOptInHonorsExplicitNonRecursive(): void
    {
        putenv(self::ENV_KEY . '=true');

        $result = $this->adapter('378/REF', false)->allFiles('378/REF', false);

        $this->assertSame(['378/REF/dokumentasi/a.png'], $result);
    }
}
