<?php
declare(strict_types=1);
namespace App\Core;

final class RateLimiter
{
    public static function hit(string $key, int $max = 5, int $window = 900): array
    {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        $dir = BASE_PATH . '/storage/ratelimit';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . '/' . $key . '.json';
        $now = time();
        $data = ['count' => 0, 'reset' => $now + $window];

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $d = $raw ? json_decode($raw, true) : null;
            if (is_array($d) && ($d['reset'] ?? 0) > $now) $data = $d;
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);

        return [
            'allowed'   => $data['count'] <= $max,
            'remaining' => max(0, $max - $data['count']),
            'reset'     => $data['reset'],
            'count'     => $data['count'],
        ];
    }

    public static function clear(string $key): void
    {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        @unlink(BASE_PATH . '/storage/ratelimit/' . $key . '.json');
    }

    public static function ipKey(string $prefix): string
    {
        return $prefix . '_' . md5(($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
