<?php
$pageTitle = 'New Memo';
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<script>window.REEL_CSRF = <?= json_encode(Csrf::token()) ?>;</script>
<main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="font-display font-semibold text-2xl text-ink mb-1">New memo</h1>
    <p class="text-inkmute text-sm mb-6">Record straight from your browser, or upload an existing audio file.</p>

    <?php if (!empty($errors['audio'])): ?><div class="alert alert-error"><?= e($errors['audio']) ?></div><?php endif; ?>

    <div id="micUnsupportedNote" class="alert alert-error hidden">Your browser doesn't support in-browser recording (MediaRecorder). Please use the Upload tab instead.</div>

    <form method="POST" action="/index.php?route=user/memos/store" enctype="multipart/form-data" id="memoForm" class="space-y-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="source" id="sourceInput" value="recorded">
        <input type="hidden" name="duration" id="durationInput" value="0">

        <!-- ---- tabs ---- -->
        <div class="flex gap-1 border-b border-line">
            <button type="button" id="tabRecordBtn" class="tab-btn is-active">Record</button>
            <button type="button" id="tabUploadBtn" class="tab-btn">Upload file</button>
        </div>

        <!-- ---- record panel ---- -->
        <div id="panelRecord" class="deck">
            <div id="speechUnsupportedNote" class="alert alert-error hidden !mb-4">Live transcription (Web Speech API) isn't supported in this browser -- Chrome/Edge only. You can still record, and type the transcript manually below.</div>

            <div class="deck-canvas-wrap mb-5">
                <canvas id="liveWaveform" width="600" height="72" class="w-full block"></canvas>
            </div>

            <div class="flex flex-col items-center gap-3 mb-5">
                <button type="button" id="recBtn" class="rec-btn"><span class="rec-icon"></span></button>
                <div class="flex items-center gap-2">
                    <span id="recDot" class="rec-dot"></span>
                    <span class="rec-timer" id="recTimer">0:00</span>
                </div>
                <p id="recStatus" class="text-inkmute text-sm">Tap the button to start recording.</p>
            </div>

            <div id="audioPreviewWrap" class="hidden mb-4">
                <label class="field-label">Preview</label>
                <audio id="audioPreview" controls class="w-full"></audio>
                <button type="button" id="reRecordBtn" class="btn-ghost btn-sm mt-2">Discard &amp; re-record</button>
            </div>

            <div>
                <label class="field-label">Live transcript (auto-filled while recording -- editable)</label>
                <div id="liveTranscript" class="transcript-live" aria-live="polite"></div>
            </div>

            <input type="file" id="recordedFileInput" name="audio_recorded" accept="audio/*" class="hidden">
        </div>

        <!-- ---- upload panel ---- -->
        <div id="panelUpload" class="deck hidden">
            <label class="field-label">Audio file</label>
            <input type="file" id="uploadFileInput" name="audio_uploaded" accept="audio/*" class="field-input">
            <p class="field-hint">webm, ogg, mp3, wav, m4a or aac -- up to 25MB. The Web Speech API can only transcribe live microphone audio, so type or paste a transcript below for uploaded files.</p>
        </div>

        <!-- ---- shared fields ---- -->
        <div class="card">
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="field-label">Title</label>
                    <input type="text" name="title" id="titleInput" class="field-input" value="<?= old($old ?? [], 'title') ?>" placeholder="e.g. Sprint standup notes" maxlength="150">
                    <?php if (!empty($errors['title'])): ?><p class="field-error"><?= e($errors['title']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Folder</label>
                    <select name="folder_id" class="field-select">
                        <option value="">Unfiled</option>
                        <?php foreach ($folders as $f): ?>
                            <option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="field-label">Tags <span class="field-hint">(comma-separated)</span></label>
                <input type="text" name="tags" class="field-input" value="<?= old($old ?? [], 'tags') ?>" placeholder="work, meeting, idea">
            </div>
            <div>
                <label class="field-label">Transcript</label>
                <textarea name="transcript" id="transcript" rows="6" class="field-textarea" placeholder="Fills in live while recording (Chrome/Edge), or type/paste it yourself..."><?= old($old ?? [], 'transcript') ?></textarea>
                <?php if (!empty($errors['transcript'])): ?><p class="field-error"><?= e($errors['transcript']) ?></p><?php endif; ?>
                <div class="flex items-center justify-between mt-2">
                    <button type="button" id="previewSummaryBtn" class="btn-ghost btn-sm">Preview summary</button>
                    <span class="field-hint">The auto-summary is generated for real when you save.</span>
                </div>
                <div id="summaryPreviewPanel" class="mt-3"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/index.php?route=user/memos" class="btn-ghost">Cancel</a>
            <button type="submit" id="saveBtn" class="btn-primary">Save memo</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<script src="/assets/js/recorder.js"></script>
