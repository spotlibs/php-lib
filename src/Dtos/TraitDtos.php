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
        $reflector = new ReflectionClass(static::class);
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (is_array($value)) {
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
                $this->{$key} = $value;
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
        $data = json_encode($this);

        return json_decode($data, true);
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
