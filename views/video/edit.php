<?php use App\Core\Csrf; use App\Core\View; ?>
<section class="container form-page">
    <header class="form-page__head">
        <p class="eyebrow">Dashboard / Video</p>
        <h1>Edit video</h1>
    </header>
    <form method="post" action="<?= View::url('video/' . $video['id']) ?>" enctype="multipart/form-data" class="form form--wide">
        <?= Csrf::field() ?>
        <label><span>Judul</span><input type="text" name="title" required maxlength="255" value="<?= View::e($video['title']) ?>"></label>
        <label><span>Deskripsi</span><textarea name="description" rows="5"><?= View::e($video['description']) ?></textarea></label>
        <div class="grid-2">
            <label>
                <span>Kategori</span>
                <select name="category_id">
                    <option value="">— Pilih —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$video['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span>Durasi (detik)</span><input type="number" name="duration" min="0" value="<?= (int)$video['duration'] ?>"></label>
        </div>
        <label><span>Ganti video</span><input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg"><small>Kosongkan jika tidak ingin ganti.</small></label>
        <label><span>Ganti thumbnail</span><input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"></label>
        <div class="form__actions">
            <button class="btn btn--primary">Simpan</button>
            <a href="<?= View::url('admin') ?>" class="btn btn--ghost">Batal</a>
        </div>
    </form>
</section>
