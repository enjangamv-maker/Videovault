<?php use App\Core\Csrf; use App\Core\View; ?>
<h1 class="auth-title">Buat password baru</h1>
<p class="auth-sub">Minimal 10 karakter. Gunakan kombinasi huruf, angka, dan simbol.</p>
<form method="post" action="<?= View::url('reset-password') ?>" class="form">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= View::e($token) ?>">
    <input type="hidden" name="id" value="<?= (int)$userId ?>">
    <label>
        <span>Password baru</span>
        <input type="password" name="password" required minlength="10" autocomplete="new-password" autofocus>
    </label>
    <label>
        <span>Konfirmasi password</span>
        <input type="password" name="confirm" required minlength="10" autocomplete="new-password">
    </label>
    <button class="btn btn--primary btn--block">Simpan Password Baru</button>
</form>
<p class="auth-alt"><a href="<?= View::url('login') ?>">← Kembali ke login</a></p>
