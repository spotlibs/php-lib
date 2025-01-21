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
 * Runtime
 *
 * @category Library
 * @package  Logs
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Runtime
{
    use TraitLog;

    protected string $channel = 'runtime';

    /**
     * Logging with loglevel warning
     *
     * @param array $data Log data in form of associative array
     *
     * @return void
     */
    public function warning(array $data): void
    {
        $this->getEmbeddedInfo($data);
        BaseLog::channel($this->channel)->warning(json_encode($data));
    }

    /**
     * Logging with loglevel error
     *
     * @param array $data Log data in form of associative array
     *
     * @return void
     */
    public function error(array $data): void
    {
        $this->getEmbeddedInfo($data);
        BaseLog::channel($this->channel)->error(json_encode($data));
    }
}
