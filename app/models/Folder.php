<?php

require_once __DIR__ . '/../../core/Database.php';

class Folder
{
    public static function forUser(int $userId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT f.*, (SELECT COUNT(*) FROM memos m WHERE m.folder_id = f.id) AS memo_count
             FROM folders f WHERE f.user_id = ? ORDER BY f.name'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM folders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, string $name): ?int
    {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare('INSERT INTO folders (user_id, name) VALUES (?, ?)');
            $stmt->execute([$userId, $name]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // Unique (user_id, name) violation -- folder already exists for this user.
            return null;
        }
    }

    public static function delete(int $id, int $userId): bool
    {
        // Memos in a deleted folder aren't deleted -- their folder_id just goes
        // NULL via the FK's ON DELETE SET NULL, so memos are never silently lost.
        $stmt = Database::connect()->prepare('DELETE FROM folders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
