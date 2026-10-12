<?php
declare(strict_types=1);
namespace App\Core;

final class Response
{
    public static function redirect(string $path, int $code = 302): never
    {
        $url = str_starts_with($path, 'http') ? $path : Env::get('APP_URL') . '/' . ltrim($path, '/');
        header('Location: ' . $url, true, $code);
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        echo $message ?: match($code) {
            404 => 'Not Found',
            403 => 'Forbidden',
            419 => 'Session Expired',
            default => 'Error',
        };
        exit;
    }
}
