<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

final class TranscodeService
{
    private const FFMPEG  = '/usr/bin/ffmpeg';
    private const FFPROBE = '/usr/bin/ffprobe';

    private const RESOLUTIONS = [
        '360p'  => ['w' => 640,  'h' => 360,  'bitrate' => '800k',  'audio' => '96k'],
        '720p'  => ['w' => 1280, 'h' => 720,  'bitrate' => '2500k', 'audio' => '128k'],
        '1080p' => ['w' => 1920, 'h' => 1080, 'bitrate' => '5000k', 'audio' => '192k'],
    ];

    public function isAvailable(): bool
    {
        return is_executable(self::FFMPEG) && is_executable(self::FFPROBE);
    }

    public function probe(string $path): array
    {
        if (!is_file($path)) throw new \RuntimeException("File tidak ditemukan: $path");

        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellarg(self::FFPROBE), escapeshellarg($path)
        );

        exec($cmd, $out, $code);
        if ($code !== 0) throw new \RuntimeException('ffprobe gagal');

        $data = json_decode(implode("\n", $out), true) ?: [];
        $video = null; $audio = null;
        foreach ($data['streams'] ?? [] as $s) {
            if (($s['codec_type'] ?? '') === 'video' && !$video) $video = $s;
            if (($s['codec_type'] ?? '') === 'audio' && !$audio) $audio = $s;
        }

        return [
            'duration' => (float)($data['format']['duration'] ?? 0),
            'size'     => (int)($data['format']['size'] ?? 0),
            'bitrate'  => (int)($data['format']['bit_rate'] ?? 0),
            'video'    => $video ? [
                'codec'  => $video['codec_name'] ?? 'unknown',
                'width'  => (int)($video['width'] ?? 0),
                'height' => (int)($video['height'] ?? 0),
            ] : null,
            'audio'    => $audio ? ['codec' => $audio['codec_name'] ?? 'unknown'] : null,
        ];
    }

    public function extractThumbnail(string $videoPath, string $outputPath, float $atSecond = 3.0): bool
    {
        $cmd = sprintf(
            '%s -y -ss %s -i %s -vframes 1 -vf "scale=1280:-2" -q:v 3 %s 2>&1',
            escapeshellarg(self::FFMPEG),
            escapeshellarg((string)$atSecond),
            escapeshellarg($videoPath),
            escapeshellarg($outputPath)
        );
        exec($cmd, $out, $code);
        return $code === 0 && is_file($outputPath);
    }

    public function transcode(string $src, string $dst, string $res, ?callable $onProgress = null): bool
    {
        if (!isset(self::RESOLUTIONS[$res])) throw new \RuntimeException("Resolusi tidak didukung: $res");

        $spec = self::RESOLUTIONS[$res];
        $duration = $this->probe($src)['duration'] ?? 0;

        $cmd = sprintf(
            '%s -y -i %s -c:v libx264 -preset medium -crf 23 -b:v %s -maxrate %s -bufsize 2M ' .
            '-vf "scale=-2:%d" -c:a aac -b:a %s -ac 2 -movflags +faststart ' .
            '-progress pipe:1 -nostats %s 2>&1',
            escapeshellarg(self::FFMPEG),
            escapeshellarg($src),
            escapeshellarg($spec['bitrate']),
            escapeshellarg($spec['bitrate']),
            $spec['h'],
            escapeshellarg($spec['audio']),
            escapeshellarg($dst)
        );

        $handle = popen($cmd, 'r');
        if (!$handle) throw new \RuntimeException('Gagal menjalankan ffmpeg');

        $last = -1;
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/out_time_ms=(\d+)/', $line, $m) && $duration > 0) {
                $sec = (int)$m[1] / 1_000_000;
                $pct = min(99, (int)round(($sec / $duration) * 100));
                if ($pct > $last && $onProgress) { $onProgress($pct); $last = $pct; }
            }
        }
        $code = pclose($handle);

        return $code === 0 && is_file($dst) && filesize($dst) > 0;
    }

    public function queue(int $videoId, string $sourceFile): int
    {
        $jobId = Database::insert('transcode_jobs', [
            'video_id'    => $videoId,
            'source_file' => $sourceFile,
            'status'      => 'pending',
        ]);

        Database::query(
            "UPDATE videos SET transcode_status = 'pending', source_file = :s WHERE id = :id",
            ['s' => $sourceFile, 'id' => $videoId]
        );

        Logger::info('Transcode queued', ['video_id' => $videoId, 'job_id' => $jobId]);
        return $jobId;
    }

    public function processJob(array $job): void
    {
        $jobId   = (int)$job['id'];
        $videoId = (int)$job['video_id'];
        $source  = $job['source_file'];

        $sourcePath = BASE_PATH . '/public/uploads/videos/' . $source;
        if (!is_file($sourcePath)) {
            $this->failJob($jobId, 'Source file tidak ditemukan');
            return;
        }

        Database::query(
            "UPDATE transcode_jobs SET status='processing', started_at=NOW() WHERE id=:id",
            ['id' => $jobId]
        );
        Database::query(
            "UPDATE videos SET transcode_status='processing' WHERE id=:id",
            ['id' => $videoId]
        );

        try {
            $meta = $this->probe($sourcePath);
            $sourceHeight = $meta['video']['height'] ?? 0;
            $duration = (int)round($meta['duration']);

            $targets = [];
            foreach (self::RESOLUTIONS as $name => $spec) {
                if ($sourceHeight >= $spec['h']) $targets[$name] = $spec;
            }
            if (empty($targets)) $targets['360p'] = self::RESOLUTIONS['360p'];

            $resolutions = [];
            $total = count($targets);
            $done = 0;

            foreach ($targets as $name => $spec) {
                $outFile = 'hls/' . $name . '/' . pathinfo($source, PATHINFO_FILENAME) . '.mp4';
                $outPath = BASE_PATH . '/public/uploads/videos/' . $outFile;
                if (!is_dir(dirname($outPath))) mkdir(dirname($outPath), 0775, true);

                $ok = $this->transcode($sourcePath, $outPath, $name, function ($pct) use ($jobId, $done, $total) {
                    $overall = (int)((($done + $pct / 100) / $total) * 100);
                    Database::query("UPDATE transcode_jobs SET progress=:p WHERE id=:id", ['p' => $overall, 'id' => $jobId]);
                });

                if ($ok) {
                    $resolutions[$name] = $outFile;
                    $done++;
                }
            }

            // Auto thumbnail kalau belum ada
            $video = Database::one("SELECT thumbnail FROM videos WHERE id=:id", ['id' => $videoId]);
            if (empty($video['thumbnail'])) {
                $thumbName = 'auto/' . pathinfo($source, PATHINFO_FILENAME) . '.jpg';
                $thumbPath = BASE_PATH . '/public/uploads/thumbnails/' . $thumbName;
                if (!is_dir(dirname($thumbPath))) mkdir(dirname($thumbPath), 0775, true);

                $at = min(3.0, $meta['duration'] / 2);
                if ($this->extractThumbnail($sourcePath, $thumbPath, $at)) {
                    foreach (['sm' => 320, 'md' => 640, 'lg' => 1280] as $tag => $w) {
                        $vd = BASE_PATH . '/public/uploads/thumbnails/' . $tag . '/auto';
                        if (!is_dir($vd)) mkdir($vd, 0775, true);
                        $this->resizeImage($thumbPath, $vd . '/' . basename($thumbName), $w);
                    }
                    Database::query(
                        "UPDATE videos SET thumbnail=:t, auto_thumbnail=1 WHERE id=:id",
                        ['t' => $thumbName, 'id' => $videoId]
                    );
                }
            }

            Database::query(
                "UPDATE transcode_jobs SET status='completed', progress=100, completed_at=NOW() WHERE id=:id",
                ['id' => $jobId]
            );
            Database::query(
                "UPDATE videos SET transcode_status='done', resolutions=:r, duration=:d WHERE id=:id",
                ['r' => json_encode($resolutions), 'd' => $duration, 'id' => $videoId]
            );

            Logger::info('Transcode complete', ['job' => $jobId, 'video' => $videoId, 'res' => array_keys($resolutions)]);
        } catch (\Throwable $e) {
            Logger::error('Transcode failed', ['job' => $jobId, 'err' => $e->getMessage()]);
            $this->failJob($jobId, $e->getMessage());
        }
    }

    private function resizeImage(string $src, string $dst, int $maxW): void
    {
        $info = @getimagesize($src);
        if (!$info) { @copy($src, $dst); return; }
        [$w, $h] = $info;
        if ($w <= $maxW) { @copy($src, $dst); return; }
        $nw = $maxW; $nh = (int)round($h * ($maxW / $w));
        $srcImg = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png'  => @imagecreatefrompng($src),
            default      => null,
        };
        if (!$srcImg) return;
        $dstImg = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
        match ($info['mime']) {
            'image/jpeg' => imagejpeg($dstImg, $dst, 82),
            'image/png'  => imagepng($dstImg, $dst, 8),
            default      => null,
        };
        imagedestroy($srcImg); imagedestroy($dstImg);
    }

    private function failJob(int $jobId, string $message): void
    {
        Database::query(
            "UPDATE transcode_jobs SET status='failed', error_message=:e, completed_at=NOW() WHERE id=:id",
            ['e' => substr($message, 0, 1000), 'id' => $jobId]
        );
        Database::query(
            "UPDATE videos SET transcode_status='failed' WHERE id=(SELECT video_id FROM transcode_jobs WHERE id=:id)",
            ['id' => $jobId]
        );
    }

    public function pendingJobs(int $limit = 5): array
    {
        return Database::all(
            "SELECT * FROM transcode_jobs WHERE status='pending' ORDER BY created_at ASC LIMIT :lim",
            ['lim' => $limit]
        );
    }

    public function jobStatus(int $videoId): ?array
    {
        return Database::one(
            "SELECT * FROM transcode_jobs WHERE video_id=:v ORDER BY id DESC LIMIT 1",
            ['v' => $videoId]
        );
    }

    /**
     * Hapus semua job untuk video (dipanggil saat video dihapus)
     */
    public function purgeForVideo(int $videoId): void
    {
        Database::query("DELETE FROM transcode_jobs WHERE video_id = :id", ['id' => $videoId]);
    }
}
