<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Core\RateLimiter;
use Tests\TestCase;

final class RateLimiterTest extends TestCase
{
    public function testFirstHitAllowed(): void
    {
        $key = 'test_' . uniqid();
        $r = RateLimiter::hit($key, 5, 60);
        $this->assertTrue($r['allowed']);
        $this->assertEquals(1, $r['count']);
        $this->assertEquals(4, $r['remaining']);
    }

    public function testHitExceedsMaxBlocked(): void
    {
        $key = 'test_' . uniqid();
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 5, 60);
        }
        $r = RateLimiter::hit($key, 5, 60);
        $this->assertFalse($r['allowed']);
        $this->assertEquals(0, $r['remaining']);
    }

    public function testClearResetsCounter(): void
    {
        $key = 'test_' . uniqid();
        RateLimiter::hit($key, 5, 60);
        RateLimiter::clear($key);
        $r = RateLimiter::hit($key, 5, 60);
        $this->assertEquals(1, $r['count']);
    }

    public function testDifferentKeySeparateCounters(): void
    {
        $k1 = 'test_a_' . uniqid();
        $k2 = 'test_b_' . uniqid();
        RateLimiter::hit($k1, 5, 60);
        RateLimiter::hit($k1, 5, 60);
        $r2 = RateLimiter::hit($k2, 5, 60);
        $this->assertEquals(1, $r2['count']);
    }
}
