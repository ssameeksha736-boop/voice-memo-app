<?php
$pageTitle = $memo['title'];
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <?php if ($success = flash_get('success')): ?><div class="alert alert-success" data-flash><?= e($success) ?></div><?php endif; ?>

    <div class="flex items-start justify-between flex-wrap gap-4 mb-2">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="font-display font-semibold text-2xl text-ink"><?= e($memo['title']) ?></h1>
                <span class="badge <?= $memo['source'] === 'recorded' ? 'badge-violet' : 'badge-slate' ?>"><?= e($memo['source']) ?></span>
            </div>
            <div class="text-inkmute text-sm mt-1">
                <?= e(format_datetime($memo['created_at'])) ?> &middot; <?= e(format_duration((int)$memo['duration_seconds'])) ?>
                <?php if ($memo['folder_name']): ?> &middot; <?= e($memo['folder_name']) ?><?php endif; ?>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="/index.php?route=user/memos/star" class="inline-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$memo['id'] ?>">
                <input type="hidden" name="back" value="user/memos/view?id=<?= (int)$memo['id'] ?>">
                <button type="submit" class="icon-btn <?= $memo['is_starred'] ? 'starred' : '' ?>" title="Toggle star">&#9733;</button>
            </form>
            <a href="/index.php?route=user/memos/edit&id=<?= (int)$memo['id'] ?>" class="btn-secondary btn-sm">Edit</a>
            <form method="POST" action="/index.php?route=user/memos/delete" class="inline-form" data-confirm="Delete this memo? This cannot be undone.">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$memo['id'] ?>">
                <button type="submit" class="btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>

    <?php if (!empty($memo['tags'])): ?>
        <div class="flex flex-wrap gap-1.5 mb-5">
            <?php foreach ($memo['tags'] as $t): ?><span class="badge badge-slate"><?= e($t) ?></span><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="waveform-static mb-3" data-waveform-seed="<?= (int)$memo['id'] ?>" data-waveform-bars="60" style="width:100%; height:36px;" id="viewWaveform"></div>
        <audio controls class="w-full" data-waveform-target="#viewWaveform" src="/index.php?route=user/memos/audio&id=<?= (int)$memo['id'] ?>"></audio>
    </div>

    <div class="card">
        <div class="card-head"><h2>Summary</h2></div>
        <?php if (!empty($memo['summary'])): ?>
            <p class="text-ink leading-relaxed"><?= nl2br(e($memo['summary'])) ?></p>
        <?php else: ?>
            <p class="empty-state">No summary yet -- this memo has no transcript to summarize. Add one from Edit.</p>
        <?php endif; ?>
    </div>

    <?php if ($analysis && !empty($memo['transcript'])): ?>
    <div class="card">
        <div class="card-head">
            <h2>How was this summary generated?</h2>
            <span class="text-xs text-inkmute"><?= (int)$analysis['sentence_count'] ?> sentences &middot; <?= (int)$analysis['word_count'] ?> words</span>
        </div>
        <p class="text-inkmute text-sm mb-4">
            An extractive word-frequency scorer (<code class="font-mono text-violet-bright">core/Summarizer.php</code>), not an AI model: every significant word (stopwords like "the"/"and" removed) is counted, sentences are scored by the sum of their words' frequency divided by sentence length, and the top-scoring sentences are kept in their original order. Highlighted rows below were selected for the summary above.
        </p>

        <?php if (!empty($analysis['full_text_fallback'])): ?>
            <div class="alert alert-success !bg-violet-soft !text-violet-bright !border-violet/30 !text-sm">
                This transcript is under 12 words &mdash; too short for extraction to say anything a full read wouldn't &mdash; so every sentence is kept and the summary is the transcript itself. Sentence scores are still shown below for reference.
            </div>
        <?php endif; ?>

        <?php if (!empty($analysis['keywords'])): ?>
            <div class="mb-4">
                <div class="text-xs font-semibold text-inkmute uppercase tracking-wide mb-2">Top keywords by frequency</div>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($analysis['keywords'] as $kw): ?>
                        <span class="keyword-chip"><?= e($kw['word']) ?> <b>&times;<?= (int)$kw['count'] ?></b></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-1">
            <?php foreach ($analysis['sentences'] as $s): ?>
                <div class="sentence-row <?= $s['selected'] ? 'is-selected' : '' ?>">
                    <div class="flex items-start justify-between gap-3">
                        <span><?= e($s['text']) ?></span>
                        <span class="sentence-score shrink-0">score <?= number_format($s['score'], 3) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Transcript</h2></div>
        <?php if (!empty($memo['transcript'])): ?>
            <p class="text-ink leading-relaxed whitespace-pre-wrap font-mono text-sm"><?= e($memo['transcript']) ?></p>
        <?php else: ?>
            <p class="empty-state">No transcript recorded for this memo.</p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
