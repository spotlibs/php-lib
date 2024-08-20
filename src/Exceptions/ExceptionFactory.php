<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.2
 * @link     https://github.com/brispot
 */

declare(strict_types=1);

namespace Brispot\PhpLib\Exceptions;

use Brispot\PhpLib\Exceptions\AccessException;
use Brispot\PhpLib\Exceptions\ParameterException;
use Brispot\PhpLib\Exceptions\DataNotFoundException;
use Brispot\PhpLib\Exceptions\InvalidRuleException;
use Brispot\PhpLib\Exceptions\ThirdPartyServiceException;
use Brispot\PhpLib\Exceptions\WaitingException;
use Brispot\PhpLib\Exceptions\UnsupportedException;
use Brispot\PhpLib\Exceptions\ExceptionInterface;
use Brispot\PhpLib\Exceptions\HeaderException;
use Brispot\PhpLib\Exceptions\RuntimeException;

class ExceptionFactory
{
	public const HEADER_EXCEPTION = 'X0';
	public const ACCESS_EXCEPTION = 'X1';
	public const PARAMETER_EXCEPTION = '01';
	public const NOTFOUND_EXCEPTION = '02';
	public const INVALIDRULE_EXCEPTION = '03';
	public const THIRDPARTY_EXCEPTION = '04';
	public const WAITING_EXCEPTION = '05';
	public const UNSUPPORTED_EXCEPTION = '06';
	public const RUNTIME_EXCEPTION = '99';
	
	public static function create(
		string $responseCode, 
		?string $responseDesc = null, 
		mixed $responseData = null
	): ExceptionInterface
	{
		switch ($responseCode) {
			case self::HEADER_EXCEPTION:
				return new HeaderException($responseDesc, $responseData);
			case self::ACCESS_EXCEPTION:
				return new AccessException($responseDesc, $responseData);
			case self::PARAMETER_EXCEPTION:
				return new ParameterException($responseDesc, $responseData);
			case self::NOTFOUND_EXCEPTION:
				return new DataNotFoundException($responseDesc, $responseData);
			case self::INVALIDRULE_EXCEPTION:
				return new InvalidRuleException($responseDesc, $responseData);
			case self::THIRDPARTY_EXCEPTION:
				return new ThirdPartyServiceException($responseDesc, $responseData);
			case self::WAITING_EXCEPTION:
				return new WaitingException($responseDesc, $responseData);
			case self::UNSUPPORTED_EXCEPTION:
				return new UnsupportedException($responseDesc, $responseData);
			case self::RUNTIME_EXCEPTION:
				return new RuntimeException($responseDesc, $responseData);
			default:
				return new RuntimeException("Unknown response from host");
		}
	}
}