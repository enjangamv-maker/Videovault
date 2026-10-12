<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public function handle(): void
    {
        if (Request::method() !== 'POST') return;
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            Response::abort(419, 'Sesi kadaluarsa. Silakan refresh halaman.');
        }
    }
}
