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

class Command extends BaseCommand
{
    use CommandTrait;

    /**
     * Creating instance
     *
     * @return self
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTaskID();
    }
}
