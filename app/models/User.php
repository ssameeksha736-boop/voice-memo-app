<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function allUsers(): array
    {
        $stmt = Database::connect()->query(
            "SELECT id, name, email, status, created_at FROM users WHERE role = 'user' ORDER BY name"
        );
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE users SET status = ? WHERE id = ? AND role = 'user'"
        );
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() > 0;
    }
}
