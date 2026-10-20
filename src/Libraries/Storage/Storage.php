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
use Illuminate\Support\Str;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\MinioDriver;
use Spotlibs\PhpLib\Libraries\Storage\Drivers\NfsDriver;
use Spotlibs\PhpLib\Libraries\Storage\Strategies\MinioConcurrentZip;
use Spotlibs\PhpLib\Libraries\Storage\Strategies\NfsDirectZip;
use Spotlibs\PhpLib\Libraries\Storage\Strategies\NfsOriginalZip;

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
     * Select the destination driver for a cross-driver copy/move (ignored by buildZip/buildZipFolder, which always write to the NFS securelink folder).
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
                foreach ($this->getAutoDetectDriverNames($filepath) as $driverName) {
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
                foreach ($this->getAutoDetectDriverNames($filepath) as $driverName) {
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

            foreach ($this->getAutoDetectDriverNames($filepath) as $driverName) {
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
            $pathDriverName = $this->getPathDriverName($dirpath);
            $resolvedDriverName = $this->pendingDriver !== null
                ? $resolver->resolveExplicit($this->pendingDriver)
                : ($pathDriverName ?? $resolver->resolveDefault());

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
                foreach ($this->getAutoDetectDriverNames($filepath) as $driverName) {
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

    /**
     * Build a zip archive from a list of files and write it to the NFS securelink folder.
     *
     * The zip is always written under {@see NfsDriver::SECURELINK_DIR} inside a random
     * sub-directory, regardless of the source drivers or any chained ->toDriver(), so the
     * returned StorageResult::$securelink is directly downloadable without a further
     * securelink() call. Only the file name of $destPath is used.
     *
     * Routes execution dynamically between ORIGINAL and OPTIMIZED strategies based on
     * the ZIP_STRATEGY environment variable.
     *
     * @param array<int, array{path_file: string, zip_path: string, driver?: string|null}> $sourceFiles files to include
     * @param string                                                                        $destPath    desired zip file name (directory part is ignored)
     *
     * @throws RuntimeException when the input is invalid or the zip cannot be built/written
     *
     * @return StorageResult zip result, with the public URL in $securelink
     */
    public function buildZip(array $sourceFiles, string $destPath): StorageResult
    {
        $resetState = function (): void {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;
        };

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

            $zipName = basename($destPath);
            if ($zipName === '' || $zipName === '.' || $zipName === '..') {
                throw new RuntimeException('buildZip requires a valid zip file name');
            }

            $randomDir = Str::random(40);
            $securelinkDestPath = rtrim(NfsDriver::SECURELINK_DIR, '/') . "/{$randomDir}/{$zipName}";

            $logResolver = new LogResolver();
            $strategy = strtoupper((string) env('ZIP_STRATEGY', 'ORIGINAL'));

            if ($strategy === 'OPTIMIZED') {
                $finalResult = $this->buildZipOptimized($sourceFiles, $securelinkDestPath, $zipName, $logResolver);
            } else {
                $finalResult = $this->buildZipOriginal($sourceFiles, $securelinkDestPath, $zipName, $logResolver);
            }

            $finalResult->securelink = env('APP_URL') . "/securelink/{$randomDir}/{$zipName}";

            return $finalResult;
        } finally {
            $resetState();
        }
    }

    /**
     * Build a zip archive from an entire folder on a single source driver,
     * preserving the folder's relative subfolder structure inside the archive,
     * and write the result to the NFS securelink folder (see buildZip()).
     *
     * @param string $sourceFolder full source folder path including prefix
     * @param string $destPath     desired zip file name (directory part is ignored; zip is written to the NFS securelink folder)
     *
     * @throws RuntimeException when the folder cannot be zipped or uploaded
     *
     * @return StorageResult zip result, with the public URL in $securelink
     */
    public function buildZipFolder(string $sourceFolder, string $destPath): StorageResult
    {
        try {
            $resolver = new DriverResolver();
            if ($this->pendingFromDriver !== null) {
                $resolvedSourceDriverName = $resolver->resolveExplicit($this->pendingFromDriver);
            } else {
                $resolvedSourceDriverName = $this->getPathDriverName($sourceFolder)
                    ?? $resolver->resolveDefault();
            }
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

            return $this->buildZip($sourceFiles, $destPath);
        } finally {
            $this->pendingDriver = null;
            $this->pendingAutoDetect = false;
            $this->pendingFromDriver = null;
            $this->pendingToDriver = null;
        }
    }

    /**
     * Execute the original sequential zip build strategy (backward compatible).
     *
     * @param array<int, array{path_file: string, zip_path: string, driver?: string|null}> $sourceFiles
     * @param string                                                                       $securelinkDestPath
     * @param string                                                                       $zipName
     * @param LogResolver                                                                  $logResolver
     *
     * @throws RuntimeException
     *
     * @return StorageResult
     */
    private function buildZipOriginal(
        array $sourceFiles,
        string $securelinkDestPath,
        string $zipName,
        LogResolver $logResolver
    ): StorageResult {
        $resolver = new DriverResolver();
        $originalStrategy = new NfsOriginalZip($logResolver);

        $streamReader = function (string $pathFile, ?string $driverName) use ($resolver) {
            if ($driverName !== null) {
                $sourceDriver = $this->makeDriver($resolver->resolveExplicit($driverName));
            } else {
                $sourceDriver = null;
                foreach ($this->getAutoDetectDriverNames($pathFile) as $name) {
                    $candidateDriver = $this->makeDriver($name);
                    if ($candidateDriver->exists($pathFile)) {
                        $sourceDriver = $candidateDriver;
                        break;
                    }
                }

                if ($sourceDriver === null) {
                    throw new RuntimeException("File not found: {$pathFile}");
                }
            }

            return $sourceDriver->readStream($pathFile);
        };

        $streamWriter = function ($archiveStream, string $destPath): StorageResult {
            return $this->makeDriver(self::NFS)->writeStream($archiveStream, $destPath);
        };

        $finalResult = $originalStrategy->build(
            $sourceFiles,
            $securelinkDestPath,
            $streamReader,
            $streamWriter
        );

        $logResolver->logAggregate('ORIGINAL_ZIP_COMPLETED', ['zip_name' => $zipName]);

        return $finalResult;
    }

    /**
     * Execute the high-performance optimized zip strategy (Zero-copy NFS, concurrent MinIO, CM_STORE)[cite: 4].
     *
     * @param array<int, array{path_file: string, zip_path: string, driver?: string|null}> $sourceFiles
     * @param string                                                                       $securelinkDestPath
     * @param string                                                                       $zipName
     * @param LogResolver                                                                  $logResolver
     *
     * @throws RuntimeException
     *
     * @return StorageResult
     */
    private function buildZipOptimized(
        array $sourceFiles,
        string $securelinkDestPath,
        string $zipName,
        LogResolver $logResolver
    ): StorageResult {
        $resolver = new DriverResolver();
        $nfsFiles = [];
        $minioGroups = []; // [driverName => [files]]

        // 1. Resolve drivers and partition files into NFS vs MinIO
        foreach ($sourceFiles as $item) {
            $pathFile = $item['path_file'];

            if (isset($item['driver'])) {
                $driverName = $resolver->resolveExplicit($item['driver']);
            } else {
                $driverName = null;
                foreach ($this->getAutoDetectDriverNames($pathFile) as $candidateName) {
                    $candidateDriver = $this->makeDriver($candidateName);
                    if ($candidateDriver->exists($pathFile)) {
                        $driverName = $candidateName;
                        break;
                    }
                }

                if ($driverName === null) {
                    throw new RuntimeException("File not found: {$pathFile}");
                }
            }

            if ($driverName === self::NFS) {
                $nfsFiles[] = $item;
            } else {
                $minioGroups[$driverName][] = $item;
            }
        }

        // Case A: Pure MinIO (Single MinIO Driver)
        if ($nfsFiles === [] && count($minioGroups) === 1) {
            $driverName = (string) array_key_first($minioGroups);
            /** @var MinioDriver $minioDriver */
            $minioDriver = $this->makeDriver($driverName);

            $minioConcurrent = new MinioConcurrentZip($logResolver);
            $minioConcurrent->build($minioGroups[$driverName], $securelinkDestPath, $minioDriver);

            $logResolver->logAggregate('OPTIMIZED_ZIP_COMPLETED', ['zip_name' => $zipName]);

            return $this->createDirectStorageResult($securelinkDestPath, $zipName);
        }

        // Case B: Pure NFS or Mixed (NFS + MinIO)
        $nfsDirect = new NfsDirectZip($logResolver);

        if ($nfsFiles !== []) {
            $nfsDirect->validateNfsMountHealth($nfsFiles);
            $nfsDirect->checkNfsPayloadSize($nfsFiles);
        }

        $minioTempDir = null;
        $minioDownloads = []; // [localTempPath => zipPath]

        try {
            // Concurrent ingest for any MinIO files in the batch
            if ($minioGroups !== []) {
                $minioTempDir = $logResolver->track('minio_mixed_setup', function (): string {
                    $dir = sys_get_temp_dir() . '/minio_mixed_' . uniqid('', true);
                    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                        throw new RuntimeException('Failed to create temp directory for MinIO downloads');
                    }
                    return $dir;
                });

                foreach ($minioGroups as $driverName => $files) {
                    /** @var MinioDriver $driver */
                    $driver = $this->makeDriver($driverName);
                    $targetMap = [];

                    foreach ($files as $file) {
                        $localPath = $minioTempDir . '/' . uniqid('minio_', true) . '.tmp';
                        $targetMap[$file['path_file']] = $localPath;
                        $minioDownloads[$localPath] = $file['zip_path'];
                    }

                    $logResolver->track('minio_concurrent_download', function () use ($driver, $targetMap, $minioTempDir): void {
                        $strategy = env('MINIO_CONCURRENT_STRATEGY', MinioDriver::STRATEGY_COMMAND_POOL);
                        $concurrency = (int) env('MINIO_CONCURRENCY', 5);
                        $driver->readMinioConcurrent($targetMap, $minioTempDir, $strategy, $concurrency);
                    });
                }
            }

            // Register directly on Securelink PVC using CM_STORE[cite: 4]
            if ($nfsFiles !== []) {
                $zip = $nfsDirect->buildDirectNfsZip($securelinkDestPath, $nfsFiles);
            } else {
                $destDir = dirname($securelinkDestPath);
                if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                    throw new RuntimeException("Failed to create securelink destination directory: {$destDir}");
                }
                $zip = new ZipArchive();
                if ($zip->open($securelinkDestPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException("Failed to open ZIP directly at destination: {$securelinkDestPath}");
                }
            }

            if ($minioDownloads !== []) {
                $logResolver->track('minio_zip_registration', function () use ($zip, $minioDownloads): void {
                    foreach ($minioDownloads as $localPath => $zipPath) {
                        if (!is_file($localPath)) {
                            throw new RuntimeException("Downloaded MinIO file is missing: {$localPath}");
                        }
                        if (!$zip->addFile($localPath, $zipPath)) {
                            throw new RuntimeException("Failed to add MinIO file to zip: {$localPath}");
                        }
                        $zip->setCompressionName($zipPath, ZipArchive::CM_STORE);
                    }
                });
            }

            $logResolver->track('zip_close', function () use ($zip): void {
                if (!$zip->close()) {
                    throw new RuntimeException('Failed to close and stream ZIP directly to destination');
                }
            });
        } finally {
            if ($minioTempDir !== null && is_dir($minioTempDir)) {
                $logResolver->track('minio_mixed_cleanup', function () use ($minioTempDir): void {
                    $files = (array) glob($minioTempDir . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            @unlink($file);
                        }
                    }
                    @rmdir($minioTempDir);
                });
            }
        }

        $logResolver->logAggregate('OPTIMIZED_ZIP_COMPLETED', ['zip_name' => $zipName]);

        return $this->createDirectStorageResult($securelinkDestPath, $zipName);
    }

    /**
     * Build the StorageResult object for a directly generated ZIP archive.
     *
     * @param string $securelinkDestPath
     * @param string $zipName
     *
     * @return StorageResult
     */
    private function createDirectStorageResult(string $securelinkDestPath, string $zipName): StorageResult
    {
        $result = new StorageResult();
        $result->driver = self::NFS;
        $result->pathFile = $zipName;
        $result->fullPath = $securelinkDestPath;
        $result->folder = dirname($securelinkDestPath) . '/';

        if (is_file($securelinkDestPath)) {
            $result->size = filesize($securelinkDestPath);
        }

        return $result;
    }

    /**
     * Detect a storage driver marker in a path.
     *
     * The earliest case-insensitive marker wins. If MINIO_BRIMEN and MINIO
     * start at the same position, the more specific MINIO_BRIMEN marker wins.
     *
     * @param string $path full path, file path, or folder path
     *
     * @return string|null detected driver name, or null when no marker exists
     */
    private function getPathDriverName(string $path): ?string
    {
        $driverPositions = [
            self::NFS => stripos($path, self::NFS),
            self::MINIO_BRIMEN => stripos($path, self::MINIO_BRIMEN),
            self::MINIO => stripos($path, self::MINIO),
        ];
        $detectedDriver = null;
        $detectedPosition = PHP_INT_MAX;
        $detectedLength = 0;

        foreach ($driverPositions as $driverName => $position) {
            if ($position === false) {
                continue;
            }

            $driverLength = strlen($driverName);
            if ($position < $detectedPosition
                || ($position === $detectedPosition && $driverLength > $detectedLength)
            ) {
                $detectedDriver = $driverName;
                $detectedPosition = $position;
                $detectedLength = $driverLength;
            }
        }

        return $detectedDriver;
    }

    /**
     * Resolve the autodetect query order from a storage path.
     *
     * A path marker is checked before querying storage so a known storage path
     * only performs one remote or filesystem existence check.
     *
     * @param string $path full path, file path, or folder path
     *
     * @return array<int, string> driver names ordered for autodetection
     */
    private function getAutoDetectDriverNames(string $path): array
    {
        $detectedDriver = $this->getPathDriverName($path);
        if ($detectedDriver !== null) {
            return [$detectedDriver];
        }

        return [self::MINIO, self::NFS, self::MINIO_BRIMEN];
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
