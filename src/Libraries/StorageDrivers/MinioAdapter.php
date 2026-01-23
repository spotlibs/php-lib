<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.7.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\StorageDrivers;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

/**
 * Storage
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class MinioAdapter extends Filesystem
{
    protected S3Client $publicClient;
    protected string $bucket;

    /**
     * Create a new Minio Filesystem Adapter instance.
     *
     * @param AwsS3Adapter $adapter      filesystem adapter
     * @param array        $config       driver config
     * @param S3Client     $publicClient client for generating presigned URL
     *
     * @return void
     */
    public function __construct(AwsS3Adapter $adapter, array $config, S3Client $publicClient)
    {
        parent::__construct($adapter, $config);

        $this->bucket = $config['bucket'];

        // Create public client for pre-signed URLs
        $this->publicClient = $publicClient ?: new S3Client(
            [
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret']
            ],
            'region' => $config['region'],
            'version' => 'latest',
            'use_path_style_endpoint' => true,
            'endpoint' => $config['url'] ?? $config['endpoint'],
            ]
        );
    }

    /**
     * Temporary URL facade
     *
     * @param string $path file path
     * @param string       $expiration time limit of url
     * @param array $options optional
     *
     * @return string
     */
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    // phpcs:disable PEAR.Commenting.FunctionComment.Missing
    public function temporaryUrl(string $path, string $expiration, array $options = []): string
    {
        $command = $this->publicClient->getCommand(
            'GetObject',
            array_merge(
                [
                'Bucket' => $this->bucket,
                'Key' => $path,
                ],
                $options
            )
        );

        $request = $this->publicClient->createPresignedRequest(
            $command,
            $expiration
        );

        return (string) $request->getUri();
    }

    /**
     * Copy a file to a new location using S3 native copy.
     *
     * @param string $path    source path
     * @param string $newpath destination path
     *
     * @return bool
     */
    public function copy($path, $newpath): bool
    {
        try {
            $this->client->copyObject(
                [
                'Bucket' => $this->bucket,
                'Key' => $to,
                'CopySource' => "{$this->bucket}/{$from}",
                ]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Move a file to a new location using S3 native copy and delete.
     *
     * @param string $path    source path
     * @param string $newpath destination path
     *
     * @return bool
     */
    public function move(string $path, string $newpath): bool
    {
        try {
            // Copy the file
            if ($this->copy($path, $path)) {
                // Delete the original
                return $this->delete($path);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
