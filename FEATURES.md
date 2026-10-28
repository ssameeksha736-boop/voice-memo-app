# FEATURES.md — Reel (Voice Memo App)

## 1. One-line pitch

A browser-based voice memo studio: Users record audio straight from the
browser (or upload existing files), get a live transcript for free via the
browser's own Web Speech API, and get an automatic extractive summary
computed by a transparent PHP scoring algorithm — while a Super Admin
oversees platform usage (accounts, memo counts, storage/time totals) but
never a user's actual memo audio, transcript, or summary text.

## 2. Core modules

1. **Auth & Role Management** — single login; both roles (Super Admin,
   User) are admin-provisioned/seeded — no self-registration (project
   #11 CampusConnect's Student persona is this suite's one deliberate
   self-registration exception, not repeated here).
2. **Recording & Upload** — MediaRecorder-based in-browser recording with
   a live Web Audio waveform visualizer, or uploading an existing audio
   file; both paths validate the stored file server-side by real MIME
   type (finfo, not the client Content-Type) and size.
3. **Live Transcription (Web Speech API)** — while recording, Chrome/Edge's
   `SpeechRecognition` produces an editable live transcript alongside the
   waveform; uploaded files (and any browser without SpeechRecognition)
   fall back to a manually-typed/pasted transcript, since the API can only
   listen to a live microphone, not decode an existing audio file.
4. **Automatic Extractive Summary** — on save, a word-frequency /
   sentence-scoring algorithm written in plain PHP (`core/Summarizer.php`,
   a small variant of Luhn's 1958 method — no external AI/API call) picks
   the highest-scoring sentences from the transcript and reassembles them
   in original order as the summary. Fully explainable: the memo detail
   page has a "How was this summary generated?" panel showing the actual
   per-sentence scores and top keyword frequencies behind the pick.
5. **Organization & Search** — folders, free-text tags, and starring;
   search/filter own memos by title/transcript text, folder, tag, source
   (recorded/uploaded), and starred-only.
6. **Studio Oversight (Super Admin)** — activate/deactivate user accounts;
   platform-wide usage dashboard (total memos, total recorded time, daily
   volume, source split); per-user activity summary (memo/starred/recorded/
   uploaded counts, total time, last activity) — structurally never the
   memo's audio, transcript, or summary.

## 3. Design notes (the non-obvious mechanics)

**The summary is a classic extractive algorithm, not a trained model or an
AI API call.** `Summarizer::analyze()` splits the transcript into
sentences, builds a word-frequency table over non-stopword tokens
(normalized against the most frequent word), scores each sentence as the
sum of its words' frequency scores divided by sentence length (so long
sentences aren't rewarded just for being long), and keeps the top ~30% of
sentences (clamped 1–6) in their *original* order. This is a documented
simplification with real, explainable limitations (no coreference, no
topic modeling) — chosen deliberately to be inspectable line-by-line in a
viva, same spirit as project #13's lexicon-based sentiment scorer.

**Nothing about the summary is cached beyond the final text.** Only the
computed `summary` column is stored (same pattern as project #13's
`sentiment_score`); the full scoring breakdown (per-sentence scores, top
keywords) is recomputed live from the stored `transcript` every time the
"How was this generated?" panel is opened, so it can never drift out of
sync with the transcript it explains.

**Recording and uploading share one storage/validation path but two
distinct file inputs.** The record tab and upload tab post through
`audio_recorded` and `audio_uploaded` respectively (only one populated per
submission, based on which tab is active) rather than sharing one `<input
type=file>` name — two file inputs sharing a name in one multipart form is
undefined/inconsistent across browsers and PHP's `$_FILES` handling, so
the controller picks the right key from `$source` instead.

**The Super Admin's oversight queries never touch memo content.** Every
model method behind the Studio Overview dashboard and per-user activity
screen (`Memo::platformStats()`, `Memo::statsForUser()`,
`Memo::dailyVolume()`) explicitly selects only counts, durations, and
timestamps — `transcript`, `summary`, and `audio_path` are never named in
those `SELECT` lists, so a memo's actual content is structurally
unreachable from the admin's screens, the same privacy pattern established
by project #13's Wellness Coordinator views.

## 4. Feature breakdown by persona

| Feature | Super Admin | User |
|---|---|---|
| Login / logout | ✅ | ✅ |
| Activate/deactivate user accounts | ✅ | — |
| Record a memo in-browser (MediaRecorder + live waveform) | — | ✅ |
| Upload an existing audio file | — | ✅ |
| Live transcript while recording (Web Speech API) | — | ✅ |
| Manually edit/type a transcript | — | ✅ |
| Automatic extractive summary + explainability panel | — | ✅ |
| Folders, tags, starring | — | ✅ |
| Search/filter own memos | — | ✅ |
| Edit/delete own memos | — | ✅ |
| Platform-wide usage dashboard | ✅ | — |
| Per-user activity summary (no memo content) | ✅ | — |

## 5. Screen list per persona

**Super Admin** (3 screens)
1. Login
2. Studio Overview (dashboard: totals, daily volume chart, source split)
3. Manage Users (list + activate/deactivate + per-user activity overview)

**User** (6 screens)
1. Login
2. Dashboard (stats, recent memos, quick "New memo" action)
3. New Memo (Record tab / Upload tab, shared title/folder/tags/transcript
   fields, live "preview summary" action)
4. My Memos (list, search/filter by text/folder/tag/source/starred)
5. Memo Detail (playback, summary, "How was this generated?" panel,
   transcript, star/edit/delete)
6. Edit Memo (title/folder/tags/transcript) & Folders (create/delete)

## 6. Out of scope

- Server-side/real speech-to-text (Google Speech-to-Text, Whisper API,
  etc.) — this project deliberately uses the browser's free, offline,
  client-side Web Speech API instead, consistent with this suite's
  no-external-paid-API policy; documented as a Chrome/Edge-only capability
  with a manual-transcript fallback everywhere else.
- Abstractive (generative) summarization or any LLM/AI API call — the
  summary is a transparent extractive scoring algorithm only, per the
  brief.
- Real-time collaborative editing of a memo/transcript.
- Audio editing/trimming/noise reduction.
- Cross-device sync conflict handling — a single web app only.
- Sharing a memo with another user (memos are private to their owner,
  matching this project's privacy pattern).
