<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage;

use Spotlibs\PhpLib\Exceptions\RuntimeException;

/**
 * DriverResolver
 *
 * Resolve storage drivers for storage operations.
 *
 * @category Library
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class DriverResolver
{
    /**
     * Resolve the driver name to use when no explicit driver was chained.
     *
     * @return string one of Storage::NFS, Storage::MINIO, Storage::MINIO_BRIMEN
     *@throws RuntimeException when env is not set or invalid
     *
     */
    public function resolveDefault(): string
    {
        $driver = env('DEFAULT_DRIVER_STORAGE');

        if ($driver === null || $driver === '') {
            throw new RuntimeException('DEFAULT_DRIVER_STORAGE is not configured');
        }

        if (!in_array($driver, [
            Storage::NFS,
            Storage::MINIO,
            Storage::MINIO_BRIMEN,
        ], true)) {
            throw new RuntimeException("DEFAULT_DRIVER_STORAGE has invalid value: {$driver}");
        }

        return $driver;
    }

    /**
     * Validate an explicitly-chained driver name.
     *
     * @param string $driver driver constant value
     *
     * @return string the same driver name, validated
     *@throws RuntimeException when driver name is invalid
     *
     */
    public function resolveExplicit(string $driver): string
    {
        if (!in_array($driver, [
            Storage::NFS,
            Storage::MINIO,
            Storage::MINIO_BRIMEN,
        ], true)) {
            throw new RuntimeException("Unknown storage driver: {$driver}");
        }

        return $driver;
    }
}
