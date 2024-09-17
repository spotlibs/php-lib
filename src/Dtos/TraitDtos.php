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

use Carbon\Carbon;
use Throwable;

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
        $reflector = new \ReflectionClass(static::class);
        foreach ($data as $key => $value) {
            try {
                $prop = $reflector->getProperty($key);
            } catch (Throwable) {
                continue;
            }
            if (gettype($value) != $prop->getType()->getName()) {
                switch ($prop->getType()) {
                    case 'integer':
                        $value = (int) $value;
                        break;

                    case 'string':
                        $value = (string) $value;
                        break;

                    case 'double':
                        $value = (double) $value;
                        break;

                    case 'float':
                        $value = (float) $value;
                        break;

                    case 'bool':
                        if (!is_bool($value)) {
                            $value = false;
                        }
                        break;

                    default:
                        if (gettype($value) == 'object') {
                            $reflector2 = new \ReflectionClass($value);
                            if ($reflector2->getName() == $prop->getType()) {
                                break;
                            }
                        } elseif ($prop->getType()->getName() == 'Carbon\Carbon') {
                            $value = Carbon::parse((string) $value);
                            break;
                        }
                        echo $key . " " . gettype($value) . " <> ";
                        echo $prop->getType() . "<br>";
                        break;
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
