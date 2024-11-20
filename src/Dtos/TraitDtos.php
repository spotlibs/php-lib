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
            if (is_array($value)) {
                $prop = $reflector->getProperty($key);
                $type = $prop->getType()->getName();
                //construct object if type is not array
                if ($type != 'array') {
                    $value = new $type($value);
                }
            }
            $this->{$key} = $value;
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
        return (array) $this;
    }

    /**
     * Convert instance to json
     *
     * @return bool|string
     */
    public function toJson()
    {
        $data = $this->toArray();
        return json_encode($data);
    }
}
