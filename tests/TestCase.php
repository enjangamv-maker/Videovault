<?php
declare(strict_types=1);
namespace Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $dbName = \App\Core\Env::get('DB_NAME');
        if ($dbName !== 'videovault_test') {
            throw new \RuntimeException("Test TIDAK pakai test DB! DB_NAME=$dbName");
        }

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    protected function cleanDatabase(): void
    {
        $pdo = Database::conn();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['videos', 'categories', 'users'] as $t) {
            $pdo->exec("TRUNCATE TABLE `$t`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function createUser(string $username = 'testuser', string $role = 'user'): int
    {
        $username = $username . '_' . substr(uniqid(), -6);
        return Database::insert('users', [
            'username' => $username,
            'email'    => $username . '@test.local',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'role'     => $role,
        ]);
    }

    protected function createCategory(string $name = 'TestCat'): int
    {
        $name = $name . '_' . substr(uniqid(), -6);
        return Database::insert('categories', [
            'name' => $name,
            'slug' => strtolower($name),
        ]);
    }

    protected function createVideo(array $overrides = []): int
    {
        $catId  = $overrides['category_id'] ?? $this->createCategory();
        $userId = $overrides['uploaded_by'] ?? $this->createUser();

        return Database::insert('videos', array_merge([
            'title'       => 'Test Video ' . uniqid(),
            'description' => 'Lorem ipsum',
            'category_id' => $catId,
            'video_file'  => 'test/' . uniqid() . '.mp4',
            'duration'    => 120,
            'uploaded_by' => $userId,
        ], $overrides));
    }
}
