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
 * Activity
 *
 * @category Library
 * @package  Logs
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Activity
{
    use TraitLog;

    protected string $channel = 'activity';
}
