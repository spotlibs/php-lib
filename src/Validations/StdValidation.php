<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Validations
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Validations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * StdValidation
 *
 * Standard validation static methods
 *
 * @category Validation
 * @package  Validations
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class StdValidation
{
    /**
     * Get all required header from rules
     *
     * @param array $header request headers in associative array
     * @param array $rules  pointer of array of rules from handler
     *
     * @return array
     */
    public static function getHeaderFromRules(array $header, array &$rules): array
    {
        $result = [];
        foreach ($rules as $key => $value) {
            if (!isset($header[strtolower($key)])) {
                continue;
            }
            $result[$key] = $header[strtolower($key)][0];
        }
        return $result;
    }

    /**
     * Validate nik on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateNIK(Request $request): void
    {
        $rules = [
            'nik' => 'required|string|size:16|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Validate npwp on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateNPWP(Request $request): void
    {
        $rules = [
            'npwp' => 'required|string|min:15|max:16|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Validate tanggal_lahir on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateTanggalLahir(Request $request): void
    {
        $rules = [
            'tanggal_lahir' => 'required|date'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Validate jenis_kelamin on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateJenisKelamin(Request $request): void
    {
        $rules = [
            'jenis_kelamin' => 'required|string|in:L,P'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Validate agama on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateAgama(Request $request): void
    {
        $rules = [
            'agama' => 'required|string|in:islam,kristen,katolik,hindu,budha'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Validate no_hp on request body
     *
     * @param \Illuminate\Http\Request $request http request object
     *
     * @return void
     */
    public static function validateNoHP(Request $request): void
    {
        $rules = [
            'no_hp' => 'required|string|min:10|max:15|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }
}
