<?php

/**
 * Validated audio file upload helper (GROUNDRULES §7/§12): checks the real
 * MIME type (via finfo, not the client-supplied Content-Type) and size, then
 * stores the file under a randomized filename to avoid overwrite/path-
 * traversal risk from the original client filename. Used both for browser
 * uploads and for recordings the browser hands to us as a Blob (which
 * arrives here as a normal $_FILES entry too -- MediaRecorder output is
 * packaged into a File before the form submits, see assets/js/recorder.js).
 */
class Upload
{
    private const ALLOWED_AUDIO_MIME = [
        'audio/webm'   => 'webm',
        'audio/ogg'    => 'ogg',
        'application/ogg' => 'ogg',
        'audio/mpeg'   => 'mp3',
        'audio/mp3'    => 'mp3',
        'audio/mp4'    => 'm4a',
        'audio/x-m4a'  => 'm4a',
        'audio/wav'    => 'wav',
        'audio/x-wav'  => 'wav',
        'audio/wave'   => 'wav',
        'audio/aac'    => 'aac',
        'audio/3gpp'   => '3gp',
    ];
    private const MAX_AUDIO_BYTES = 25 * 1024 * 1024; // 25MB -- generous for a voice memo

    /**
     * @param array $file one entry of $_FILES
     * @return array{path: string, mime: string}|null the stored relative path + detected mime, or null on failure
     */
    public static function storeMemoAudio(array $file): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($file['size'] > self::MAX_AUDIO_BYTES || $file['size'] <= 0) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_AUDIO_MIME[$mime])) {
            return null;
        }

        $ext = self::ALLOWED_AUDIO_MIME[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destDir = __DIR__ . '/../public/uploads/memos';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
            return null;
        }

        return ['path' => 'memos/' . $filename, 'mime' => $mime];
    }

    /**
     * Streams a memo's audio file to the client. The caller (UserController::audio())
     * MUST have already verified the requesting user owns the memo via
     * Memo::find($id, Auth::id()) -- this method itself performs no auth check,
     * it only turns a DB-resolved relative path into an HTTP response so the
     * audio is never served straight out of public/uploads/ without that check.
     */
    public static function streamMemoAudio(string $relativePath, string $mime): void
    {
        $full = __DIR__ . '/../public/uploads/' . $relativePath;
        if (!is_file($full)) {
            http_response_code(404);
            require __DIR__ . '/../app/views/errors/404.php';
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($full));
        header('Content-Disposition: inline; filename="' . basename($relativePath) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');
        readfile($full);
    }

    public static function deleteMemoAudio(string $relativePath): void
    {
        $full = __DIR__ . '/../public/uploads/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    public static function fileSize(string $relativePath): int
    {
        $full = __DIR__ . '/../public/uploads/' . $relativePath;
        return is_file($full) ? filesize($full) : 0;
    }
}
