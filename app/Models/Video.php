<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

final class Video
{
    public static function recent(int $limit = 20): array
    {
        return Database::all(
            "SELECT v.*, c.name AS category_name
             FROM videos v LEFT JOIN categories c ON v.category_id = c.id
             ORDER BY v.created_at DESC LIMIT $limit"
        );
    }
}
