<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Summarizer.php';
require_once __DIR__ . '/Tag.php';

class Memo
{
    // ------------------------------------------------------------------
    // User-facing CRUD (these DO select transcript/summary/audio_path --
    // only the owning user ever sees a memo's actual content, see the
    // Super-Admin-facing section below for the structurally privacy-safe
    // counterparts).
    // ------------------------------------------------------------------

    public static function create(
        int $userId,
        string $title,
        string $audioPath,
        string $audioMime,
        int $durationSeconds,
        string $source,
        ?int $folderId,
        string $transcript,
        array $tagNames
    ): int {
        $summary = $transcript !== '' ? Summarizer::summarize($transcript) : '';
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'INSERT INTO memos (user_id, folder_id, title, audio_path, audio_mime, duration_seconds, source, transcript, summary)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $folderId, $title, $audioPath, $audioMime, $durationSeconds, $source, $transcript, $summary]);
        $memoId = (int)$pdo->lastInsertId();

        self::syncTags($memoId, $tagNames);

        return $memoId;
    }

    public static function update(
        int $id,
        int $userId,
        string $title,
        ?int $folderId,
        string $transcript,
        array $tagNames
    ): bool {
        $summary = $transcript !== '' ? Summarizer::summarize($transcript) : '';
        $pdo = Database::connect();

        $stmt = $pdo->prepare(
            'UPDATE memos SET title = ?, folder_id = ?, transcript = ?, summary = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$title, $folderId, $transcript, $summary, $id, $userId]);

        if ($stmt->rowCount() === 0 && !self::find($id, $userId)) {
            return false; // genuinely not found / not owned
        }

        self::syncTags($id, $tagNames);
        return true;
    }

    public static function toggleStar(int $id, int $userId): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE memos SET is_starred = NOT is_starred WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    /** Returns the stored audio path (so the controller can delete the file) or null if not found/owned. */
    public static function delete(int $id, int $userId): ?string
    {
        $memo = self::find($id, $userId);
        if (!$memo) {
            return null;
        }
        $stmt = Database::connect()->prepare('DELETE FROM memos WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0 ? $memo['audio_path'] : null;
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT m.*, f.name AS folder_name
             FROM memos m LEFT JOIN folders f ON f.id = m.folder_id
             WHERE m.id = ? AND m.user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $memo = $stmt->fetch();
        if (!$memo) {
            return null;
        }
        $memo['tags'] = Tag::forMemo($id);
        return $memo;
    }

    /**
     * Own memos, newest first, with optional filters. Search matches title
     * and transcript text -- always scoped to the owning user, never
     * cross-user (this is the only place transcript text is ever queried
     * outside of find(), and both are gated server-side to Auth::id()).
     */
    public static function byUser(int $userId, array $filters = []): array
    {
        $sql = 'SELECT m.*, f.name AS folder_name FROM memos m
                LEFT JOIN folders f ON f.id = m.folder_id
                WHERE m.user_id = ?';
        $params = [$userId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (m.title LIKE ? OR m.transcript LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['folder_id'])) {
            $sql .= ' AND m.folder_id = ?';
            $params[] = (int)$filters['folder_id'];
        }
        if (!empty($filters['tag'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM memo_tags mt JOIN tags t ON t.id = mt.tag_id
                       WHERE mt.memo_id = m.id AND t.name = ?)';
            $params[] = mb_strtolower($filters['tag']);
        }
        if (!empty($filters['starred'])) {
            $sql .= ' AND m.is_starred = 1';
        }
        if (!empty($filters['source'])) {
            $sql .= ' AND m.source = ?';
            $params[] = $filters['source'];
        }

        $sql .= ' ORDER BY m.created_at DESC';

        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        $memos = $stmt->fetchAll();

        foreach ($memos as &$memo) {
            $memo['tags'] = Tag::forMemo((int)$memo['id']);
        }

        return $memos;
    }

    public static function memoCount(int $userId): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM memos WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetch()['c'];
    }

    public static function totalDuration(int $userId): int
    {
        $stmt = Database::connect()->prepare('SELECT COALESCE(SUM(duration_seconds),0) AS s FROM memos WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetch()['s'];
    }

    public static function recentForUser(int $userId, int $limit = 5): array
    {
        return array_slice(self::byUser($userId), 0, $limit);
    }

    // ------------------------------------------------------------------
    // Super-Admin-facing oversight queries -- structurally privacy-safe:
    // `transcript`, `summary`, and `audio_path` are never named in any
    // SELECT below, so a memo's actual content/audio can never leak into
    // the Super Admin's screens even by accident (same pattern as
    // JournalEntry's Coordinator-facing methods in project #13).
    // ------------------------------------------------------------------

    /** Platform-wide stats for the admin dashboard: never touches memo content. */
    public static function platformStats(): array
    {
        $pdo = Database::connect();
        $totals = $pdo->query(
            'SELECT COUNT(*) AS memo_count, COALESCE(SUM(duration_seconds),0) AS total_seconds
             FROM memos'
        )->fetch();

        $today = $pdo->query(
            'SELECT COUNT(*) AS c FROM memos WHERE DATE(created_at) = CURDATE()'
        )->fetch();

        $bySource = $pdo->query(
            'SELECT source, COUNT(*) AS c FROM memos GROUP BY source'
        )->fetchAll();

        return [
            'memo_count'    => (int)$totals['memo_count'],
            'total_seconds' => (int)$totals['total_seconds'],
            'today_count'   => (int)$today['c'],
            'by_source'     => $bySource,
        ];
    }

    /** Per-user activity summary for the admin user list/overview -- counts and timestamps only. */
    public static function statsForUser(int $userId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT COUNT(*) AS memo_count,
                    COALESCE(SUM(duration_seconds),0) AS total_seconds,
                    SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) AS starred_count,
                    SUM(CASE WHEN source = "recorded" THEN 1 ELSE 0 END) AS recorded_count,
                    SUM(CASE WHEN source = "uploaded" THEN 1 ELSE 0 END) AS uploaded_count,
                    MAX(created_at) AS last_memo_at
             FROM memos WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return [
            'memo_count'      => (int)$row['memo_count'],
            'total_seconds'   => (int)$row['total_seconds'],
            'starred_count'   => (int)$row['starred_count'],
            'recorded_count'  => (int)$row['recorded_count'],
            'uploaded_count'  => (int)$row['uploaded_count'],
            'last_memo_at'    => $row['last_memo_at'],
        ];
    }

    /** Daily memo volume for the last N days, for the admin dashboard's activity chart. */
    public static function dailyVolume(int $days = 14): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM memos
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY d'
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------

    private static function syncTags(int $memoId, array $tagNames): void
    {
        $pdo = Database::connect();
        $del = $pdo->prepare('DELETE FROM memo_tags WHERE memo_id = ?');
        $del->execute([$memoId]);

        if (empty($tagNames)) {
            return;
        }

        $link = $pdo->prepare('INSERT IGNORE INTO memo_tags (memo_id, tag_id) VALUES (?, ?)');
        foreach ($tagNames as $name) {
            $tagId = Tag::findOrCreate($name);
            $link->execute([$memoId, $tagId]);
        }
    }
}
