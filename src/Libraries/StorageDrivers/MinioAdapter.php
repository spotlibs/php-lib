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
    protected array $config;

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
        $this->config = $config;

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
     * @return bool
     */
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    // phpcs:disable PEAR.Commenting.FunctionComment.Missing
    public function temporaryUrl($path, $expiration, $options = []): string
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
}
