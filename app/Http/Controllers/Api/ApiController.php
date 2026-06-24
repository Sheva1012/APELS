<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticResult;
use App\Models\UserLearningPath;
use App\Models\Feedback;
use App\Models\Module;
use App\Models\UserModuleProgress;
use App\Models\Question;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ApiController extends Controller
{
    /**
     * GET /api/dashboard
     * Retrieve the authenticated user's diagnostic scores, learning path, and feedback.
     */
    public function dashboard(): JsonResponse
    {
        $userId = auth()->id();

        // 1. Get latest diagnostic result
        $latestResult = DiagnosticResult::where('user_id', $userId)
            ->latest()
            ->first();

        // 2. Get active learning path
        $activePath = UserLearningPath::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        // 3. Get latest feedback
        $latestFeedback = Feedback::where('user_id', $userId)
            ->latest()
            ->first();

        $speaking = $latestResult ? (int) $latestResult->speaking : 0;
        $grammar = $latestResult ? (int) $latestResult->grammar : 0;
        $vocabulary = $latestResult ? (int) $latestResult->vocabulary : 0;

        // Derive writing and business_vocab scores (Req 25.1 / APELS-REVISI.md dashboard representation)
        $writing = $latestResult ? (int) max(0, min(100, round($grammar * 1.09))) : 0;
        $businessVocab = $latestResult ? (int) max(0, min(100, round($vocabulary * 0.91))) : 0;

        $skills = [
            'speaking'       => $speaking,
            'grammar'        => $grammar,
            'vocabulary'     => $vocabulary,
            'writing'        => $writing,
            'business_vocab' => $businessVocab,
        ];

        return ApiResponse::success('Dashboard data retrieved successfully', [
            'skills'        => $skills,
            'learning_path' => $activePath ? $activePath->path_key : 'none',
            'feedback'      => $latestFeedback ? $latestFeedback->message : 'Belum ada tes diagnosis yang dikerjakan.',
        ]);
    }

    /**
     * GET /api/learning-path
     * Retrieve active learning path key along with lists of unlocked and locked modules.
     */
    public function learningPath(): JsonResponse
    {
        $userId = auth()->id();

        $activePath = UserLearningPath::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        $pathKey = $activePath ? $activePath->path_key : 'none';

        // Retrieve active modules
        $modules = Module::where('is_active', true)->get();

        // Retrieve user module progress map
        $progressMap = UserModuleProgress::where('user_id', $userId)
            ->get()
            ->keyBy('module_id');

        $unlocked = [];
        $locked = [];

        foreach ($modules as $module) {
            $progress = $progressMap->get($module->id);
            
            // A module is unlocked if is_unlocked is true in progress, or it's a basic level module
            $isUnlocked = ($progress && $progress->is_unlocked) || ($module->level === 'basic');
            $moduleIdentifier = Str::snake($module->title);

            if ($isUnlocked) {
                $unlocked[] = $moduleIdentifier;
            } else {
                $locked[] = $moduleIdentifier;
            }
        }

        return ApiResponse::success('Learning path details retrieved successfully', [
            'learning_path'    => $pathKey,
            'modules_unlocked' => $unlocked,
            'modules_locked'   => $locked,
        ]);
    }

    /**
     * GET /api/feedback
     * Retrieve the latest feedback text.
     */
    public function feedback(): JsonResponse
    {
        $userId = auth()->id();

        $latestFeedback = Feedback::where('user_id', $userId)
            ->latest()
            ->first();

        $message = $latestFeedback ? $latestFeedback->message : 'Belum ada feedback yang tersedia.';

        return ApiResponse::success('Latest feedback retrieved successfully', [
            'feedback' => $message,
        ]);
    }

    /**
     * POST /api/questions
     * Create a new question in the Question Bank (restricted to admin & dosen).
     */
    public function createQuestion(Request $request): JsonResponse
    {
        // Support options array or individual options
        $options = $request->input('options');
        
        $optionA = $request->input('option_a');
        $optionB = $request->input('option_b');
        $optionC = $request->input('option_c');
        $optionD = $request->input('option_d');

        if (is_array($options)) {
            $optionA = $options['a'] ?? $options[0] ?? null;
            $optionB = $options['b'] ?? $options[1] ?? null;
            $optionC = $options['c'] ?? $options[2] ?? null;
            $optionD = $options['d'] ?? $options[3] ?? null;
        }

        $validatorData = array_merge($request->all(), [
            'option_a' => $optionA,
            'option_b' => $optionB,
            'option_c' => $optionC,
            'option_d' => $optionD,
        ]);

        $validator = Validator::make($validatorData, [
            'question'       => 'required|string',
            'option_a'       => 'required|string',
            'option_b'       => 'required|string',
            'option_c'       => 'required|string',
            'option_d'       => 'required|string',
            'correct_answer' => 'required|string|in:a,b,c,d',
            'tag'            => 'required|string|in:basic,intermediate,advanced',
            'type'           => 'nullable|string|in:grammar,vocabulary',
            'difficulty'     => 'nullable|integer|between:1,5',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation errors occurred', 422, $validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $question = Question::create([
            'question'       => $validated['question'],
            'option_a'       => $validated['option_a'],
            'option_b'       => $validated['option_b'],
            'option_c'       => $validated['option_c'],
            'option_d'       => $validated['option_d'],
            'correct_answer' => $validated['correct_answer'],
            'tag'            => $validated['tag'],
            'type'           => $validated['type'] ?? 'grammar',
            'difficulty'     => $validated['difficulty'] ?? 1,
            'is_active'      => true,
        ]);

        return ApiResponse::success('Question created successfully', $question->toArray(), 201);
    }
}
