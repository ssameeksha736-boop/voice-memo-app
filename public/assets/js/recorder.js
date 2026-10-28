/**
 * Reel's recording deck: MediaRecorder for audio capture, the Web Speech
 * API (SpeechRecognition) for a live transcript while recording, and a
 * Web Audio AnalyserNode driving the canvas waveform -- all client-side,
 * all free, no external service. Runs on the "New Memo" page only.
 *
 * Browser support note (documented in README): MediaRecorder is supported
 * in all modern browsers, but SpeechRecognition is a Chrome/Edge-only
 * (webkitSpeechRecognition) API -- Firefox/Safari can still record audio
 * here, they just won't get a live transcript, and the transcript box
 * stays manually editable either way.
 */
(function () {
    var recBtn = document.getElementById('recBtn');
    if (!recBtn) return; // not on the new-memo page

    var recTimerEl = document.getElementById('recTimer');
    var recDotEl = document.getElementById('recDot');
    var recStatusEl = document.getElementById('recStatus');
    var canvas = document.getElementById('liveWaveform');
    var canvasCtx = canvas ? canvas.getContext('2d') : null;
    var transcriptBox = document.getElementById('transcript');
    var liveTranscriptEl = document.getElementById('liveTranscript');
    var audioPreviewWrap = document.getElementById('audioPreviewWrap');
    var audioPreview = document.getElementById('audioPreview');
    var reRecordBtn = document.getElementById('reRecordBtn');
    var sourceInput = document.getElementById('sourceInput');
    var durationInput = document.getElementById('durationInput');
    var recordedFileInput = document.getElementById('recordedFileInput');
    var speechUnsupportedNote = document.getElementById('speechUnsupportedNote');
    var micUnsupportedNote = document.getElementById('micUnsupportedNote');
    var titleInput = document.getElementById('titleInput');
    var saveBtn = document.getElementById('saveBtn');

    var mediaRecorder = null;
    var mediaStream = null;
    var chunks = [];
    var recognition = null;
    var finalTranscript = '';
    var timerHandle = null;
    var elapsedSeconds = 0;
    var audioCtx = null;
    var analyser = null;
    var rafHandle = null;

    var SpeechRecognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognitionClass && speechUnsupportedNote) {
        speechUnsupportedNote.classList.remove('hidden');
    }
    if (!navigator.mediaDevices || !window.MediaRecorder) {
        if (micUnsupportedNote) micUnsupportedNote.classList.remove('hidden');
        recBtn.disabled = true;
    }

    recBtn.addEventListener('click', function () {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            stopRecording();
        } else {
            startRecording();
        }
    });

    if (reRecordBtn) {
        reRecordBtn.addEventListener('click', function () {
            resetRecording();
        });
    }

    function startRecording() {
        navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
            mediaStream = stream;
            chunks = [];
            finalTranscript = '';
            if (transcriptBox) transcriptBox.value = '';
            if (liveTranscriptEl) liveTranscriptEl.innerHTML = '';

            var mimeType = pickSupportedMimeType();
            mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType: mimeType }) : new MediaRecorder(stream);

            mediaRecorder.ondataavailable = function (e) {
                if (e.data && e.data.size > 0) chunks.push(e.data);
            };
            mediaRecorder.onstop = function () {
                finishRecording(mediaRecorder.mimeType || mimeType || 'audio/webm');
            };
            mediaRecorder.start();

            recBtn.classList.add('is-recording');
            if (recDotEl) recDotEl.classList.add('is-live');
            if (recStatusEl) recStatusEl.textContent = 'Recording...';
            if (saveBtn) saveBtn.disabled = true;

            elapsedSeconds = 0;
            updateTimer();
            timerHandle = setInterval(function () {
                elapsedSeconds++;
                updateTimer();
            }, 1000);

            setupWaveform(stream);
            setupSpeechRecognition();
        }).catch(function (err) {
            alert('Could not access the microphone: ' + err.message + '. You can still upload an audio file instead.');
        });
    }

    function stopRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        if (recognition) {
            try { recognition.stop(); } catch (e) { /* already stopped */ }
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(function (t) { t.stop(); });
        }
        clearInterval(timerHandle);
        cancelAnimationFrame(rafHandle);
        if (audioCtx) { audioCtx.close(); audioCtx = null; }

        recBtn.classList.remove('is-recording');
        if (recDotEl) recDotEl.classList.remove('is-live');
        if (recStatusEl) recStatusEl.textContent = 'Processing...';
    }

    function finishRecording(mimeType) {
        var blob = new Blob(chunks, { type: mimeType });
        var ext = mimeType.indexOf('ogg') !== -1 ? 'ogg' : (mimeType.indexOf('mp4') !== -1 ? 'm4a' : 'webm');
        var file = new File([blob], 'memo-recording.' + ext, { type: mimeType });

        var dt = new DataTransfer();
        dt.items.add(file);
        recordedFileInput.files = dt.files;

        var url = URL.createObjectURL(blob);
        if (audioPreview) audioPreview.src = url;
        if (audioPreviewWrap) audioPreviewWrap.classList.remove('hidden');

        if (durationInput) durationInput.value = elapsedSeconds;
        if (sourceInput) sourceInput.value = 'recorded';
        if (recStatusEl) recStatusEl.textContent = 'Recorded ' + formatTime(elapsedSeconds) + ' -- review below, then save.';
        if (saveBtn) saveBtn.disabled = false;

        if (transcriptBox && finalTranscript.trim() !== '') {
            transcriptBox.value = finalTranscript.trim();
        }

        if (titleInput && titleInput.value.trim() === '') {
            titleInput.value = 'Memo -- ' + new Date().toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
        }
    }

    function resetRecording() {
        chunks = [];
        finalTranscript = '';
        elapsedSeconds = 0;
        updateTimer();
        recordedFileInput.value = '';
        if (audioPreviewWrap) audioPreviewWrap.classList.add('hidden');
        if (recStatusEl) recStatusEl.textContent = 'Tap the button to start recording.';
        if (saveBtn) saveBtn.disabled = true;
    }

    function updateTimer() {
        if (recTimerEl) recTimerEl.textContent = formatTime(elapsedSeconds);
    }

    function formatTime(total) {
        var m = Math.floor(total / 60);
        var s = total % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function pickSupportedMimeType() {
        var candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
        for (var i = 0; i < candidates.length; i++) {
            if (window.MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) {
                return candidates[i];
            }
        }
        return '';
    }

    // ---- live waveform (Web Audio AnalyserNode -> canvas bars) ----
    function setupWaveform(stream) {
        if (!canvasCtx) return;
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var source = audioCtx.createMediaStreamSource(stream);
        analyser = audioCtx.createAnalyser();
        analyser.fftSize = 256;
        source.connect(analyser);
        var data = new Uint8Array(analyser.frequencyBinCount);

        function draw() {
            rafHandle = requestAnimationFrame(draw);
            analyser.getByteFrequencyData(data);
            var w = canvas.width, h = canvas.height;
            canvasCtx.clearRect(0, 0, w, h);
            var barCount = 48;
            var step = Math.floor(data.length / barCount);
            var barWidth = w / barCount;
            for (var i = 0; i < barCount; i++) {
                var v = data[i * step] / 255;
                var barHeight = Math.max(3, v * h);
                var x = i * barWidth;
                var y = (h - barHeight) / 2;
                var grad = canvasCtx.createLinearGradient(0, y, 0, y + barHeight);
                grad.addColorStop(0, '#A78BFA');
                grad.addColorStop(1, '#5B3FA0');
                canvasCtx.fillStyle = grad;
                canvasCtx.fillRect(x + 1, y, Math.max(2, barWidth - 2), barHeight);
            }
        }
        draw();
    }

    // ---- live transcript (Web Speech API) ----
    function setupSpeechRecognition() {
        if (!SpeechRecognitionClass) return;
        recognition = new SpeechRecognitionClass();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'en-IN';

        recognition.onresult = function (event) {
            var interim = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                var transcriptPiece = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcriptPiece + ' ';
                } else {
                    interim += transcriptPiece;
                }
            }
            if (liveTranscriptEl) {
                liveTranscriptEl.textContent = finalTranscript;
                if (interim) {
                    var interimSpan = document.createElement('span');
                    interimSpan.className = 'interim';
                    interimSpan.textContent = interim;
                    liveTranscriptEl.appendChild(interimSpan);
                }
                liveTranscriptEl.scrollTop = liveTranscriptEl.scrollHeight;
            }
        };
        recognition.onerror = function () { /* mic permission or no-speech -- recording continues regardless */ };
        recognition.onend = function () {
            // Some browsers auto-stop recognition after a pause; restart it
            // while we're still actively recording so the transcript keeps going.
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                try { recognition.start(); } catch (e) { /* ignore */ }
            }
        };
        try { recognition.start(); } catch (e) { /* ignore */ }
    }

    // ---- upload tab: try to read duration from the picked file's metadata ----
    var uploadFileInput = document.getElementById('uploadFileInput');
    if (uploadFileInput) {
        uploadFileInput.addEventListener('change', function () {
            var file = uploadFileInput.files[0];
            if (!file) return;

            if (titleInput && titleInput.value.trim() === '') {
                titleInput.value = file.name.replace(/\.[^/.]+$/, '');
            }

            var probe = document.createElement('audio');
            probe.preload = 'metadata';
            probe.onloadedmetadata = function () {
                URL.revokeObjectURL(probe.src);
                var d = probe.duration;
                if (isFinite(d) && !isNaN(d) && durationInput) {
                    durationInput.value = Math.round(d);
                }
            };
            probe.src = URL.createObjectURL(file);
        });
    }

    // ---- tabs ----
    var tabRecordBtn = document.getElementById('tabRecordBtn');
    var tabUploadBtn = document.getElementById('tabUploadBtn');
    var panelRecord = document.getElementById('panelRecord');
    var panelUpload = document.getElementById('panelUpload');

    function activateTab(which) {
        var isRecord = which === 'record';
        if (tabRecordBtn) tabRecordBtn.classList.toggle('is-active', isRecord);
        if (tabUploadBtn) tabUploadBtn.classList.toggle('is-active', !isRecord);
        if (panelRecord) panelRecord.classList.toggle('hidden', !isRecord);
        if (panelUpload) panelUpload.classList.toggle('hidden', isRecord);
        if (sourceInput) sourceInput.value = isRecord ? 'recorded' : 'uploaded';
        if (saveBtn) saveBtn.disabled = isRecord ? !(recordedFileInput && recordedFileInput.files.length) : !(uploadFileInput && uploadFileInput.files.length);
    }
    if (tabRecordBtn) tabRecordBtn.addEventListener('click', function () { activateTab('record'); });
    if (tabUploadBtn) tabUploadBtn.addEventListener('click', function () { activateTab('upload'); });
    if (uploadFileInput) uploadFileInput.addEventListener('change', function () {
        if (saveBtn) saveBtn.disabled = !uploadFileInput.files.length;
    });

    // ---- live "regenerate summary" preview (calls the server's real Summarizer) ----
    var previewBtn = document.getElementById('previewSummaryBtn');
    var previewPanel = document.getElementById('summaryPreviewPanel');
    if (previewBtn && transcriptBox) {
        previewBtn.addEventListener('click', function () {
            var text = transcriptBox.value.trim();
            if (text === '') {
                previewPanel.innerHTML = '<p class="empty-state">Add a transcript first.</p>';
                return;
            }
            previewBtn.disabled = true;
            previewBtn.textContent = 'Summarizing...';
            var body = new URLSearchParams();
            body.set('transcript', text);
            body.set('csrf_token', window.REEL_CSRF || '');

            fetch('/index.php?route=user/memos/summarize-preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            }).then(function (r) { return r.json(); }).then(function (data) {
                renderSummaryPreview(data);
            }).catch(function () {
                previewPanel.innerHTML = '<p class="field-error">Could not generate a preview. Try again.</p>';
            }).finally(function () {
                previewBtn.disabled = false;
                previewBtn.textContent = 'Preview summary';
            });
        });
    }

    function renderSummaryPreview(data) {
        if (!previewPanel) return;
        if (!data.summary) {
            previewPanel.innerHTML = '<p class="empty-state">Nothing to summarize yet.</p>';
            return;
        }
        var html = '<p class="text-sm text-ink leading-relaxed mb-3">' + escapeHtml(data.summary) + '</p>';
        if (data.keywords && data.keywords.length) {
            html += '<div class="flex flex-wrap gap-1.5">';
            data.keywords.slice(0, 8).forEach(function (k) {
                html += '<span class="keyword-chip">' + escapeHtml(k.word) + ' <b>&times;' + k.count + '</b></span>';
            });
            html += '</div>';
        }
        previewPanel.innerHTML = html;
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
})();
