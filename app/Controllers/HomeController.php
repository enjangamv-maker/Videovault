<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Category;
use App\Services\VideoService;

final class HomeController
{
    public function index(): void
    {
        $vs = new VideoService();
        $q = Request::str('q');
        $cat = Request::int('category') ?: null;
        $page = Request::int('page', 1) ?: 1;

        $result = $vs->paginate($q ?: null, $cat, $page);
        $categories = Category::all();

        // Saran kalau 0 hasil
        $suggestions = [];
        if ($result['total'] === 0 && $q !== '') {
            $suggestions = $vs->suggestSimilar($q);
        }

        View::render('home.index', [
            'pageTitle'  => $q ? "Cari: $q" : 'Katalog Video',
            'videos'     => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'search'     => $q,
            'categoryId' => $cat,
            'categories' => $categories,
            'mode'       => $result['mode'] ?? 'browse',
            'suggestions' => $suggestions,
        ]);
    }
}
