<?php
use App\Core\Session;
use App\Core\View;
$success = Session::flash('success');
$error   = Session::flash('error');
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e($pageTitle ?? 'Masuk') ?> — VideoVault</title>
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
<body class="auth-body">
<div class="grain"></div>
<button type="button" class="theme-toggle theme-toggle--fixed" id="themeToggle" aria-label="Ganti tema" title="Ganti tema">
    <svg class="theme-toggle__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
    </svg>
    <svg class="theme-toggle__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
</button>
<div class="auth-wrap">
    <div class="auth-card">
        <a href="<?= View::url('') ?>" class="brand brand--center">
            <span class="brand__mark">◐</span>
            <span class="brand__name">Video<em>Vault</em></span>
        </a>
        <?php if ($success): ?><div class="alert alert--ok"><?= View::e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert--err"><?= View::e($error) ?></div><?php endif; ?>
        <?= $content ?>
    </div>
    <a href="<?= View::url('') ?>" class="auth-back">← Kembali ke katalog</a>
</div>
<script src="<?= View::asset('js/app.js') ?>" defer></script>
</body>
</html>
