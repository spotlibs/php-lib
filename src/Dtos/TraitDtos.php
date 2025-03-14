<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Dtos
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Dtos;

use Exception;
use ReflectionClass;

/**
 * TraitDtos
 *
 * @category Library
 * @package  Dtos
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
trait TraitDtos
{
    /**
     * Construct a DTO instance from associative array. Array key and value data type must comply DTO class property
     *
     * @param array $data associative array
     *
     * @return mixed
     */
    public function __construct(array $data = [])
    {
        if (!isset($this->arrayOfObjectMap)) {
            $this->arrayOfObjectMap = [];
        }
        if (!isset($this->aliases)) {
            $this->aliases = [];
        }
        $reflector = new ReflectionClass(static::class);
        try {
            foreach ($data as $key => $value) {
                $temp_key = array_search($key, $this->aliases, true);
                $key = is_string($temp_key) ? $temp_key : $key;
                if (property_exists($this, $key)) {
                    if (is_array($value)) {
                        $this->convertArray($reflector, $key, $value);
                    }
                    $this->{$key} = $value;
                }
            }
        } catch (\Throwable $th) {
            if (substr($th->getMessage(), 0, 30) == "property_exists(): Argument #2") {
                $jsonEncoded = json_encode($data);
                throw new Exception($th->getMessage() . '. Error constructing this array to DTO: ' . $jsonEncoded);
            }
            throw $th;
        }
    }

    /**
     * Convert if value is array
     *
     * @param ReflectionClass $reflector type reflection helper
     * @param string          $key       class property
     * @param array           $value     array value
     *
     * @return void
     */
    private function convertArray(ReflectionClass &$reflector, string $key, array &$value): void
    {
        $prop = $reflector->getProperty($key);
        $type = $prop->getType()->getName();
        //construct object if type is not array
        if ($type != 'array') {
            $value = new $type($value);
        } else {
            if (array_key_exists($key, $this->arrayOfObjectMap)) {
                $value = $this->createArrayOfObject($this->arrayOfObjectMap[$key], $value);
            }
        }
    }

    /**
     * Create a DTO instance from associative array. Array key and value data type must comply DTO class property
     *
     * @param array $data associative array
     *
     * @return mixed
     */
    public static function create(array $data): mixed
    {
        $self = new self($data);

        return $self;
    }

    /**
     * Convert instance to associative array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = $this->recursiveToArray(get_object_vars($this));

        return $data;
    }

    /**
     * Recursively convert instance to associative array
     *
     * @param array $x array to convert
     *
     * @return array
     */
    public function recursiveToArray(array $x): array
    {
        $result = [];
        foreach ($x as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->recursiveToArray($value);
            } elseif (is_object($value)) {
                if (method_exists($value, 'recursiveToArray')) {
                    $result[$key] = $value->recursiveToArray((array) $value);
                    continue;
                }
                $result[$key] = get_object_vars($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Convert instance to json
     *
     * @return bool|string
     */
    public function toJson(): bool|string
    {
        return json_encode($this);
    }

    /**
     * Map array of objects
     *
     * @param string $className map of ClassName => property_name
     * @param array  $data      map of ClassName => property_name
     *
     * @return array
     */
    private function createArrayOfObject(string $className, array $data): array
    {
        $result = [];
        foreach ($data as $d) {
            array_push($result, new $className($d));
        }

        return $result;
    }
}
