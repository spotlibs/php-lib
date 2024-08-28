<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.2
 * @link     https://github.com/spotlibs
 */

declare(strict_types= 1);

namespace Spotlibs\PhpLib\Responses;

use Spotlibs\PhpLib\Exceptions\ExceptionInterface;
use Illuminate\Http\Response;

class StdResponse
{
    public static array $headers = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'Server'=> 'BRISPOT',
        'X-Powered-By' => 'BRISPOT'
    ];
    /**
     * Create success http response
     *
     * @param  string  $responseDesc
     * @param  mixed  $responseData nullable
     * @return  \Illuminate\Http\Response
     */
    public static function success(string $responseDesc, mixed $responseData = null): Response
    {
        $result = [
            'responseCode' => '00',
            'responseDesc' => $responseDesc
        ];
        if ($responseData !== null) {
            $result['responseData'] = $responseData;
        }
        return new Response($result, 200, self::$headers);
    }
    
    /**
     * Create failure http response
     *
     * @param  \Spotlibs\PhpLib\Exceptions\ExceptionInterface $exception
     * @return  \Illuminate\Http\Response
     */
    public static function error(ExceptionInterface $exception): Response
    {
        $result = [
            'responseCode' => $exception->getErrorCode(),
            'responseDesc' => $exception->getErrorMessage()
        ];
        if ($exception->getData() !== null) {
            $result['responseData'] = $exception->getData();
        }

        return new Response($result, $exception->getHttpCode(), self::$headers);
    }
}