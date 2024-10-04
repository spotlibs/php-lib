<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Providers
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.3
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Providers;

use Illuminate\Support\ServiceProvider;
use Spotlibs\PhpLib\Services\Context;

/**
 * ContextServiceProvider
 *
 * Service provider for context
 *
 * @category StandardService
 * @package  ServiceProvider
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ContextServiceProvider extends ServiceProvider
{
    /**
     * Register context service provider
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            Context::class,
            function ($app) {
                return new Context();
            }
        );
    }
}
