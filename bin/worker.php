#!/usr/bin/env php
<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Services\TranscodeService;

$opts = $argv ?? [];
$loop = in_array('--loop', $opts, true);
$check = in_array('--check', $opts, true);

$svc = new TranscodeService();

if ($check) {
    echo "FFmpeg : " . ($svc->isAvailable() ? "✓ OK" : "✗ TIDAK ADA") . PHP_EOL;
    exit($svc->isAvailable() ? 0 : 1);
}

echo "════════════════════════════════════════════════" . PHP_EOL;
echo "  VideoVault — Transcode Worker" . PHP_EOL;
echo "════════════════════════════════════════════════" . PHP_EOL;
echo "Mode: " . ($loop ? "LOOP" : "SINGLE") . PHP_EOL;
echo "PID : " . getmypid() . PHP_EOL;
echo "════════════════════════════════════════════════" . PHP_EOL;

if (!$svc->isAvailable()) { fwrite(STDERR, "❌ FFmpeg tidak ada\n"); exit(1); }

$running = true;
if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, function () use (&$running) { $running = false; });
    pcntl_signal(SIGINT,  function () use (&$running) { $running = false; });
}

do {
    $jobs = $svc->pendingJobs(1);
    if (empty($jobs)) {
        if ($loop) { sleep(5); continue; }
        echo "ℹ️  Tidak ada job pending.\n";
        break;
    }
    $job = $jobs[0];
    echo "▶️  Job #{$job['id']} (video #{$job['video_id']})...\n";
    $t = microtime(true);
    $svc->processJob($job);
    echo "   ✓ " . round(microtime(true) - $t, 2) . "s\n\n";
    if (!$loop) break;
} while ($running);

echo "Worker stopped.\n";
