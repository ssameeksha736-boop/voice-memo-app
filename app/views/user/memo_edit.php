<?php
$pageTitle = 'Edit Memo';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="font-display font-semibold text-2xl text-ink mb-1">Edit memo</h1>
    <p class="text-inkmute text-sm mb-6">Update the title, folder, tags, or transcript. The summary is recomputed automatically from the transcript when you save.</p>

    <form method="POST" action="/index.php?route=user/memos/update" class="card space-y-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int)$memo['id'] ?>">

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Title</label>
                <input type="text" name="title" class="field-input" value="<?= old($old, 'title') ?>" maxlength="150">
                <?php if (!empty($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="field-label">Folder</label>
                <select name="folder_id" class="field-select">
                    <option value="">Unfiled</option>
                    <?php foreach ($folders as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= (string)($old['folder_id'] ?? '') === (string)$f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="field-label">Tags <span class="field-hint">(comma-separated)</span></label>
            <input type="text" name="tags" class="field-input" value="<?= old($old, 'tags') ?>">
        </div>

        <div>
            <label class="field-label">Transcript</label>
            <textarea name="transcript" rows="8" class="field-textarea"><?= old($old, 'transcript') ?></textarea>
            <?php if (!empty($errors['transcript'])): ?><p class="field-error"><?= e($errors['transcript']) ?></p><?php endif; ?>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="/index.php?route=user/memos/view&id=<?= (int)$memo['id'] ?>" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Save changes</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
