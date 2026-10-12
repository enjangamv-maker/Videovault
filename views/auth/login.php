<?php use App\Core\Csrf; use App\Core\View; ?>
<h1 class="auth-title">Selamat datang</h1>
<p class="auth-sub">Masuk untuk mengelola koleksi video Anda.</p>
<form method="post" action="<?= View::url('login') ?>" class="form">
    <?= Csrf::field() ?>
    <label>
        <span>Username atau email</span>
        <input type="text" name="username" required autofocus autocomplete="username">
    </label>
    <label>
        <span>Password</span>
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <div style="text-align:right;margin-top:-8px;">
        <a href="<?= View::url('forgot-password') ?>" style="font-size:.8rem;color:var(--ink-3);">Lupa password?</a>
    </div>
    <button class="btn btn--primary btn--block">Masuk</button>
</form>
<p class="auth-alt">Belum punya akun? <a href="<?= View::url('register') ?>">Daftar</a></p>
