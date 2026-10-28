// Reel — small shared UX touches (flash auto-dismiss, delete confirmation,
// deterministic decorative waveform bars for stored memos).
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss flash banners after a few seconds.
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    // Confirm before any destructive form submit.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Deterministic "static waveform" bars for a memo id -- purely decorative
    // (we don't decode real audio server-side), but consistent per memo so
    // the same memo always looks the same across the dashboard and list.
    document.querySelectorAll('[data-waveform-seed]').forEach(function (el) {
        var seed = parseInt(el.getAttribute('data-waveform-seed'), 10) || 1;
        var bars = parseInt(el.getAttribute('data-waveform-bars'), 10) || 28;
        var rnd = mulberry32(seed);
        var frag = document.createDocumentFragment();
        for (var i = 0; i < bars; i++) {
            var h = Math.round(4 + rnd() * 24);
            var span = document.createElement('span');
            span.style.height = h + 'px';
            frag.appendChild(span);
        }
        el.appendChild(frag);
    });

    // Toggle an audio player's static waveform to "playing" tint.
    document.querySelectorAll('audio[data-waveform-target]').forEach(function (audio) {
        var target = document.querySelector(audio.getAttribute('data-waveform-target'));
        if (!target) return;
        audio.addEventListener('play', function () { target.classList.add('is-playing'); });
        audio.addEventListener('pause', function () { target.classList.remove('is-playing'); });
        audio.addEventListener('ended', function () { target.classList.remove('is-playing'); });
    });
});

// Small deterministic PRNG so the same memo id always renders the same bars.
function mulberry32(a) {
    return function () {
        a |= 0; a = (a + 0x6D2B79F5) | 0;
        var t = Math.imul(a ^ (a >>> 15), 1 | a);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}
