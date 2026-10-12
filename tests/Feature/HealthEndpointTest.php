<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Core\Database;
use Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function testDatabaseIsReachable(): void
    {
        $this->assertEquals(1, Database::scalar('SELECT 1'));
    }

    public function testUserTableExists(): void
    {
        $tables = Database::all('SHOW TABLES');
        $names = array_map(fn($r) => array_values($r)[0], $tables);
        $this->assertContains('users', $names);
        $this->assertContains('videos', $names);
        $this->assertContains('categories', $names);
    }

    public function testInsertAndRetrieve(): void
    {
        $id = $this->createUser('integration');
        $user = Database::one('SELECT username FROM users WHERE id = :id', ['id' => $id]);
        $this->assertStringStartsWith('integration', $user['username']);
    }
}
