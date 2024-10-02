<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Logs
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Logs;

use Spotlibs\PhpLib\Services\Context;

/**
 * Log
 *
 * @category Library
 * @package  Logs
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Log
{
    /**
     * Logging to activity channel
     *
     * @return Activity
     */
    public static function activity(): Activity
    {
        $context = app(Context::class);
        return new Activity(context: $context);
    }

    /**
     * Logging to activity channel
     *
     * @return Runtime
     */
    public static function runtime(): Runtime
    {
        $context = app(Context::class);
        return new Runtime($context);
    }

    /**
     * Logging to activity channel
     *
     * @return Worker
     */
    public static function worker(): Worker
    {
        $context = app(Context::class);
        return new Worker($context);
    }
}
