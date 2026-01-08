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

namespace Spotlibs\PhpLib\Libraries\Filesystem\Traits;

/**
 * NfsPermissionTrait
 *
 * Name for NfsPermissionTrait
 *
 * @category HttpClient
 * @package  Client
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
trait NfsPermissionTrait
{
    /**
     * Default owner for files/directories.
     *
     * @var string
     */
    protected string $owner = 'www-data';

    /**
     * Default group for files/directories.
     *
     * @var string
     */
    protected string $group = 'www-data';

    /**
     * Default file permission.
     *
     * @var int
     */
    protected int $filePermission = 0644;

    /**
     * Default directory permission.
     *
     * @var int
     */
    protected int $directoryPermission = 0755;

    /**
     * Set permission configuration from config array.
     *
     * @param array $config
     * @return void
     */
    protected function setPermissionConfig(array $config): void
    {
        $this->owner = $config['owner'] ?? 'www-data';
        $this->group = $config['group'] ?? 'www-data';
        $this->filePermission = $config['file_permission'] ?? 0644;
        $this->directoryPermission = $config['directory_permission'] ?? 0755;
    }

    /**
     * Set ownership and permission for a file.
     *
     * @param string $path Full path to file
     * @return bool
     */
    protected function setFilePermission(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $this->changeMode($path, $this->filePermission);
        $this->changeOwner($path);

        return true;
    }

    /**
     * Set ownership and permission for a directory.
     *
     * @param string $path Full path to directory
     * @return bool
     */
    protected function setDirectoryPermission(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $this->changeMode($path, $this->directoryPermission);
        $this->changeOwner($path);

        return true;
    }

    /**
     * Change file/directory mode (chmod).
     *
     * @param string $path
     * @param int $permission
     * @return bool
     */
    protected function changeMode(string $path, int $permission): bool
    {
        try {
            return chmod($path, $permission);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Change file/directory owner and group (chown & chgrp).
     *
     * @param string $path
     * @return bool
     */
    protected function changeOwner(string $path): bool
    {
        try {
            chown($path, $this->owner);
            chgrp($path, $this->group);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ensure directory exists, create if not.
     * Also sets proper permission for all created directories.
     *
     * @param string $path Full path to directory
     * @return bool
     */
    protected function ensureDirectoryExists(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        try {
            // Create directory recursively
            mkdir($path, $this->directoryPermission, true);

            // Set permission for the created directory
            $this->setDirectoryPermission($path);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ensure directory exists and set permission for all parent directories.
     * Useful when creating nested directories.
     *
     * @param string $basePath Base path (root)
     * @param string $relativePath Relative path from base
     * @return bool
     */
    protected function ensureDirectoryExistsWithParents(string $basePath, string $relativePath): bool
    {
        $parts = array_filter(explode('/', $relativePath));
        $currentPath = rtrim($basePath, '/');

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;

            if (!is_dir($currentPath)) {
                try {
                    mkdir($currentPath, $this->directoryPermission);
                    $this->setDirectoryPermission($currentPath);
                } catch (\Throwable $e) {
                    return false;
                }
            }
        }

        return true;
    }
}