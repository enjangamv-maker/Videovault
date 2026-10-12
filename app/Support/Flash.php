<?php
declare(strict_types=1);
namespace App\Support;

use App\Core\Session;

final class Flash
{
    public static function ok(string $m): void    { Session::flash('success', $m); }
    public static function err(string $m): void   { Session::flash('error', $m); }
    public static function info(string $m): void  { Session::flash('info', $m); }
    public static function warn(string $m): void  { Session::flash('warning', $m); }
}
