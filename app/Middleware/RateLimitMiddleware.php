<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Session;

final class RateLimitMiddleware
{
    public function handle(): void
    {
        $key = '_rl_' . md5($_SERVER['REQUEST_URI'] ?? '');
        $bucket = Session::get($key, ['count' => 0, 'reset' => time() + 60]);
        if ($bucket['reset'] < time()) {
            $bucket = ['count' => 0, 'reset' => time() + 60];
        }
        $bucket['count']++;
        Session::set($key, $bucket);
        if ($bucket['count'] > 60) {
            http_response_code(429);
            exit('Terlalu banyak permintaan. Coba lagi sebentar.');
        }
    }
}
