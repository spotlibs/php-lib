<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.5.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

/**
 * Security
 *
 * Security helper
 *
 * @category Library
 * @package  Security
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Security
{
    public static function encrypt(string $plaintext): string
    {
        $plaintext = hex2bin($plaintext);
        if (!$plaintext) {
            throw new Exception("failed to convert plaintext into bin");
        }
        $ecrypted = openssl_encrypt($plaintext, "AES-128-CBC", "xxx", OPENSSL_ZERO_PADDING, "ivx");
        if (!$ecrypted) {
            throw new Exception("failed to encrypt string");
        }
        return $ecrypted;
    }
}
