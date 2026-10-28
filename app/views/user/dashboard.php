<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<script>window.REEL_CSRF = <?= json_encode(Csrf::token()) ?>;</script>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error" data-flash><?= e($error) ?></div><?php endif; ?>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h1 class="font-display font-semibold text-2xl text-ink">Welcome back, <?= e(explode(' ', Auth::name())[0]) ?></h1>
            <p class="text-inkmute text-sm mt-1">Your memos, at a glance.</p>
        </div>
        <a href="/index.php?route=user/memos/new" class="btn-primary">
            <span class="rec-dot"></span> New memo
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="stat-tile stat-tile-accent">
            <span class="stat-value"><?= (int)$memoCount ?></span>
            <span class="stat-label">Total memos</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value font-mono-tab"><?= e(format_duration($totalSeconds)) ?></span>
            <span class="stat-label">Total recorded time</span>
        </div>
        <div class="stat-tile stat-tile-mint">
            <span class="stat-value"><?= count($folders) ?></span>
            <span class="stat-label">Folders</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= count(array_filter($recentMemos, fn($m) => (bool)$m['is_starred'])) ?></span>
            <span class="stat-label">Starred (recent)</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Recent memos</h2>
            <a href="/index.php?route=user/memos" class="text-sm text-violet-bright font-semibold hover:underline">View all &rarr;</a>
        </div>

        <?php if (empty($recentMemos)): ?>
            <div class="empty-state">
                No memos yet. Tap <a href="/index.php?route=user/memos/new">New memo</a> to record your first one.
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentMemos as $memo): ?>
                    <a href="/index.php?route=user/memos/view&id=<?= (int)$memo['id'] ?>" class="memo-row">
                        <div class="waveform-static" data-waveform-seed="<?= (int)$memo['id'] ?>" data-waveform-bars="20" style="width:90px;"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-ink truncate"><?= e($memo['title']) ?></span>
                                <?php if ($memo['is_starred']): ?><span class="text-amber-400">&#9733;</span><?php endif; ?>
                            </div>
                            <div class="text-xs text-inkmute mt-0.5"><?= e(format_datetime($memo['created_at'])) ?> &middot; <?= e(format_duration((int)$memo['duration_seconds'])) ?><?= $memo['folder_name'] ? ' &middot; ' . e($memo['folder_name']) : '' ?></div>
                        </div>
                        <span class="badge <?= $memo['source'] === 'recorded' ? 'badge-violet' : 'badge-slate' ?>"><?= e($memo['source']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
