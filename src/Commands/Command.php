<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.2
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

abstract class Command extends BaseCommand implements CommandInterface
{
    public string $taskID;
    /**
     * Obligatory function of set task ID
     *
     * @return void
     */
    final public function setTaskID(): void
    {
        $this->taskID = uniqid() . '00000';
    }

    /**
     * Creating instance
     *
     * @return self
     */
    public function __construct()
    {
        parent::__construct();
        $context = app(Context::class);
        $this->setTaskID();
        $meta = $context->get(Metadata::class);
        if ($meta instanceof Metadata) {
            $meta->task_id = $this->taskID;
        }
    }
}
