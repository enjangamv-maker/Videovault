<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Env;
use App\Core\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

final class MailerService
{
    /**
     * Kirim email — pakai SMTP kalau dikonfigurasi, else fallback ke log
     */
    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $driver = Env::get('MAIL_DRIVER', 'log');

        if ($driver === 'smtp') {
            return $this->sendSmtp($to, $subject, $htmlBody, $textBody);
        }

        return $this->sendLog($to, $subject, $htmlBody);
    }

    private function sendSmtp(string $to, string $subject, string $html, ?string $text): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = Env::get('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = Env::get('MAIL_USER', '');
            $mail->Password   = Env::get('MAIL_PASS', '');
            $mail->SMTPSecure = Env::get('MAIL_ENCRYPTION', 'tls');
            $mail->Port       = Env::int('MAIL_PORT', 587);
            $mail->CharSet    = 'UTF-8';

            $from = Env::get('MAIL_FROM', 'noreply@videovault.local');
            $fromName = Env::get('MAIL_FROM_NAME', 'VideoVault');

            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text ?? strip_tags($html);

            $mail->send();
            Logger::info('Email sent via SMTP', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (MailException $e) {
            Logger::error('Email SMTP failed', ['to' => $to, 'err' => $e->getMessage()]);
            return false;
        }
    }

    private function sendLog(string $to, string $subject, string $html): bool
    {
        $file = BASE_PATH . '/storage/mail/' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "\n%s\nTO      : %s\nSUBJECT : %s\n%s\n%s\n%s\n",
            str_repeat('═', 70),
            $to, $subject,
            str_repeat('─', 70),
            strip_tags($html),
            str_repeat('═', 70)
        );
        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
        Logger::info('Email logged (dev mode)', ['to' => $to, 'subject' => $subject]);
        return true;
    }

    /**
     * Template: Verifikasi Email
     */
    public function verificationEmail(string $username, string $verifyUrl): string
    {
        $appName = Env::get('APP_NAME', 'VideoVault');
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0e0d0b;font-family:-apple-system,Segoe UI,sans-serif;color:#f5f1ea;">
<div style="max-width:560px;margin:40px auto;padding:40px;background:#1a1816;border-radius:14px;border:1px solid #2a2723;">
    <h1 style="margin:0 0 8px;font-family:Georgia,serif;font-size:24px;color:#e8a13c;">$appName</h1>
    <p style="margin:0 0 24px;color:#b8b1a5;font-size:14px;">Verifikasi akun Anda</p>

    <p style="font-size:15px;line-height:1.6;">Halo <strong>$username</strong>,</p>
    <p style="font-size:15px;line-height:1.6;color:#b8b1a5;">
        Terima kasih telah mendaftar. Klik tombol di bawah untuk mengaktifkan akun Anda.
    </p>

    <p style="margin:32px 0;text-align:center;">
        <a href="$verifyUrl" style="display:inline-block;padding:14px 32px;background:#e8a13c;color:#1a1206;text-decoration:none;border-radius:100px;font-weight:600;font-size:15px;">Verifikasi Email</a>
    </p>

    <p style="font-size:13px;color:#71717a;margin:0;">
        Link berlaku 24 jam. Kalau bukan Anda yang mendaftar, abaikan email ini.
    </p>

    <p style="margin-top:24px;padding-top:20px;border-top:1px solid #2a2723;font-size:12px;color:#71717a;">
        Kalau tombol tidak berfungsi, salin link berikut:<br>
        <span style="color:#3b82f6;word-break:break-all;">$verifyUrl</span>
    </p>
</div>
</body></html>
HTML;
    }

    /**
     * Template: Password Reset
     */
    public function resetEmail(string $username, string $resetUrl): string
    {
        $appName = Env::get('APP_NAME', 'VideoVault');
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0e0d0b;font-family:-apple-system,Segoe UI,sans-serif;color:#f5f1ea;">
<div style="max-width:560px;margin:40px auto;padding:40px;background:#1a1816;border-radius:14px;border:1px solid #2a2723;">
    <h1 style="margin:0 0 8px;font-family:Georgia,serif;font-size:24px;color:#e8a13c;">$appName</h1>
    <p style="margin:0 0 24px;color:#b8b1a5;font-size:14px;">Reset password</p>

    <p style="font-size:15px;line-height:1.6;">Halo <strong>$username</strong>,</p>
    <p style="font-size:15px;line-height:1.6;color:#b8b1a5;">
        Kami menerima permintaan reset password untuk akun Anda.
        Klik tombol di bawah untuk membuat password baru.
    </p>

    <p style="margin:32px 0;text-align:center;">
        <a href="$resetUrl" style="display:inline-block;padding:14px 32px;background:#e8a13c;color:#1a1206;text-decoration:none;border-radius:100px;font-weight:600;font-size:15px;">Reset Password</a>
    </p>

    <p style="font-size:13px;color:#71717a;margin:0;">
        Link berlaku 1 jam. Kalau bukan Anda yang meminta, abaikan email ini — password Anda tetap aman.
    </p>

    <p style="margin-top:24px;padding-top:20px;border-top:1px solid #2a2723;font-size:12px;color:#71717a;">
        Kalau tombol tidak berfungsi, salin link berikut:<br>
        <span style="color:#3b82f6;word-break:break-all;">$resetUrl</span>
    </p>
</div>
</body></html>
HTML;
    }
}
