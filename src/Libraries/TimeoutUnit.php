<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

/**
 * ClientTimeoutUnit
 *
 * Name for HTTP Client timeout unit
 *
 * @category Helper
 * @package  Client
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class TimeoutUnit
{
    public static float $day_ = 86400;
    public static float $hour_ = 3600;
    public static float $minute_ = 60;
    public static float $second_ = 1;
    public static float $milisecond_ = 1 / 1e3;
    public static float $nanosecond_ = 1 / 1e6;
}
