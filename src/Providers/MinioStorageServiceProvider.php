<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Providers
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace App\Providers;

use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Illuminate\Support\Facades\Storage;
use Spotlibs\PhpLib\Libraries\StorageDrivers\MinioAdapter;

/**
 * MinioStorageServiceProvider
 *
 * Service provider for MinIO storage
 *
 * @category StandardService
 * @package  ServiceProvider
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class MinioStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('minio', function ($app, $config) {
            // Client for internal Docker network communication
            $client = new S3Client([
                'credentials' => [
                    'key' => $config["key"],
                    'secret' => $config["secret"]
                ],
                'region' => $config["region"],
                'version' => "latest",
                'bucket_endpoint' => false,
                'use_path_style_endpoint' => true,
                'endpoint' => $config["endpoint"], // e.g., http://minio:9000
            ]);
            
            $adapter = new AwsS3Adapter($client, $config["bucket"]);
            
            // Public client for browser-accessible URLs
            $publicClient = new S3Client([
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret']
                ],
                'region' => $config['region'],
                'version' => 'latest',
                'use_path_style_endpoint' => true,
                'endpoint' => $config['url'] ?? $config['endpoint'], // e.g., http://localhost:9000
            ]);
            
            // Return your custom MinioAdapter (which extends Filesystem)
            return new MinioAdapter($adapter, $config, $publicClient);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        
    }
}
