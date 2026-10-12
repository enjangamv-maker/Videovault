<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Database;

final class User
{
    public static function all(): array
    {
        return Database::all("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    }
}
