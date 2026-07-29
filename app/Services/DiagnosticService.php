<?php

namespace App\Services;

use App\Jobs\ProcessSpeakingAudio;
use App\Models\DiagnosticResult;
use App\Models\Feedback;
use Illuminate\Support\Facades\Log;

/**
 * DiagnosticService — orchestrator for Diagnostic Test submission flow.
 *
 * process() flow (Req 4.1-4.6):
 *   1. Call NlpAnalysisService (sync) → grammar + vocabulary scores
 *   2. Create DiagnosticResult record (speaking=0, is_speaking_processed=false)
 *   3. If audio → dispatch ProcessSpeakingAudio job (async)
 *   4. If no audio → run engine+feedback synchronously with speaking=0
 *   5. Return API response data
 *
 * runEngineAndFeedback() flow (Req 8, 9, 12.6):
 *   1. Calculate overall score via ScoringService
 *   2. Run AdaptiveEngineService (evaluate + persist)
 *   3. Generate feedback via FeedbackService
 *   4. Persist Feedback record
 *
 * Requirements: 4.1-4.6, 8, 9, 12.6, 27.2-27.3, 28.1-28.4
 */
class DiagnosticService
{
    /**
     * Urutan SET yang dirotasi (APELS-EXECUTION-SPEC §4).
     * Attempt ke-1 → A, ke-2 → B, ke-3 → C, ke-4 → A, dst.
     */
    private const SETS = ['A', 'B', 'C'];

    public function __construct(
        private NlpAnalysisService $nlpService,
        private ScoringService $scoringService,
        private AdaptiveEngineService $adaptiveEngine,
        private FeedbackService $feedbackService,
    ) {}

    /**
     * Tentukan SET aktif berdasarkan jumlah attempt sebelumnya.
     * Rotasi: attempt%3 → index SET (Req §4).
     */
    public function resolveActiveSet(int $userId): string
    {
        $attempt = DiagnosticResult::where('user_id', $userId)->count();
        return self::SETS[$attempt % count(self::SETS)];
    }

    /**
     * Nomor attempt berikutnya (1-indexed).
     */
    public function nextAttemptNumber(int $userId): int
    {
        return DiagnosticResult::where('user_id', $userId)->count() + 1;
    }

    /**
     * Process a Diagnostic Test submission.
     *
     * @param  int    $userId
     * @param  array  $input  Keys:
     *                          grammar_score (int, 0-100) — dihitung oleh ScoringService di frontend
     *                          vocab_score (int, 0-100)   — dihitung oleh ScoringService di frontend
     *                          audio_path (string|null)
     *                          answers (array|null)       — jawaban mentah untuk disimpan
     *                          [LEGACY] text_answers (array<string>) — jika masuk via API lama
     * @return array{diagnostic_id: int, grammar: int, vocabulary: int, message: string}
     */
    public function process(int $userId, array $input): array
    {
        $audioPath         = $input['audio_path']         ?? null;
        $speakingReference = $input['speaking_reference'] ?? null;

        // Jika skor sudah dihitung di frontend (alur MCQ baru), gunakan langsung.
        // Jika tidak (alur API lama/legacy), fallback ke NLP.
        if (isset($input['grammar_score']) && isset($input['vocab_score'])) {
            $grammar    = (int) $input['grammar_score'];
            $vocabulary = (int) $input['vocab_score'];
        } else {
            // LEGACY: NLP analysis (sync) — untuk backward compat API lama
            $textAnswers = $input['text_answers'] ?? [];
            try {
                $nlpResult = $this->nlpService->analyze($textAnswers);
            } catch (\Throwable $e) {
                Log::error('DiagnosticService::process — NLP failed, using fallback', [
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
                $nlpResult = ['grammar_score' => 0, 'vocab_score' => 0];
            }
            $grammar    = $nlpResult['grammar_score'];
            $vocabulary = $nlpResult['vocab_score'];
        }


        // 2. Create DiagnosticResult (Req 4.2)
        $setUsed       = $this->resolveActiveSet($userId);
        $attemptNumber = $this->nextAttemptNumber($userId);
        $attempt       = $attemptNumber; // backward compat dengan kolom 'attempt' lama

        $diagnostic = DiagnosticResult::create([
            'user_id'               => $userId,
            'set_used'              => $setUsed,
            'attempt_number'        => $attemptNumber,
            'grammar'               => $grammar,
            'vocabulary'            => $vocabulary,
            'speaking'              => 0,
            'overall'               => 0,
            'attempt'               => $attempt,
            'audio_path'            => $audioPath,
            'speaking_reference'    => $speakingReference,
            'is_speaking_processed' => false,
            'answers'               => $input['answers'] ?? null,
        ]);

        // 3. Dispatch async job for speaking audio or run sync when no audio (Req 4.3, 4.4)
        if ($audioPath) {
            ProcessSpeakingAudio::dispatch($userId, $audioPath, $diagnostic->id);
        } else {
            // No audio — run engine synchronously with speaking=0 (Req 4.4)
            $diagnostic->update(['is_speaking_processed' => true]);
            $this->runEngineAndFeedback($userId, $diagnostic->fresh());
        }


        // 4. Return response data (Req 4.5)
        return [
            'diagnostic_id' => $diagnostic->id,
            'grammar'       => $grammar,
            'vocabulary'    => $vocabulary,
            'message'       => 'Diagnostic test berhasil diproses.',
        ];
    }

    /**
     * Run Adaptive Engine + Feedback after scores are final.
     *
     * Called by ProcessSpeakingAudio job (after Whisper) or synchronously
     * when no audio is submitted.
     *
     * @param  int               $userId
     * @param  DiagnosticResult  $diagnostic  Fresh instance with final scores.
     */
    public function runEngineAndFeedback(int $userId, DiagnosticResult $diagnostic): void
    {
        $scores = [
            'speaking'   => $diagnostic->speaking,
            'grammar'    => $diagnostic->grammar,
            'vocabulary' => $diagnostic->vocabulary,
        ];

        // 1. Calculate overall (Req 8)
        $overall = $this->scoringService->calculateOverall($scores);
        $diagnostic->update(['overall' => $overall]);

        // 2. Run Adaptive Engine (Req 9)
        $pathResult = $this->adaptiveEngine->run($userId, $scores);

        // 3. Generate feedback (Req 12)
        $feedback = $this->feedbackService->generate($scores, $pathResult['learning_path']);

        // 4. Persist Feedback record (Req 12.6)
        Feedback::create([
            'user_id'              => $userId,
            'diagnostic_result_id' => $diagnostic->id,
            'message'              => $feedback['message'],
            'details'              => $feedback['details'],
            'next_focus'           => $feedback['next_focus'],
        ]);
    }
}
