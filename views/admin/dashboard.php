<?php use App\Core\Csrf; use App\Core\View; ?>
<?php $breadcrumb = [['label' => 'Beranda', 'url' => View::url('')], ['label' => 'Dashboard']]; ?>
<section class="container admin">
    <header class="admin__head">
        <div>
            <p class="eyebrow">Administrasi</p>
            <h1>Dashboard</h1>
            <p class="muted">Kelola koleksi VideoVault. <?= number_format((int)$total) ?> video.</p>
        </div>
        <a href="<?= View::url('video/create') ?>" class="btn btn--primary">+ Tambah video</a>
    </header>
    <div class="stats">
        <div class="stat"><span>Video</span><strong><?= number_format($stats['videos']) ?></strong></div>
        <div class="stat"><span>Pengguna</span><strong><?= number_format($stats['users']) ?></strong></div>
        <div class="stat"><span>Kategori</span><strong><?= number_format($stats['categories']) ?></strong></div>
        <div class="stat"><span>Total views</span><strong><?= number_format($stats['views']) ?></strong></div>
    </div>
    <header class="section__head">
        <h2>Kelola Video</h2>
        <form method="get" class="search search--inline">
            <input type="search" name="q" placeholder="Cari…" value="<?= View::e($q) ?>" aria-label="Cari video">
            <button class="btn btn--primary btn--sm">Cari</button>
        </form>
    </header>
    <?php if (!$videos): ?>
        <div class="empty">
            <p class="empty__icon">◌</p>
            <h3><?= $q ? 'Tidak ada hasil' : 'Belum ada video' ?></h3>
            <?php if (!$q): ?><a href="<?= View::url('video/create') ?>" class="btn btn--primary">Tambah video</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Thumb</th><th>Judul</th><th>Kategori</th><th>Views</th><th>Tanggal</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($videos as $v): ?>
                    <tr>
                        <td>
                            <?php if (!empty($v['thumbnail'])): ?>
                                <img class="thumb" src="<?= View::url('uploads/thumbnails/sm/' . $v['thumbnail']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <div class="thumb thumb--empty">◐</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= View::e($v['title']) ?></strong></td>
                        <td><?= View::e($v['category_name'] ?? '—') ?></td>
                        <td><?= number_format((int)$v['views']) ?></td>
                        <td><?= date('d M Y', strtotime($v['created_at'])) ?></td>
                        <td class="table__actions">
                            <a href="<?= View::url('video/' . $v['id']) ?>" class="btn btn--ghost btn--sm">Lihat</a>
                            <a href="<?= View::url('video/' . $v['id'] . '/edit') ?>" class="btn btn--ghost btn--sm">Edit</a>
                            <form method="post" action="<?= View::url('video/' . $v['id'] . '/delete') ?>" class="inline" data-confirm="Hapus video ini?">
                                <?= Csrf::field() ?>
                                <button class="btn btn--danger btn--sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pager">
                <?php $qb = array_filter(['q' => $q]); ?>
                <?php if ($page > 1): ?><a class="pager__link" href="<?= View::url('admin?' . http_build_query(array_merge($qb, ['page' => $page - 1]))) ?>">← Prev</a><?php endif; ?>
                <span class="pager__info">Halaman <?= $page ?> / <?= $pages ?></span>
                <?php if ($page < $pages): ?><a class="pager__link" href="<?= View::url('admin?' . http_build_query(array_merge($qb, ['page' => $page + 1]))) ?>">Next →</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
