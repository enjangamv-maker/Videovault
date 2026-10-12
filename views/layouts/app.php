<?php
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
$user = [
    'id'   => Session::get('user_id'),
    'name' => Session::get('user_name'),
    'role' => Session::get('user_role'),
];
$success = Session::flash('success');
$error   = Session::flash('error');
$info    = Session::flash('info');
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e($pageTitle ?? 'VideoVault') ?> — VideoVault</title>
<meta name="description" content="<?= View::e($metaDescription ?? 'Katalog dan pengelolaan video.') ?>">
<meta property="og:title" content="<?= View::e($pageTitle ?? 'VideoVault') ?>">
<meta property="og:description" content="<?= View::e($metaDescription ?? 'Katalog dan pengelolaan video.') ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= View::url($_SERVER['REQUEST_URI'] ?? '') ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= View::e($ogImage) ?>"><?php endif; ?>
<meta name="color-scheme" content="dark light">
<script>
(function(){
    try {
        var t = localStorage.getItem('vv-theme');
        if (t === 'light' || t === 'dark') {
            document.documentElement.setAttribute('data-theme', t);
        }
    } catch(e) {}
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
</head>
<body>
<div class="grain"></div>
<a href="#main" class="skip">Lewati ke konten</a>
<header class="nav">
    <div class="container nav__inner">
        <button type="button" class="nav__burger" id="navBurger" aria-label="Buka menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <a href="<?= View::url('') ?>" class="brand">
            <span class="brand__mark">◐</span>
            <span class="brand__name">Video<em>Vault</em></span>
        </a>
        <nav class="nav__menu" id="navMenu">
            <a href="<?= View::url('') ?>">Katalog</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= View::url('admin') ?>">Dashboard</a>
                <a href="<?= View::url('video/create') ?>">Tambah</a>
            <?php endif; ?>
        </nav>
        <div class="nav__actions">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Ganti tema" title="Ganti tema (T)">
                <svg class="theme-toggle__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                <svg class="theme-toggle__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
            <?php if ($user['id']): ?>
                <span class="user-chip">
                    <?= View::e($user['name']) ?>
                    <?php if ($user['role'] === 'admin'): ?><em>admin</em><?php endif; ?>
                </span>
                <form method="post" action="<?= View::url('logout') ?>" class="inline">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--ghost btn--sm">Keluar</button>
                </form>
            <?php else: ?>
                <a href="<?= View::url('login') ?>" class="btn btn--ghost btn--sm">Masuk</a>
                <a href="<?= View::url('register') ?>" class="btn btn--primary btn--sm">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="main" id="main">
    <?php if (!empty($breadcrumb)): ?>
        <nav class="breadcrumb container" aria-label="Breadcrumb">
            <?php foreach ($breadcrumb as $i => $b): ?>
                <?php if ($i > 0): ?><span class="breadcrumb__sep">/</span><?php endif; ?>
                <?php if (!empty($b['url'])): ?><a href="<?= View::e($b['url']) ?>"><?= View::e($b['label']) ?></a>
                <?php else: ?><span aria-current="page"><?= View::e($b['label']) ?></span><?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <?php if ($success): ?><div class="container"><div class="alert alert--ok"><?= View::e($success) ?></div></div><?php endif; ?>
    <?php if ($error): ?><div class="container"><div class="alert alert--err"><?= View::e($error) ?></div></div><?php endif; ?>
    <?php if ($info): ?><div class="container"><div class="alert alert--info"><?= View::e($info) ?></div></div><?php endif; ?>
    <?= $content ?>
</main>
<footer class="footer">
    <div class="container footer__inner">
        <p><strong>VideoVault</strong> — Katalog video editorial.</p>
        <nav class="footer__links">
            <a href="<?= View::url('privacy') ?>">Privasi</a>
            <a href="<?= View::url('terms') ?>">Ketentuan</a>
        </nav>
        <p class="muted">© <?= date('Y') ?></p>
    </div>
</footer>
<div id="toasts" class="toasts" aria-live="polite" aria-atomic="true"></div>
<script src="<?= View::asset('js/app.js') ?>" defer></script>
</body>
</html>
