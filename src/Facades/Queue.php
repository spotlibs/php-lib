<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Facades
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.2
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Facades;

use Illuminate\Support\Facades\Queue as BaseQueue;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

/**
 * Queue
 *
 * Spolibs queue facades. Child of Illuminate\Support\Facades\Queue
 *
 * @category Helper
 * @package  Facades
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Queue extends BaseQueue
{
    /**
     * Push job to queue
     *
     * @param string        $queue queue name
     * @param object|string $job   instance of a job
     * @param mixed         $data  additional data (optional)
     *
     * @return mixed
     */
    public static function pushOn(string $queue, object|string $job, mixed $data = ''): mixed
    {
        $context = app(Context::class);
        if ($context) {
            $meta = $context->get(Metadata::class);
            if (isset($meta->req_id)) {
                $taskID = $meta->req_id;
            } else {
                $taskID = self::generateTaskID();
            }
        } else {
            $taskID = self::generateTaskID();
        }
        $data = ['taskID' => $taskID];

        return parent::pushOn($queue, $job, $data);
    }

    /**
     * Generate new task ID
     *
     * @return string
     */
    private static function generateTaskID(): string
    {
        return uniqid() . '00000';
    }
}
