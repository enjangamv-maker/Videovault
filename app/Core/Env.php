<?php
declare(strict_types=1);
namespace App\Core;

final class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (self::$loaded) return;
        if (!is_readable($file)) {
            throw new \RuntimeException("Env file tidak ditemukan: $file");
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            $v = trim($v);
            if (preg_match('/^"(.*)"$/', $v, $m)) $v = $m[1];
            elseif (preg_match("/^'(.*)'$/", $v, $m)) $v = $m[1];
            self::$vars[$k] = $v;
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$vars[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::$vars[$key] ?? null;
        if ($v === null) return $default;
        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int)(self::$vars[$key] ?? $default);
    }
}
