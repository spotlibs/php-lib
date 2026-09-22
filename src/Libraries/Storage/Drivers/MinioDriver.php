<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage\Drivers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Storage as StorageManager;
use Spotlibs\PhpLib\Libraries\Storage\StorageDriverInterface;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;

/**
 * MinioDriver
 *
 * Storage driver backed by Laravel's MinIO filesystem disk.
 *
 * @category Library
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class MinioDriver implements StorageDriverInterface
{
    protected string $disk;

    /**
     * Create a MinIO storage driver.
     *
     * @param string $disk Laravel filesystem disk name
     */
    public function __construct(string $disk = 'minio')
    {
        $this->disk = $disk;
    }

    /**
     * Upload a file to MinIO.
     *
     * @param UploadedFile $file     file to upload
     * @param string       $dirpath  destination directory path
     * @param string       $filename optionally override file name
     *
     * @throws RuntimeException when the upload fails
     *
     * @return StorageResult upload result
     */
    public function upload(UploadedFile $file, string $dirpath, string $filename = ''): StorageResult
    {
        $fileName = $filename === '' ? $file->getClientOriginalName() : $filename;
        $destPath = rtrim($dirpath, '/') . '/' . $fileName;

        if (!LaravelStorage::disk($this->disk)->put($destPath, $file->getContent())) {
            throw new RuntimeException("Failed to upload file to MinIO: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = $fileName;
        $result->fullPath = $destPath;
        $result->folder = rtrim($dirpath, '/') . '/';

        return $result;
    }

    /**
     * Write a resource stream to MinIO.
     *
     * @param resource $stream   readable stream
     * @param string   $destPath destination file path
     *
     * @throws RuntimeException when the stream cannot be written
     *
     * @return StorageResult write result
     */
    public function writeStream($stream, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->writeStream($destPath, $stream)) {
            throw new RuntimeException("Failed to write stream to MinIO: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Copy a file within MinIO.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the copy fails
     *
     * @return StorageResult copy result
     */
    public function copySameDriver(string $srcPath, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->copy($srcPath, $destPath)) {
            throw new RuntimeException("MinIO copy failed: {$srcPath} -> {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Move a file within MinIO.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the move fails
     *
     * @return StorageResult move result
     */
    public function moveSameDriver(string $srcPath, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->move($srcPath, $destPath)) {
            throw new RuntimeException("MinIO move failed: {$srcPath} -> {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Delete a file from MinIO.
     *
     * @param string $filepath file path to delete
     *
     * @throws RuntimeException when the deletion fails
     *
     * @return void
     */
    public function delete(string $filepath): void
    {
        $disk = LaravelStorage::disk($this->disk);

        if (str_ends_with($filepath, '/') || (method_exists($disk, 'directoryExists') && $disk->directoryExists($filepath))) {
            throw new RuntimeException("Cannot delete a folder: {$filepath}");
        }

        if (!$disk->delete($filepath)) {
            throw new RuntimeException("MinIO delete failed: {$filepath}");
        }
    }

    /**
     * Generate a temporary URL for a MinIO file.
     *
     * @param string   $filepath file path for the secure link
     * @param int|null $ttl      link lifetime in seconds
     *
     * @return string secure link URL
     */
    public function securelink(string $filepath, ?int $ttl = null): string
    {
        $ttlSeconds = $ttl ?? (int) env('MINIO_EXPIRED_URL', 60);

        return LaravelStorage::disk($this->disk)->temporaryUrl(
            $filepath,
            Carbon::now()->addSeconds($ttlSeconds)
        );
    }

    /**
     * Check whether a file exists in MinIO.
     *
     * @param string $filepath file path to check
     *
     * @return bool whether the file exists
     */
    public function exists(string $filepath): bool
    {
        return LaravelStorage::disk($this->disk)->exists($filepath);
    }

    /**
     * Open a readable stream for a MinIO file.
     *
     * @param string $filepath file path to read
     *
     * @throws RuntimeException when the stream cannot be opened
     *
     * @return resource readable file stream
     */
    public function readStream(string $filepath)
    {
        $stream = LaravelStorage::disk($this->disk)->readStream($filepath);
        if ($stream === null || $stream === false) {
            throw new RuntimeException("Failed to open read stream for: {$filepath}");
        }

        return $stream;
    }

    /**
     * List files directly within a MinIO directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function files(string $dirpath): array
    {
        $disk = LaravelStorage::disk($this->disk);
        $paths = $disk->files($dirpath);

        $result = [];
        foreach ($paths as $path) {
            $result[] = [
                'path' => $path,
                'size' => $disk->size($path),
            ];
        }

        return $result;
    }

    /**
     * List files recursively within a MinIO directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function allFiles(string $dirpath): array
    {
        $disk = LaravelStorage::disk($this->disk);
        $paths = $disk->allFiles($dirpath);

        $result = [];
        foreach ($paths as $path) {
            $result[] = [
                'path' => $path,
                'size' => $disk->size($path),
            ];
        }

        return $result;
    }

    /**
     * Get information about a specific file.
     *
     * @param string $filepath file path to inspect
     *
     * @throws RuntimeException when the file is not found
     *
     * @return StorageResult
     */
    public function info(string $filepath): StorageResult
    {
        $disk = LaravelStorage::disk($this->disk);
        if (!$disk->exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($filepath);
        $result->fullPath = $filepath;
        $result->folder = dirname($filepath) . '/';
        $result->size = $disk->size($filepath);

        return $result;
    }
}
