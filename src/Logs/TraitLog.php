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

use Illuminate\Support\Facades\Log as BaseLog;

/**
 * TraitLog
 *
 * @category Library
 * @package  Logs
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
trait TraitLog
{
    /**
     * Logging with loglevel info
     *
     * @param array $data Log data in form of associative array
     *
     * @return void
     */
    public function info(array $data)
    {
        BaseLog::channel($this->channel)->info(json_encode($data));
    }
}
