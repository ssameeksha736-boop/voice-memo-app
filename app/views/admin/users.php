<?php
$pageTitle = 'Manage Users';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error" data-flash><?= e($error) ?></div><?php endif; ?>

    <h1 class="font-display font-semibold text-2xl text-ink mb-1">Manage users</h1>
    <p class="text-inkmute text-sm mb-6">Activate/deactivate accounts and view each user's activity summary -- never their memo content.</p>

    <div class="card table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Memos</th>
                    <th>Total time</th>
                    <th>Last activity</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php $s = $statsByUser[$u['id']]; ?>
                    <tr>
                        <td class="font-semibold"><a href="/index.php?route=admin/users/overview&user_id=<?= (int)$u['id'] ?>" class="hover:underline"><?= e($u['name']) ?></a></td>
                        <td class="text-inkmute"><?= e($u['email']) ?></td>
                        <td><?= (int)$s['memo_count'] ?></td>
                        <td class="font-mono-tab"><?= e(format_duration($s['total_seconds'])) ?></td>
                        <td class="text-inkmute text-sm"><?= $s['last_memo_at'] ? e(format_datetime($s['last_memo_at'])) : '-' ?></td>
                        <td>
                            <span class="badge <?= $u['status'] === 'active' ? 'badge-mint' : 'badge-slate' ?>"><?= e($u['status']) ?></span>
                        </td>
                        <td>
                            <form method="POST" action="/index.php?route=admin/users/status" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button type="submit" class="btn-ghost btn-sm"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
