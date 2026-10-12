<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Core\Response;

final class AuthMiddleware
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            Response::redirect('login');
        }
    }
}
