<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Storage\Drivers;

use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage as LaravelStorage;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use Spotlibs\PhpLib\Libraries\Storage\Storage as StorageManager;
use Spotlibs\PhpLib\Libraries\Storage\StorageDriverInterface;
use Spotlibs\PhpLib\Libraries\Storage\StorageResult;

/**
 * MinioDriver
 *
 * Storage driver backed by Laravel's MinIO filesystem disk.
 *
 * @category Library
 * @package  Libraries\Storage\Drivers
 * @author   Mufthi Ryanda <mufthi.ryan@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class MinioDriver implements StorageDriverInterface
{
    protected string $disk;

    public const STRATEGY_COMMAND_POOL = 'command_pool';
    public const STRATEGY_CURL_MULTI = 'curl_multi';

    /**
     * Create a MinIO storage driver.
     *
     * @param string $disk Laravel filesystem disk name
     */
    public function __construct(string $disk = 'minio')
    {
        $this->disk = $disk;
    }

    /**
     * Upload a file to MinIO.
     *
     * @param UploadedFile $file     file to upload
     * @param string       $dirpath  destination directory path
     * @param string       $filename optionally override file name
     *
     * @throws RuntimeException when the upload fails
     *
     * @return StorageResult upload result
     */
    public function upload(UploadedFile $file, string $dirpath, string $filename = ''): StorageResult
    {
        $fileName = $filename === '' ? $file->getClientOriginalName() : $filename;
        $destPath = rtrim($dirpath, '/') . '/' . $fileName;

        if (!LaravelStorage::disk($this->disk)->put($destPath, $file->getContent())) {
            throw new RuntimeException("Failed to upload file to MinIO: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = $fileName;
        $result->fullPath = $destPath;
        $result->folder = rtrim($dirpath, '/') . '/';

        return $result;
    }

    /**
     * Write a resource stream to MinIO.
     *
     * @param resource $stream   readable stream
     * @param string   $destPath destination file path
     *
     * @throws RuntimeException when the stream cannot be written
     *
     * @return StorageResult write result
     */
    public function writeStream($stream, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->writeStream($destPath, $stream)) {
            throw new RuntimeException("Failed to write stream to MinIO: {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Copy a file within MinIO.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the copy fails
     *
     * @return StorageResult copy result
     */
    public function copySameDriver(string $srcPath, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->copy($srcPath, $destPath)) {
            throw new RuntimeException("MinIO copy failed: {$srcPath} -> {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Move a file within MinIO.
     *
     * @param string $srcPath  source file path
     * @param string $destPath destination file path
     *
     * @throws RuntimeException when the move fails
     *
     * @return StorageResult move result
     */
    public function moveSameDriver(string $srcPath, string $destPath): StorageResult
    {
        if (!LaravelStorage::disk($this->disk)->move($srcPath, $destPath)) {
            throw new RuntimeException("MinIO move failed: {$srcPath} -> {$destPath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($destPath);
        $result->fullPath = $destPath;
        $result->folder = dirname($destPath) . '/';

        return $result;
    }

    /**
     * Delete a file from MinIO.
     *
     * @param string $filepath file path to delete
     *
     * @throws RuntimeException when the deletion fails
     *
     * @return void
     */
    public function delete(string $filepath): void
    {
        $disk = LaravelStorage::disk($this->disk);

        if (str_ends_with($filepath, '/') || (method_exists($disk, 'directoryExists') && $disk->directoryExists($filepath))) {
            throw new RuntimeException("Cannot delete a folder: {$filepath}");
        }

        if (!$disk->delete($filepath)) {
            throw new RuntimeException("MinIO delete failed: {$filepath}");
        }
    }

    /**
     * Generate a temporary URL for a MinIO file.
     *
     * @param string   $filepath file path for the secure link
     * @param int|null $ttl      link lifetime in seconds
     *
     * @return string secure link URL
     */
    public function securelink(string $filepath, ?int $ttl = null): string
    {
        $ttlSeconds = $ttl ?? (int) env('MINIO_EXPIRED_URL', 60);

        return LaravelStorage::disk($this->disk)->temporaryUrl(
            $filepath,
            Carbon::now()->addSeconds($ttlSeconds)
        );
    }

    /**
     * Check whether a file exists in MinIO.
     *
     * @param string $filepath file path to check
     *
     * @return bool whether the file exists
     */
    public function exists(string $filepath): bool
    {
        return LaravelStorage::disk($this->disk)->exists($filepath);
    }

    /**
     * Open a readable stream for a MinIO file.
     *
     * @param string $filepath file path to read
     *
     * @throws RuntimeException when the stream cannot be opened
     *
     * @return resource readable file stream
     */
    public function readStream(string $filepath)
    {
        $stream = LaravelStorage::disk($this->disk)->readStream($filepath);
        if ($stream === null || $stream === false) {
            throw new RuntimeException("Failed to open read stream for: {$filepath}");
        }

        return $stream;
    }

    /**
     * List files directly within a MinIO directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function files(string $dirpath): array
    {
        return $this->listFileEntries($dirpath, false);
    }

    /**
     * List files recursively within a MinIO directory.
     *
     * @param string $dirpath directory path to list
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    public function allFiles(string $dirpath): array
    {
        return $this->listFileEntries($dirpath, true);
    }

    /**
     * List file entries through Flysystem listContents, independent of the
     * adapter's allFiles()/files() contract.
     *
     * @param string $dirpath   directory path to list
     * @param bool   $recursive whether to descend into subdirectories
     *
     * @return array<int, array{path: string, size: int|string}> file entries
     */
    private function listFileEntries(string $dirpath, bool $recursive): array
    {
        $result = [];
        foreach (LaravelStorage::disk($this->disk)->listContents($dirpath, $recursive) as $entry) {
            if (($entry['type'] ?? null) !== 'file') {
                continue;
            }

            $result[] = [
                'path' => $entry['path'],
                'size' => isset($entry['size']) ? (int) $entry['size'] : '',
            ];
        }

        return $result;
    }

    /**
     * Get information about a specific file.
     *
     * @param string $filepath file path to inspect
     *
     * @throws RuntimeException when the file is not found
     *
     * @return StorageResult
     */
    public function info(string $filepath): StorageResult
    {
        $disk = LaravelStorage::disk($this->disk);
        if (!$disk->exists($filepath)) {
            throw new RuntimeException("File not found: {$filepath}");
        }

        $result = new StorageResult();
        $result->driver = $this->disk === 'minio_brimen'
            ? StorageManager::MINIO_BRIMEN
            : StorageManager::MINIO;
        $result->pathFile = basename($filepath);
        $result->fullPath = $filepath;
        $result->folder = dirname($filepath) . '/';
        $result->size = $disk->size($filepath);

        return $result;
    }

    /**
     * Download multiple MinIO files concurrently to local disk.
     *
     * Accepts either a list of MinIO file paths (['dir/a.pdf', 'dir/b.pdf'])
     * or an explicit map of MinIO path => local destination path
     * (['dir/a.pdf' => '/tmp/zip_123/a.pdf']).
     *
     * @param array<int|string, string> $files       list of MinIO paths or map of [minioPath => localPath]
     * @param string|null               $tempDir     target local directory when $files is a list
     * @param string                    $strategy    self::STRATEGY_COMMAND_POOL or self::STRATEGY_CURL_MULTI
     * @param int                       $concurrency max parallel transfers (default 5 for 512Mi pods)
     *
     * @throws RuntimeException when any file fails to download
     *
     * @return array<string, string> map of [minioPath => localFilePath]
     */
    public function readMinioConcurrent(
        array $files,
        ?string $tempDir = null,
        string $strategy = self::STRATEGY_COMMAND_POOL,
        int $concurrency = 5
    ): array {
        if ($files === []) {
            return [];
        }

        if ($strategy === self::STRATEGY_CURL_MULTI) {
            return $this->readConcurrentCurlMulti($files, $tempDir, $concurrency);
        }

        if ($strategy === self::STRATEGY_COMMAND_POOL) {
            return $this->readConcurrentCommandPool($files, $tempDir, $concurrency);
        }

        throw new RuntimeException("Unknown concurrent read strategy: {$strategy}");
    }

    /**
     * Download multiple MinIO files concurrently using pre-signed URLs and curl_multi.
     *
     * Reuses TCP/TLS connections and DNS cache via curl_share and maintains a rolling
     * window of $concurrency open file handles to keep pod memory and fd usage flat.
     *
     * @param array<int|string, string> $files       list of MinIO paths or map of [minioPath => localPath]
     * @param string|null               $tempDir     target local directory when $files is a list
     * @param int                       $concurrency max simultaneous transfers
     * @param int|null                  $ttl         pre-signed URL lifetime in seconds
     *
     * @throws RuntimeException when any transfer fails
     *
     * @return array<string, string> map of [minioPath => localFilePath]
     */
    public function readConcurrentCurlMulti(
        array $files,
        ?string $tempDir = null,
        int $concurrency = 5,
        ?int $ttl = null
    ): array {
        if ($files === []) {
            return [];
        }

        $targetMap = $this->normalizeTargetMap($files, $tempDir);
        $ttlSeconds = $ttl ?? max((int) env('MINIO_EXPIRED_URL', 60), 300);
        $maxParallel = max(1, $concurrency);

        $queue = [];
        foreach ($targetMap as $filepath => $localPath) {
            $queue[] = [
                'filepath' => $filepath,
                'local_path' => $localPath,
                'url' => $this->securelink($filepath, $ttlSeconds),
            ];
        }

        $mh = curl_multi_init();
        curl_multi_setopt($mh, CURLMOPT_MAX_TOTAL_CONNECTIONS, $maxParallel);
        curl_multi_setopt($mh, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);

        $sh = curl_share_init();
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);

        $activeHandles = [];
        $errors = [];

        $enqueueNext = static function () use (
            &$queue,
            &$activeHandles,
            &$errors,
            $mh,
            $sh
        ): void {
            if ($queue === []) {
                return;
            }

            $item = array_shift($queue);
            $fp = @fopen($item['local_path'], 'wb');
            if ($fp === false) {
                $errors[$item['filepath']] = "Failed to open local file for writing: {$item['local_path']}";

                return;
            }

            $ch = curl_init($item['url']);
            if ($ch === false) {
                fclose($fp);
                $errors[$item['filepath']] = "Failed to initialize curl handle for: {$item['filepath']}";

                return;
            }

            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_SHARE => $sh,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => (bool) env('MINIO_SSL_VERIFY', false),
                CURLOPT_SSL_VERIFYHOST => (bool) env('MINIO_SSL_VERIFY', false) ? 2 : 0,
            ]);

            curl_multi_add_handle($mh, $ch);
            $activeHandles[spl_object_id($ch)] = [
                'ch' => $ch,
                'fp' => $fp,
                'filepath' => $item['filepath'],
                'local_path' => $item['local_path'],
            ];
        };

        try {
            while (count($activeHandles) < $maxParallel && $queue !== []) {
                $enqueueNext();
            }

            $running = 0;
            do {
                $status = curl_multi_exec($mh, $running);

                while (($info = curl_multi_info_read($mh)) !== false) {
                    $ch = $info['handle'];
                    if (!$ch instanceof \CurlHandle) {
                        continue;
                    }

                    $handleId = spl_object_id($ch);
                    if (!isset($activeHandles[$handleId])) {
                        continue;
                    }

                    $meta = $activeHandles[$handleId];
                    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);

                    curl_multi_remove_handle($mh, $ch);
                    curl_close($ch);
                    fclose($meta['fp']);
                    unset($activeHandles[$handleId]);

                    if ($info['result'] !== CURLE_OK || $httpCode < 200 || $httpCode >= 300) {
                        @unlink($meta['local_path']);
                        $errors[$meta['filepath']] = $curlError !== ''
                            ? $curlError
                            : "HTTP {$httpCode}";
                    }

                    while (count($activeHandles) < $maxParallel && $queue !== []) {
                        $enqueueNext();
                    }
                }

                if ($running > 0 && $status === CURLM_OK) {
                    curl_multi_select($mh, 0.5);
                }
            } while (($running > 0 || $activeHandles !== []) && $status === CURLM_OK);
        } finally {
            foreach ($activeHandles as $meta) {
                curl_multi_remove_handle($mh, $meta['ch']);
                curl_close($meta['ch']);
                if (is_resource($meta['fp'])) {
                    fclose($meta['fp']);
                }
            }

            curl_multi_close($mh);
            curl_share_close($sh);
        }

        if ($errors !== []) {
            $firstFailedPath = (string) array_key_first($errors);
            $firstError = $errors[$firstFailedPath];

            throw new RuntimeException(
                "MinIO curl_multi failed to read {$firstFailedPath}: {$firstError}"
            );
        }

        return $targetMap;
    }

    /**
     * Normalize input files into an associative map of [minioPath => localFilePath].
     *
     * @param array<int|string, string> $files   list of MinIO paths or map of [minioPath => localPath]
     * @param string|null               $tempDir target directory when $files is a list
     *
     * @throws RuntimeException when the destination directory cannot be created
     *
     * @return array<string, string>
     */
    private function normalizeTargetMap(array $files, ?string $tempDir): array
    {
        $baseDir = $tempDir ?? sys_get_temp_dir();
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException("Failed to create temp directory: {$baseDir}");
        }

        $targetMap = [];
        foreach ($files as $key => $value) {
            if (is_int($key)) {
                $filepath = $value;
                $localPath = rtrim($baseDir, '/') . '/' . uniqid('minio_', true);
            } else {
                $filepath = $key;
                $localPath = $value;
            }

            $targetMap[$filepath] = $localPath;
        }

        return $targetMap;
    }

    /**
     * Download multiple MinIO files concurrently using AWS SDK CommandPool.
     *
     * Uses a Generator and Guzzle's SaveAs lazy file stream so at most
     * $concurrency sockets and file descriptors are open at once.
     *
     * @param array<int|string, string> $files       list of MinIO paths or map of [minioPath => localPath]
     * @param string|null               $tempDir     target local directory when $files is a list
     * @param int                       $concurrency max simultaneous transfers
     *
     * @throws RuntimeException when any transfer fails
     *
     * @return array<string, string> map of [minioPath => localFilePath]
     */
    public function readConcurrentCommandPool(
        array $files,
        ?string $tempDir = null,
        int $concurrency = 5
    ): array {
        if ($files === []) {
            return [];
        }

        $targetMap = $this->normalizeTargetMap($files, $tempDir);
        [$s3Client, $bucket, $keyPrefix] = $this->resolveS3ClientAndBucket();

        $keysByIndex = array_keys($targetMap);
        $errors = [];

        $commandGenerator = static function () use (
            $s3Client,
            $bucket,
            $keyPrefix,
            $targetMap
        ): \Generator {
            foreach ($targetMap as $filepath => $localPath) {
                $objectKey = $keyPrefix !== ''
                    ? $keyPrefix . ltrim($filepath, '/')
                    : ltrim($filepath, '/');

                yield $s3Client->getCommand('GetObject', [
                    'Bucket' => $bucket,
                    'Key' => $objectKey,
                    'SaveAs' => $localPath,
                ]);
            }
        };

        $pool = new CommandPool($s3Client, $commandGenerator(), [
            'concurrency' => max(1, $concurrency),
            'rejected' => static function (
                \Throwable $reason,
                int $index
            ) use (&$errors, $keysByIndex): void {
                $filepath = $keysByIndex[$index] ?? "index_{$index}";
                $message = $reason instanceof AwsException
                    ? ($reason->getAwsErrorMessage() ?? $reason->getMessage())
                    : $reason->getMessage();
                $errors[$filepath] = $message;
            },
        ]);

        $pool->promise()->wait();

        if ($errors !== []) {
            foreach (array_keys($errors) as $failedMinioPath) {
                if (isset($targetMap[$failedMinioPath]) && is_file($targetMap[$failedMinioPath])) {
                    @unlink($targetMap[$failedMinioPath]);
                }
            }

            $firstFailedPath = (string) array_key_first($errors);
            $firstError = $errors[$firstFailedPath];

            throw new RuntimeException(
                "MinIO CommandPool failed to read {$firstFailedPath}: {$firstError}"
            );
        }

        return $targetMap;
    }

    /**
     * Extract the S3Client, bucket name, and path prefix from the Laravel disk.
     *
     * Supports Laravel's AwsS3V3Adapter, coraxster/flysystem-aws-s3-v3-minio's
     * AwsS3Adapter, and fallback disk config resolution.
     *
     * @throws RuntimeException when the S3Client or bucket cannot be resolved
     *
     * @return array{0: S3Client, 1: string, 2: string}
     */
    private function resolveS3ClientAndBucket(): array
    {
        $disk = LaravelStorage::disk($this->disk);
        $adapter = null;

        if (method_exists($disk, 'getAdapter')) {
            $adapter = $disk->getAdapter();
        } elseif (method_exists($disk, 'getDriver') && method_exists($disk->getDriver(), 'getAdapter')) {
            $adapter = $disk->getDriver()->getAdapter();
        }

        $client = null;
        if (method_exists($disk, 'getClient')) {
            $client = $disk->getClient();
        } elseif ($adapter !== null && method_exists($adapter, 'getClient')) {
            $client = $adapter->getClient();
        }

        $bucket = null;
        if ($adapter !== null && method_exists($adapter, 'getBucket')) {
            $bucket = $adapter->getBucket();
        }

        $prefix = '';
        if ($adapter !== null && method_exists($adapter, 'getPathPrefix')) {
            $prefix = (string) $adapter->getPathPrefix();
        }

        $diskConfig = function_exists('config')
            ? (array) config("filesystems.disks.{$this->disk}", [])
            : [];

        if ($bucket === null && isset($diskConfig['bucket'])) {
            $bucket = (string) $diskConfig['bucket'];
        }

        if ($prefix === '' && !empty($diskConfig['root'])) {
            $prefix = rtrim((string) $diskConfig['root'], '/') . '/';
        }

        if (!$client instanceof S3Client && !empty($diskConfig['key'])) {
            $client = new S3Client([
                'version' => (string) ($diskConfig['version'] ?? 'latest'),
                'region' => (string) ($diskConfig['region'] ?? 'us-east-1'),
                'endpoint' => $diskConfig['endpoint'] ?? null,
                'use_path_style_endpoint' => (bool) ($diskConfig['use_path_style_endpoint'] ?? true),
                'credentials' => [
                    'key' => (string) $diskConfig['key'],
                    'secret' => (string) ($diskConfig['secret'] ?? ''),
                ],
            ]);
        }

        if (!$client instanceof S3Client || $bucket === null || $bucket === '') {
            throw new RuntimeException(
                "Unable to resolve S3Client or bucket for disk: {$this->disk}"
            );
        }

        return [$client, $bucket, $prefix];
    }
}
