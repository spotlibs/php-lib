<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Providers
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Spotlibs\PhpLib\Logs\Log;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

/**
 * QueueEventServiceProvider
 *
 * Service provider for context
 *
 * @category StandardService
 * @package  ServiceProvider
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class QueueEventServiceProvider extends ServiceProvider
{
    /**
     * Execute the service provider
     *
     * @return void
     */
    public function boot()
    {
        Queue::before(
            function (JobProcessing $event) {
                app()->forgetInstance(Context::class);
                $job = unserialize($event->job->payload()['data']['command']);
                $context = app(Context::class);
                $meta = new Metadata();
                $meta->task_id = $job->taskID;
                $meta->identifier = $job->identifier;
                $context->set(Metadata::class, $meta);
            }
        );

        // Queue::after(
        //     function (JobProcessed $event) {
        //         Log::runtime()->info(['message' => 'job is done, clearing context in app...']);
        //         app()->forgetInstance(Context::class);
        //     }
        // );

        // Queue::failing(
        //     function (JobFailed $event) {
        //         Log::runtime()->info(['message' => 'job is failing, still we should clearing context in app...']);
        //         app()->forgetInstance(Context::class);
        //     }
        // );
    }
}
