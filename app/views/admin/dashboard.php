<?php
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';

$maxDaily = 1;
foreach ($dailyVolume as $row) {
    $maxDaily = max($maxDaily, (int)$row['c']);
}
?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>

    <h1 class="font-display font-semibold text-2xl text-ink mb-1">Studio overview</h1>
    <p class="text-inkmute text-sm mb-6">Platform-wide usage stats. Memo audio, transcripts, and summaries stay private to each user -- this dashboard only ever sees counts and timestamps, never memo content.</p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="stat-tile stat-tile-accent">
            <span class="stat-value"><?= (int)$stats['memo_count'] ?></span>
            <span class="stat-label">Total memos</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value font-mono-tab"><?= e(format_duration($stats['total_seconds'])) ?></span>
            <span class="stat-label">Total recorded time</span>
        </div>
        <div class="stat-tile stat-tile-mint">
            <span class="stat-value"><?= (int)$stats['today_count'] ?></span>
            <span class="stat-label">Memos today</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= (int)$activeUsers ?>/<?= (int)$totalUsers ?></span>
            <span class="stat-label">Active users</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Daily memo volume (last 14 days)</h2></div>
        <?php if (empty($dailyVolume)): ?>
            <div class="empty-state">No memos recorded in this window yet.</div>
        <?php else: ?>
            <div class="flex items-end gap-2" style="height:120px;">
                <?php foreach ($dailyVolume as $row): ?>
                    <?php $h = max(4, round(((int)$row['c'] / $maxDaily) * 100)); ?>
                    <div class="flex-1 flex flex-col items-center justify-end gap-1" title="<?= e($row['d']) ?>: <?= (int)$row['c'] ?>">
                        <div class="w-full rounded-t" style="height:<?= $h ?>px; background:linear-gradient(180deg,#A78BFA,#5B3FA0);"></div>
                        <span class="text-[10px] text-inkmute font-mono-tab"><?= e(date('d/m', strtotime($row['d']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Memos by source</h2></div>
        <div class="flex gap-3 flex-wrap">
            <?php foreach ($stats['by_source'] as $row): ?>
                <span class="badge <?= $row['source'] === 'recorded' ? 'badge-violet' : 'badge-slate' ?>"><?= e($row['source']) ?>: <?= (int)$row['c'] ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="text-right">
        <a href="/index.php?route=admin/users" class="text-sm text-violet-bright font-semibold hover:underline">Manage users &rarr;</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
