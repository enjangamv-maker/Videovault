<?php
declare(strict_types=1);
namespace App\Core;

final class App
{
    public static function boot(): void
    {
        self::securityHeaders();
        Session::start();
        self::registerErrorHandler();
    }

    private static function securityHeaders(): void
    {
        if (headers_sent()) return;
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

        if (Env::bool('FORCE_HTTPS', false)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    private static function registerErrorHandler(): void
    {
        set_exception_handler(function (\Throwable $e) {
            $traceId = bin2hex(random_bytes(6));
            Logger::error($e->getMessage(), [
                'trace_id' => $traceId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            $debug = Env::bool('APP_DEBUG', false);
            if (Request::isAjax()) {
                Response::json(['error' => 'internal', 'trace_id' => $traceId], 500);
            }
            Response::abort(500, $debug
                ? '<pre>' . htmlspecialchars((string)$e) . '</pre>'
                : "Terjadi kesalahan internal. Kode: $traceId");
        });
    }
}
