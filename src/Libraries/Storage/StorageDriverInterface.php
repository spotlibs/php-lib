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

use Illuminate\Http\UploadedFile;
use Spotlibs\PhpLib\Exceptions\RuntimeException;

/**
 * StorageDriverInterface
 *
 * Contract for storage driver implementations.
 *
 * @category Library
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
interface StorageDriverInterface
{
    /**
     * Upload a file to the storage driver.
     *
     * @param UploadedFile $file    file to upload
     * @param string       $dirpath destination directory path
     *
     * @throws RuntimeException when the upload fails
     *
     * @return StorageResult upload result
     */
    public function upload(UploadedFile $file, string $dirpath): StorageResult;

    /**
     * Copy a file within the same storage driver.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the copy fails
     *
     * @return StorageResult copy result
     */
    public function copySameDriver(string $srcPath, string $destPath): StorageResult;

    /**
     * Move a file natively within the same storage driver.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the move fails
     *
     * @return StorageResult move result
     */
    public function moveSameDriver(string $srcPath, string $destPath): StorageResult;

    /**
     * Write a resource stream to the storage driver.
     *
     * @param resource $stream   readable stream
     * @param string   $destPath destination file path
     *
     * @throws RuntimeException when the write fails
     *
     * @return StorageResult upload result
     */
    public function writeStream($stream, string $destPath): StorageResult;

    /**
     * Delete a file from the storage driver.
     *
     * @param string $filepath file path to delete
     *
     * @throws RuntimeException when the deletion fails
     *
     * @return void
     */
    public function delete(string $filepath): void;

    /**
     * Create a secure link for a file.
     *
     * @param string   $filepath file path for the secure link
     * @param int|null $ttl      link lifetime in seconds
     *
     * @throws RuntimeException when the secure link cannot be created
     *
     * @return string secure link URL
     */
    public function securelink(string $filepath, ?int $ttl = null): string;

    /**
     * Check whether a file exists on the storage driver.
     *
     * @param string $filepath file path to check
     *
     * @return bool whether the file exists
     */
    public function exists(string $filepath): bool;

    /**
     * Open a readable stream for a file.
     *
     * @param string $filepath file path to read
     *
     * @throws RuntimeException when the file cannot be read
     *
     * @return resource readable file stream
     */
    public function readStream(string $filepath);

    /**
     * List files in a directory without traversing subdirectories.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function files(string $dirpath): array;

    /**
     * List files in a directory recursively.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function allFiles(string $dirpath): array;
}
