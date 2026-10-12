<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Core\Csrf;
use Tests\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenIsGenerated(): void
    {
        $token = Csrf::token();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testTokenIsStableWithinSession(): void
    {
        $t1 = Csrf::token();
        $t2 = Csrf::token();
        $this->assertEquals($t1, $t2);
    }

    public function testVerifyAcceptsValidToken(): void
    {
        $token = Csrf::token();
        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsInvalidToken(): void
    {
        Csrf::token();
        $this->assertFalse(Csrf::verify('fake_token_123'));
    }

    public function testVerifyRejectsNullToken(): void
    {
        Csrf::token();
        $this->assertFalse(Csrf::verify(null));
    }
}
