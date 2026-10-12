<?php
declare(strict_types=1);
namespace App\Core;

final class Csrf
{
    private const TTL = 3600;

    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || (time() - ($_SESSION['_csrf_time'] ?? 0)) > self::TTL) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_time'] = time();
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(?string $token): bool
    {
        if (empty($_SESSION['_csrf']) || !$token) return false;
        if ((time() - ($_SESSION['_csrf_time'] ?? 0)) > self::TTL) return false;
        return hash_equals($_SESSION['_csrf'], $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }
}
