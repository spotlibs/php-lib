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
use Illuminate\Support\Str;

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
class ControllerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        $this->createSwagger();
        $this->createTest();
    }
    /**
     * Create a swagger file controller.
     *
     * @return void
     */
    protected function createSwagger()
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:controller-swagger',
            [
            'name' => $className
            ]
        );
    }
    /**
     * Create a unit test file.
     *
     * @return void
     */
    protected function createTest()
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:test',
            [
            'name' => $className
            ]
        );
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
        return parent::getPath($name . 'Controller');
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
        $stub = str_replace('DummyUsecase', Str::ucfirst($this->argument('name')) . 'Usecase', $stub);
        $stub = str_replace('dummyUsecase', Str::lower($this->argument('name')) . 'Usecase', $stub);
        $stub = str_replace("/", "\\", Str::ucfirst($this->argument('name')) . 'Usecase');
        $stub = str_replace("/", "\\", Str::lower($this->argument('name')) . 'Usecase');
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
            return __DIR__ . '/stubs/controller.stub';
        }
        return __DIR__ . '/stubs/controller.plain.stub';
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
        return $rootNamespace . '\Http\Controllers';
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource controller class.'],
        ];
    }
}
