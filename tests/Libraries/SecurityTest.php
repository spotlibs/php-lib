<?php

declare(strict_types=1);

namespace Tests\Libraries;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Libraries\Security;

class SecurityTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    public function testEncrypt1(): void
    {
        $plain = 'beautiful soup';
        $encrypted = Security::encrypt($plain);
        $decrypted = Security::decrypt($encrypted);
        $this->assertEquals($plain, $decrypted);
    }
}