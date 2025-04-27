<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Responses
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.2
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Responses;

use Illuminate\Http\Response;
use Spotlibs\PhpLib\Exceptions\ExceptionInterface;
use Spotlibs\PhpLib\Exceptions\ParameterException;

/**
 * StdResponse
 *
 * @category Library
 * @package  Responses
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class StdResponse
{
    /**
     * Create success http response
     *
     * @param string $responseDesc description of response
     * @param mixed  $responseData data of response, nullable
     *
     * @return \Illuminate\Http\Response
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

        return new Response($result, 200);
    }

    /**
     * Create failure http response
     *
     * @param \Spotlibs\PhpLib\Exceptions\ExceptionInterface $exception throwed exception
     *
     * @return \Illuminate\Http\Response
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
        if ($exception instanceof ParameterException) {
            $result['responseValidation'] = $exception->getValidationErrors();
        }

        return new Response($result, $exception->getHttpCode());
    }
}
