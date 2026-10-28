<?php

require_once __DIR__ . '/../models/Memo.php';
require_once __DIR__ . '/../models/User.php';

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole('super_admin');

        $stats = Memo::platformStats();
        $dailyVolume = Memo::dailyVolume(14);

        $users = User::allUsers();
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));

        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function users(): void
    {
        Auth::requireRole('super_admin');

        $users = User::allUsers();
        $statsByUser = [];
        foreach ($users as $u) {
            $statsByUser[$u['id']] = Memo::statsForUser((int)$u['id']);
        }

        require __DIR__ . '/../views/admin/users.php';
    }

    public function userStatus(): void
    {
        Auth::requireRole('super_admin');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

        if (User::setStatus($id, $status)) {
            flash_set('success', 'User status updated.');
        } else {
            flash_set('error', 'User not found.');
        }
        redirect('admin/users');
    }

    /** Per-user activity overview -- structurally never touches memo content (see Memo::statsForUser). */
    public function userOverview(): void
    {
        Auth::requireRole('super_admin');

        $userId = (int)($_GET['user_id'] ?? 0);
        $user = User::find($userId);
        if (!$user || $user['role'] !== 'user') {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $stats = Memo::statsForUser($userId);

        require __DIR__ . '/../views/admin/user_overview.php';
    }
}
