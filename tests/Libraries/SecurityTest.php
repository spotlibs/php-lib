<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\InvalidRuleException;
use Spotlibs\PhpLib\Libraries\Security;

class SecurityTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testEncrypt1(): void
    {
        putenv('SECURITY_KEY=123456789ABCDefg');
        $plain = 'beautiful soup';
        $encrypted = Security::encrypt($plain);
        $decrypted = Security::decrypt($encrypted);
        $this->assertEquals($plain, $decrypted);
    }

    public function testDecrypt1(): void
    {
        putenv('SECURITY_KEY=0123456789abcdef');
        $encrypted = '69687168694E496177653970746B6834383021D52B533A55ECBA5BACC753055AD59F65DD091541A32FA262B3116CFDC3';
        $decrypted = Security::decrypt($encrypted);
        $this->assertEquals('AES CBC with secure random IV', $decrypted);
    }

    public function testEncryptError(): void
    {
        $this->expectException(\Exception::class);
        putenv('SECURITY_KEY=0123456789abcd');
        $x = Security::encrypt('beautiful soup');
        putenv('SECURITY_KEY=0123456789abcd321');
        Security::decrypt($x);
    }

    public function testSanitizeFilename(): void
    {
        $tc = [
            //path traversal
            "../../../etc/passwd",
            "..\..\windows\system32\hosts",
            "....//....//etc/shadow",
            //null byte injection
            "innocent.txt\0.php",
            "image.jpg%00.exe",
            "file.pdf\x00malicious.sh",
            //reserved names
            "CON.txt",
            "PRN.pdf",
            "AUX",
            "NUL.jpg",
            "COM1.exe",
            "LPT1.doc",
            //invalid characters
            "file<script>.txt",
            "name|with|pipes.pdf",
            "file:with:colons.jpg",
            "file\"with\"quotes.txt",
            "file*with*asterisks.doc",
            "file?with?questions.pdf",
            //edge cases
            "...",
            ".",
            "..",
        ];
        foreach ($tc as $c) {
            $x = Security::sanitizeFilename($c);
            $this->assertNotEquals($c, $x);
        }
    }
}