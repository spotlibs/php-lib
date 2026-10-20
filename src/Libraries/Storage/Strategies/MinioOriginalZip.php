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
use Spotlibs\PhpLib\Libraries\Storage\LogResolver;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;
use Throwable;
use ZipArchive;

/**
 * MinioOriginalZip
 *
 * The original sequential zip building strategy for MinIO. Copies all MinIO files
 * one-by-one to a local pod temporary directory, builds the archive locally with
 * CPU compression, streams it to the destination, and cleans up the pod storage.
 *
 * @category Library
 * @package  Libraries\Storage\Strategies
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class MinioOriginalZip
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
     * Build the zip using the original sequential MinIO streaming mechanism.
     *
     * @param array<int, array{path_file: string, zip_path: string, driver?: string|null}> $sourceFiles        MinIO files to include
     * @param string                                                                       $securelinkDestPath Target NFS destination path
     * @param callable                                                                     $streamReader       Callback to resolve and return a file stream: fn(string $pathFile, ?string $driverName): resource
     * @param callable                                                                     $streamWriter       Callback to upload the final zip: fn(resource $archiveStream, string $destPath): StorageResult
     *
     * @throws RuntimeException when the zip cannot be built or uploaded
     *
     * @return StorageResult zip result
     */
    public function build(
        array $sourceFiles,
        string $securelinkDestPath,
        callable $streamReader,
        callable $streamWriter
    ): StorageResult {
        $tempDir = $this->logResolver->track('minio_original_zip_setup', function (): string {
            $dir = sys_get_temp_dir() . '/minio_zip_' . uniqid('', true);
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Failed to create temp directory on pod for MinIO ZIP');
            }
            return $dir;
        });

        $zip = null;
        $zipOpened = false;
        $finalResult = null;

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
            $zip = new ZipArchive();

            $this->logResolver->track('minio_original_zip_open', function () use ($zip, $tempDir, &$zipOpened): void {
                if ($zip->open($tempDir . '/final.zip', ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('Failed to create MinIO zip on pod');
                }
                $zipOpened = true;
            });

            $this->logResolver->track('minio_original_stream_to_pod', function () use ($sourceFiles, $tempDir, $zip, $streamReader): void {
                foreach ($sourceFiles as $item) {
                    $pathFile = $item['path_file'];
                    $driver = $item['driver'] ?? null;
                    $stream = null;

                    try {
                        // Downloads the MinIO file sequentially into pod memory
                        $stream = $streamReader($pathFile, $driver);

                        if (!$stream) {
                            throw new RuntimeException("Failed to open MinIO read stream for: {$pathFile}");
                        }

                        $temporaryFile = $tempDir . '/' . uniqid('minio_', true);
                        if (file_put_contents($temporaryFile, $stream) === false) {
                            throw new RuntimeException("Failed to write MinIO stream to pod: {$pathFile}");
                        }

                        if (is_resource($stream)) {
                            fclose($stream);
                            $stream = null;
                        }

                        if (!$zip->addFile($temporaryFile, $item['zip_path'])) {
                            throw new RuntimeException("Failed to add MinIO file to zip: {$pathFile}");
                        }
                    } catch (Throwable $exception) {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        throw new RuntimeException(
                            "MinIO buildZip failed to stream {$pathFile}: {$exception->getMessage()}"
                        );
                    }
                }
            });

            $this->logResolver->track('minio_original_zip_close_and_compress', function () use ($zip, &$zipOpened): void {
                // Triggers heavy CPU Deflate compression on the pod
                if (!$zip->close()) {
                    $zipOpened = false;
                    throw new RuntimeException('Failed to close and compress MinIO zip on pod');
                }
                $zipOpened = false;
            });

            $finalResult = $this->logResolver->track('minio_original_upload_to_nfs', function () use ($tempDir, $streamWriter, $securelinkDestPath): StorageResult {
                $archiveStream = fopen($tempDir . '/final.zip', 'r');
                if ($archiveStream === false) {
                    throw new RuntimeException('Failed to open MinIO zip for upload');
                }

                try {
                    return $streamWriter($archiveStream, $securelinkDestPath);
                } finally {
                    fclose($archiveStream);
                }
            });
        } finally {
            $this->logResolver->track('minio_original_cleanup', function () use ($zipOpened, $zip, $cleanup, $tempDir): void {
                if ($zipOpened && $zip !== null) {
                    $zip->close();
                }

                $cleanup($tempDir);
            });
        }

        return $finalResult;
    }
}