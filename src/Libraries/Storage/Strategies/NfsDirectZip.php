<?php

/**
 * PHP version 8.3
 *
 * @category Application
 * @package  Libraries\Storage\Strategies
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage\Strategies;

use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\LogResolver;
use ZipArchive;

/**
 * NfsDirectZip
 *
 * Handles zero-copy zip building for NFS mounted files.
 *
 * @category Library
 * @package  Libraries\Storage\Strategies
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class NfsDirectZip
{
    private LogResolver $logResolver;

    /**
     * Constructor.
     *
     * @param LogResolver $logResolver Latency tracker instance
     */
    public function __construct(LogResolver $logResolver)
    {
        $this->logResolver = $logResolver;
    }

    /**
     * Verify that NFS base directories are mounted and readable.
     *
     * @param array<int, array{path_file: string}> $nfsFiles
     *
     * @throws RuntimeException
     * @return void
     */
    public function validateNfsMountHealth(array $nfsFiles): void
    {
        $this->logResolver->track('nfs_mount_health_check', function () use ($nfsFiles): void {
            $directories = [];
            foreach ($nfsFiles as $file) {
                $dir = dirname($file['path_file']);
                $directories[$dir] = true;
            }

            foreach (array_keys($directories) as $dir) {
                if (!is_dir($dir) || !is_readable($dir)) {
                    throw new RuntimeException("NFS Mount Unavailable or unreadable: {$dir}");
                }
            }
        });
    }

    /**
     * Calculate NFS footprint without loading files into memory and enforce payload limits.
     *
     * @param array<int, array{path_file: string}> $nfsFiles
     * @param int $maxBytes The maximum allowed size (default 480MB)
     *
     * @throws RuntimeException
     * @return int The total size of the NFS files in bytes
     */
    public function checkNfsPayloadSize(array $nfsFiles, int $maxBytes = 480 * 1024 * 1024): int
    {
        return $this->logResolver->track('nfs_size_check', function () use ($nfsFiles, $maxBytes): int {
            $runningBytes = 0;

            foreach ($nfsFiles as $file) {
                $path = $file['path_file'];

                if (!is_file($path)) {
                    throw new RuntimeException("NFS file not found: {$path}");
                }

                $runningBytes += filesize($path);

                if ($runningBytes > $maxBytes) {
                    throw new RuntimeException("Payload exceeds maximum allowed size of 480MB");
                }
            }

            return $runningBytes;
        });
    }

    /**
     * Open ZipArchive directly on the target PVC and register NFS files (Zero-copy).
     *
     * @param string $securelinkDestPath The final absolute path on the Securelink PVC
     * @param array<int, array{path_file: string, zip_path: string}> $nfsFiles
     *
     * @throws RuntimeException
     * @return ZipArchive
     */
    public function buildDirectNfsZip(string $securelinkDestPath, array $nfsFiles): ZipArchive
    {
        return $this->logResolver->track('nfs_zip_registration', function () use ($securelinkDestPath, $nfsFiles): ZipArchive {
            $destDir = dirname($securelinkDestPath);
            if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                throw new RuntimeException("Failed to create securelink destination directory: {$destDir}");
            }

            $zip = new ZipArchive();
            if ($zip->open($securelinkDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Failed to open ZIP directly at destination: {$securelinkDestPath}");
            }

            foreach ($nfsFiles as $item) {
                if (!$zip->addFile($item['path_file'], $item['zip_path'])) {
                    $zip->close();
                    throw new RuntimeException("Failed to add NFS file to zip: {$item['path_file']}");
                }

                // Instruct libzip to use 0% compression to bypass heavy Deflate algorithms
                $zip->setCompressionName($item['zip_path'], ZipArchive::CM_STORE);
            }

            return $zip;
        });
    }
}