<?php

/**
 * Extractive summarizer -- a classic word-frequency / sentence-scoring
 * algorithm (a small variant of Luhn's 1958 method), written in plain PHP.
 * Deliberately NOT a trained model or an external AI API call -- same spirit
 * as this suite's project #13 (Journal App) lexicon-based sentiment scorer:
 * a fixed, transparent, fully offline heuristic that can be read line by
 * line and defended in a viva, not a black box.
 *
 * How it works:
 *   1. Split the transcript into sentences on ./!/? boundaries.
 *   2. Tokenize into lowercase words, discarding a fixed stopword list
 *      (common words like "the", "is", "and" that carry no topical signal).
 *   3. Build a word-frequency table over the remaining "significant" words,
 *      normalized against the most frequent word (so frequency scores sit
 *      in [0, 1] regardless of transcript length).
 *   4. Score each sentence as the sum of its significant words' frequency
 *      scores, divided by the sentence's word count -- dividing avoids
 *      simply rewarding the longest sentence in the transcript.
 *   5. Take the highest-scoring sentences (a fixed proportion, clamped to a
 *      sensible range) and re-assemble them in their ORIGINAL order, so the
 *      summary still reads as a coherent excerpt rather than a shuffled
 *      bag of "important" lines.
 *
 * Nothing here is stored pre-computed except the final summary text (same
 * pattern as Sentiment::analyze() -- see JournalEntry in project #13): the
 * full scoring breakdown is recomputed on demand from the stored transcript
 * whenever a memo's "How was this summary generated?" panel is opened, so
 * it can never drift out of sync with the transcript it explains.
 */
class Summarizer
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'if', 'then', 'so', 'because',
        'as', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between',
        'into', 'through', 'during', 'before', 'after', 'above', 'below',
        'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under',
        'again', 'further', 'once', 'here', 'there', 'when', 'where', 'why',
        'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other',
        'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'than',
        'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should',
        'now', 'is', 'am', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'having', 'do', 'does', 'did', 'doing',
        'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you',
        'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his',
        'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself',
        'they', 'them', 'their', 'theirs', 'themselves', 'what', 'which',
        'who', 'whom', 'this', 'that', 'these', 'those', 'that\'ll',
        'that\'s', 'um', 'uh', 'okay', 'ok', 'like', 'basically', 'actually',
        'really', 'gonna', 'wanna', 'yeah', 'right',
    ];

    // Fraction of a transcript's sentences kept in the summary, and the hard
    // floor/ceiling around it -- keeps a 3-sentence memo from being reduced
    // to nothing and a 60-sentence one from producing an unreadably long
    // "summary".
    private const KEEP_RATIO = 0.3;
    private const MIN_SENTENCES = 1;
    private const MAX_SENTENCES = 6;

    // Below this word count there's nothing useful to extract -- the whole
    // transcript IS the summary. This threshold lives here, inside analyze(),
    // rather than in summarize(), so both the stored summary and the
    // explainability panel are produced by one authority and can never
    // disagree about which sentences the summary is made of.
    private const MIN_WORDS_FOR_EXTRACTION = 12;

    /**
     * Full, explainable breakdown -- what the memo view's "How was this
     * generated?" panel renders.
     *
     * @return array{
     *   sentences: list<array{index:int, text:string, score:float, selected:bool}>,
     *   keywords: list<array{word:string, count:int, frequency:float}>,
     *   summary: string,
     *   sentence_count: int,
     *   word_count: int,
     * }
     */
    public static function analyze(string $transcript): array
    {
        $transcript = trim($transcript);
        if ($transcript === '') {
            return [
                'sentences' => [], 'keywords' => [], 'summary' => '',
                'sentence_count' => 0, 'word_count' => 0,
                'full_text_fallback' => false,
            ];
        }

        $sentences = self::splitSentences($transcript);
        $sentenceCount = count($sentences);

        // ---- word frequency table over significant (non-stopword) words ----
        static $stopSet = null;
        if ($stopSet === null) {
            $stopSet = array_flip(self::STOPWORDS);
        }

        $freq = [];
        $totalWords = 0;
        $sentenceWords = [];
        foreach ($sentences as $i => $sentence) {
            $words = self::tokenize($sentence);
            $sentenceWords[$i] = $words;
            foreach ($words as $w) {
                $totalWords++;
                if (isset($stopSet[$w]) || strlen($w) < 2) {
                    continue;
                }
                $freq[$w] = ($freq[$w] ?? 0) + 1;
            }
        }

        $maxFreq = $freq === [] ? 1 : max($freq);
        $freqScore = [];
        foreach ($freq as $w => $c) {
            $freqScore[$w] = $c / $maxFreq;
        }

        // ---- score every sentence ----
        $scored = [];
        foreach ($sentences as $i => $sentence) {
            $words = $sentenceWords[$i];
            $wordCount = count($words) ?: 1;
            $sum = 0.0;
            foreach ($words as $w) {
                $sum += $freqScore[$w] ?? 0.0;
            }
            $scored[$i] = [
                'index' => $i,
                'text'  => $sentence,
                'score' => round($sum / $wordCount, 4),
            ];
        }

        // ---- pick top-N by score, then restore original reading order ----
        // A transcript too short to extract from keeps every sentence, so the
        // summary is the full text -- and the panel below correctly shows all
        // rows as selected, instead of highlighting one sentence while the
        // stored summary quietly contains all of them.
        $fullTextFallback = str_word_count($transcript) < self::MIN_WORDS_FOR_EXTRACTION;

        if ($fullTextFallback) {
            $keep = $sentenceCount;
        } else {
            $keep = (int)ceil($sentenceCount * self::KEEP_RATIO);
            $keep = max(self::MIN_SENTENCES, min(self::MAX_SENTENCES, $keep));
            $keep = min($keep, $sentenceCount);
        }

        $byScore = $scored;
        usort($byScore, fn($a, $b) => $b['score'] <=> $a['score']);
        $selectedIndexes = array_flip(array_column(array_slice($byScore, 0, $keep), 'index'));

        $summaryParts = [];
        foreach ($scored as $i => $row) {
            $scored[$i]['selected'] = isset($selectedIndexes[$i]);
            if ($scored[$i]['selected']) {
                $summaryParts[] = $row['text'];
            }
        }

        // Top keywords for the explainability panel (by raw frequency).
        arsort($freq);
        $keywords = [];
        foreach (array_slice($freq, 0, 10, true) as $w => $c) {
            $keywords[] = ['word' => $w, 'count' => $c, 'frequency' => round($freqScore[$w], 3)];
        }

        return [
            'sentences'      => array_values($scored),
            'keywords'       => $keywords,
            'summary'        => implode(' ', $summaryParts),
            'sentence_count' => $sentenceCount,
            'word_count'     => $totalWords,
            'full_text_fallback' => $fullTextFallback,
        ];
    }

    /**
     * Convenience wrapper for callers that only need the summary text. Kept as
     * a pure delegation to analyze() -- it must never apply a rule of its own,
     * or the stored summary and the "How was this generated?" panel drift apart.
     */
    public static function summarize(string $transcript): string
    {
        return self::analyze($transcript)['summary'];
    }

    /** Splits on sentence-ending punctuation, keeping the punctuation attached. */
    private static function splitSentences(string $text): array
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        $parts = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9"\'])|(?<=[.!?])$/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [$text];
        }
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }

    private static function tokenize(string $sentence): array
    {
        $words = preg_split('/[^\p{L}\p{N}\']+/u', mb_strtolower($sentence), -1, PREG_SPLIT_NO_EMPTY);
        return $words ?: [];
    }
}
