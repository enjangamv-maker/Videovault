<?php
declare(strict_types=1);
namespace App\Support;

final class Str
{
    public static function slug(string $s): string
    {
        $s = preg_replace('~[^\pL\d]+~u', '-', $s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
        $s = preg_replace('~[^-\w]+~', '', $s);
        $s = trim($s, '-');
        $s = preg_replace('~-+~', '-', $s);
        return strtolower($s ?: 'n-a');
    }

    public static function mask(string $s, int $show = 4): string
    {
        $len = strlen($s);
        if ($len <= $show) return str_repeat('•', $len);
        return str_repeat('•', $len - $show) . substr($s, -$show);
    }
}
