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
    /**
     * Encrypting sensitive string data
     *
     * @param string $plaintext string to encrypt
     *
     * @throws \Exception
     * @return bool|string
     */
    public static function encrypt(string $plaintext): string
    {
        $charset = array_merge(
            range('0', '9'),
            range('a', 'z'),
            range('A', 'Z'),
        );
        $ivArr = [];
        for ($i = 0; $i < 16; $i++) {
            $ivArr[] = $charset[random_int(0, 61)];
        }
        $iv = implode('', $ivArr);
        $ecrypted = openssl_encrypt($plaintext, "AES-128-CBC", env('SECURITY_KEY'), OPENSSL_RAW_DATA, $iv);
        if (!$ecrypted) {
            throw new \Exception("failed to encrypt string");
        }
        
        return strtoupper(bin2hex($iv . $ecrypted));
    }

    /**
     * Decrypt encrypted string
     *
     * @param string $encrypted string to decrypt
     *
     * @throws \Exception
     * @return bool|string
     */
    public static function decrypt(string $encrypted): string
    {

        $ivHex = substr($encrypted, 0, 32);
        $iv = hex2bin($ivHex);
        $encrypted  = substr($encrypted, 32);
        $decrypted = openssl_decrypt(hex2bin($encrypted), "AES-128-CBC", env('SECURITY_KEY'), OPENSSL_RAW_DATA, $iv);
        if (!$decrypted) {
            throw new \Exception("failed to decrypt string");
        }
        return $decrypted;
    }
}
