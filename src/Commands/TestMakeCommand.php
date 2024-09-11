<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Str;

/**
 * CollectionMakeCommand
 *
 * Custom command
 *
 * @category Console
 * @package  Commands
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 */
class TestMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:test';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class';
    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test';
    /**
     * Get the destination class path.
     *
     * @param string $name name of the type
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->laravel->getNamespace(), '', $name);
        return $this->laravel->basePath() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $name . 'Test') . '.php';
    }
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('resource')) {
            return __DIR__ . '/stubs/test.stub';
        }
        return __DIR__ . '/stubs/test.plain.stub';
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['resource', null, InputOption::VALUE_NONE, 'Generate a resource test class.'],
        ];
    }
}
