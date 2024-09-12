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
 * ModelMakeCommand
 *
 * Custom command
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ModelMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:model';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $this->createCollection();
        $this->createRepository();
    }
    /**
     * Create a collection file for the model.
     *
     * @return void
     */
    protected function createCollection()
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:collection',
            [
            'name' => $className
            ]
        );
    }
    /**
     * Create a collection file for the model.
     *
     * @return void
     */
    protected function createRepository()
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:repository',
            [
            'name' => $className
            ]
        );
    }
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('resource')) {
            return __DIR__ . '/stubs/model.stub';
        }
        return __DIR__ . '/stubs/model.plain.stub';
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
        return $rootNamespace . '\Models';
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource model class.'],
        ];
    }
}
