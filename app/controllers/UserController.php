<?php

require_once __DIR__ . '/../models/Memo.php';
require_once __DIR__ . '/../models/Folder.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../../core/Upload.php';
require_once __DIR__ . '/../../core/Summarizer.php';

class UserController
{
    public function dashboard(): void
    {
        Auth::requireRole('user');

        $userId = Auth::id();
        $recentMemos = Memo::recentForUser($userId, 5);
        $memoCount = Memo::memoCount($userId);
        $totalSeconds = Memo::totalDuration($userId);
        $folders = Folder::forUser($userId);

        require __DIR__ . '/../views/user/dashboard.php';
    }

    public function memos(): void
    {
        Auth::requireRole('user');

        $filters = [
            'q'         => trim(str_field($_GET, 'q')),
            'folder_id' => trim(str_field($_GET, 'folder_id')),
            'tag'       => trim(str_field($_GET, 'tag')),
            'starred'   => trim(str_field($_GET, 'starred')),
            'source'    => trim(str_field($_GET, 'source')),
        ];

        $memos = Memo::byUser(Auth::id(), $filters);
        $folders = Folder::forUser(Auth::id());
        $availableTags = Tag::forUser(Auth::id());

        require __DIR__ . '/../views/user/memos.php';
    }

    public function newMemo(): void
    {
        Auth::requireRole('user');
        $errors = [];
        $old = [];
        $folders = Folder::forUser(Auth::id());
        require __DIR__ . '/../views/user/memo_form.php';
    }

    public function store(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $title = trim(str_field($_POST, 'title')) ?: 'Untitled memo';
        $transcript = trim(str_field($_POST, 'transcript'));
        $tags = Tag::parseList(str_field($_POST, 'tags'));
        $source = str_field($_POST, 'source') === 'uploaded' ? 'uploaded' : 'recorded';
        $duration = max(0, min(4 * 3600, (int)($_POST['duration'] ?? 0)));
        $folderId = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;

        $v = new Validator();
        $v->maxLength($_POST, 'title', 150, 'Title')
          ->maxLength($_POST, 'transcript', 20000, 'Transcript');

        if ($folderId !== null && !Folder::find($folderId, Auth::id())) {
            $folderId = null; // silently drop a folder id that isn't ours
        }

        // The record tab and upload tab post through two distinct file input
        // names (only one is ever populated, depending on $source) rather
        // than sharing one input -- avoids the undefined behavior of two
        // <input type=file> elements sharing a name in the same form.
        $fileKey = $source === 'uploaded' ? 'audio_uploaded' : 'audio_recorded';
        $stored = Upload::storeMemoAudio($_FILES[$fileKey] ?? []);
        if (!$stored) {
            $errors = $v->errors();
            $errors['audio'] = 'Please provide a valid audio recording or upload (webm, ogg, mp3, wav, m4a, aac -- max 25MB).';
            $old = $_POST;
            $folders = Folder::forUser(Auth::id());
            require __DIR__ . '/../views/user/memo_form.php';
            return;
        }

        if ($v->fails()) {
            Upload::deleteMemoAudio($stored['path']);
            $errors = $v->errors();
            $old = $_POST;
            $folders = Folder::forUser(Auth::id());
            require __DIR__ . '/../views/user/memo_form.php';
            return;
        }

        $memoId = Memo::create(
            Auth::id(), $title, $stored['path'], $stored['mime'], $duration,
            $source, $folderId, $transcript, $tags
        );

        flash_set('success', 'Memo saved.');
        redirect('user/memos/view?id=' . $memoId);
    }

    public function viewMemo(): void
    {
        Auth::requireRole('user');

        $id = (int)($_GET['id'] ?? 0);
        $memo = Memo::find($id, Auth::id());
        if (!$memo) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $analysis = !empty($memo['transcript']) ? Summarizer::analyze($memo['transcript']) : null;

        require __DIR__ . '/../views/user/memo_view.php';
    }

    /**
     * Streams a memo's audio through an ownership check instead of serving it
     * as a static file -- a static /uploads/<random>.<ext> URL has no way to
     * revoke access once the filename leaks (Referer, screenshot, browser
     * history sync, ...), so this route re-checks Memo::find($id, Auth::id())
     * exactly like every other memo action before streaming the file.
     */
    public function audio(): void
    {
        Auth::requireRole('user');

        $id = (int)($_GET['id'] ?? 0);
        $memo = Memo::find($id, Auth::id());
        if (!$memo) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        Upload::streamMemoAudio($memo['audio_path'], $memo['audio_mime']);
    }

    public function editMemo(): void
    {
        Auth::requireRole('user');

        $id = (int)($_GET['id'] ?? 0);
        $memo = Memo::find($id, Auth::id());
        if (!$memo) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $errors = [];
        $old = [
            'title'      => $memo['title'],
            'transcript' => $memo['transcript'],
            'tags'       => implode(', ', $memo['tags']),
            'folder_id'  => $memo['folder_id'],
        ];
        $folders = Folder::forUser(Auth::id());

        require __DIR__ . '/../views/user/memo_edit.php';
    }

    public function update(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $title = trim(str_field($_POST, 'title')) ?: 'Untitled memo';
        $transcript = trim(str_field($_POST, 'transcript'));
        $tags = Tag::parseList(str_field($_POST, 'tags'));
        $folderId = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;

        $v = new Validator();
        $v->maxLength($_POST, 'title', 150, 'Title')
          ->maxLength($_POST, 'transcript', 20000, 'Transcript');

        if ($folderId !== null && !Folder::find($folderId, Auth::id())) {
            $folderId = null;
        }

        if ($v->fails()) {
            $memo = ['id' => $id];
            $errors = $v->errors();
            $old = $_POST;
            $folders = Folder::forUser(Auth::id());
            require __DIR__ . '/../views/user/memo_edit.php';
            return;
        }

        if (!Memo::update($id, Auth::id(), $title, $folderId, $transcript, $tags)) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        flash_set('success', 'Memo updated.');
        redirect('user/memos/view?id=' . $id);
    }

    public function delete(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        $audioPath = Memo::delete($id, Auth::id());
        if ($audioPath !== null) {
            Upload::deleteMemoAudio($audioPath);
            flash_set('success', 'Memo deleted.');
        } else {
            flash_set('error', 'Memo not found.');
        }
        redirect('user/memos');
    }

    public function star(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        Memo::toggleStar($id, Auth::id());

        $back = trim(str_field($_POST, 'back')) ?: 'user/memos';
        redirect($back);
    }

    /** AJAX endpoint: recompute the extractive summary live from a transcript draft, before saving. */
    public function summarizePreview(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        // str_field() guards against e.g. transcript[]=x, which would
        // otherwise reach trim() as an array and throw an uncaught TypeError.
        $transcript = trim(str_field($_POST, 'transcript'));
        header('Content-Type: application/json');

        if ($transcript === '') {
            echo json_encode(['summary' => '', 'keywords' => [], 'sentences' => []]);
            return;
        }

        $analysis = Summarizer::analyze($transcript);
        echo json_encode([
            'summary'   => $analysis['summary'],
            'keywords'  => $analysis['keywords'],
            'sentences' => $analysis['sentences'],
        ]);
    }

    public function folders(): void
    {
        Auth::requireRole('user');
        $folders = Folder::forUser(Auth::id());
        require __DIR__ . '/../views/user/folders.php';
    }

    public function folderStore(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $name = trim(str_field($_POST, 'name'));
        if ($name === '') {
            flash_set('error', 'Folder name is required.');
        } elseif (Folder::create(Auth::id(), mb_substr($name, 0, 60)) === null) {
            flash_set('error', 'You already have a folder with that name.');
        } else {
            flash_set('success', 'Folder created.');
        }
        redirect('user/folders');
    }

    public function folderDelete(): void
    {
        Auth::requireRole('user');
        Csrf::requireValid();

        $id = (int)($_POST['id'] ?? 0);
        if (Folder::delete($id, Auth::id())) {
            flash_set('success', 'Folder deleted. Its memos were kept (moved to Unfiled).');
        } else {
            flash_set('error', 'Folder not found.');
        }
        redirect('user/folders');
    }
}
