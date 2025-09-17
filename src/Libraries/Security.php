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

    /**
     * Sanitize filename if it is user-controlled data
     *
     * Example: Security::sanitizeFilename($request->filename);
     *
     * @param string $filename string to decrypt
     *
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // First decode any URL encoding to catch encoded malicious characters
        $filename = urldecode($filename);

        // Remove path components
        $filename = basename($filename);

        // Remove or replace dangerous characters (including null bytes)
        $filename = preg_replace('/[<>:"|?*\x00-\x1F\x7F]/', '', $filename);

        // Remove leading/trailing whitespace and dots
        $filename = trim($filename, " \t\n\r\0\x0B.");

        // Prevent reserved names (Windows)
        $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4',
                 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2',
                 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];

        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameWithoutExt), $reserved)) {
            $filename = 'file_' . $filename;
        }

        // Ensure filename isn't empty
        if (empty($filename)) {
            $filename = 'unnamed_file';
        }

        return $filename;
    }
}
