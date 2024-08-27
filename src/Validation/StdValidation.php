<?php

declare(strict_types=1);

namespace Spotlibs\PhpLib\Validation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StdValidation
{
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
    
    public static function validateNIK(Request $request): void
    {
        $rules = [
            'nik' => 'required|string|size:16|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    public static function validateNPWP(Request $request): void
    {
        $rules = [
            'npwp' => 'required|string|min:15|max:16|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    public static function validateTanggalLahir(Request $request): void
    {
        $rules = [
            'tanggal_lahir' => 'required|date'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    public static function validateJenisKelamin(Request $request): void
    {
        $rules = [
            'jenis_kelamin' => 'required|string|in:L,P'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    public static function validateAgama(Request $request): void
    {
    	$rules = [
            'agama' => 'required|string|in:islam,kristen,katolik,hindu,budha'
        ];
        Validator::make($request->all(), $rules)->validate();
    }

    public static function validateNoHP(Request $request): void
    {
        $rules = [
            'no_hp' => 'required|string|min:10|max:15|regex:/^[0-9]+$/'
        ];
        Validator::make($request->all(), $rules)->validate();
    }
}