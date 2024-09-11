<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * ResponseDtoMakeCommand
 *
 * Custom command
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ResponseDtoMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:response_dto';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new response DTO class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Response DTO';

    /**
     * Get the destination class path.
     *
     * @param string $name name of the type
     *
     * @return string
     */
    protected function getPath($name)
    {
        return parent::getPath($name . 'Response');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        $this->createDto();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('resource')) {
            return __DIR__ . '/stubs/response.dto.stub';
        }
        return __DIR__ . '/stubs/response.dto.plain.stub';
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
        return $rootNamespace . '\Models\Dtos';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource response DTO class.'],
        ];
    }

    /**
     * Create a collection file for the model.
     *
     * @return void
     */
    protected function createDto()
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:dto',
            [
            'name' => $className
            ]
        );
    }
}
