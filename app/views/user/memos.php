<?php
$pageTitle = 'My Memos';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<script>window.REEL_CSRF = <?= json_encode(Csrf::token()) ?>;</script>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error" data-flash><?= e($error) ?></div><?php endif; ?>

    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <h1 class="font-display font-semibold text-2xl text-ink">My Memos</h1>
        <a href="/index.php?route=user/memos/new" class="btn-primary">New memo</a>
    </div>

    <form method="GET" action="/index.php" class="card flex flex-wrap items-end gap-3">
        <input type="hidden" name="route" value="user/memos">
        <div class="flex-1 min-w-[200px]">
            <label class="field-label">Search title / transcript</label>
            <input type="text" name="q" class="field-input" placeholder="e.g. budget, lecture..." value="<?= old($_GET, 'q') ?>">
        </div>
        <div>
            <label class="field-label">Folder</label>
            <select name="folder_id" class="field-select">
                <option value="">All folders</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= (string)($_GET['folder_id'] ?? '') === (string)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Tag</label>
            <select name="tag" class="field-select">
                <option value="">All tags</option>
                <?php foreach ($availableTags as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($_GET['tag'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Source</label>
            <select name="source" class="field-select">
                <option value="">Any</option>
                <option value="recorded" <?= ($_GET['source'] ?? '') === 'recorded' ? 'selected' : '' ?>>Recorded</option>
                <option value="uploaded" <?= ($_GET['source'] ?? '') === 'uploaded' ? 'selected' : '' ?>>Uploaded</option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink pb-2">
            <input type="checkbox" name="starred" value="1" <?= !empty($_GET['starred']) ? 'checked' : '' ?>> Starred only
        </label>
        <button type="submit" class="btn-secondary">Filter</button>
        <?php if (!empty($_GET['q']) || !empty($_GET['folder_id']) || !empty($_GET['tag']) || !empty($_GET['starred']) || !empty($_GET['source'])): ?>
            <a href="/index.php?route=user/memos" class="btn-ghost">Clear</a>
        <?php endif; ?>
    </form>

    <div class="mt-4">
        <?php if (empty($memos)): ?>
            <div class="card"><div class="empty-state">No memos match these filters.</div></div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($memos as $memo): ?>
                    <div class="memo-row">
                        <a href="/index.php?route=user/memos/view&id=<?= (int)$memo['id'] ?>" class="waveform-static shrink-0" data-waveform-seed="<?= (int)$memo['id'] ?>" data-waveform-bars="22" style="width:100px;"></a>
                        <div class="flex-1 min-w-0">
                            <a href="/index.php?route=user/memos/view&id=<?= (int)$memo['id'] ?>" class="font-semibold text-ink hover:underline truncate block"><?= e($memo['title']) ?></a>
                            <div class="text-xs text-inkmute mt-0.5 flex flex-wrap items-center gap-x-2">
                                <span><?= e(format_datetime($memo['created_at'])) ?></span>
                                <span>&middot; <?= e(format_duration((int)$memo['duration_seconds'])) ?></span>
                                <?php if ($memo['folder_name']): ?><span>&middot; <?= e($memo['folder_name']) ?></span><?php endif; ?>
                                <?php foreach ($memo['tags'] as $t): ?><span class="badge badge-slate"><?= e($t) ?></span><?php endforeach; ?>
                            </div>
                        </div>
                        <span class="badge <?= $memo['source'] === 'recorded' ? 'badge-violet' : 'badge-slate' ?>"><?= e($memo['source']) ?></span>
                        <form method="POST" action="/index.php?route=user/memos/star" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$memo['id'] ?>">
                            <input type="hidden" name="back" value="user/memos">
                            <button type="submit" class="icon-btn <?= $memo['is_starred'] ? 'starred' : '' ?>" title="Toggle star">&#9733;</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
