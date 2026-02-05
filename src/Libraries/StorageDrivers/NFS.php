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

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage;

/**
 * Storage
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class NFS extends Storage implements StorageInterface
{
    /**
     * Create a new NFS instance.
     *
     * @param string $driver driver name. example: nfs_xxx
     *
     * @return void
     */
    public function __construct(string $driver)
    {
        parent::__construct($driver);
    }
    /**
     * Upload file to disk
     *
     * @param UploadedFile $file     file from http request
     * @param string       $dirpath  where to put the file
     * @param string       $filename optionally overide file name
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return bool
     */
    public function upload(UploadedFile $file, string $dirpath, string $filename = ''): bool
    {
        if (!is_dir($dirpath)) {
            if (!mkdir($dirpath, 0755, true)) {
                throw new RuntimeException("Failed to create destination directory: $dirpath");
            }
        }
        $file->move($dirpath, $filename == '' ? $file->getClientOriginalName() : $filename);
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
            if (!mkdir($destDir, 0755, true)) {
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
            if (!mkdir($destDir, 0755, true)) {
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
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return string
     */
    public function securelink(string $filepath): string
    {
        if (!is_file($filepath)) {
            throw new RuntimeException("File not found within filepath: $filepath");
        }
        $random = Str::random(40);
        if (!exec("ln -s $filepath /var/www/html/public/securelink/$random")) {
            throw new RuntimeException("Failed to create secure link for file: $filepath");
        }
        return env('APP_URL') . "/securelink/$random";
    }
    /**
     * Create temporary URL for a folder in disk
     *
     * @param string $dirpath directory path to create secure link
     *
     * @throws \Spotlibs\PhpLib\Exceptions\RuntimeException
     *
     * @return array<string>
     */
    public function securelinkFolder(string $dirpath): array
    {
        if (!is_dir($dirpath)) {
            throw new RuntimeException("Directory not found within dirpath: $dirpath");
        }
        $random = Str::random(40);
        if (!exec("ln -sf $dirpath /var/www/html/public/securelink/$random")) {
            throw new RuntimeException("Failed to create secure link for file: $dirpath");
        }
        $result = glob("/var/www/html/public/securelink/$random/*");
        foreach ($result as $key => $r) {
            $result[$key] = str_replace("/var/www/html", env('APP_URL'), $r);
        }
        return $result;
    }
}
