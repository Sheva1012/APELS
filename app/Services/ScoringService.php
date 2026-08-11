<?php

namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * ScoringService — pure weighted-overall and normalization helper.
 *
 * Bobot tetap (Req 8.1):
 *   speaking = 0.40, grammar = 0.30, vocabulary = 0.30 (sum = 1.00).
 *
 * Dipakai oleh DiagnosticService::runEngineAndFeedback untuk update
 * `diagnostic_results.overall` setelah skor speaking final tersedia.
 *
 * Properti yang akan diuji via PBT:
 *  - Property 1: bounds [0..100] + missing-key fallback + determinism (Req 8.2-8.4).
 *  - Property 2: normalize boundary correctness + monotonicity (Req 8.5).
 */
class ScoringService
{
    /**
     * Bobot per skill — tetap konstan sesuai Req 8.1.
     *
     * @var array<string, float>
     */
    private array $weights = [
        'speaking'   => 0.40,
        'grammar'    => 0.30,
        'vocabulary' => 0.30,
    ];

    /**
     * Hitung skor satu seksi (Grammar atau Vocabulary) berbasis bobot difficulty (APELS-EXECUTION-SPEC §7).
     *
     * Formula:
     *   total_bobot = sum(question.difficulty)
     *   earned = sum(difficulty) untuk jawaban benar
     *   score = round((earned / total_bobot) * 100)
     *
     * Grammar  : total bobot = 18 (3×1 + 3×2 + 3×3)
     * Vocabulary: total bobot = 21 (4×1 + 4×2 + 3×3)
     *
     * @param  Collection                $questions  Koleksi Question model/array dengan key difficulty, id, correct_answer.
     * @param  array<int|string, string> $answers    Map questionId => jawaban user ('a'/'b'/'c'/'d').
     * @return int                                   Skor 0–100.
     */
    public function scoreSection(Collection $questions, array $answers): int
    {
        $earned = 0;
        $total  = $questions->sum(fn($q) => is_array($q) ? ($q['difficulty'] ?? 1) : $q->difficulty);

        foreach ($questions as $q) {
            $qId        = is_array($q) ? $q['id'] : $q->id;
            $qType      = is_array($q) ? ($q['question_type'] ?? 'multiple_choice') : ($q->question_type ?? 'multiple_choice');
            $difficulty = is_array($q) ? ($q['difficulty'] ?? 1) : $q->difficulty;
            $given      = $answers[$qId] ?? null;
            $isCorrect  = false;

            if ($given !== null) {
                if ($qType === 'multiple_choice') {
                    $correct = is_array($q) ? ($q['correct_answer'] ?? '') : $q->correct_answer;
                    if (is_string($given) && strtolower(trim($given)) === strtolower(trim($correct))) {
                        $isCorrect = true;
                    }
                } elseif ($qType === 'short_answer') {
                    $ansJson = is_array($q) ? ($q['answer_json'] ?? []) : ($q->answer_json ?? []);
                    $acceptable = is_array($ansJson) ? ($ansJson['acceptable_answers'] ?? []) : [];
                    $givenStr = strtolower(trim((string)$given));
                    foreach ($acceptable as $acc) {
                        $accText = is_array($acc) ? ($acc['answer'] ?? '') : $acc;
                        if (strtolower(trim((string)$accText)) === $givenStr) {
                            $isCorrect = true;
                            break;
                        }
                    }
                } elseif ($qType === 'matching' || $qType === 'ordering') {
                    $ansJson = is_array($q) ? ($q['answer_json'] ?? []) : ($q->answer_json ?? []);
                    if (is_array($given) && is_array($ansJson)) {
                        if (json_encode($given) === json_encode($ansJson)) {
                            $isCorrect = true;
                        }
                    }
                }
            }

            if ($isCorrect) {
                $earned += $difficulty;
            }
        }

        return $total > 0 ? (int) round(($earned / $total) * 100) : 0;
    }

    /**
     * Hitung overall score sebagai weighted average.
     *
     * - Skor key absent diperlakukan sebagai 0 (Req 8.4).
     * - Skor di luar 0..100 atau non-numeric → InvalidArgumentException (Req 8.3).
     *
     * @param  array<string, int|float>  $scores Keys: speaking, grammar, vocabulary.
     * @return float                             Overall pada [0.0, 100.0], dibulatkan 2 desimal (Req 8.2).
     *
     * @throws InvalidArgumentException
     */
    public function calculateOverall(array $scores): float
    {
        $sum = 0.0;
        foreach ($this->weights as $skill => $weight) {
            $value = $scores[$skill] ?? 0;
            if (!is_numeric($value)) {
                throw new InvalidArgumentException("Score for {$skill} must be numeric, got " . gettype($value));
            }
            if ($value < 0 || $value > 100) {
                throw new InvalidArgumentException("Score for {$skill} must be within 0..100, got {$value}");
            }
            $sum += $value * $weight;
        }
        return round($sum, 2);
    }

    /**
     * Normalisasi `$score` dari rentang [$min, $max] ke integer [0, 100] (Req 8.5).
     *
     * - Jika `$min >= $max` → InvalidArgumentException (cegah pembagian 0).
     * - Jika `$score` di luar [$min, $max] → InvalidArgumentException.
     */
    public function normalize(float $score, float $min = 0.0, float $max = 1.0): int
    {
        if ($min >= $max) {
            throw new InvalidArgumentException("normalize: \$min ({$min}) must be < \$max ({$max})");
        }
        if ($score < $min || $score > $max) {
            throw new InvalidArgumentException("normalize: \$score ({$score}) must be within [{$min}, {$max}]");
        }
        return (int) round((($score - $min) / ($max - $min)) * 100);
    }
}
