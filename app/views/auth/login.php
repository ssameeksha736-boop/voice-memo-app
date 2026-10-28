<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in &middot; Reel &mdash; Voice Memo Studio</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230B0B10'/%3E%3Cpath d='M9 6a3 3 0 0 1 6 0v6a3 3 0 0 1-6 0z' fill='%238B5CF6'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { theme: { extend: { colors: {
        studio: { DEFAULT: '#0B0B10', surface: '#15151D', card: '#1A1A24', inset: '#0E0E14' },
        line: '#2A2A36', ink: '#EDEBF5', inkmute: '#8E8BA3',
        violet: { DEFAULT: '#8B5CF6', bright: '#A78BFA', deep: '#5B3FA0', soft: '#211A3B' },
        rec: '#FB4D4D', mint: '#34D399',
    }, fontFamily: { display: ['"Space Grotesk"', 'sans-serif'], body: ['"Inter"', 'sans-serif'], mono: ['"JetBrains Mono"', 'monospace'] } } } };
</script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="font-body auth-hero min-h-screen flex items-center justify-center px-4 py-10">
<div class="w-full max-w-md">
    <div class="flex items-center justify-center gap-3 mb-8">
        <span class="w-12 h-12 rounded-2xl bg-violet-soft border border-violet/40 flex items-center justify-center">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 6a3 3 0 0 1 6 0v6a3 3 0 0 1-6 0z" fill="#A78BFA"/><path d="M6 11a6 6 0 0 0 12 0M12 17v3" stroke="#A78BFA" stroke-width="1.6" stroke-linecap="round"/></svg>
        </span>
        <div class="leading-tight">
            <div class="font-display font-semibold text-ink text-2xl tracking-tight">Reel</div>
            <div class="text-inkmute text-xs tracking-wide uppercase">Voice Memo Studio</div>
        </div>
    </div>
    <div class="bg-studio-card border border-line rounded-2xl shadow-2xl shadow-black/40 p-8">
        <h1 class="font-display font-semibold text-xl text-ink mb-1">Welcome back</h1>
        <p class="text-inkmute text-sm mb-6">Sign in to record, transcribe, and organize your memos.</p>

        <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php /* Error flashes land here too -- e.g. Auth::requireLogin() bouncing a
                 session whose account was deactivated mid-use. Without this the
                 user was dropped on a bare login form with no explanation. */ ?>
        <?php if ($flashError = flash_get('error')): ?><div class="alert alert-error"><?= e($flashError) ?></div><?php endif; ?>
        <?php if (!empty($errors['login'])): ?><div class="alert alert-error"><?= e($errors['login']) ?></div><?php endif; ?>

        <form method="POST" action="/index.php?route=login" class="space-y-4">
            <?= Csrf::field() ?>
            <div>
                <label class="field-label">Email</label>
                <input type="email" name="email" class="field-input" value="<?= old($_POST ?? [], 'email') ?>" required autofocus>
                <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="field-label">Password</label>
                <input type="password" name="password" class="field-input" required>
                <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full justify-center">Log in</button>
        </form>
    </div>
    <p class="text-center text-inkmute text-xs mt-6">Accounts are admin-provisioned &mdash; see this project's README for demo logins.</p>
</div>
</body>
</html>
