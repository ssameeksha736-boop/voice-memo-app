<?php
$pageTitle = 'User Overview';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <a href="/index.php?route=admin/users" class="text-sm text-violet-bright font-semibold hover:underline">&larr; Back to users</a>

    <div class="flex items-center justify-between flex-wrap gap-4 mt-3 mb-6">
        <div>
            <h1 class="font-display font-semibold text-2xl text-ink"><?= e($user['name']) ?></h1>
            <p class="text-inkmute text-sm mt-1"><?= e($user['email']) ?> &middot; <span class="badge <?= $user['status'] === 'active' ? 'badge-mint' : 'badge-slate' ?>"><?= e($user['status']) ?></span></p>
        </div>
        <form method="POST" action="/index.php?route=admin/users/status" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <input type="hidden" name="status" value="<?= $user['status'] === 'active' ? 'inactive' : 'active' ?>">
            <button type="submit" class="btn-secondary"><?= $user['status'] === 'active' ? 'Deactivate account' : 'Activate account' ?></button>
        </form>
    </div>

    <div class="alert alert-success !bg-violet-soft !text-violet-bright !border-violet/30">
        This overview shows only activity counts. Memo audio, transcripts, and summaries are private to the user and are never queried here -- see <code class="font-mono">Memo::statsForUser()</code> in the README's viva notes.
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-5">
        <div class="stat-tile stat-tile-accent">
            <span class="stat-value"><?= (int)$stats['memo_count'] ?></span>
            <span class="stat-label">Total memos</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value font-mono-tab"><?= e(format_duration($stats['total_seconds'])) ?></span>
            <span class="stat-label">Total recorded time</span>
        </div>
        <div class="stat-tile stat-tile-mint">
            <span class="stat-value"><?= (int)$stats['starred_count'] ?></span>
            <span class="stat-label">Starred</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= (int)$stats['recorded_count'] ?></span>
            <span class="stat-label">Recorded</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= (int)$stats['uploaded_count'] ?></span>
            <span class="stat-label">Uploaded</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value text-base"><?= $stats['last_memo_at'] ? e(format_datetime($stats['last_memo_at'])) : '-' ?></span>
            <span class="stat-label">Last activity</span>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
