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

namespace Spotlibs\PhpLib\Libraries\StorageDrivers;

use Illuminate\Http\File;
use Illuminate\Support\Str;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Logs\Log;

/**
 * Storage
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class NFS implements StorageInterface
{
    /**
     * Upload file to disk
     *
     * @param File   $file    file from http request
     * @param string $dirpath where to put the file
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return bool
     */
    public function upload(File $file, string $dirpath): bool
    {
        if (!is_dir($dirpath)) {
            if (!mkdir($dirpath, 0664, true)) {
                throw new RuntimeException("Failed to create destination directory: $dirpath");
            }
        }
        $file->move($dirpath);
        return true;
    }
    /**
     * Copy file in disk
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return bool
     */
    public function copy(string $srcPath, string $destPath): bool
    {
        if (!is_file($srcPath)) {
            throw new RuntimeException("File not found within filepath: $srcPath");
        }
        $tmp = explode("/", $destPath);
        $destDir = implode("/", array_slice($tmp, 0, -1));
        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0664, true)) {
                throw new RuntimeException("Failed to create destination directory: $destDir");
            }
        }
        if (!exec("mv $srcPath $destPath")) {
            throw new RuntimeException("Failed to move from $srcPath to $destPath");
        }

        return (bool) exec("chown -R 33:33 $destDir");
    }
    /**
     * Delete file in disk
     *
     * @param string $filepath file path to delete
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return bool
     */
    public function delete(string $filepath): bool
    {
        if (!is_file($filepath)) {
            return true;
        }
        if (!exec("rm $filepath")) {
            throw new RuntimeException("Failed to delete $filepath");
        }
        return true;
    }
    /**
     * Move file in disk
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return bool
     */
    public function move(string $srcPath, string $destPath): bool
    {
        $tmp = explode("/", $destPath);
        $destDir = implode("/", array_slice($tmp, 0, -1));
        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0664, true)) {
                throw new RuntimeException("Failed to create destination directory: $destDir");
            }
        }
        if (!exec("mv $srcPath $destPath")) {
            throw new RuntimeException("Failed to move from $srcPath to $destPath");
        }

        return (bool) exec("chown -R 33:33 $destDir");
    }
    /**
     * Create temporary URL for file in disk
     *
     * @param string $filepath file path to create secure link
     *
     * @return string
     */
    public function temporaryUrl(string $filepath): string
    {
        if (!is_file($filepath)) {
            Log::runtime()->warning(
                [
                    "message" => "File not found within filepath: $filepath"
                ]
            );
            return "";
        }
        $random = Str::random(40);
        if (!exec("ln -s $filepath /var/www/html/public/securelink/$random")) {
            Log::runtime()->warning(
                [
                "error" => "Failed to create secure link for file: $filepath",
                ]
            );
            return "";
        }
        return env("APP_URL", "localhost:8080") . "/securelink/$random";
    }
}
