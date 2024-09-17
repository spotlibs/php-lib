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
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;
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
        $reflector = new ReflectionClass(static::class);
        foreach ($data as $key => $value) {
            try {
                $prop = $reflector->getProperty($key);
            } catch (Throwable) {
                continue;
            }
            if (gettype($value) != $prop->getType()->getName()) {
                $message = '[0] "Cannot assign ';
                switch ($prop->getType()->getName()) {
                    case 'integer':
                        $value = (int) $value;
                        $message .= gettype($value);
                        $this->concatMessage($message, $reflector, $prop);
                        break;

                    case 'string':
                        $value = (string) $value;
                        $message .= gettype($value);
                        $this->concatMessage($message, $reflector, $prop);
                        break;

                    case 'double':
                        $value = (double) $value;
                        $message .= gettype($value);
                        $this->concatMessage($message, $reflector, $prop);
                        break;

                    case 'float':
                        $value = (float) $value;
                        $message .= gettype($value);
                        $this->concatMessage($message, $reflector, $prop);
                        break;

                    case 'bool':
                        if (!is_bool($value)) {
                            $message .= gettype($value);
                            $this->concatMessage($message, $reflector, $prop);
                            $value = false;
                        }
                        break;

                    default:
                        if (gettype($value) == 'object') {
                            $reflector2 = new ReflectionClass($value);
                            if ($reflector2->getName() !== $prop->getType()->getName()) {
                                $message .= $reflector2->getName();
                                $this->concatMessage($message, $reflector, $prop);
                                break;
                            }
                        } elseif ($prop->getType()->getName() == 'Carbon\Carbon') {
                            $value = Carbon::parse((string) $value);
                        } elseif (is_null($value) && $prop->getType()->allowsNull()) {
                            break;
                        }
                        break;
                }
                $message .= ' on line 39 of file /var/www/html/vendor/spotlibs/php-lib/src/Dtos/TraitDtos.php';
                $message .= ' [requestID:' . (app()->request->header('X-Request-ID') ?? null) . ']';
                Log::channel('runtime')->error($message);
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

    /**
     * Concatenate runtime error message string
     *
     * @param string             $message   pointer of message
     * @param ReflectionClass    $reflector pointer of reflection class
     * @param ReflectionProperty $prop      pointer of reflection property
     *
     * @return void
     */
    private function concatMessage(string &$message, ReflectionClass &$reflector, ReflectionProperty &$prop): void
    {
        $message .= ' to property ' . $reflector->getName() . '::' . $prop->getName();
        $message .= ' of type ' . $prop->getType()->getName() . '"';
    }
}
