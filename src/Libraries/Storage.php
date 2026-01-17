<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.7.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\StorageDrivers\Minio;
use Spotlibs\PhpLib\Libraries\StorageDrivers\NFS;
use Spotlibs\PhpLib\Libraries\StorageDrivers\StorageInterface;

/**
 * Storage
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Storage
{
    private static array $drivers = ['nfs', 'minio'];
    private string $disk;
    /**
     * Create a new Storage instance for the specified driver.
     *
     * @param string $driver The storage driver to use.
     *
     * @throws RuntimeException
     *
     * @return Storage
     */
    public static function disk(string $driver): StorageInterface
    {
        if (!in_array($driver, self::$drivers)) {
            throw new RuntimeException("Invalid driver: $driver");
        }

        if ($driver === 'nfs') {
            return new NFS();
        }

        return new Minio();
    }
}
