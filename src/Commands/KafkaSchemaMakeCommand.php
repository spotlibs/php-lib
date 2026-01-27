<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Commands
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use JsonException;
use Symfony\Component\Console\Input\InputOption;

/**
 * KafkaSchemaMakeCommand
 *
 * Custom command for Kafka schema
 *
 * @category Console
 * @package  Commands
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class KafkaSchemaMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:kafka-schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Kafka schema model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Kafka Schema Model';

    /**
     * Schema input from user
     *
     * @var array
     */
    protected array $schemaData = [];

    /**
     * Schema type
     *
     * @var string
     */
    protected string $schemaType = 'avro';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FileNotFoundException
     */
    public function handle() : int
    {
        $this->schemaType = $this->option('type') ?? 'avro';

        $this->info('Please paste your ' . strtoupper($this->schemaType) . ' schema (press Ctrl+D or Ctrl+Z when done):');
        if (!in_array($this->schemaType, ['avro', 'json'])) {
            $this->error('Invalid schema type. Must be either "avro" or "json".');
            return 1;
        }

        $schemaInput = stream_get_contents(STDIN);

        try {
            $this->schemaData = json_decode($schemaInput, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error('Invalid JSON schema: ' . $e->getMessage());
            return 1;
        }

        parent::handle();

        $this->createCollection();
        return 0;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->schemaType === 'json') {
            return __DIR__ . '/stubs/kafka.schema.json.stub';
        }

        return __DIR__ . '/stubs/kafka.schema.avro.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name name of the class
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        return $this->replaceSchema($stub);
    }

    /**
     * Replace schema placeholders in stub
     *
     * @param string $stub stub content
     *
     * @return string
     */
    protected function replaceSchema(string $stub): string
    {
        $className = class_basename($this->argument('name'));

        if ($this->schemaType === 'avro') {
            $fields = $this->schemaData['fields'] ?? [];
            $fillable = $this->generateFillableFromAvro($fields);
            $casts = $this->generateCastsFromAvro($fields);
            $schemaName = $this->schemaData['name'] ?? 'value_Schema' . str_replace('CDC', '', $className);
            $namespace = $this->schemaData['namespace'] ?? strtolower(preg_replace('/(?<!^)[A-Z]/', '', str_replace('CDC', '', $className)));
        } else {
            $properties = $this->schemaData['properties'] ?? [];
            $fillable = $this->generateFillableFromJson($properties);
            $casts = $this->generateCastsFromJson($properties);
            $schemaName = 'Schema' . str_replace('CDC', '', $className);
            $namespace = strtolower(preg_replace('/(?<!^)[A-Z]/', '', str_replace('CDC', '', $className)));
        }

        $stub = str_replace('DummyFillable', $fillable, $stub);
        $stub = str_replace('DummyCasts', $casts, $stub);
        $stub = str_replace('DummySchemaBody', $this->formatSchemaBody(), $stub);
        $stub = str_replace('DummySchemaName', $schemaName, $stub);
        $stub = str_replace('DummySchemaNamespace', $namespace, $stub);

        return $stub;
    }

    /**
     * Generate fillable array from AVRO fields
     *
     * @param array $fields AVRO fields
     *
     * @return string
     */
    protected function generateFillableFromAvro(array $fields): string
    {
        $fillable = array_map(fn($field) => "    '{$field['name']}'", $fields);
        return implode(",\n", $fillable);
    }

    /**
     * Generate casts array from AVRO fields
     *
     * @param array $fields AVRO fields
     *
     * @return string
     */
    protected function generateCastsFromAvro(array $fields): string
    {
        $casts = [];
        foreach ($fields as $field) {
            $type = is_array($field['type']) ? $field['type'][1] ?? 'string' : $field['type'];
            $phpType = $this->mapAvroTypeToPhp($type);
            $casts[] = "    '{$field['name']}' => '{$phpType}'";
        }
        return implode(",\n", $casts);
    }

    /**
     * Generate fillable array from JSON properties
     *
     * @param array $properties JSON properties
     *
     * @return string
     */
    protected function generateFillableFromJson(array $properties): string
    {
        $fillable = array_map(fn($name) => "    '{$name}'", array_keys($properties));
        return implode(",\n", $fillable);
    }

    /**
     * Generate casts array from JSON properties
     *
     * @param array $properties JSON properties
     *
     * @return string
     */
    protected function generateCastsFromJson(array $properties): string
    {
        $casts = [];
        foreach ($properties as $name => $definition) {
            $type = $definition['type'] ?? 'string';
            $phpType = $this->mapJsonTypeToPhp($type);
            $casts[] = "    '{$name}' => '{$phpType}'";
        }
        return implode(",\n", $casts);
    }

    /**
     * Map AVRO type to PHP cast type
     *
     * @param string $avroType AVRO type
     *
     * @return string
     */
    protected function mapAvroTypeToPhp(string $avroType): string
    {
        return match ($avroType) {
            'int', 'long' => 'integer',
            'float', 'double' => 'float',
            'boolean' => 'boolean',
            'string' => 'string',
            default => 'string',
        };
    }

    /**
     * Map JSON type to PHP cast type
     *
     * @param string $jsonType JSON type
     *
     * @return string
     */
    protected function mapJsonTypeToPhp(string $jsonType): string
    {
        return match ($jsonType) {
            'integer' => 'integer',
            'number' => 'float',
            'boolean' => 'boolean',
            'string' => 'string',
            default => 'string',
        };
    }

    /**
     * Format schema body for output
     *
     * @return string
     */
    protected function formatSchemaBody(): string
    {
        $schema = json_encode($this->schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $lines = explode("\n", $schema);
        $indented = array_map(fn($line) => '                ' . $line, $lines);
        return implode("\n", $indented);
    }

    /**
     * Create a collection file for the model.
     *
     * @return void
     */
    protected function createCollection(): void
    {
        $className = class_basename($this->argument('name'));
        $this->call(
            'make:collection',
            [
                'name' => $className,
                '--resource' => true
            ]
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace root namespace (generally App)
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Models';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['type', null, InputOption::VALUE_OPTIONAL, 'Schema type (avro or json)', 'avro'],
        ];
    }
}