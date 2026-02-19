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

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as FacadeStorage;
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
class Minio extends Storage implements StorageInterface
{
    /**
     * Create a new NFS instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->driver = "minio";
    }

    /**
     * Set disk driver for different minio config setup in config/filesystem.php
     *
     * @param string $driver driver name according to config setup in config/filesystem.php
     *
     * @return Minio
     */
    public function disk(string $driver): self
    {
        $this->driver = $driver;
        return $this;
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
    public function upload(UploadedFile $file, string $dirpath, string $filename = ""): bool
    {
        $fileName = $filename == "" ? $file->getClientOriginalName() : $filename;
        FacadeStorage::disk($this->driver)->put($dirpath . "/" . $fileName, $file->getContent());
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
        FacadeStorage::disk($this->driver)->copy($srcPath, $destPath);
        return true;
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
        FacadeStorage::disk($this->driver)->delete($filepath);
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
        FacadeStorage::disk($this->driver)->move($srcPath, $destPath);
        return true;
    }
    /**
     * Create temporary URL for file in disk
     *
     * @param string $filepath file path to create secure link
     *
     * @return string
     */
    public function securelink(string $filepath): string
    {
        /**
         * Illuminate\Filesystem\FilesystemAdapter $disk file storage disk
         *
         * @var \Illuminate\Filesystem\FilesystemAdapter $disk file storage disk
        */
        $disk = FacadeStorage::disk($this->driver);
        $urlpath = $disk->temporaryUrl($filepath, Carbon::now()->addSeconds((int) env('MINIO_EXPIRED_URL', 60)));
        return $urlpath;
    }
    /**
     * Create temporary URL for a folder in disk
     *
     * @param string $dirpath directory path to create secure link
     *
     * @return array<string>
     */
    public function securelinkFolder(string $dirpath): array
    {
        $random = Str::random(40);
        $linkFolder = "/var/www/html/public/securelink/$random";
        if (!mkdir($linkFolder, 0755, true)) {
            throw new RuntimeException("Failed to create securelink directory: $linkFolder");
        }
        $allFiles = FacadeStorage::disk($this->driver)->allFiles($dirpath);
        $result = [];
        foreach ($allFiles as $file) {
            $filename = parent::getFileName($file["path"]);
            $fileStream = FacadeStorage::disk($this->driver)->readStream($file["path"]);
            $writeStream = fopen($linkFolder . "/" . $filename, 'w');
            stream_copy_to_stream($fileStream, $writeStream);
            fclose($fileStream);
            fclose($writeStream);
            $result[] = env('APP_URL') . "/securelink/" . $random . "/" . $filename;
        }

        return $result;
    }
}
