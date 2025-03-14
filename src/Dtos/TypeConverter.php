<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Dtos
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.2.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Dtos;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionProperty;

/**
 * TypeConverter
 *
 * @category Library
 * @package  Dtos
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class TypeConverter
{
    private static array $nullValues = [
        'int' => 0,
        'float' => 0,
        'string' => '',
        'bool' => false,
        'array' => []
    ];

    /**
     * Asserting type of value to match DTO property type. Generate runtime log as note for developers
     *
     * @param mixed              $value     value from array key
     * @param ReflectionClass    $reflector DTO class reflector
     * @param ReflectionProperty $prop      DTO class property reflector
     *
     * @return mixed
     */
    public static function assertType(mixed $value, ReflectionClass $reflector, ReflectionProperty $prop): mixed
    {
        $message = '[0] "Cannot assign ';
        if (get_debug_type($value) != $prop->getType()->getName() && !is_null($value)) {
            switch ($prop->getType()->getName()) {
                case 'int':
                    $message .= get_debug_type($value);
                    self::concatMessage($message, $reflector, $prop);
                    Log::channel('runtime')->error($message);
                    $value = (int) $value;
                    break;

                case 'string':
                    $message .= get_debug_type($value);
                    self::concatMessage($message, $reflector, $prop);
                    Log::channel('runtime')->error($message);
                    $value = (string) $value;
                    break;

                case 'float':
                    $message .= get_debug_type($value);
                    self::concatMessage($message, $reflector, $prop);
                    Log::channel('runtime')->error($message);
                    $value = (float) $value;
                    break;

                case 'bool':
                    if (!is_bool($value)) {
                        $message .= get_debug_type($value);
                        self::concatMessage($message, $reflector, $prop);
                        Log::channel('runtime')->error($message);
                        $value = false;
                    }
                    break;

                default:
                    if (gettype($value) == 'object') {
                        $reflector2 = new ReflectionClass($value);
                        if ($reflector2->getName() !== $prop->getType()->getName()) {
                            $message .= $reflector2->getName();
                            self::concatMessage($message, $reflector, $prop);
                            Log::channel('runtime')->error($message);
                            $propClass = $prop->getType()->getName();
                            $value = new $propClass();
                            break;
                        }
                    } elseif ($prop->getType()->getName() == 'Carbon\Carbon') {
                        $value = Carbon::parse((string) $value);
                    }
                    break;
            }
        } elseif (is_null($value) && !$prop->getType()->allowsNull()) {
            $message .= get_debug_type($value);
            self::concatMessage($message, $reflector, $prop);
            Log::channel('runtime')->error($message);
            $value = self::$nullValues[$prop->getType()->getName()];
        }
        return $value;
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
    private static function concatMessage(string &$message, ReflectionClass &$reflector, ReflectionProperty &$prop): void
    {
        $message .= ' to property ' . $reflector->getName() . '::' . $prop->getName();
        $message .= ' of type ' . $prop->getType()->getName() . '"';
        $message .= ' on line 50 of file /var/www/html/vendor/spotlibs/php-lib/src/Dtos/TraitDtos.php';
        $message .= ' [requestID:' . (app()->request->header('X-Request-ID') ?? null) . ']';
    }
}
