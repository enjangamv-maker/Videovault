<?php use App\Core\Csrf; use App\Core\View; ?>
<h1 class="auth-title">Lupa password?</h1>
<p class="auth-sub">Masukkan email Anda. Kami akan kirim link reset.</p>
<form method="post" action="<?= View::url('forgot-password') ?>" class="form">
    <?= Csrf::field() ?>
    <label>
        <span>Email</span>
        <input type="email" name="email" required autofocus autocomplete="email" placeholder="nama@email.com">
    </label>
    <button class="btn btn--primary btn--block">Kirim Link Reset</button>
</form>
<p class="auth-alt"><a href="<?= View::url('login') ?>">← Kembali ke login</a></p>
