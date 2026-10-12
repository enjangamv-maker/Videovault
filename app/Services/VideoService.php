<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Exceptions\NotFoundException;

final class VideoService
{
    public function paginate(?string $q, ?int $categoryId, int $page = 1, int $perPage = 12): array
    {
        $where = [];
        $whereParams = [];
        $selectParams = [];
        $mode = 'browse';

        if ($q !== null && mb_strlen(trim($q)) >= 3) {
            $boolean = $this->buildBooleanQuery($q);
            $where[] = "MATCH(v.title, v.description) AGAINST (:ftq IN BOOLEAN MODE)";
            $whereParams['ftq'] = $boolean;
            $mode = 'fulltext';
        } elseif ($q !== null && trim($q) !== '') {
            $where[] = "(v.title LIKE :ql1 OR v.description LIKE :ql2)";
            $whereParams['ql1'] = '%' . $q . '%';
            $whereParams['ql2'] = '%' . $q . '%';
            $mode = 'like';
        }

        if ($categoryId) {
            $where[] = "v.category_id = :cat";
            $whereParams['cat'] = $categoryId;
        }

        $ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)Database::scalar("SELECT COUNT(*) FROM videos v $ws", $whereParams);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        if ($mode === 'fulltext') {
            $selectParams = $whereParams;
            $selectParams['ftq2'] = $whereParams['ftq'];
            $orderBy = "MATCH(v.title, v.description) AGAINST (:ftq2 IN BOOLEAN MODE) DESC, v.created_at DESC";
        } else {
            $selectParams = $whereParams;
            $orderBy = "v.created_at DESC";
        }

        $videos = Database::all(
            "SELECT v.*, c.name AS category_name
             FROM videos v
             LEFT JOIN categories c ON v.category_id = c.id
             $ws
             ORDER BY $orderBy
             LIMIT $perPage OFFSET $offset",
            $selectParams
        );

        return [
            'items' => $videos, 'total' => $total,
            'page' => $page, 'pages' => $pages,
            'mode' => $mode, 'query' => $q,
        ];
    }

    private function buildBooleanQuery(string $q): string
    {
        $q = trim($q);
        $parts = [];
        if (preg_match_all('/"([^"]+)"/', $q, $phrases)) {
            foreach ($phrases[1] as $p) $parts[] = '"' . $this->sanitize($p) . '"';
            $q = preg_replace('/"[^"]+"/', '', $q);
        }
        $words = preg_split('/\s+/', $q) ?: [];
        foreach ($words as $w) {
            $w = $this->sanitize($w);
            if (mb_strlen($w) < 3) continue;
            $parts[] = '+' . $w . '*';
        }
        return implode(' ', $parts) ?: '""';
    }

    private function sanitize(string $s): string
    {
        return preg_replace('/[+\-><\(\)~*\"@]+/', ' ', $s);
    }

    public function find(int $id): array
    {
        $v = Database::one(
            "SELECT v.*, c.name AS category_name, u.username AS uploader
             FROM videos v
             LEFT JOIN categories c ON v.category_id = c.id
             LEFT JOIN users u ON v.uploaded_by = u.id
             WHERE v.id = :id",
            ['id' => $id]
        );
        if (!$v) throw new NotFoundException('Video tidak ditemukan.');
        return $v;
    }

    public function incrementViews(int $id): void
    {
        $seen = $_SESSION['_viewed'] ?? [];
        if (in_array($id, $seen, true)) return;
        $seen[] = $id;
        $_SESSION['_viewed'] = array_slice($seen, -50);
        Database::query("UPDATE videos SET views = views + 1 WHERE id = :id", ['id' => $id]);
    }

    public function create(array $data): int
    {
        $id = Database::insert('videos', $data);
        Audit::log('video_created', ['id' => $id, 'title' => $data['title'] ?? '']);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        if (!$data) return;
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        Database::query("UPDATE videos SET $sets WHERE id = :id", array_merge($data, ['id' => $id]));
        Audit::log('video_updated', ['id' => $id, 'fields' => array_keys($data)]);
    }

    public function delete(int $id): void
    {
        Database::query("DELETE FROM videos WHERE id = :id", ['id' => $id]);
        Audit::log('video_deleted', ['id' => $id]);
    }

    public function stats(): array
    {
        return [
            'videos'     => (int)Database::scalar("SELECT COUNT(*) FROM videos"),
            'users'      => (int)Database::scalar("SELECT COUNT(*) FROM users"),
            'categories' => (int)Database::scalar("SELECT COUNT(*) FROM categories"),
            'views'      => (int)Database::scalar("SELECT COALESCE(SUM(views), 0) FROM videos"),
        ];
    }

    public function recentAdmin(int $page = 1, int $perPage = 20, ?string $q = null): array
    {
        $where = []; $params = [];
        if ($q) {
            if (mb_strlen(trim($q)) >= 3) {
                $where[] = "MATCH(v.title, v.description) AGAINST (:ftq IN BOOLEAN MODE)";
                $params['ftq'] = $this->buildBooleanQuery($q);
            } else {
                $where[] = "(v.title LIKE :q1 OR v.description LIKE :q2)";
                $params['q1'] = '%' . $q . '%'; $params['q2'] = '%' . $q . '%';
            }
        }
        $ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int)Database::scalar("SELECT COUNT(*) FROM videos v $ws", $params);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;
        $items = Database::all(
            "SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id
             $ws ORDER BY v.created_at DESC LIMIT $perPage OFFSET $offset", $params
        );
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'q' => $q];
    }

    public function suggestSimilar(string $q, int $limit = 5): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 3) return [];
        $first = mb_substr($q, 0, max(3, (int)(mb_strlen($q) * 0.6)));
        $words = Database::all(
            "SELECT DISTINCT title FROM videos WHERE title LIKE :q ORDER BY LENGTH(title) ASC LIMIT :lim",
            ['q' => $first . '%', 'lim' => $limit]
        );
        return array_column($words, 'title');
    }
}
