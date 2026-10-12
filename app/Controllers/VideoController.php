<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Services\AuthService;
use App\Services\TranscodeService;
use App\Services\UploadService;
use App\Services\VideoService;

final class VideoController
{
    public function show(string $id): void
    {
        $vs = new VideoService();
        try { $video = $vs->find((int)$id); }
        catch (NotFoundException $e) { Response::abort(404, 'Video tidak ditemukan.'); }

        $vs->incrementViews((int)$id);

        // Ambil status transcode
        $transcode = null;
        if (!empty($video['id'])) {
            $transcode = (new TranscodeService())->jobStatus((int)$video['id']);
        }

        View::render('video.show', [
            'pageTitle' => $video['title'],
            'video'     => $video,
            'transcode' => $transcode,
            'isAdmin'   => (new AuthService())->isAdmin(),
        ]);
    }

    public function create(): void
    {
        View::render('video.create', [
            'pageTitle' => 'Tambah Video',
            'categories' => Category::all(),
        ]);
    }

    public function store(): void
    {
        $title = Request::str('title');
        $desc  = Request::str('description');
        $cat   = Request::int('category_id') ?: null;
        $duration = Request::int('duration');

        try {
            if ($title === '') throw new ValidationException(['title' => 'Judul wajib diisi.']);

            $vfile = Request::file('video_file');
            if (!$vfile || $vfile['error'] === UPLOAD_ERR_NO_FILE) {
                throw new ValidationException(['file' => 'File video wajib diunggah.']);
            }

            $uploader = new UploadService();
            $videoFile = $uploader->handleVideo($vfile);

            // Thumbnail: upload manual atau auto-generate nanti via worker
            $thumbName = null;
            $tfile = Request::file('thumbnail');
            if ($tfile && $tfile['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = $uploader->handleThumbnail($tfile);
                $thumbName = $result['filename'];
            }

            // Simpan metadata video
            $id = (new VideoService())->create([
                'title'       => $title,
                'description' => $desc,
                'category_id' => $cat,
                'video_file'  => $videoFile,
                'thumbnail'   => $thumbName,
                'duration'    => $duration,
                'uploaded_by' => (new AuthService())->id(),
            ]);

            // AUTO-QUEUE TRANSCODE — proses di background
            try {
                (new TranscodeService())->queue($id, $videoFile);
            } catch (\Throwable $e) {
                // Jangan batalkan upload kalau queue gagal
                \App\Core\Logger::warn('Transcode queue gagal', ['video_id' => $id, 'err' => $e->getMessage()]);
            }

            Session::flash('success', 'Video berhasil diunggah. Transcoding sedang diproses di background.');
            Response::redirect('admin');
        } catch (ValidationException $e) {
            Session::flash('error', implode(' ', $e->errors));
            Response::redirect('video/create');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('video/create');
        }
    }

    public function edit(string $id): void
    {
        try {
            $video = (new VideoService())->find((int)$id);
        } catch (NotFoundException $e) {
            Response::abort(404);
        }
        View::render('video.edit', [
            'pageTitle' => 'Edit Video',
            'video'     => $video,
            'categories' => Category::all(),
        ]);
    }

    public function update(string $id): void
    {
        $id = (int)$id;
        $vs = new VideoService();
        try { $video = $vs->find($id); }
        catch (NotFoundException $e) { Response::abort(404); }

        $title = Request::str('title');
        if ($title === '') {
            Session::flash('error', 'Judul wajib diisi.');
            Response::redirect("video/$id/edit");
        }

        try {
            $uploader = new UploadService();
            $update = [
                'title'       => $title,
                'description' => Request::str('description'),
                'category_id' => Request::int('category_id') ?: null,
                'duration'    => Request::int('duration'),
            ];

            $reQueue = false;

            // Ganti video → transcode ulang
            $vfile = Request::file('video_file');
            if ($vfile && $vfile['error'] !== UPLOAD_ERR_NO_FILE) {
                $newVideo = $uploader->handleVideo($vfile);
                $update['video_file'] = $newVideo;
                $update['transcode_status'] = 'pending';
                $reQueue = true;

                // Hapus file lama
                $uploader->delete('videos', $video['video_file']);
            }

            // Ganti thumbnail
            $tfile = Request::file('thumbnail');
            if ($tfile && $tfile['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = $uploader->handleThumbnail($tfile);
                $update['thumbnail'] = $result['filename'];
                $uploader->delete('thumbnails', $video['thumbnail']);
            }

            $vs->update($id, $update);

            // Re-queue transcode kalau video diganti
            if ($reQueue) {
                try {
                    (new TranscodeService())->queue($id, $update['video_file']);
                } catch (\Throwable $e) {
                    \App\Core\Logger::warn('Re-queue transcode gagal', ['id' => $id, 'err' => $e->getMessage()]);
                }
            }

            Session::flash('success', 'Video berhasil diperbarui.');
            Response::redirect('admin');
        } catch (ValidationException $e) {
            Session::flash('error', implode(' ', $e->errors));
            Response::redirect("video/$id/edit");
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect("video/$id/edit");
        }
    }

    public function delete(string $id): void
    {
        $id = (int)$id;
        $vs = new VideoService();
        try {
            $video = $vs->find($id);

            // Purge transcode jobs
            (new TranscodeService())->purgeForVideo($id);

            // Hapus file video (semua variant)
            (new UploadService())->delete('videos', $video['video_file']);
            (new UploadService())->delete('thumbnails', $video['thumbnail']);

            // Hapus file HLS
            $hlsBase = BASE_PATH . '/public/uploads/videos/hls';
            if (is_dir($hlsBase)) {
                foreach (['360p', '720p', '1080p'] as $res) {
                    $dir = $hlsBase . '/' . $res . '/' . dirname($video['video_file']);
                    $base = pathinfo($video['video_file'], PATHINFO_FILENAME);
                    @unlink($dir . '/' . $base . '.mp4');
                }
            }

            $vs->delete($id);
            Session::flash('success', 'Video berhasil dihapus.');
        } catch (NotFoundException $e) {
            Session::flash('error', 'Video tidak ditemukan.');
        }
        Response::redirect('admin');
    }
}
