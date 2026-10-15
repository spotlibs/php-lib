<?php

declare(strict_types=1);

namespace Tests\Libraries\StorageDrivers;

use Mockery;
use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Libraries\StorageDrivers\MinioAdapter;

/**
 * Pins the MinioAdapter::allFiles legacy contract (v1.7.2): raw listContents
 * entries, non-recursive by default. Legacy callers depend on this shape.
 */
class MinioAdapterTest extends TestCase
{
    private array $entries = [
        ['type' => 'dir', 'path' => '378/REF/dokumentasi', 'basename' => 'dokumentasi'],
        ['type' => 'file', 'path' => '378/REF/dokumentasi/a.png', 'size' => '10'],
    ];

    protected function tearDown(): void
    {
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
        $result = $this->adapter('378/REF', false)->allFiles('378/REF');

        $this->assertSame($this->entries, $result);
    }

    public function testAllFilesHonorsExplicitRecursive(): void
    {
        $result = $this->adapter('378/REF', true)->allFiles('378/REF', true);

        $this->assertSame($this->entries, $result);
    }
}
