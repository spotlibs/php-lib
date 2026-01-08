<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Filesystem\Adapters;

use DateTimeInterface;
use FilesystemIterator;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spotlibs\PhpLib\Libraries\Filesystem\Contracts\CustomFilesystemInterface;
use Spotlibs\PhpLib\Libraries\Filesystem\Traits\NfsPermissionTrait;
use Throwable;

/**
 * NfsAdapter
 *
 * Adapter for NFS (Network File System) storage
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class NfsAdapter implements CustomFilesystemInterface
{
    use NfsPermissionTrait;

    /**
     * Base path for NFS storage.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Base URL for public access.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Secret key for generating signed URLs.
     *
     * @var string
     */
    protected string $secretKey;

    /**
     * Constructor.
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config)
    {
        $this->basePath = rtrim($config['base_path'] ?? '', '/');
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->secretKey = $config['secret_key'] ?? '';

        $this->setPermissionConfig($config);
    }

    /**
     * Write contents to a file.
     *
     * @param string          $path     File path relative to base path
     * @param string|resource $contents File contents
     * @param array           $options  Additional options
     *
     * @return bool
     */
    public function put(string $path, $contents, array $options = []): bool
    {
        try {
            $fullPath = $this->getFullPath($path);
            $directory = dirname($fullPath);

            $this->ensureDirectoryExists($directory);

            $result = file_put_contents($fullPath, $contents);

            if ($result === false) {
                return false;
            }

            $this->setFilePermission($fullPath);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Store uploaded file with auto-generated filename.
     *
     * @param string             $path    Directory path
     * @param File|UploadedFile  $file    Uploaded file
     * @param array              $options Additional options
     *
     * @return string|false Return stored path or false on failure
     */
    public function putFile(string $path, $file, array $options = []): string|false
    {
        $name = Str::random(40) . '.' . $this->getFileExtension($file);

        return $this->putFileAs($path, $file, $name, $options);
    }

    /**
     * Store uploaded file with custom filename.
     *
     * @param string            $path    Directory path
     * @param File|UploadedFile $file    Uploaded file
     * @param string            $name    Custom filename
     * @param array             $options Additional options
     *
     * @return string|false Return stored path or false on failure
     */
    public function putFileAs(string $path, $file, string $name, array $options = []): string|false
    {
        try {
            $path = trim($path, '/');
            $directory = $this->getFullPath($path);

            $this->ensureDirectoryExists($directory);

            $file->move($directory, $name);

            $fullFilePath = $directory . '/' . $name;
            $this->setFilePermission($fullFilePath);

            return $path . '/' . $name;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get public URL for a file.
     *
     * @param string $path File path
     *
     * @return string
     */
    public function url(string $path): string
    {
        $path = trim($path, '/');

        return $this->baseUrl . '/' . $path;
    }

    /**
     * Get temporary signed URL with expiration.
     *
     * @param string            $path       File path
     * @param DateTimeInterface $expiration Expiration time
     * @param array             $options    Additional options
     *
     * @return string
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        $path = trim($path, '/');
        $expires = $expiration->getTimestamp();

        $signature = $this->generateSignature($path, $expires);

        return $this->baseUrl . '/' . $path . '?expires=' . $expires . '&signature=' . $signature;
    }

    /**
     * Get all files in a directory recursively.
     *
     * @param string|null $directory Directory path
     *
     * @return array
     */
    public function allFiles(?string $directory = null): array
    {
        $fullPath = $directory ? $this->getFullPath($directory) : $this->basePath;

        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $relativePath = str_replace($this->basePath . '/', '', $item->getPathname());
                    $files[] = $relativePath;
                }
            }
        } catch (Throwable $e) {
            return [];
        }

        return $files;
    }

    /**
     * Get all directories in a directory recursively.
     *
     * @param string|null $directory Directory path
     *
     * @return array
     */
    public function allDirectories(?string $directory = null): array
    {
        $fullPath = $directory ? $this->getFullPath($directory) : $this->basePath;

        if (!is_dir($fullPath)) {
            return [];
        }

        $directories = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $relativePath = str_replace($this->basePath . '/', '', $item->getPathname());
                    $directories[] = $relativePath;
                }
            }
        } catch (Throwable $e) {
            return [];
        }

        return $directories;
    }

    /**
     * Get full path from relative path.
     *
     * @param string $path Relative path
     *
     * @return string
     */
    protected function getFullPath(string $path): string
    {
        return $this->basePath . '/' . trim($path, '/');
    }

    /**
     * Get file extension from uploaded file.
     *
     * @param File|UploadedFile $file Uploaded file
     *
     * @return string
     */
    protected function getFileExtension(File|UploadedFile $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension();
        }

        return $file->getExtension();
    }

    /**
     * Generate signature for temporary URL.
     *
     * @param string $path    File path
     * @param int    $expires Expiration timestamp
     *
     * @return string
     */
    protected function generateSignature(string $path, int $expires): string
    {
        return hash_hmac('sha256', $path . $expires, $this->secretKey);
    }
}