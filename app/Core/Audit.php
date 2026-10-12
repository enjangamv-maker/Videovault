<?php
declare(strict_types=1);
namespace App\Core;

final class Audit
{
    public static function log(string $action, array $meta = []): void
    {
        $entry = [
            'ts'     => date('c'),
            'action' => $action,
            'user'   => Session::get('user_name') ?? 'guest',
            'uid'    => Session::get('user_id'),
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            'meta'   => $meta,
        ];
        $dir = BASE_PATH . '/storage/audit';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . '/' . date('Y-m-d') . '.jsonl';
        @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
