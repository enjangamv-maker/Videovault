<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Env;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Session;

final class AuthService
{
    public function attempt(string $login, string $password): bool
    {
        $rlKey = RateLimiter::ipKey('login_' . strtolower($login));
        $rl = RateLimiter::hit($rlKey, Env::int('LOGIN_MAX_ATTEMPTS', 5), Env::int('LOGIN_LOCKOUT_TIME', 900));

        if (!$rl['allowed']) {
            $mins = (int)ceil(($rl['reset'] - time()) / 60);
            throw new \RuntimeException("Terlalu banyak percobaan. Coba lagi dalam $mins menit.");
        }

        $user = Database::one(
            "SELECT id, username, email, password, role, email_verified_at FROM users WHERE username = :u OR email = :e LIMIT 1",
            ['u' => $login, 'e' => $login]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            Logger::warn('Login gagal', ['login' => $login, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            Audit::log('login_failed', ['login' => $login]);
            return false;
        }

        // Cek verifikasi email (kalau diaktifkan)
        if (Env::bool('ENABLE_EMAIL_VERIFICATION', false) && empty($user['email_verified_at'])) {
            throw new \RuntimeException('Email belum diverifikasi. Cek inbox Anda atau kirim ulang.');
        }

        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $new = password_hash($password, PASSWORD_ARGON2ID);
            Database::query("UPDATE users SET password = :p WHERE id = :id", ['p' => $new, 'id' => $user['id']]);
        }

        RateLimiter::clear($rlKey);
        Session::regenerate();
        Session::set('user_id', (int)$user['id']);
        Session::set('user_name', $user['username']);
        Session::set('user_role', $user['role']);
        Session::set('_last_regen', time());

        Logger::info('Login berhasil', ['user' => $user['username']]);
        Audit::log('login_success', ['user' => $user['username']]);
        return true;
    }

    public function logout(): void
    {
        Audit::log('logout');
        Session::destroy();
    }

    public function user(): ?array
    {
        $id = Session::get('user_id');
        if (!$id) return null;
        return Database::one("SELECT id, username, email, role FROM users WHERE id = :id", ['id' => $id]);
    }

    public function check(): bool   { return Session::get('user_id') !== null; }
    public function isAdmin(): bool { return Session::get('user_role') === 'admin'; }
    public function id(): ?int      { $id = Session::get('user_id'); return $id ? (int)$id : null; }
    public function name(): ?string { return Session::get('user_name'); }

    /**
     * Register — kirim email verifikasi kalau diaktifkan
     */
    public function register(string $username, string $email, string $password): int
    {
        $min = Env::int('PASSWORD_MIN_LENGTH', 10);
        if (strlen($password) < $min) throw new \RuntimeException("Password minimal $min karakter.");

        $weak = ['password','password123','123456789','qwerty12345','admin12345'];
        if (in_array(strtolower($password), $weak, true)) {
            throw new \RuntimeException('Password terlalu umum.');
        }

        $exists = Database::one(
            "SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1",
            ['u' => $username, 'e' => $email]
        );
        if ($exists) throw new \RuntimeException('Username atau email sudah terdaftar.');

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $verify = Env::bool('ENABLE_EMAIL_VERIFICATION', false);

        $data = [
            'username' => $username,
            'email'    => $email,
            'password' => $hash,
            'role'     => 'user',
        ];

        // Generate token verifikasi kalau fitur aktif
        $plainToken = null;
        if ($verify) {
            $plainToken = bin2hex(random_bytes(32));
            $data['verification_token'] = hash('sha256', $plainToken);
            $data['verification_sent_at'] = date('Y-m-d H:i:s');
            $data['email_verified_at'] = null;
        } else {
            $data['email_verified_at'] = date('Y-m-d H:i:s');
        }

        $id = Database::insert('users', $data);
        Logger::info('User registered', ['id' => $id, 'username' => $username, 'verify' => $verify]);
        Audit::log('user_registered', ['id' => $id, 'username' => $username]);

        // Kirim email verifikasi
        if ($verify && $plainToken) {
            $this->sendVerificationEmail($id, $plainToken);
        }

        return $id;
    }

    /**
     * Kirim email verifikasi
     */
    public function sendVerificationEmail(int $userId, ?string $plainToken = null): void
    {
        $user = Database::one("SELECT id, username, email FROM users WHERE id = :id", ['id' => $userId]);
        if (!$user) return;

        if ($plainToken === null) {
            // Generate baru (untuk resend)
            $plainToken = bin2hex(random_bytes(32));
            Database::query(
                "UPDATE users SET verification_token = :t, verification_sent_at = NOW() WHERE id = :id",
                ['t' => hash('sha256', $plainToken), 'id' => $userId]
            );
        }

        $url = rtrim(Env::get('APP_URL', ''), '/') . '/verify-email?token=' . $plainToken . '&id=' . $userId;
        $mailer = new MailerService();
        $html = $mailer->verificationEmail($user['username'], $url);
        $mailer->send($user['email'], 'Verifikasi Email — VideoVault', $html);

        Logger::info('Verification email sent', ['user_id' => $userId, 'email' => $user['email']]);
    }

    /**
     * Verifikasi email via token
     */
    public function verifyEmail(int $userId, string $token): bool
    {
        $hash = hash('sha256', $token);
        $user = Database::one(
            "SELECT id, verification_token, verification_sent_at, email_verified_at FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) return false;
        if (!empty($user['email_verified_at'])) return true; // sudah terverifikasi
        if (empty($user['verification_token'])) return false;
        if (!hash_equals($user['verification_token'], $hash)) return false;

        // Expiry 24 jam
        $sent = strtotime($user['verification_sent_at'] ?? '');
        if (!$sent || (time() - $sent) > 86400) return false;

        Database::query(
            "UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = :id",
            ['id' => $userId]
        );
        Logger::info('Email verified', ['user_id' => $userId]);
        Audit::log('email_verified', ['user_id' => $userId]);
        return true;
    }

    /**
     * Request reset password
     */
    public function requestPasswordReset(string $email): bool
    {
        // Rate limit per email
        $rlKey = 'reset_' . md5(strtolower($email));
        $rl = RateLimiter::hit($rlKey, 3, 900); // max 3 per 15 menit
        if (!$rl['allowed']) {
            throw new \RuntimeException('Terlalu banyak permintaan reset. Coba lagi nanti.');
        }

        $user = Database::one(
            "SELECT id, username, email FROM users WHERE email = :e LIMIT 1",
            ['e' => $email]
        );

        // Selalu return true (jangan bocorkan apakah email terdaftar)
        if (!$user) {
            Logger::info('Reset requested for unknown email', ['email' => $email]);
            return true;
        }

        $plainToken = bin2hex(random_bytes(32));
        Database::query(
            "UPDATE users SET reset_token = :t, reset_sent_at = NOW() WHERE id = :id",
            ['t' => hash('sha256', $plainToken), 'id' => $user['id']]
        );

        $url = rtrim(Env::get('APP_URL', ''), '/') . '/reset-password?token=' . $plainToken . '&id=' . $user['id'];
        $mailer = new MailerService();
        $html = $mailer->resetEmail($user['username'], $url);
        $mailer->send($user['email'], 'Reset Password — VideoVault', $html);

        Logger::info('Password reset email sent', ['user_id' => $user['id']]);
        Audit::log('password_reset_requested', ['user_id' => $user['id']]);
        return true;
    }

    /**
     * Reset password via token
     */
    public function resetPassword(int $userId, string $token, string $newPassword): bool
    {
        $min = Env::int('PASSWORD_MIN_LENGTH', 10);
        if (strlen($newPassword) < $min) {
            throw new \RuntimeException("Password minimal $min karakter.");
        }

        $hash = hash('sha256', $token);
        $user = Database::one(
            "SELECT id, reset_token, reset_sent_at FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user || empty($user['reset_token'])) return false;
        if (!hash_equals($user['reset_token'], $hash)) return false;

        // Expiry 1 jam
        $sent = strtotime($user['reset_sent_at'] ?? '');
        if (!$sent || (time() - $sent) > 3600) return false;

        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        Database::query(
            "UPDATE users SET password = :p, reset_token = NULL WHERE id = :id",
            ['p' => $newHash, 'id' => $userId]
        );

        Logger::info('Password reset success', ['user_id' => $userId]);
        Audit::log('password_reset_completed', ['user_id' => $userId]);
        return true;
    }

    /**
     * Cek apakah token reset valid
     */
    public function validateResetToken(int $userId, string $token): bool
    {
        $hash = hash('sha256', $token);
        $user = Database::one(
            "SELECT reset_token, reset_sent_at FROM users WHERE id = :id",
            ['id' => $userId]
        );
        if (!$user || empty($user['reset_token'])) return false;
        if (!hash_equals($user['reset_token'], $hash)) return false;
        $sent = strtotime($user['reset_sent_at'] ?? '');
        if (!$sent || (time() - $sent) > 3600) return false;
        return true;
    }
}
