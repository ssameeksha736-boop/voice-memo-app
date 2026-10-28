<?php

require_once __DIR__ . '/../../core/Database.php';

class Tag
{
    /**
     * Find an existing tag by name or create it. Returns its id.
     *
     * `tags.name` is UNIQUE and this table is shared platform-wide, so a plain
     * SELECT-then-INSERT races: two users saving memos that both introduce the
     * same brand-new tag at the same time each see the SELECT miss, and the
     * loser's INSERT hits a 1062 duplicate-key error. That exception was
     * uncaught, so it surfaced as a blank HTTP 500 page -- while the memo row
     * itself had already been inserted by Memo::create() a moment earlier, so
     * the memo was actually saved and the user was told it had failed.
     * Catching the duplicate and re-reading the winner's row makes the whole
     * thing idempotent: whoever loses simply adopts the id that now exists.
     */
    public static function findOrCreate(string $name): int
    {
        $name = trim($name);
        $pdo = Database::connect();

        $stmt = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
        $stmt->execute([$name]);
        $tag = $stmt->fetch();
        if ($tag) {
            return (int)$tag['id'];
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO tags (name) VALUES (?)');
            $stmt->execute([$name]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // Lost the race -- another request created this exact tag between
            // our SELECT and our INSERT. Re-read and use theirs.
            $stmt = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
            $stmt->execute([$name]);
            $tag = $stmt->fetch();
            if ($tag) {
                return (int)$tag['id'];
            }
            throw $e; // a genuinely different failure -- don't swallow it
        }
    }

    /** Parses a comma-separated tag string from a form field into a clean, de-duplicated list. */
    public static function parseList(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_filter($parts, fn($t) => $t !== '');
        $parts = array_map(fn($t) => mb_strtolower(substr($t, 0, 30)), $parts);
        return array_values(array_unique($parts));
    }

    public static function forMemo(int $memoId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT t.name FROM tags t
             JOIN memo_tags mt ON mt.tag_id = t.id
             WHERE mt.memo_id = ? ORDER BY t.name'
        );
        $stmt->execute([$memoId]);
        return array_column($stmt->fetchAll(), 'name');
    }

    /** Distinct tag names a given user has ever used, for the filter dropdown. */
    public static function forUser(int $userId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT DISTINCT t.name FROM tags t
             JOIN memo_tags mt ON mt.tag_id = t.id
             JOIN memos m ON m.id = mt.memo_id
             WHERE m.user_id = ? ORDER BY t.name'
        );
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'name');
    }
}
