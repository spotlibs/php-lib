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
        return new Activity();
    }

    /**
     * Logging to activity channel
     *
     * @return Runtime
     */
    public static function runtime(): Runtime
    {
        return new Runtime();
    }

    /**
     * Logging to activity channel
     *
     * @return Worker
     */
    public static function worker(): Worker
    {
        return new Worker();
    }
}
