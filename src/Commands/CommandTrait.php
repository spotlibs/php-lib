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

use Spotlibs\PhpLib\Services\Context;

/**
 * Command
 *
 * Command trait
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
trait CommandTrait
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
}
