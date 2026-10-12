<?php use App\Core\Csrf; use App\Core\View; ?>
<section class="container form-page">
    <header class="form-page__head">
        <p class="eyebrow">Dashboard / Video</p>
        <h1>Tambah video</h1>
        <p class="muted">Lengkapi metadata dan unggah file.</p>
    </header>
    <form method="post" action="<?= View::url('video') ?>" enctype="multipart/form-data" class="form form--wide">
        <?= Csrf::field() ?>
        <label><span>Judul</span><input type="text" name="title" required maxlength="255"></label>
        <label><span>Deskripsi</span><textarea name="description" rows="5"></textarea></label>
        <div class="grid-2">
            <label>
                <span>Kategori</span>
                <select name="category_id">
                    <option value="">— Pilih —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Durasi (detik)</span><input type="number" name="duration" min="0" value="0"></label>
        </div>
        <label><span>File video</span><input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg" required><small>MP4/WebM/OGG · maks 100MB</small></label>
        <label><span>Thumbnail</span><input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"><small>JPG/PNG/WebP · maks 2MB</small></label>
        <div class="form__actions">
            <button class="btn btn--primary">Simpan</button>
            <a href="<?= View::url('admin') ?>" class="btn btn--ghost">Batal</a>
        </div>
    </form>
</section>
