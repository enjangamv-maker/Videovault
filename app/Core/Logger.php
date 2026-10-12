<?php
declare(strict_types=1);
namespace App\Core;

final class Logger
{
    private static string $dir = '';

    public static function init(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
        if (!is_dir(self::$dir)) @mkdir(self::$dir, 0775, true);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d');
        $file = self::$dir . "/app-$date.log";
        $ts = date('Y-m-d H:i:s');
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = "[$ts] [$level] $message$ctx" . PHP_EOL;
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $m, array $c = []): void  { self::log('INFO', $m, $c); }
    public static function warn(string $m, array $c = []): void  { self::log('WARN', $m, $c); }
    public static function error(string $m, array $c = []): void { self::log('ERROR', $m, $c); }
}
