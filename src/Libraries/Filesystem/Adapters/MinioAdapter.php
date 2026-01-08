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

namespace Spotlibs\PhpLib\Libraries\Filesystem\Adapters;

use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spotlibs\PhpLib\Libraries\Filesystem\Contracts\CustomFilesystemInterface;
use Throwable;

/**
 * MinioAdapter
 *
 * Adapter for MinIO (S3 Compatible) storage
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class MinioAdapter implements CustomFilesystemInterface
{
    /**
     * S3 Client instance.
     *
     * @var S3Client
     */
    protected S3Client $client;

    /**
     * MinIO endpoint URL.
     *
     * @var string
     */
    protected string $endpoint;

    /**
     * Public URL for replacing MinIO endpoint.
     *
     * @var string
     */
    protected string $publicUrl;

    /**
     * Bucket name.
     *
     * @var string
     */
    protected string $bucket;

    /**
     * Default URL expiration in seconds.
     *
     * @var int
     */
    protected int $urlExpiration;

    /**
     * Constructor.
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config)
    {
        $this->endpoint = rtrim($config['endpoint'] ?? '', '/');
        $this->publicUrl = rtrim($config['public_url'] ?? $this->endpoint, '/');
        $this->bucket = $config['bucket'] ?? '';
        $this->urlExpiration = (int) ($config['url_expiration'] ?? 3600);

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?? 'us-east-1',
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
        ]);
    }

    /**
     * Write contents to a file.
     *
     * @param string          $path     File path relative to bucket
     * @param string|resource $contents File contents
     * @param array           $options  Additional options
     *
     * @return bool
     */
    public function put(string $path, $contents, array $options = []): bool
    {
        try {
            $path = trim($path, '/');

            $params = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
            ];

            if (isset($options['ContentType'])) {
                $params['ContentType'] = $options['ContentType'];
            }

            if (isset($options['visibility']) && $options['visibility'] === 'public') {
                $params['ACL'] = 'public-read';
            }

            $this->client->putObject($params);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Store uploaded file with auto-generated filename.
     *
     * @param string            $path    Directory path
     * @param File|UploadedFile $file    Uploaded file
     * @param array             $options Additional options
     *
     * @return string|false Return stored path or false on failure
     */
    public function putFile(string $path, $file, array $options = []): string|false
    {
        $name = Str::random(40) . '.' . $this->getFileExtension($file);

        return $this->putFileAs($path, $file, $name, $options);
    }

    /**
     * Store uploaded file with custom filename.
     *
     * @param string            $path    Directory path
     * @param File|UploadedFile $file    Uploaded file
     * @param string            $name    Custom filename
     * @param array             $options Additional options
     *
     * @return string|false Return stored path or false on failure
     */
    public function putFileAs(string $path, $file, string $name, array $options = []): string|false
    {
        try {
            $path = trim($path, '/');
            $fullPath = $path . '/' . $name;

            $params = [
                'Bucket' => $this->bucket,
                'Key' => $fullPath,
                'SourceFile' => $file->getPathname(),
            ];

            $contentType = $this->getMimeType($file);
            if ($contentType) {
                $params['ContentType'] = $contentType;
            }

            if (isset($options['visibility']) && $options['visibility'] === 'public') {
                $params['ACL'] = 'public-read';
            }

            $this->client->putObject($params);

            return $fullPath;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get public URL for a file.
     *
     * @param string $path File path
     *
     * @return string
     */
    public function url(string $path): string
    {
        $path = trim($path, '/');

        $minioUrl = $this->endpoint . '/' . $this->bucket . '/' . $path;

        return str_replace($this->endpoint, $this->publicUrl, $minioUrl);
    }

    /**
     * Get temporary signed URL with expiration.
     *
     * @param string            $path       File path
     * @param DateTimeInterface $expiration Expiration time
     * @param array             $options    Additional options
     *
     * @return string
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        try {
            $path = trim($path, '/');

            $command = $this->client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            $request = $this->client->createPresignedRequest($command, $expiration);
            $presignedUrl = (string) $request->getUri();

            return str_replace($this->endpoint, $this->publicUrl, $presignedUrl);
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Get all files in a directory recursively.
     *
     * @param string|null $directory Directory path
     *
     * @return array
     */
    public function allFiles(?string $directory = null): array
    {
        try {
            $prefix = $directory ? trim($directory, '/') . '/' : '';

            $objects = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            $files = [];

            if (isset($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    $key = $object['Key'];

                    // Skip if it's a directory (ends with /)
                    if (!str_ends_with($key, '/')) {
                        $files[] = $key;
                    }
                }
            }

            return $files;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get all directories in a directory recursively.
     *
     * @param string|null $directory Directory path
     *
     * @return array
     */
    public function allDirectories(?string $directory = null): array
    {
        try {
            $prefix = $directory ? trim($directory, '/') . '/' : '';

            $objects = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            $directories = [];

            if (isset($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    $key = $object['Key'];

                    // Extract directory paths from file keys
                    $parts = explode('/', $key);
                    array_pop($parts); // Remove filename

                    $currentPath = '';
                    foreach ($parts as $part) {
                        $currentPath .= ($currentPath ? '/' : '') . $part;

                        if (!in_array($currentPath, $directories) && $currentPath !== trim($directory, '/')) {
                            $directories[] = $currentPath;
                        }
                    }
                }
            }

            // Also check CommonPrefixes for explicit directories
            if (isset($objects['CommonPrefixes'])) {
                foreach ($objects['CommonPrefixes'] as $prefix) {
                    $dir = rtrim($prefix['Prefix'], '/');
                    if (!in_array($dir, $directories)) {
                        $directories[] = $dir;
                    }
                }
            }

            sort($directories);

            return $directories;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get file extension from uploaded file.
     *
     * @param File|UploadedFile $file Uploaded file
     *
     * @return string
     */
    protected function getFileExtension($file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension();
        }

        return $file->getExtension();
    }

    /**
     * Get MIME type from uploaded file.
     *
     * @param File|UploadedFile $file Uploaded file
     *
     * @return string|null
     */
    protected function getMimeType($file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientMimeType();
        }

        return $file->getMimeType();
    }

    /**
     * Get S3 client instance.
     *
     * @return S3Client
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }

    /**
     * Get bucket name.
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }
}