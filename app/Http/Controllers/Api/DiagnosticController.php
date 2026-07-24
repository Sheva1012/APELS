<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitDiagnosticRequest;
use App\Models\Question;
use App\Services\DiagnosticService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * DiagnosticController — API endpoints for Diagnostic Test.
 *
 * GET  /api/diagnostic/start  → Ambil soal untuk attempt berikutnya (spec §10)
 * POST /api/diagnostic/submit → Submit jawaban dan audio (spec §10)
 *
 * Middleware: auth:sanctum/web, role:mahasiswa, daily.test.limit (submit)
 */
class DiagnosticController extends Controller
{
    public function __construct(
        private DiagnosticService $diagnosticService,
    ) {}

    /**
     * GET /api/diagnostic/start
     * Return active SET, attempt number, and questions for next diagnostic.
     * Spec §10: response shape {set, attempt_number, grammar_questions, vocab_questions}
     */
    public function start(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = $request->user()->id;

        $activeSet     = $this->diagnosticService->resolveActiveSet($userId);
        $attemptNumber = $this->diagnosticService->nextAttemptNumber($userId);

        $grammarQs = Question::active()
            ->bySet($activeSet)
            ->byType('grammar')
            ->orderBy('difficulty')
            ->get(['id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'tag', 'difficulty']);

        $vocabQs = Question::active()
            ->bySet($activeSet)
            ->byType('vocabulary')
            ->orderBy('difficulty')
            ->get(['id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'tag', 'difficulty']);

        return ApiResponse::success('Diagnostic session started', [
            'set'               => $activeSet,
            'attempt_number'    => $attemptNumber,
            'grammar_questions' => $grammarQs,
            'vocab_questions'   => $vocabQs,
        ]);
    }

    /**
     * POST /api/diagnostic/submit
     * Handle Diagnostic Test submission.
     */
    public function submit(SubmitDiagnosticRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Store audio to private disk (Req 5.5, 23.1)
        $audioPath = null;
        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('diagnostic-audio', 'private');
        }

        $result = $this->diagnosticService->process($user->id, [
            'text_answers' => $request->validated('text_answers'),
            'audio_path'   => $audioPath,
        ]);

        return ApiResponse::success($result['message'], $result);
    }
}
