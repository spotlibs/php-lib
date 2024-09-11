<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.6
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * UsecaseMakeCommand
 *
 * Custom command
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class UsecaseMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:usecase';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new usecase for model class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Usecase';
    /**
     * Get the destination class path.
     *
     * @param string $name name of the type
     *
     * @return string
     */
    protected function getPath($name)
    {
        return parent::getPath($name . 'Usecase');
    }
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('resource')) {
            return __DIR__ . '/stubs/usecase.stub';
        }
        return __DIR__ . '/stubs/usecase.plain.stub';
    }
    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace root namespace (generally App)
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Usecases';
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource usecase class.'],
        ];
    }
}
