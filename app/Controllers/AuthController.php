<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

final class AuthController
{
    public function showLogin(): void
    {
        if (Session::get('user_id')) Response::redirect('');
        View::render('auth.login', ['pageTitle' => 'Masuk'], 'auth');
    }

    public function login(): void
    {
        $login = Request::str('username');
        $pass = (string)Request::post('password', '');

        if ($login === '' || $pass === '') {
            Session::flash('error', 'Isi semua field.');
            Response::redirect('login');
        }

        try {
            $auth = new AuthService();
            if ($auth->attempt($login, $pass)) {
                Response::redirect($auth->isAdmin() ? 'admin' : '');
            }
            Session::flash('error', 'Username atau password salah.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('login');
    }

    public function showRegister(): void
    {
        if (Session::get('user_id')) Response::redirect('');
        View::render('auth.register', ['pageTitle' => 'Daftar'], 'auth');
    }

    public function register(): void
    {
        $username = Request::str('username');
        $email = Request::str('email');
        $pass = (string)Request::post('password', '');
        $confirm = (string)Request::post('confirm', '');

        $errors = [];
        if (strlen($username) < 3 || strlen($username) > 50) $errors[] = 'Username harus 3-50 karakter.';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'Username hanya huruf, angka, underscore.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($pass) < 10) $errors[] = 'Password minimal 10 karakter.';
        if ($pass !== $confirm) $errors[] = 'Konfirmasi password tidak cocok.';

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            Response::redirect('register');
        }

        try {
            (new AuthService())->register($username, $email, $pass);
            Session::flash('success', 'Registrasi berhasil! Cek email untuk verifikasi (kalau diaktifkan).');
            Response::redirect('login');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('register');
        }
    }

    public function logout(): void
    {
        (new AuthService())->logout();
        Response::redirect('');
    }

    // ═══════════════════════════════════════════════════
    // VERIFY EMAIL
    // ═══════════════════════════════════════════════════
    public function showVerifyEmail(): void
    {
        $token = Request::str('token');
        $userId = Request::int('id');

        if ($token === '' || $userId <= 0) {
            Session::flash('error', 'Link verifikasi tidak valid.');
            Response::redirect('login');
        }

        if ((new AuthService())->verifyEmail($userId, $token)) {
            Session::flash('success', 'Email berhasil diverifikasi! Silakan login.');
            Response::redirect('login');
        }

        Session::flash('error', 'Link verifikasi tidak valid atau sudah kadaluarsa.');
        Response::redirect('login');
    }

    public function resendVerification(): void
    {
        $email = Request::str('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Email tidak valid.');
            Response::redirect('login');
        }

        $userId = (int)\App\Core\Database::scalar(
            "SELECT id FROM users WHERE email = :e AND email_verified_at IS NULL LIMIT 1",
            ['e' => $email]
        );

        if ($userId > 0) {
            (new AuthService())->sendVerificationEmail($userId);
        }

        Session::flash('success', 'Kalau email terdaftar dan belum diverifikasi, kami sudah kirim ulang.');
        Response::redirect('login');
    }

    // ═══════════════════════════════════════════════════
    // PASSWORD RESET
    // ═══════════════════════════════════════════════════
    public function showForgotPassword(): void
    {
        View::render('auth.forgot', ['pageTitle' => 'Lupa Password'], 'auth');
    }

    public function sendResetLink(): void
    {
        $email = Request::str('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Email tidak valid.');
            Response::redirect('forgot-password');
        }

        try {
            (new AuthService())->requestPasswordReset($email);
            Session::flash('success', 'Kalau email terdaftar, kami sudah kirim link reset. Cek inbox Anda.');
            Response::redirect('login');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('forgot-password');
        }
    }

    public function showResetPassword(): void
    {
        $token = Request::str('token');
        $userId = Request::int('id');

        if ($token === '' || $userId <= 0) {
            Session::flash('error', 'Link reset tidak valid.');
            Response::redirect('login');
        }

        if (!(new AuthService())->validateResetToken($userId, $token)) {
            Session::flash('error', 'Link reset sudah kadaluarsa atau tidak valid.');
            Response::redirect('forgot-password');
        }

        View::render('auth.reset', [
            'pageTitle' => 'Reset Password',
            'token'     => $token,
            'userId'    => $userId,
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $token = Request::str('token');
        $userId = Request::int('id');
        $pass = (string)Request::post('password', '');
        $confirm = (string)Request::post('confirm', '');

        if ($pass !== $confirm) {
            Session::flash('error', 'Konfirmasi password tidak cocok.');
            Response::redirect("reset-password?token=$token&id=$userId");
        }

        try {
            if ((new AuthService())->resetPassword($userId, $token, $pass)) {
                Session::flash('success', 'Password berhasil diubah. Silakan login dengan password baru.');
                Response::redirect('login');
            }
            Session::flash('error', 'Gagal reset password. Link mungkin sudah kadaluarsa.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect('forgot-password');
    }
}
