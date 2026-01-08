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

namespace Spotlibs\PhpLib\Libraries\Filesystem\Contracts;

use DateTimeInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

/**
 * CustomFilesystemInterface
 *
 * Name for CustomFilesystemInterface
 *
 * @category HttpClient
 * @package  Client
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
interface CustomFilesystemInterface
{
    /**
     * Write contents to a file.
     *
     * @param string $path
     * @param string $contents
     * @param array $options
     * @return bool
     */
    public function put(string $path, string $contents, array $options = []): bool;

    /**
     * Store uploaded file with auto-generated filename.
     *
     * @param string $path Directory path
     * @param File|UploadedFile $file
     * @param array $options
     * @return string|false Return stored path or false on failure
     */
    public function putFile(string $path, $file, array $options = []): string|false;

    /**
     * Store uploaded file with custom filename.
     *
     * @param string $path Directory path
     * @param File|UploadedFile $file
     * @param string $name Custom filename
     * @param array $options
     * @return string|false Return stored path or false on failure
     */
    public function putFileAs(string $path, $file, string $name, array $options = []): string|false;

    /**
     * Get public URL for a file.
     *
     * @param string $path
     * @return string
     */
    public function url(string $path): string;

    /**
     * Get temporary signed URL with expiration.
     *
     * @param string $path
     * @param DateTimeInterface $expiration
     * @param array $options
     * @return string
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string;

    /**
     * Get all files in a directory recursively.
     *
     * @param string|null $directory
     * @return array
     */
    public function allFiles(?string $directory = null): array;

    /**
     * Get all directories in a directory recursively.
     *
     * @param string|null $directory
     * @return array
     */
    public function allDirectories(?string $directory = null): array;
}