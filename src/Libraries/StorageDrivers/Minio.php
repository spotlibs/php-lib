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
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Storage
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Minio implements StorageInterface
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
        Storage::disk('minio')->put($dirpath, $file->getContent());
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
        Storage::disk('minio')->copy($srcPath, $destPath);
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
        Storage::disk('minio')->delete($filepath);
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
        Storage::disk('minio')->move($srcPath, $destPath);
        return true;
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
        /**
         * Illuminate\Filesystem\FilesystemAdapter $disk file storage disk
         *
         * @var \Illuminate\Filesystem\FilesystemAdapter $disk file storage disk
        */
        $disk = Storage::disk('minio');
        return $disk->temporaryUrl($filepath, Carbon::now()->addSeconds(env('MINIO_EXPIRED_URL', 60)));
    }
}
