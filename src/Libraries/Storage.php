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
    protected string $driver;
    /**
     * Create a new Storage instance for the specified driver.
     *
     * @param string $driver The storage driver to use.
     *
     * @return \Spotlibs\PhpLib\Libraries\StorageDrivers\StorageInterface
     */
    public static function disk(string $driver): StorageInterface
    {
        if (str_contains(strtolower($driver), "minio")) {
            return new Minio($driver);
        }
        return new NFS($driver);
    }

    /**
     * Create a new Storage instance.
     *
     * @param string $driver driver name. example: nfs_xxx
     *
     * @return void
     */
    public function __construct(string $driver)
    {
        $this->driver = $driver;
    }
    /**
     * Parse file name from filepath
     *
     * @param string $filepath path of the file
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return string
     */
    protected function getFileName(string $filepath): string
    {
        if ($x = explode("/", $filepath)) {
            return $x[-1];
        }
        throw new RuntimeException("failed to get filename from $filepath");
    }
}
