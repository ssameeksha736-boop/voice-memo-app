<?php

require_once __DIR__ . '/Database.php';

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Clears the auth session but keeps a live session to carry the flash
     * message through the redirect. (Auth::logout()'s session_destroy() +
     * cookie delete would throw the flash away with it, so the user would
     * land on a bare login form with no explanation of what happened.)
     */
    private static function forceLogout(string $message): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        flash_set('error', $message);
        redirect('login');
    }

    /**
     * Account status is re-read from the DB on every request, not just trusted
     * from the session as it was at login time. Without this, a Super Admin
     * deactivating a user mid-session did nothing until that user happened to
     * log out -- they kept full access to record, edit and delete memos with a
     * session minted while they were still active.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login');
        }

        static $verified = false;
        if ($verified) {
            return; // one check per request, not once per requireRole() call
        }

        $stmt = Database::connect()->prepare('SELECT role, status FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([self::id()]);
        $user = $stmt->fetch();

        if (!$user) {
            self::forceLogout('Your account no longer exists. Please sign in again.');
        }
        if ($user['status'] !== 'active') {
            self::forceLogout('Your account has been deactivated by an administrator. Please contact support.');
        }

        // Keep the session's role in step with the DB too, so a role change
        // can't leave a stale privilege sitting in an open session.
        $_SESSION['user_role'] = $user['role'];
        $verified = true;
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            require __DIR__ . '/../app/views/errors/404.php';
            exit;
        }
    }

    public static function dashboardRoute(string $role): string
    {
        return match ($role) {
            'super_admin' => 'admin/dashboard',
            'user'        => 'user/dashboard',
            default       => 'login',
        };
    }
}
