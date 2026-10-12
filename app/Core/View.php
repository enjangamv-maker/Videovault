<?php
declare(strict_types=1);
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewFile = BASE_PATH . '/views/' . str_replace('.', '/', $view) . '.php';
        $layoutFile = BASE_PATH . '/views/layouts/' . $layout . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View tidak ditemukan: $view");
        }

        extract($data, EXTR_SKIP);
        $content = (function () use ($viewFile, $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $viewFile;
            return ob_get_clean();
        })();

        if ($layout && is_file($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function url(string $path = ''): string
    {
        return rtrim(Env::get('APP_URL', ''), '/') . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}
