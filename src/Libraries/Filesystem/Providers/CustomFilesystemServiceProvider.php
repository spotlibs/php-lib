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

namespace Spotlibs\PhpLib\Libraries\Filesystem\Providers;

use Illuminate\Support\ServiceProvider;
use Spotlibs\PhpLib\Libraries\Filesystem\CustomFilesystemManager;

/**
 * CustomFilesystemServiceProvider
 *
 * Service provider for registering CustomFilesystemManager
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class CustomFilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register CustomFilesystemManager as singleton
        $this->app->singleton('custom-filesystem', function ($app) {
            return new CustomFilesystemManager($app);
        });

        // Bind interface to manager for dependency injection
        $this->app->singleton(
            CustomFilesystemManager::class,
            function ($app) {
                return $app['custom-filesystem'];
            }
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Nothing to boot for Lumen
        // Config is loaded manually in bootstrap/app.php
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'custom-filesystem',
            CustomFilesystemManager::class,
        ];
    }
}