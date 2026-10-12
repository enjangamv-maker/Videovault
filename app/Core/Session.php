<?php
declare(strict_types=1);
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'domain' => '',
            'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_name('VV_SESSID');
        session_start();

        $regen = Env::int('SESSION_REGEN_INTERVAL', 900);
        if (empty($_SESSION['_init'])) {
            session_regenerate_id(true);
            $_SESSION['_init'] = true;
            $_SESSION['_last_regen'] = time();
        } elseif (($regen > 0) && (time() - ($_SESSION['_last_regen'] ?? 0) > $regen)) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = time();
        }
    }

    public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, mixed $v): void         { $_SESSION[$k] = $v; }
    public static function forget(string $k): void                { unset($_SESSION[$k]); }

    public static function flash(string $k, ?string $v = null): ?string
    {
        if ($v !== null) { $_SESSION['_flash'][$k] = $v; return null; }
        $x = $_SESSION['_flash'][$k] ?? null;
        unset($_SESSION['_flash'][$k]);
        return $x;
    }
    public static function regenerate(): void { session_regenerate_id(true); }
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
