<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage;

/**
 * StorageResult
 *
 * Result data transfer object for storage operations
 *
 * @category DataClass
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class StorageResult
{
    public string $driver;
    public string $folder;
    public string $pathFile;
    public string $fullPath;
    public int|string $size = '';
}
