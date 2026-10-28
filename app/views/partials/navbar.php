<?php
$role = Auth::role();
$navItems = [];
if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard' => 'Dashboard',
        'admin/users'     => 'Users',
    ];
} elseif ($role === 'user') {
    $navItems = [
        'user/dashboard'  => 'Dashboard',
        'user/memos'      => 'Memos',
        'user/folders'    => 'Folders',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="sticky top-0 z-30 bg-studio-surface/95 backdrop-blur border-b border-line">
    <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <a href="/index.php?route=<?= e(Auth::dashboardRoute($role ?? '')) ?>" class="flex items-center gap-2.5 shrink-0">
            <span class="w-9 h-9 rounded-xl bg-violet-soft border border-violet/40 flex items-center justify-center">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 6a3 3 0 0 1 6 0v6a3 3 0 0 1-6 0z" fill="#A78BFA"/><path d="M6 11a6 6 0 0 0 12 0M12 17v3" stroke="#A78BFA" stroke-width="1.6" stroke-linecap="round"/></svg>
            </span>
            <span class="font-display font-semibold text-ink text-lg tracking-tight hidden sm:inline">Reel</span>
        </a>
        <div class="flex-1 flex items-center gap-1 text-sm font-medium px-3">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="nav-link px-3 py-2 rounded-lg whitespace-nowrap <?= $currentRoute === $route ? 'nav-link-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="hidden md:flex flex-col items-end leading-tight">
                <span class="text-ink text-sm font-semibold"><?= e(Auth::name()) ?></span>
                <span class="text-inkmute text-xs capitalize"><?= e(str_replace('_', ' ', $role ?? '')) ?></span>
            </span>
            <form method="POST" action="/index.php?route=logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-ghost btn-sm">Log out</button>
            </form>
        </div>
    </div>
</nav>
