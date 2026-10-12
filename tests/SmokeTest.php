<?php
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testBootstrapLoads(): void { $this->assertTrue(defined('BASE_PATH')); }

    public function testRateLimiter(): void
    {
        $r = \App\Core\RateLimiter::hit('test_' . uniqid(), 3, 60);
        $this->assertTrue($r['allowed']);
    }
}
