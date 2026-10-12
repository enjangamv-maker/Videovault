<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Env;
use App\Core\Logger;
use App\Exceptions\ValidationException;

final class UploadService
{
    private const VIDEO_MIME = ['video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogg'];
    private const IMAGE_MIME = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    public function handleVideo(array $file): string
    {
        return $this->handle($file, self::VIDEO_MIME, Env::int('UPLOAD_MAX_VIDEO', 104857600), 'videos');
    }

    public function handleThumbnail(array $file): array
    {
        $filename = $this->handle($file, self::IMAGE_MIME, Env::int('UPLOAD_MAX_THUMB', 2097152), 'thumbnails');
        $base = BASE_PATH . '/public/uploads/thumbnails/' . $filename;
        $sizes = ['sm' => Env::int('THUMB_SM', 320), 'md' => Env::int('THUMB_MD', 640), 'lg' => Env::int('THUMB_LG', 1280)];
        $quality = Env::int('THUMB_QUALITY', 82);
        foreach ($sizes as $tag => $w) {
            $this->resize($base, BASE_PATH . "/public/uploads/thumbnails/$tag/" . basename($filename), $w, $quality);
        }
        return ['filename' => $filename];
    }

    private function handle(array $file, array $allowed, int $maxSize, string $subdir): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException(['file' => 'Upload gagal (kode ' . $file['error'] . ')']);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException(['file' => 'File tidak valid.']);
        }
        if (($file['size'] ?? 0) > $maxSize) {
            throw new ValidationException(['file' => 'Ukuran melebihi batas (' . round($maxSize / 1048576, 1) . 'MB).']);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            Logger::warn('Upload MIME ditolak', ['mime' => $mime]);
            throw new ValidationException(['file' => 'Format tidak didukung.']);
        }
        // Basic malware heuristic
        $head = @file_get_contents($file['tmp_name'], false, null, 0, 16);
        if ($head && preg_match('/^<\?php|^<script/i', $head)) {
            throw new ValidationException(['file' => 'File berisi konten berbahaya.']);
        }

        $ext = $allowed[$mime];
        $filename = date('Y/m/') . bin2hex(random_bytes(16)) . '.' . $ext;
        $targetDir = BASE_PATH . '/public/uploads/' . $subdir . '/' . dirname($filename);
        $targetPath = BASE_PATH . '/public/uploads/' . $subdir . '/' . $filename;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Gagal buat direktori upload.');
        }
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Gagal simpan file.');
        }
        @chmod($targetPath, 0644);
        Logger::info('File uploaded', ['path' => "$subdir/$filename", 'mime' => $mime, 'size' => $file['size']]);
        return $filename;
    }

    private function resize(string $src, string $dst, int $maxWidth, int $quality): void
    {
        if (!is_file($src)) return;
        $info = @getimagesize($src);
        if (!$info) return;
        [$w, $h] = $info;

        if ($w <= $maxWidth) { @copy($src, $dst); return; }
        $nw = $maxWidth;
        $nh = (int)round($h * ($maxWidth / $w));

        $srcImg = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : null,
            default      => null,
        };
        if (!$srcImg) return;

        $dstImg = imagecreatetruecolor($nw, $nh);
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $dir = dirname($dst);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        match ($info['mime']) {
            'image/jpeg' => imagejpeg($dstImg, $dst, $quality),
            'image/png'  => imagepng($dstImg, $dst, 8),
            'image/webp' => function_exists('imagewebp') ? imagewebp($dstImg, $dst, $quality) : null,
            default      => null,
        };
        imagedestroy($srcImg);
        imagedestroy($dstImg);
    }

    public function delete(string $subdir, ?string $filename): void
    {
        if (!$filename) return;
        @unlink(BASE_PATH . '/public/uploads/' . $subdir . '/' . $filename);
        foreach (['sm','md','lg'] as $tag) {
            @unlink(BASE_PATH . '/public/uploads/' . $subdir . "/$tag/" . basename($filename));
        }
    }
}
