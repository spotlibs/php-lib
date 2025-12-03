<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Commands;

use Illuminate\Console\Command as BaseCommand;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

/**
 * CommandInterface
 *
 * Command interface
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */

abstract class Command extends BaseCommand
{
    public string $taskID;

    /**
     * Creating instance
     *
     * @return self
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Setting context required for logger.
     * 
     * @return void
     */
    public function setContext(): void
    {
        $this->taskID = uniqid() . '00000';
        $context = app(Context::class);
        $meta = new Metadata();
        $meta->task_id = $this->taskID;
        $meta->identifier = explode(" ", $this->signature)[0];
        $context->set(Metadata::class, $meta);
    }
}
