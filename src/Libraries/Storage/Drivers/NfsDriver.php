<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage\Drivers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Storage;
use Spotlibs\PhpLib\Libraries\Storage\StorageDriverInterface;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;

/**
 * NfsDriver
 *
 * Native filesystem driver for NFS storage.
 *
 * @category Library
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class NfsDriver implements StorageDriverInterface
{
    /**
     * Resolve the actual absolute path using fallbacks if necessary.
     *
     * @param string $path file or directory path
     *
     * @return string resolved absolute path or original path if not found
     */
    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/data/NFS_') || str_starts_with($path, '/')) {
            return $path;
        }

        $envFallbacks = (string) env('PATH_NFS_FALLBACK_STORAGE_SPOTLIB', '');
        if ($envFallbacks === '') {
            return $path;
        }

        $fallbacks = array_filter(array_map('trim', explode(',', $envFallbacks)));
        
        foreach ($fallbacks as $basePath) {
            $fullPath = rtrim($basePath, '/') . '/' . ltrim($path, '/');
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return $path;
    }

    /**
     * Upload a file to an NFS directory.
     *
     * @param UploadedFile $file     file to upload
     * @param string       $dirpath  destination directory path
     * @param string       $filename optionally override file name
     *
     * @throws RuntimeException when the destination directory cannot be created
     *
     * @return StorageResult upload result
     */
    public function upload(UploadedFile $file, string $dirpath, string $filename = ''): StorageResult
    {
        if (!is_dir($dirpath) && !mkdir($dirpath, 0755, true)) {
            throw new RuntimeException("Failed to create destination directory: {$dirpath}");
        }

        $fileName = $filename === '' ? $file->getClientOriginalName() : $filename;
        $file->move($dirpath, $fileName);

        $result = new StorageResult();
        $result->driver = Storage::NFS;
        $result->pathFile = $fileName;
        $result->fullPath = rtrim($dirpath, '/') . '/' . $fileName;
        $result->folder = rtrim($dirpath, '/') . '/';

        return $result;
    }

    /**
     * Write a stream to an NFS destination path.
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
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (file_put_contents($destPath, $stream) === false) {
            throw new RuntimeException("Failed to write stream to NFS: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = Storage::NFS;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = $destDir . '/';

        return $result;
    }

    /**
     * Copy a file within the NFS filesystem.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the source is missing or the copy fails
     *
     * @return StorageResult copy result
     */
    public function copySameDriver(string $srcPath, string $destPath): StorageResult
    {
        $srcPath = $this->resolvePath($srcPath);

        if (!file_exists($srcPath)) {
            throw new RuntimeException("Source file not found: {$srcPath}");
        }

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!copy($srcPath, $destPath)) {
            throw new RuntimeException("Failed to copy file to: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = Storage::NFS;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = $destDir . '/';

        return $result;
    }

    /**
     * Move a file within the NFS filesystem.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the source is missing or the move fails
     *
     * @return StorageResult move result
     */
    public function moveSameDriver(string $srcPath, string $destPath): StorageResult
    {
        $srcPath = $this->resolvePath($srcPath);

        if (!file_exists($srcPath)) {
            throw new RuntimeException("Source file not found: {$srcPath}");
        }

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        if (!rename($srcPath, $destPath)) {
            throw new RuntimeException("Failed to move file to: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = Storage::NFS;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = $destDir . '/';

        return $result;
    }

    /**
     * Delete a file from the NFS filesystem.
     *
     * @param string $filepath file path to delete
     *
     * @throws RuntimeException when the file is missing or cannot be deleted
     *
     * @return void
     */
    public function delete(string $filepath): void
    {
        $filepath = $this->resolvePath($filepath);

        if (str_ends_with($filepath, '/') || is_dir($filepath)) {
            throw new RuntimeException("Cannot delete a folder: {$filepath}");
        }

        if (!file_exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        if (!unlink($filepath)) {
            throw new RuntimeException("Failed to delete file: {$filepath}");
        }
    }

    /**
     * Create a secure link to an NFS file.
     *
     * @param string   $filepath file path for the secure link
     * @param int|null $ttl      ignored because NFS links do not expire natively
     *
     * @throws RuntimeException when the source is missing or the link fails
     *
     * @return string secure link URL
     */
    public function securelink(string $filepath, ?int $ttl = null): string
    {
        $filepath = $this->resolvePath($filepath);

        if (!is_file($filepath)) {
            throw new RuntimeException("File not found within filepath: {$filepath}");
        }

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $random = Str::random(40);
        $random = $extension !== '' ? "{$random}.{$extension}" : $random;
        $securelinkDir = '/var/www/html/public/securelink';

        if (!is_dir($securelinkDir)) {
            @mkdir($securelinkDir, 0755, true);
        }

        exec("ln -s \"{$filepath}\" {$securelinkDir}/{$random}", $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException("Failed to create secure link for file: {$filepath}");
        }

        return env('APP_URL') . "/securelink/{$random}";
    }

    /**
     * Check whether a file exists on the NFS filesystem.
     *
     * @param string $filepath file path to check
     *
     * @return bool whether the file exists
     */
    public function exists(string $filepath): bool
    {
        return file_exists($this->resolvePath($filepath));
    }

    /**
     * Open a readable stream for an NFS file.
     *
     * @param string $filepath file path to read
     *
     * @throws RuntimeException when the file is missing or cannot be opened
     *
     * @return resource readable file stream
     */
    public function readStream(string $filepath)
    {
        $filepath = $this->resolvePath($filepath);

        if (!file_exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        $stream = fopen($filepath, 'r');
        if ($stream === false) {
            throw new RuntimeException("Failed to open read stream for: {$filepath}");
        }

        return $stream;
    }

    /**
     * List files directly within an NFS directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function files(string $dirpath): array
    {
        $dirpath = $this->resolvePath($dirpath);

        if (!is_dir($dirpath)) {
            return [];
        }

        $result = [];
        foreach (scandir($dirpath) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = rtrim($dirpath, '/') . '/' . $entry;
            if (is_file($fullPath)) {
                $size = filesize($fullPath);
                $result[] = [
                    'path' => $fullPath,
                    'size' => $size !== false ? $size : '',
                ];
            }
        }

        return $result;
    }

    /**
     * List files recursively within an NFS directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function allFiles(string $dirpath): array
    {
        $dirpath = $this->resolvePath($dirpath);

        if (!is_dir($dirpath)) {
            return [];
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirpath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $fullPath = $fileInfo->getPathname();
                $size = $fileInfo->getSize();
                $result[] = [
                    'path' => $fullPath,
                    'size' => $size !== false ? $size : '',
                ];
            }
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
        $filepath = $this->resolvePath($filepath);

        if (!file_exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        $size = filesize($filepath);

        $result = new StorageResult();
        $result->driver = Storage::NFS;
        $result->pathFile = basename($filepath);
        $result->fullPath = $filepath;
        $result->folder = dirname($filepath) . '/';
        $result->size = $size !== false ? $size : '';

        return $result;
    }
}
