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

namespace Spotlibs\PhpLib\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * ControllerMakeCommand
 *
 * Standard command
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class CommandMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new command class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Command';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
    }
    /**
     * Get the destination class path.
     *
     * @param string $name name of the type
     *
     * @return string
     */
    protected function getPath($name)
    {
        return parent::getPath($name . 'Command');
    }
    /**
     * ReplaceClass
     *
     * @param string $stub filename of stub file
     * @param string $name name of the type
     *
     * @return string|string[]
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);
        $temp = explode("/", $this->argument('name'));
        $signature = 'signature:' . $temp[count($temp) - 1];
        $stub = str_replace('DummySignature', $signature, $stub);
        return $stub;
    }
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('resource')) {
            return __DIR__ . '/stubs/command.stub';
        }
        return __DIR__ . '/stubs/command.plain.stub';
    }
    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace namespace of root (generally App)
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Console\Commands';
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource command class.'],
        ];
    }
}
