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

use Illuminate\Http\UploadedFile;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\MinioDriver;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\NfsDriver;

/**
 * Storage
 *
 * Storage driver constants.
 *
 * @category Library
 * @package  Libraries\Storage
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class Storage
{
    public const NFS = 'NFS';
    public const MINIO = 'MINIO';
    public const MINIO_BRIMEN = 'MINIO_BRIMEN';

    private ?string $pendingDriver = null;
    private bool $pendingAutoDetect = false;
    private ?string $pendingFromDriver = null;
    private ?string $pendingToDriver = null;

    /**
     * Explicitly select a driver for the current operation.
     *
     * @param string $driver one of Storage::NFS, Storage::MINIO, Storage::MINIO_BRIMEN
     *
     * @return static
     */
    public function driver(string $driver): static
    {
        $this->pendingDriver = $driver;

        return $this;
    }

    /**
     * Query all drivers to detect which one has the file. Read-only operations only.
     *
     * @return static
     */
    public function autoDetect(): static
    {
        $this->pendingAutoDetect = true;

        return $this;
    }

    /**
     * Select the source driver for a cross-driver copy/move.
     *
     * @param string $driver one of Storage::NFS, Storage::MINIO, Storage::MINIO_BRIMEN
     *
     * @return static
     */
    public function fromDriver(string $driver): static
    {
        $this->pendingFromDriver = $driver;

        return $this;
    }

    /**
     * Select the destination driver for a cross-driver copy/move (or the buildZip target driver).
     *
     * @param string $driver one of Storage::NFS, Storage::MINIO, Storage::MINIO_BRIMEN
     *
     * @return static
     */
    public function toDriver(string $driver): static
    {
        $this->pendingToDriver = $driver;

        return $this;
    }

    /**
     * Open a readable stream for a file on the resolved driver.
     *
     * @param string $filepath full path including prefix and filename
     *
     * @throws RuntimeException when the file cannot be read
     *
     * @return resource readable file stream
     */
    public function readStream(string $filepath)
    {
        try {
            $resolver = new DriverResolver();

            if ($this->pendingAutoDetect) {
                foreach ([self::MINIO, self::MINIO_BRIMEN, self::NFS] as $driverName) {
                    $driver = $this->makeDriver($driverName);
                    if ($driver->exists($filepath)) {
                        return $driver->readStream($filepath);
                    }
                }

                throw new RuntimeException("File not found: {$filepath}");
            }

            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : $resolver->resolveDefault();

            return $this->makeDriver($resolvedDriverName)->readStream($filepath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Check if a file exists on the resolved driver.
     *
     * @param string $filepath full path including prefix and filename
     *
     * @return bool
     */
    public function exists(string $filepath): bool
    {
        try {
            if ($this->pendingAutoDetect) {
                foreach ([self::MINIO, self::MINIO_BRIMEN, self::NFS] as $driverName) {
                    if ($this->makeDriver($driverName)->exists($filepath)) {
                        return true;
                    }
                }

                return false;
            }

            $resolver = new DriverResolver();
            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : $resolver->resolveDefault();

            return $this->makeDriver($resolvedDriverName)->exists($filepath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Upload a file to the resolved driver.
     *
     * @param UploadedFile $file     file from http request
     * @param string       $dirpath  full destination path including prefix
     * @param string       $filename optionally override file name
     *
     * @throws RuntimeException
     *
     * @return StorageResult
     */
    public function upload(UploadedFile $file, string $dirpath, string $filename = ''): StorageResult
    {
        try {
            if ($this->pendingAutoDetect) {
                throw new RuntimeException(
                    'autoDetect is not allowed for upload'
                );
            }

            $resolver = new DriverResolver();
            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : $resolver->resolveDefault();

            return $this->makeDriver($resolvedDriverName)->upload($file, $dirpath, $filename);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Delete a file on the resolved driver.
     *
     * @param string $filepath full path including prefix and filename
     *
     * @throws RuntimeException when the deletion fails
     *
     * @return void
     */
    public function delete(string $filepath): void
    {
        if (env('ALLOW_DELETE_STORAGE_SPOTLIB', false) == false) {
            throw new RuntimeException('disallow action');
        }

        try {
            if ($this->pendingAutoDetect) {
                throw new RuntimeException('autoDetect is not allowed for delete');
            }

            if ($this->pendingDriver === null) {
                throw new RuntimeException('delete requires explicit driver. Call ->driver()');
            }

            $resolver = new DriverResolver();
            $resolvedDriverName = $resolver->resolveExplicit($this->pendingDriver);

            $this->makeDriver($resolvedDriverName)->delete($filepath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Get information about a file, automatically detecting the driver if not explicitly chained.
     *
     * @param string $filepath full path including prefix and filename
     *
     * @throws RuntimeException when the file is not found
     *
     * @return StorageResult
     */
    public function info(string $filepath): StorageResult
    {
        try {
            if ($this->pendingDriver !== null) {
                $resolver = new DriverResolver();
                $resolvedDriverName = $resolver->resolveExplicit($this->pendingDriver);
                
                return $this->makeDriver($resolvedDriverName)->info($filepath);
            }

            foreach ([self::MINIO, self::MINIO_BRIMEN, self::NFS] as $driverName) {
                $driver = $this->makeDriver($driverName);
                if ($driver->exists($filepath)) {
                    return $driver->info($filepath);
                }
            }

            throw new RuntimeException("File not found: {$filepath}");
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Get an array of all files in a directory on the resolved driver.
     *
     * @param string $dirpath directory path
     *
     * @throws RuntimeException when autoDetect is used
     *
     * @return array
     */
    public function allFiles(string $dirpath): array
    {
        try {
            if ($this->pendingAutoDetect) {
                throw new RuntimeException(
                    'autoDetect is not allowed for allFiles'
                );
            }

            $resolver = new DriverResolver();
            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : $resolver->resolveDefault();

            return $this->makeDriver($resolvedDriverName)->allFiles($dirpath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Generate a temporary URL for a file on the resolved driver.
     *
     * @param string   $filepath full path including prefix and filename
     * @param int|null $ttl      TTL in seconds, null = no expiry
     *
     * @throws RuntimeException when the secure link cannot be created
     *
     * @return string secure link URL
     */
    public function securelink(string $filepath, ?int $ttl = null): string
    {
        try {
            $resolver = new DriverResolver();

            if ($this->pendingAutoDetect) {
                foreach ([self::MINIO, self::MINIO_BRIMEN, self::NFS] as $driverName) {
                    $driver = $this->makeDriver($driverName);
                    if ($driver->exists($filepath)) {
                        return $driver->securelink($filepath, $ttl);
                    }
                }

                throw new RuntimeException("File not found: {$filepath}");
            }

            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : $resolver->resolveDefault();

            return $this->makeDriver($resolvedDriverName)->securelink($filepath, $ttl);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
        }
    }

    /**
     * Copy a file, same-driver or cross-driver.
     *
     * @param string $srcPath  full source path including prefix and filename
     * @param string $destPath full destination path including prefix and filename
     *
     * @throws RuntimeException when the copy cannot be completed
     *
     * @return StorageResult copy result
     */
    public function copy(string $srcPath, string $destPath): StorageResult
    {
        if ($this->pendingDriver !== null
            && ($this->pendingFromDriver !== null || $this->pendingToDriver !== null)
        ) {
            throw new RuntimeException(
                'copy() cannot mix ->driver() with ->fromDriver()/->toDriver()'
            );
        }

        if ($this->pendingDriver === null
            && $this->pendingFromDriver === null
            && $this->pendingToDriver === null
        ) {
            throw new RuntimeException(
                'copy/move requires explicit driver. Call ->driver() or ->fromDriver() + ->toDriver()'
            );
        }

        if (($this->pendingFromDriver === null) xor ($this->pendingToDriver === null)) {
            throw new RuntimeException(
                'copy() cross-driver requires both ->fromDriver() and ->toDriver()'
            );
        }

        $resolver = new DriverResolver();

        if ($this->pendingDriver !== null) {
            $resolvedDriverName = $resolver->resolveExplicit($this->pendingDriver);
            $result = $this->makeDriver($resolvedDriverName)->copySameDriver($srcPath, $destPath);
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;

            return $result;
        }

        $resolvedFromDriver = $resolver->resolveExplicit($this->pendingFromDriver);
        $resolvedToDriver = $resolver->resolveExplicit($this->pendingToDriver);
        $stream = $this->makeDriver($resolvedFromDriver)->readStream($srcPath);
        $result = $this->makeDriver($resolvedToDriver)->writeStream($stream, $destPath);
        $this->pendingDriver = null;
        $this->pendingAutoDetect = false;
        $this->pendingFromDriver = null;
        $this->pendingToDriver = null;

        return $result;
    }

    /**
     * Move a file, same-driver or cross-driver.
     *
     * @param string $srcPath  full source path including prefix and filename
     * @param string $destPath full destination path including prefix and filename
     *
     * @throws RuntimeException when the move cannot be completed
     *
     * @return StorageResult move result
     */
    public function move(string $srcPath, string $destPath): StorageResult
    {
        if ($this->pendingDriver !== null
            && ($this->pendingFromDriver !== null || $this->pendingToDriver !== null)
        ) {
            throw new RuntimeException(
                'copy() cannot mix ->driver() with ->fromDriver()/->toDriver()'
            );
        }

        if ($this->pendingDriver === null
            && $this->pendingFromDriver === null
            && $this->pendingToDriver === null
        ) {
            throw new RuntimeException(
                'copy/move requires explicit driver. Call ->driver() or ->fromDriver() + ->toDriver()'
            );
        }

        if (($this->pendingFromDriver === null) xor ($this->pendingToDriver === null)) {
            throw new RuntimeException(
                'copy() cross-driver requires both ->fromDriver() and ->toDriver()'
            );
        }

        $resolver = new DriverResolver();

        if ($this->pendingDriver !== null) {
            $resolvedDriverName = $resolver->resolveExplicit($this->pendingDriver);
            $result = $this->makeDriver($resolvedDriverName)->moveSameDriver($srcPath, $destPath);
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;

            return $result;
        }

        $fromDriver = $resolver->resolveExplicit($this->pendingFromDriver);
        $sourceDriver = $this->makeDriver($fromDriver);
        $result = $this->copy($srcPath, $destPath);

        try {
            $sourceDriver->delete($srcPath);
        } catch (\Exception $exception) {
            throw new RuntimeException(
                "move() copy succeeded but delete of source failed: {$exception->getMessage()}"
            );
        }

        $this->pendingDriver = null;
        $this->pendingAutoDetect = false;
        $this->pendingFromDriver = null;
        $this->pendingToDriver = null;

        return $result;
    }

    public function buildZip(array $sourceFiles, string $destPath): StorageResult
    {
        $resetState = function (): void {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;
        };

        $finalResult = null;

        try {
            if ($sourceFiles === []) {
                throw new RuntimeException('buildZip requires at least one source file');
            }

            $zipPaths = [];
            foreach ($sourceFiles as $item) {
                $zipPath = $item['zip_path'];
                if (isset($zipPaths[$zipPath])) {
                    throw new RuntimeException("buildZip has duplicate zip_path: {$zipPath}");
                }

                $zipPaths[$zipPath] = true;
            }

            $resolver = new DriverResolver();
            $resolvedDestinationName = $this->pendingToDriver !== null
                ? $resolver->resolveExplicit($this->pendingToDriver)
                : $resolver->resolveDefault();
            $destinationDriver = $this->makeDriver($resolvedDestinationName);

            if ($destinationDriver->exists($destPath)) {
                $result = new StorageResult();
                $result->driver = $resolvedDestinationName;
                $result->pathFile = basename($destPath);
                $result->fullPath = $destPath;
                $result->folder = dirname($destPath) . '/';

                $finalResult = $result;
            } else {
                $tempDir = sys_get_temp_dir() . '/zip_' . uniqid('', true);
                $zip = null;
                $zipOpened = false;

                $cleanup = static function (string $directory): void {
                    if (!is_dir($directory)) {
                        return;
                    }

                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
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
                    if (!mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
                        throw new RuntimeException('Failed to create zip on pod');
                    }

                    $zip = new \ZipArchive();
                    if ($zip->open($tempDir . '/final.zip', \ZipArchive::CREATE) !== true) {
                        throw new RuntimeException('Failed to create zip on pod');
                    }
                    $zipOpened = true;

                    foreach ($sourceFiles as $item) {
                        $pathFile = $item['path_file'];
                        $stream = null;

                        try {
                            if (isset($item['driver'])) {
                                $sourceDriver = $this->makeDriver(
                                    $resolver->resolveExplicit($item['driver'])
                                );
                            } else {
                                $sourceDriver = null;
                                foreach ([self::MINIO, self::MINIO_BRIMEN, self::NFS] as $driverName) {
                                    $candidateDriver = $this->makeDriver($driverName);
                                    if ($candidateDriver->exists($pathFile)) {
                                        $sourceDriver = $candidateDriver;
                                        break;
                                    }
                                }

                                if ($sourceDriver === null) {
                                    throw new RuntimeException("File not found: {$pathFile}");
                                }
                            }

                            $stream = $sourceDriver->readStream($pathFile);
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
                        } catch (\Throwable $exception) {
                            if (is_resource($stream)) {
                                fclose($stream);
                            }

                            throw new RuntimeException(
                                "buildZip failed to stream {$pathFile}: {$exception->getMessage()}"
                            );
                        }
                    }

                    if (!$zip->close()) {
                        $zipOpened = false;
                        throw new RuntimeException('Failed to create zip on pod');
                    }
                    $zipOpened = false;

                    $archiveStream = fopen($tempDir . '/final.zip', 'r');
                    if ($archiveStream === false) {
                        throw new RuntimeException('Failed to open zip for upload');
                    }

                    try {
                        $finalResult = $destinationDriver->writeStream($archiveStream, $destPath);
                    } finally {
                        fclose($archiveStream);
                    }
                } finally {
                    if ($zipOpened && $zip !== null) {
                        $zip->close();
                    }

                    $cleanup($tempDir);
                }
            }
        } finally {
            $resetState();
        }

        return $finalResult;
    }

    /**
     * Build a zip archive from an entire folder on a single source driver,
     * preserving the folder's relative subfolder structure inside the archive,
     * and upload the result to a single destination.
     *
     * @param string $sourceFolder full source folder path including prefix
     * @param string $destPath     full destination path for the resulting zip, including prefix and filename
     *
     * @throws RuntimeException when the folder cannot be zipped or uploaded
     *
     * @return StorageResult zip result
     */
    public function buildZipFolder(string $sourceFolder, string $destPath): StorageResult
    {
        try {
            $resolver = new DriverResolver();
            $resolvedSourceDriverName = $this->pendingFromDriver !== null
                ? $resolver->resolveExplicit($this->pendingFromDriver)
                : $resolver->resolveDefault();
            $sourceDriver = $this->makeDriver($resolvedSourceDriverName);
            $files = $sourceDriver->allFiles($sourceFolder);

            if ($files === []) {
                throw new RuntimeException("buildZipFolder found no files in {$sourceFolder}");
            }

            $sourcePrefix = rtrim($sourceFolder, '/') . '/';
            $sourceFiles = [];
            foreach ($files as $file) {
                $filePath = $file['path'];
                $zipPath = str_starts_with($filePath, $sourcePrefix)
                    ? substr($filePath, strlen($sourcePrefix))
                    : ltrim(substr($filePath, strlen(rtrim($sourceFolder, '/'))), '/');
                $sourceFiles[] = [
                    'path_file' => $filePath,
                    'driver' => $resolvedSourceDriverName,
                    'zip_path' => $zipPath,
                ];
            }

            if ($this->pendingToDriver !== null) {
                $this->toDriver($this->pendingToDriver);
            }

            return $this->buildZip($sourceFiles, $destPath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;
        }
    }

    /**
     * Instantiate the concrete driver for a given driver name.
     *
     * @param string $driverName one of Storage::NFS, Storage::MINIO, Storage::MINIO_BRIMEN
     *
     * @throws RuntimeException when the driver has no concrete implementation yet
     *
     * @return StorageDriverInterface
     */
    private function makeDriver(string $driverName): StorageDriverInterface
    {
        switch ($driverName) {
            case self::NFS:
                return new NfsDriver();
            case self::MINIO:
                return new MinioDriver('minio');
            case self::MINIO_BRIMEN:
                return new MinioDriver('minio_brimen');
            default:
                throw new RuntimeException("Unknown storage driver: {$driverName}");
        }
    }
}
