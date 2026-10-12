<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

final class Category
{
    public static function all(): array
    {
        return Database::all("SELECT * FROM categories ORDER BY name");
    }
}
