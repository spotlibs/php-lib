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
 * NfsOriginalZip
 *
 * The original zip building strategy. Copies all source files to a local pod
 * temporary directory, builds the archive locally, streams it to the destination,
 * and then cleans up the ephemeral storage. Includes latency tracking.
 *
 * @category Library
 * @package  Libraries\Storage\Strategies
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class NfsOriginalZip
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
     * Build the zip using the original local-pod temporary storage mechanism.
     *
     * @param array<int, array{path_file: string, zip_path: string, driver?: string|null}> $sourceFiles        Files to include
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
        $tempDir = $this->logResolver->track('original_zip_setup', function (): string {
            $dir = sys_get_temp_dir() . '/zip_' . uniqid('', true);
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Failed to create temp directory on pod');
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

            $this->logResolver->track('original_zip_open', function () use ($zip, $tempDir, &$zipOpened): void {
                if ($zip->open($tempDir . '/final.zip', ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('Failed to create zip on pod');
                }
                $zipOpened = true;
            });

            $this->logResolver->track('original_zip_stream_to_pod', function () use ($sourceFiles, $tempDir, $zip, $streamReader): void {
                foreach ($sourceFiles as $item) {
                    $pathFile = $item['path_file'];
                    $driver = $item['driver'] ?? null;
                    $stream = null;

                    try {
                        $stream = $streamReader($pathFile, $driver);

                        if (!$stream) {
                            throw new RuntimeException("Failed to open read stream for: {$pathFile}");
                        }

                        $temporaryFile = $tempDir . '/' . uniqid('', true);
                        if (file_put_contents($temporaryFile, $stream) === false) {
                            throw new RuntimeException("Failed to write stream to pod: {$pathFile}");
                        }

                        if (is_resource($stream)) {
                            fclose($stream);
                            $stream = null;
                        }

                        if (!$zip->addFile($temporaryFile, $item['zip_path'])) {
                            throw new RuntimeException("Failed to add file to zip: {$pathFile}");
                        }
                    } catch (Throwable $exception) {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        throw new RuntimeException(
                            "buildZip failed to stream {$pathFile}: {$exception->getMessage()}"
                        );
                    }
                }
            });

            $this->logResolver->track('original_zip_close_and_compress', function () use ($zip, &$zipOpened): void {
                if (!$zip->close()) {
                    $zipOpened = false;
                    throw new RuntimeException('Failed to close and compress zip on pod');
                }
                $zipOpened = false;
            });

            $finalResult = $this->logResolver->track('original_zip_upload_to_nfs', function () use ($tempDir, $streamWriter, $securelinkDestPath): StorageResult {
                $archiveStream = fopen($tempDir . '/final.zip', 'r');
                if ($archiveStream === false) {
                    throw new RuntimeException('Failed to open zip for upload');
                }

                try {
                    return $streamWriter($archiveStream, $securelinkDestPath);
                } finally {
                    fclose($archiveStream);
                }
            });
        } finally {
            $this->logResolver->track('original_zip_cleanup', function () use ($zipOpened, $zip, $cleanup, $tempDir): void {
                if ($zipOpened && $zip !== null) {
                    $zip->close();
                }

                $cleanup($tempDir);
            });
        }

        return $finalResult;
    }
}