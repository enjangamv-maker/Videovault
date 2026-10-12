<?php use App\Core\Csrf; use App\Core\View; ?>
<section class="container detail">
    <div class="detail__player">
        <video controls preload="metadata" playsinline <?= !empty($video['thumbnail']) ? 'poster="' . View::url('uploads/thumbnails/lg/' . $video['thumbnail']) . '"' : '' ?>>
            <?php $ext = pathinfo($video['video_file'], PATHINFO_EXTENSION); ?>
            <source src="<?= View::url('uploads/videos/' . $video['video_file']) ?>" type="video/<?= $ext === 'webm' ? 'webm' : ($ext === 'ogg' ? 'ogg' : 'mp4') ?>">
            Browser tidak mendukung video.
        </video>
    </div>
    <header class="detail__head">
        <?php if (!empty($video['category_name'])): ?>
            <span class="eyebrow"><?= View::e($video['category_name']) ?></span>
        <?php endif; ?>
        <h1><?= View::e($video['title']) ?></h1>
        <div class="detail__meta">
            <span><?= number_format((int)$video['views']) ?> views</span>
            <span><?= date('d M Y', strtotime($video['created_at'])) ?></span>
            <?php if (!empty($video['uploader'])): ?><span>oleh <?= View::e($video['uploader']) ?></span><?php endif; ?>
        </div>
    </header>
    <?php if (!empty($video['description'])): ?>
        <div class="detail__desc"><?= nl2br(View::e($video['description'])) ?></div>
    <?php endif; ?>
    <div class="detail__actions">
        <a href="<?= View::url('') ?>" class="btn btn--ghost">← Katalog</a>
        <?php if ($isAdmin): ?>
            <a href="<?= View::url('video/' . $video['id'] . '/edit') ?>" class="btn btn--primary">Edit video</a>
        <?php endif; ?>
    </div>
</section>
