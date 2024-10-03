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
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

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
     * Initiate Log
     *
     * @param Context $context context instance
     *
     * @return self
     */
    public function __construct(protected Context $context)
    {
    }

    /**
     * Logging with loglevel info
     *
     * @param array $data Log data in form of associative array
     *
     * @return void
     */
    public function info(array $data)
    {
        $this->getEmbeddedInfo($data);
        BaseLog::channel($this->channel)->info(json_encode($data));
    }

    /**
     * Embed basic info to data log
     *
     * @param array $data pointer of log data
     *
     * @return void
     */
    private function getEmbeddedInfo(array &$data): void
    {
        $data['TraceID'] = ['requestID' => '', 'taskID' => ''];
        $data['identifier'] = '';
        if ($taskID = $this->context->get('taskID')) {
            $data['TraceID']['taskID'] = $taskID;
            return;
        }
        if ($meta = $this->context->get(Metadata::class)) {
            if ($meta instanceof Metadata) {
                if (isset($meta->req_id)) {
                    $data['TraceID']['requestID'] = $meta->req_id;
                }
            }
        }
    }
}
