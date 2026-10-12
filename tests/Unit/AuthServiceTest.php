<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Core\Database;
use App\Services\AuthService;
use Tests\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testRegisterCreatesUser(): void
    {
        $auth = new AuthService();
        $id = $auth->register('newuser_' . uniqid(), 'new_' . uniqid() . '@test.local', 'StrongPass123!');
        $this->assertGreaterThan(0, $id);
    }

    public function testRegisterRejectsDuplicateUsername(): void
    {
        $auth = new AuthService();
        $u = 'dupuser_' . uniqid();
        $auth->register($u, 'a_' . uniqid() . '@test.local', 'StrongPass123!');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Username atau email sudah terdaftar');
        $auth->register($u, 'b_' . uniqid() . '@test.local', 'StrongPass123!');
    }

    public function testRegisterRejectsWeakPassword(): void
    {
        $auth = new AuthService();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Password minimal');
        $auth->register('weak_' . uniqid(), 'w_' . uniqid() . '@test.local', 'short');
    }

    public function testRegisterRejectsCommonPassword(): void
    {
        $auth = new AuthService();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('terlalu umum');
        $auth->register('common_' . uniqid(), 'c_' . uniqid() . '@test.local', 'password123');
    }

    public function testLoginWithValidCredentials(): void
    {
        $username = 'logintest_' . uniqid();
        Database::insert('users', [
            'username' => $username,
            'email'    => $username . '@test.local',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'role'     => 'user',
        ]);
        $auth = new AuthService();
        $this->assertTrue($auth->attempt($username, 'password123'));
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        $username = 'wrongpw_' . uniqid();
        Database::insert('users', [
            'username' => $username,
            'email'    => $username . '@test.local',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'role'     => 'user',
        ]);
        $auth = new AuthService();
        $this->assertFalse($auth->attempt($username, 'wrongpassword'));
    }

    public function testLoginRateLimitAfter5Attempts(): void
    {
        $username = 'ratelimited_' . uniqid();
        Database::insert('users', [
            'username' => $username,
            'email'    => $username . '@test.local',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'role'     => 'user',
        ]);
        $auth = new AuthService();
        for ($i = 0; $i < 5; $i++) {
            try { $auth->attempt($username, 'wrong'); } catch (\Throwable $e) {}
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Terlalu banyak percobaan');
        $auth->attempt($username, 'wrong');
    }
}
