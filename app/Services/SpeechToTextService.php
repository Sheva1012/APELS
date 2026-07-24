<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SpeechToTextService — Groq Whisper Large V3 wrapper for speaking score.
 *
 * Sends audio file to Groq Whisper API, counts words in transcript,
 * and computes a speaking score (0..100).
 *
 * Scoring formula (aligned with APELS.md spec — speaking ≈ 65 for normal speech):
 *   - n = 0          → score = 0  (no speech detected)
 *   - 1 ≤ n ≤ 3      → score = 40 (very short, base floor for any speech)
 *   - 4 ≤ n ≤ 9      → score = round(40 + (n / 50 * 60))   ≈ 45–51%
 *   - 10 ≤ n ≤ 49    → score = round(50 + (n / 50 * 50))   ≈ 60–99%
 *   - n ≥ 50         → score = 100
 *
 * Rationale: 30–60 seconds of natural speech produces ~50–150 words.
 *   5 words  → ~45%  (very short sentence, needs more)
 *  10 words  → ~62%  (short paragraph, fair baseline)
 *  20 words  → ~74%  (decent intro)
 *  50+ words → 100%  (fluent speaker)
 *
 * Error handling (Req 6.7, 23.4, 27.1):
 *   - File not found → log error, return 0
 *   - API exception/timeout → log error, return 0
 *
 * Requirements: 6.1, 6.2, 6.3, 6.7, 23.4, 25.3, 27.1, 28.3
 */
class SpeechToTextService
{
    public function __construct(
        private GroqService $groq
    ) {}

    /**
     * Analyze audio file and return speaking score + transcript.
     *
     * @param  string  $audioPath  Relative path on 'private' disk.
     * @return array{score: int, transcript: string}
     */
    public function analyze(string $audioPath): array
    {
        // Check file exists on private disk (Req 23.4)
        if (!Storage::disk('private')->exists($audioPath)) {
            Log::error('SpeechToTextService::analyze — file not found', [
                'audio_path' => $audioPath,
            ]);
            return ['score' => 0, 'transcript' => ''];
        }

        try {
            $absolutePath = Storage::disk('private')->path($audioPath);

            // Call Groq Whisper Large V3 API (Req 9.1)
            $response = $this->groq->transcribeAudio($absolutePath, 'en');

            $transcript = $response['text'] ?? '';

            return [
                'score'      => $this->scoreFromTranscript($transcript),
                'transcript' => $transcript,
            ];
        } catch (\Throwable $e) {
            Log::error('SpeechToTextService::analyze — API failure', [
                'audio_path' => $audioPath,
                'error'      => $e->getMessage(),
            ]);
            return ['score' => 0, 'transcript' => ''];
        }
    }

    /**
     * Compute speaking score from transcript word count.
     *
     * Exposed as public for PBT (Property 8).
     *
     * @param  string  $transcript
     * @return int     Score 0..100
     */
    public function scoreFromTranscript(string $transcript): int
    {
        $n = str_word_count(trim($transcript));

        return $this->scoreFromWordCount($n);
    }

    /**
     * Pure scoring logic from word count.
     *
     * Formula aligned with APELS.md spec (speaking ≈ 65 for normal intro speech).
     * Target: 50 words = 100% (≈ 30–60s of fluent speech).
     *
     * @param  int  $n  Word count >= 0
     * @return int      Score 0..100
     */
    public function scoreFromWordCount(int $n): int
    {
        if ($n <= 0) {
            return 0;
        }

        // Very short utterance — any detectable speech gets a base floor of 40
        if ($n <= 3) {
            return 40;
        }

        // Short phrase (4–9 words): scale from 40 → ~51%
        if ($n < 10) {
            return min(59, (int) round(40 + ($n / 50 * 60)));
        }

        // Normal speech (10–49 words): scale from 60 → 99%
        if ($n < 50) {
            return min(99, (int) round(50 + ($n / 50 * 50)));
        }

        // Fluent speaker — 50+ words = full score
        return 100;
    }
}
