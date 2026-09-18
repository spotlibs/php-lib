<?php

declare(strict_types=1);

namespace Tests\Libraries\Storage;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\DriverResolver;
use Spotlibs\PhpLib\Libraries\Storage\Storage;

class DriverResolverTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../bootstrap/app.php';
    }

    public function testResolveDefaultNfs(): void
    {
        putenv('DEFAULT_DRIVER_STORAGE=' . Storage::NFS);
        $resolver = new DriverResolver();
        $this->assertEquals(Storage::NFS, $resolver->resolveDefault());
    }

    public function testResolveDefaultEmpty(): void
    {
        putenv('DEFAULT_DRIVER_STORAGE=');
        $resolver = new DriverResolver();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEFAULT_DRIVER_STORAGE is not configured');
        $resolver->resolveDefault();
    }

    public function testResolveDefaultInvalid(): void
    {
        putenv('DEFAULT_DRIVER_STORAGE=INVALID');
        $resolver = new DriverResolver();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEFAULT_DRIVER_STORAGE has invalid value: INVALID');
        $resolver->resolveDefault();
    }

    public function testResolveExplicitValid(): void
    {
        $resolver = new DriverResolver();
        $this->assertEquals(Storage::MINIO, $resolver->resolveExplicit(Storage::MINIO));
    }

    public function testResolveExplicitInvalid(): void
    {
        $resolver = new DriverResolver();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown storage driver: INVALID');
        $resolver->resolveExplicit('INVALID');
    }
}
