<?php
$pageTitle = 'Folders';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error" data-flash><?= e($error) ?></div><?php endif; ?>

    <h1 class="font-display font-semibold text-2xl text-ink mb-1">Folders</h1>
    <p class="text-inkmute text-sm mb-6">Organize memos into folders. Deleting a folder keeps its memos -- they just move to Unfiled.</p>

    <form method="POST" action="/index.php?route=user/folders/store" class="card flex items-end gap-3">
        <?= Csrf::field() ?>
        <div class="flex-1">
            <label class="field-label">New folder name</label>
            <input type="text" name="name" class="field-input" maxlength="60" placeholder="e.g. Work" required>
        </div>
        <button type="submit" class="btn-primary">Create</button>
    </form>

    <div class="mt-5 space-y-3">
        <?php if (empty($folders)): ?>
            <div class="card"><div class="empty-state">No folders yet.</div></div>
        <?php else: ?>
            <?php foreach ($folders as $f): ?>
                <div class="folder-chip">
                    <div>
                        <div class="font-semibold text-ink"><?= e($f['name']) ?></div>
                        <div class="text-xs text-inkmute"><?= (int)$f['memo_count'] ?> memo<?= (int)$f['memo_count'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="/index.php?route=user/memos&folder_id=<?= (int)$f['id'] ?>" class="btn-ghost btn-sm">View memos</a>
                        <form method="POST" action="/index.php?route=user/folders/delete" class="inline-form" data-confirm="Delete this folder? Its memos will be kept as Unfiled.">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
