<?php use App\Core\Csrf; use App\Core\View; ?>
<h1 class="auth-title">Buat akun</h1>
<p class="auth-sub">Gratis. Cukup 30 detik.</p>
<form method="post" action="<?= View::url('register') ?>" class="form">
    <?= Csrf::field() ?>
    <label><span>Username</span><input type="text" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+" autocomplete="username"><small>3–50 karakter, huruf/angka/underscore.</small></label>
    <label><span>Email</span><input type="email" name="email" required autocomplete="email"></label>
    <label><span>Password</span><input type="password" name="password" required minlength="8" autocomplete="new-password"><small>Minimal 8 karakter.</small></label>
    <label><span>Konfirmasi password</span><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn--primary btn--block">Daftar</button>
</form>
<p class="auth-alt">Sudah punya akun? <a href="<?= View::url('login') ?>">Masuk</a></p>
