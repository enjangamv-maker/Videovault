<?php
use App\Core\View;

// Helper highlight
function highlightText(string $text, string $query): string {
    if ($query === '') return View::e($text);
    $words = preg_split('/\s+/', trim($query)) ?: [];
    $words = array_filter($words, fn($w) => mb_strlen($w) >= 2);
    if (empty($words)) return View::e($text);

    $escaped = View::e($text);
    foreach ($words as $w) {
        $w = preg_quote(View::e($w), '/');
        $escaped = preg_replace('/(' . $w . ')/iu', '<mark>$1</mark>', $escaped);
    }
    return $escaped;
}
?>
<section class="hero container">
    <p class="eyebrow">Katalog video editorial</p>
    <h1>Simpan. Cari. <em>Tonton.</em></h1>
    <p class="lede">Platform pengelolaan video dengan pencarian, kategori, dan kurasi rapi — dibangun untuk koleksi pribadi maupun tim.</p>

    <form method="get" class="search">
        <input type="search" name="q" placeholder="Cari judul atau deskripsi…" value="<?= View::e($search) ?>" aria-label="Cari">
        <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= (int)$categoryId ?>"><?php endif; ?>
        <button class="btn btn--primary">Cari</button>
    </form>

    <div class="chips">
        <a href="<?= View::url('') ?>" class="chip <?= !$categoryId && !$search ? 'is-active' : '' ?>">Semua</a>
        <?php foreach ($categories as $c): ?>
            <a href="<?= View::url('?category=' . $c['id'] . ($search ? '&q=' . urlencode($search) : '')) ?>"
               class="chip <?= $categoryId === (int)$c['id'] ? 'is-active' : '' ?>">
                <?= View::e($c['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <header class="section__head">
        <h2>
            <?php if ($search): ?>
                Hasil: “<?= View::e($search) ?>”
                <?php if ($mode === 'fulltext'): ?>
                    <span class="badge badge--ft" title="Menggunakan FULLTEXT index">⚡ relevan</span>
                <?php elseif ($mode === 'like'): ?>
                    <span class="badge" title="Pencarian partial">🔍 partial</span>
                <?php endif; ?>
            <?php else: ?>
                Semua Video
            <?php endif; ?>
        </h2>
        <span class="muted"><?= (int)$total ?> video</span>
    </header>

    <?php if (!$videos): ?>
        <div class="empty">
            <p class="empty__icon">◌</p>
            <h3>Tidak ada hasil<?= $search ? ' untuk “' . View::e($search) . '”' : '' ?></h3>
            <p>Coba kata kunci lain atau pilih kategori berbeda.</p>

            <?php if (!empty($suggestions)): ?>
                <div style="margin-top:16px;">
                    <p class="muted" style="font-size:.85rem;margin-bottom:8px;">Mungkin maksud Anda:</p>
                    <div class="chips" style="justify-content:center;">
                        <?php foreach ($suggestions as $s): ?>
                            <a href="<?= View::url('?q=' . urlencode($s)) ?>" class="chip">
                                <?= View::e($s) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <a href="<?= View::url('') ?>" class="btn btn--primary" style="margin-top:20px;">Lihat semua video</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($videos as $v): ?>
                <a href="<?= View::url('video/' . $v['id']) ?>" class="card">
                    <div class="card__thumb">
                        <?php if (!empty($v['thumbnail'])): ?>
                            <img src="<?= View::url('uploads/thumbnails/md/' . $v['thumbnail']) ?>" alt="<?= View::e($v['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card__placeholder">◐</div>
                        <?php endif; ?>
                        <?php if (!empty($v['duration'])): ?>
                            <span class="card__dur"><?= gmdate('i:s', (int)$v['duration']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card__body">
                        <?php if (!empty($v['category_name'])): ?>
                            <span class="card__cat"><?= View::e($v['category_name']) ?></span>
                        <?php endif; ?>
                        <h3 class="card__title"><?= highlightText($v['title'], $search) ?></h3>
                        <?php if (!empty($v['description'])): ?>
                            <p class="card__desc"><?= highlightText(mb_substr($v['description'], 0, 150), $search) ?></p>
                        <?php endif; ?>
                        <div class="card__meta">
                            <span><?= date('d M Y', strtotime($v['created_at'])) ?></span>
                            <span><?= number_format((int)$v['views']) ?> views</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pager">
                <?php $qb = array_filter(['q' => $search, 'category' => $categoryId]); ?>
                <?php if ($page > 1): ?>
                    <a href="<?= View::url('?' . http_build_query(array_merge($qb, ['page' => $page - 1]))) ?>" class="pager__link">← Sebelumnya</a>
                <?php endif; ?>
                <span class="pager__info">Halaman <?= $page ?> dari <?= $pages ?></span>
                <?php if ($page < $pages): ?>
                    <a href="<?= View::url('?' . http_build_query(array_merge($qb, ['page' => $page + 1]))) ?>" class="pager__link">Selanjutnya →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
