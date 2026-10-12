<?php
declare(strict_types=1);
namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
    public static function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($base && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));
        return '/' . trim($uri, '/');
    }
    public static function get(string $k, mixed $d = null): mixed { return $_GET[$k] ?? $d; }
    public static function post(string $k, mixed $d = null): mixed { return $_POST[$k] ?? $d; }
    public static function input(string $k, mixed $d = null): mixed { return $_POST[$k] ?? $_GET[$k] ?? $d; }
    public static function str(string $k, string $d = ''): string
    {
        $v = self::input($k, $d);
        return is_string($v) ? trim($v) : $d;
    }
    public static function int(string $k, int $d = 0): int { return (int)self::input($k, $d); }
    public static function file(string $k): ?array { return $_FILES[$k] ?? null; }
    public static function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public static function ua(): string { return $_SERVER['HTTP_USER_AGENT'] ?? ''; }
    public static function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }
}
