<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Validations
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.5
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Validations;

class GeneralValidation
{
    /**
     * Get header keys 
     * @param array $header
     * @param array $rules
     * @return array
     */
    public static function getHeaderFromRules(array $header, array &$rules): array
    {
        $result = [];
        foreach ($rules as $key => $value) {
            if (str_contains($value, 'required')) {
                if (!isset($header[strtolower($key)])) {
                    continue;
                }
                $result[$key] = $header[strtolower($key)][0];
            }
        }
        return $result;
    }
}