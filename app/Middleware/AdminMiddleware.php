<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Response;

final class AdminMiddleware
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) Response::redirect('login');
        if (($_SESSION['user_role'] ?? '') !== 'admin') Response::abort(403, 'Akses ditolak.');
    }
}
