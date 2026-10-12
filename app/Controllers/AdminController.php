<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Services\AuthService;
use App\Services\VideoService;

final class AdminController
{
    public function dashboard(): void
    {
        $vs = new VideoService();
        $page = Request::int('page', 1) ?: 1;
        $q = Request::str('q');
        $list = $vs->recentAdmin($page, 20, $q ?: null);

        View::render('admin.dashboard', [
            'pageTitle' => 'Dashboard',
            'stats'     => $vs->stats(),
            'videos'    => $list['items'],
            'page'      => $list['page'],
            'pages'     => $list['pages'],
            'total'     => $list['total'],
            'q'         => $list['q'],
            'user'      => (new AuthService())->user(),
        ]);
    }

    public function health(): void
    {
        header('Content-Type: application/json');
        try {
            $db = \App\Core\Database::scalar('SELECT 1');
            echo json_encode([
                'status' => 'ok',
                'time'   => date('c'),
                'db'     => $db === 1 ? 'connected' : 'error',
                'app'    => 'videovault',
                'ver'    => '2.1.0',
            ]);
        } catch (\Throwable $e) {
            http_response_code(503);
            echo json_encode(['status' => 'fail', 'error' => 'db_unreachable']);
        }
    }

    public function privacy(): void
    {
        View::render('pages.privacy', ['pageTitle' => 'Kebijakan Privasi']);
    }

    public function terms(): void
    {
        View::render('pages.terms', ['pageTitle' => 'Ketentuan Layanan']);
    }
}
