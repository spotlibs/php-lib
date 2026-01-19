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

/**
 * AEADEncryptResult
 *
 * Standard encryption with AEAD result type
 *
 * @category Validation
 * @package  Validations
 * @author   Hendri Nursyahbani <hendrinursyahbani@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class AEADEncryptResult
{
    public string $ciphertext;
    public string $tag;
}
