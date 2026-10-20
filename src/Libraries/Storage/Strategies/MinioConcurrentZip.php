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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\MinioDriver;
use Spotlibs\PhpLib\Libraries\Storage\LogResolver;
use Throwable;
use ZipArchive;

/**
 * MinioConcurrentZip
 *
 * High-performance concurrent zip builder for MinIO.
 * Fetches files in parallel to local pod storage, builds the archive directly
 * onto the destination NFS Securelink PVC (0% CPU compression), and cleans up.
 *
 * @category Library
 * @package  Libraries\Storage\Strategies
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class MinioConcurrentZip
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
     * Build the MinIO zip concurrently and write directly to the securelink PVC.
     *
     * @param array<int, array{path_file: string, zip_path: string}> $sourceFiles        Files to include
     * @param string                                                 $securelinkDestPath Final absolute path on Securelink PVC
     * @param MinioDriver                                            $minioDriver        Resolved MinIO Driver instance
     *
     * @throws RuntimeException when the zip cannot be built or files cannot be downloaded
     *
     * @return void
     */
    public function build(
        array $sourceFiles,
        string $securelinkDestPath,
        MinioDriver $minioDriver
    ): void {
        $tempDir = $this->logResolver->track('minio_concurrent_setup', function (): string {
            $dir = sys_get_temp_dir() . '/minio_zip_' . uniqid('', true);
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Failed to create temp directory on pod for MinIO ZIP');
            }
            return $dir;
        });

        $cleanup = static function (string $directory): void {
            if (!is_dir($directory)) {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    @rmdir($fileInfo->getPathname());
                    continue;
                }
                @unlink($fileInfo->getPathname());
            }
            @rmdir($directory);
        };

        try {
            // 1. Map Source Files to specific local pod temp paths
            $targetMap = [];
            $zipPathMapping = []; // Links the local tmp file to its desired path inside the ZIP

            foreach ($sourceFiles as $item) {
                $localPath = $tempDir . '/' . uniqid('minio_', true) . '.tmp';
                $targetMap[$item['path_file']] = $localPath;
                $zipPathMapping[$localPath] = $item['zip_path'];
            }

            // 2. Fetch all files concurrently
            $this->logResolver->track('minio_concurrent_download', function () use ($minioDriver, $targetMap, $tempDir): void {
                $strategy = env('MINIO_CONCURRENT_STRATEGY', MinioDriver::STRATEGY_COMMAND_POOL);
                $concurrency = (int) env('MINIO_CONCURRENCY', 5);

                $minioDriver->readMinioConcurrent($targetMap, $tempDir, $strategy, $concurrency);
            });

            // 3. Register directly to Securelink PVC with zero-compression
            $this->logResolver->track('minio_concurrent_zip_registration', function () use ($securelinkDestPath, $zipPathMapping): void {
                $destDir = dirname($securelinkDestPath);
                if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                    throw new RuntimeException("Failed to create securelink destination directory: {$destDir}");
                }

                $zip = new ZipArchive();
                if ($zip->open($securelinkDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException("Failed to open ZIP directly at destination: {$securelinkDestPath}");
                }

                try {
                    foreach ($zipPathMapping as $localPath => $zipPath) {
                        if (!is_file($localPath)) {
                            throw new RuntimeException("Downloaded MinIO file is missing: {$localPath}");
                        }

                        if (!$zip->addFile($localPath, $zipPath)) {
                            throw new RuntimeException("Failed to add MinIO file to zip: {$localPath}");
                        }

                        // Protect CPU by disabling Deflate compression
                        $zip->setCompressionName($zipPath, ZipArchive::CM_STORE);
                    }
                } catch (Throwable $exception) {
                    $zip->close();
                    throw $exception;
                }

                if (!$zip->close()) {
                    throw new RuntimeException('Failed to close and stream ZIP directly to NFS destination');
                }
            });

        } finally {
            // 4. Strict ephemeral cleanup
            $this->logResolver->track('minio_concurrent_cleanup', function () use ($cleanup, $tempDir): void {
                $cleanup($tempDir);
            });
        }
    }
}