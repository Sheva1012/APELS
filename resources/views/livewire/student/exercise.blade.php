<?php

use App\Models\Module;
use App\Models\Question;
use App\Models\UserModuleProgress;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Exercise/Quiz per Modul (Req 15.1-15.5).
 *
 * Load questions matching module tag, score on submit,
 * mark module completed if score >= 70.
 */
new #[Layout('components.layouts.app')] class extends Component {
    public ?Module $module = null;
    public array $questions = [];
    public array $answers = [];
    public ?int $score = null;
    public bool $submitted = false;
    public bool $passed = false;
    public string $writingSubmission = '';
    public ?int $mcScore = null;
    public ?int $aiWritingScore = null;
    public ?string $aiWritingFeedback = null;
    public bool $isEvaluating = false;

    public function mount(Module $module): void
    {
        $this->module = $module;

        // Guard: check unlocked
        $progress = UserModuleProgress::where('user_id', auth()->id())
            ->where('module_id', $this->module->id)
            ->first();

        if (!$progress || !$progress->is_unlocked) {
            session()->flash('error', 'Modul ini masih terkunci.');
            $this->redirect(route('learning-path'), navigate: true);
            return;
        }

        // Guard: check if all lessons are completed
        $completedLessonIds = \App\Models\UserLessonProgress::where('user_id', auth()->id())
            ->pluck('lesson_id')
            ->toArray();
            
        $modLessonIds = $this->module->lessons->pluck('id')->toArray();
        if (count($modLessonIds) > 0 && count(array_diff($modLessonIds, $completedLessonIds)) > 0) {
            session()->flash('error', 'Anda harus menyelesaikan semua materi di modul ini terlebih dahulu sebelum mengambil quiz.');
            $this->redirect(route('learning-path'), navigate: true);
            return;
        }

        $this->loadQuestions();
    }

    private function loadQuestions(): void
    {
        // Req 15.1 (Revised): Fetch specific questions belonging to this module
        $questions = $this->module->questions()
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $this->questions = $questions->map(fn ($q) => [
            'id'            => $q->id,
            'question'      => $q->question,
            'question_type' => $q->question_type ?? 'multiple_choice',
            'option_a'      => $q->option_a,
            'option_b'      => $q->option_b,
            'option_c'      => $q->option_c,
            'option_d'      => $q->option_d,
            'correct'       => $q->correct_answer,
            'options_json'  => is_string($q->options_json) ? json_decode($q->options_json, true) : ($q->options_json ?? []),
            'answer_json'   => is_string($q->answer_json) ? json_decode($q->answer_json, true) : ($q->answer_json ?? []),
        ])->toArray();

        foreach ($this->questions as &$q) {
            if ($q['question_type'] === 'ordering' && isset($q['options_json']['items'])) {
                shuffle($q['options_json']['items']);
            }
            if ($q['question_type'] === 'matching' && isset($q['options_json']['pairs'])) {
                $rights = array_column($q['options_json']['pairs'], 'right');
                shuffle($rights);
                $q['shuffled_rights'] = $rights;
            }
        }

        // Initialize answers
        $this->answers = array_fill(0, count($this->questions), null);
    }

    /**
     * Submit answers and calculate score (Req 15.3-15.5 + AI Writing Evaluation).
     */
    public function submit(): void
    {
        if (empty($this->questions) && empty(trim($this->module->writing_prompt ?? ''))) {
            return;
        }

        // 1. Grade Questions if available
        if (!empty($this->questions)) {
            $correctCount = 0;
            $total = count($this->questions);

            foreach ($this->questions as $index => $q) {
                $given = $this->answers[$index] ?? null;
                $qType = $q['question_type'] ?? 'multiple_choice';
                $isCorrect = false;

                if ($given !== null) {
                    if ($qType === 'multiple_choice') {
                        if (is_string($given) && strtolower(trim($given)) === strtolower(trim($q['correct'] ?? ''))) {
                            $isCorrect = true;
                        }
                    } elseif ($qType === 'short_answer') {
                        $acceptable = $q['answer_json']['acceptable_answers'] ?? [];
                        $givenStr = strtolower(trim((string)$given));
                        foreach ($acceptable as $acc) {
                            $accText = is_array($acc) ? ($acc['answer'] ?? '') : $acc;
                            if (strtolower(trim((string)$accText)) === $givenStr) {
                                $isCorrect = true;
                                break;
                            }
                        }
                    } elseif ($qType === 'matching' || $qType === 'ordering') {
                        if (is_array($given) && isset($q['answer_json'])) {
                            if (json_encode($given) === json_encode($q['answer_json'])) {
                                $isCorrect = true;
                            }
                        }
                    }
                }

                if ($isCorrect) {
                    $correctCount++;
                }
            }
            $this->mcScore = $total > 0 ? (int) round($correctCount / $total * 100) : 0;
        }

        // 2. Evaluate AI Writing Prompt using Groq API if present
        $hasWritingPrompt = !empty(trim($this->module->writing_prompt ?? ''));
        if ($hasWritingPrompt && !empty(trim($this->writingSubmission))) {
            try {
                $groq = app(\App\Services\GroqService::class);
                $promptText = "Writing Task Prompt:\n" . $this->module->writing_prompt . "\n\n"
                    . "Evaluation Parameters & Guidelines:\n" . ($this->module->ai_evaluation_parameters ?: "Evaluate overall English grammar, vocabulary usage, clarity, and task relevance.") . "\n\n"
                    . "Student Writing Answer:\n" . $this->writingSubmission;

                $messages = [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI English Writing Assessor. Evaluate the student writing submission strictly based on the provided prompt and guidelines. Respond ONLY in valid JSON format with two keys: "score" (integer between 0 and 100) and "feedback" (string containing clear, constructive, professional feedback in Indonesian language).'
                    ],
                    [
                        'role' => 'user',
                        'content' => $promptText
                    ]
                ];

                $response = $groq->chatCompletion($messages, [
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object']
                ]);

                $content = $response['choices'][0]['message']['content'] ?? '{}';
                $json = json_decode($content, true);

                $this->aiWritingScore = isset($json['score']) ? (int) $json['score'] : 75;
                $this->aiWritingFeedback = $json['feedback'] ?? 'Jawaban Anda telah dievaluasi oleh AI.';
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('AI Writing Evaluation Error: ' . $e->getMessage());
                $this->aiWritingScore = 75;
                $this->aiWritingFeedback = 'Gagal terhubung dengan layanan AI Groq. Nilai default diberikan.';
            }
        }

        // 3. Calculate Final Combined Score
        if ($this->mcScore !== null && $this->aiWritingScore !== null) {
            $this->score = (int) round(($this->mcScore + $this->aiWritingScore) / 2);
        } elseif ($this->aiWritingScore !== null) {
            $this->score = $this->aiWritingScore;
        } else {
            $this->score = $this->mcScore ?? 0;
        }

        $this->submitted = true;
        $this->passed = $this->score >= 70;

        // 4. Update module progress in DB
        $userId = auth()->id();

        if ($this->passed) {
            UserModuleProgress::updateOrCreate(
                ['user_id' => $userId, 'module_id' => $this->module->id],
                [
                    'is_completed' => true,
                    'score'        => $this->score,
                    'completed_at' => now(),
                ]
            );

            // Unlock next module in sequence
            $nextProgress = UserModuleProgress::where('user_id', $userId)
                ->whereHas('module', function ($query) {
                    $query->where('order_index', '>', $this->module->order_index)
                          ->where('is_active', true);
                })
                ->join('modules', 'user_module_progress.module_id', '=', 'modules.id')
                ->orderBy('modules.order_index', 'asc')
                ->select('user_module_progress.*')
                ->first();

            if ($nextProgress && !$nextProgress->is_unlocked) {
                $nextProgress->update(['is_unlocked' => true]);
            }
        } else {
            UserModuleProgress::updateOrCreate(
                ['user_id' => $userId, 'module_id' => $this->module->id],
                ['score' => $this->score]
            );
        }
    }

    /**
     * Reset for retry.
     */
    public function retry(): void
    {
        $this->submitted = false;
        $this->score = null;
        $this->mcScore = null;
        $this->aiWritingScore = null;
        $this->aiWritingFeedback = null;
        $this->writingSubmission = '';
        $this->passed = false;
        $this->answers = array_fill(0, count($this->questions), null);
        $this->loadQuestions();
    }
}; ?>

<div class="w-full min-w-0 py-8 px-4 text-zinc-900 dark:text-zinc-100"
    x-data="exercisePersist({{ auth()->id() }}, {{ $module->id }})"
    x-init="init()">

    <!-- Offline Banner -->
    <div x-show="isOffline"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed top-0 left-0 right-0 z-50 flex items-center justify-center gap-2 py-3 px-4 bg-red-600 text-white text-sm font-semibold shadow-lg"
         x-cloak>
        <svg class="w-4 h-4 animate-pulse flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M12 12h.01M3 3l18 18"/>
        </svg>
        <span>Koneksi internet terputus. Jawaban Anda tersimpan secara offline &amp; akan pulih otomatis.</span>
    </div>
    <div class="mb-6">
        <a href="{{ route('module-detail', $module->id) }}" wire:navigate class="text-blue-600 dark:text-blue-400 text-sm hover:underline">← Kembali ke Modul</a>
    </div>

    <h1 class="text-2xl font-bold mb-2">Exercise: {{ $module->title }}</h1>

    @if (empty($questions) && empty(trim($module->writing_prompt ?? '')))
        <div class="text-center py-12 text-gray-500 dark:text-zinc-400">
            <p>No questions or exercises available for this module yet. Please contact your lecturer.</p>
        </div>
    @elseif ($submitted)
        {{-- Result --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow border border-transparent dark:border-zinc-800 p-6 mb-6">
            <h2 class="text-xl font-semibold mb-2 text-zinc-800 dark:text-zinc-100">Exercise Result</h2>
            <div class="text-4xl font-bold mb-2 {{ $passed ? 'text-green-600 dark:text-green-500' : 'text-red-600 dark:text-red-500' }}">
                {{ $score }}%
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-4">
                @if($mcScore !== null)
                    <div class="p-4 bg-zinc-900/90 dark:bg-zinc-950/90 rounded-xl border border-zinc-700/80 dark:border-zinc-700/80">
                        <span class="text-xs text-zinc-400 dark:text-zinc-400 block font-semibold uppercase tracking-wider">Multiple Choice Score</span>
                        <span class="text-2xl font-extrabold text-white dark:text-white mt-1 block">{{ $mcScore }}%</span>
                    </div>
                @endif
                @if($aiWritingScore !== null)
                    <div class="p-4 bg-zinc-900/90 dark:bg-zinc-950/90 rounded-xl border border-indigo-500/40 dark:border-indigo-500/40">
                        <span class="text-xs text-indigo-400 dark:text-indigo-400 block font-semibold uppercase tracking-wider">AI Writing Score</span>
                        <span class="text-2xl font-extrabold text-indigo-300 dark:text-indigo-300 mt-1 block">{{ $aiWritingScore }}%</span>
                    </div>
                @endif
            </div>

            @if($aiWritingFeedback)
                <div class="mt-4 p-4 rounded-xl bg-zinc-900/90 dark:bg-zinc-950/90 border border-indigo-500/30 dark:border-indigo-500/30">
                    <h3 class="font-bold text-indigo-400 dark:text-indigo-400 mb-1.5 flex items-center gap-2 text-sm">
                        <span>🤖</span> AI Groq Evaluation &amp; Feedback:
                    </h3>
                    <p class="text-sm text-zinc-200 dark:text-zinc-200 leading-relaxed whitespace-pre-line">{{ $aiWritingFeedback }}</p>
                </div>
            @endif

            <p class="text-gray-600 dark:text-zinc-400 mt-4">
                @if ($passed)
                    🎉 Congratulations! You passed this exercise. Module marked as complete.
                @else
                    Minimum score is 70% to complete this module. Please try again.
                @endif
            </p>
            <div class="mt-4 flex gap-3">
                @if (!$passed)
                    <flux:button wire:click="retry" variant="primary">Try Again</flux:button>
                @endif
                <a href="{{ route('learning-path') }}" wire:navigate>
                    <flux:button variant="ghost">Back to Roadmap</flux:button>
                </a>
            </div>
        </div>
    @else
        {{-- Questions & Writing Prompt Form --}}
        <form wire:submit="submit" class="space-y-6">
            @if (!empty($questions))
                <div class="space-y-6">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>📝</span> Part 1: Multiple Choice Questions
                    </h2>
                    @foreach ($questions as $index => $q)
                        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow border border-transparent dark:border-zinc-800 p-4">
                            <div class="font-medium mb-3 text-zinc-800 dark:text-zinc-100 flex gap-2 items-start">
                                <span>{{ $index + 1 }}.</span>
                                <div class="[&>p]:inline [&_strong]:font-bold [&_strong]:text-indigo-600 dark:[&_strong]:text-indigo-400 [&_b]:font-bold [&_b]:text-indigo-600 dark:[&_b]:text-indigo-400 [&_u]:underline">{!! $q['question'] !!}</div>
                            </div>
                            
                            @if (($q['question_type'] ?? 'multiple_choice') === 'multiple_choice')
                                <div class="space-y-2">
                                    @foreach (['a', 'b', 'c', 'd'] as $opt)
                                        <label class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-zinc-800 cursor-pointer transition-colors">
                                            <input type="radio"
                                                   wire:model="answers.{{ $index }}"
                                                   value="{{ $opt }}"
                                                   class="text-blue-600 dark:bg-zinc-950 border-gray-300 dark:border-zinc-700">
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ strtoupper($opt) }}. {{ $q["option_{$opt}"] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            
                            @elseif (($q['question_type'] ?? '') === 'short_answer')
                                <div class="mt-2">
                                    <input type="text"
                                           wire:model="answers.{{ $index }}"
                                           placeholder="Type your answer here..."
                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500 focus:border-indigo-500 p-2">
                                </div>
                            
                            @elseif (($q['question_type'] ?? '') === 'matching')
                                <div class="space-y-4 mt-4" x-data="{
                                    pairs: @js($q['options_json']['pairs'] ?? []),
                                    rights: @js($q['shuffled_rights'] ?? []),
                                    answers: {},
                                    init() {
                                        this.$watch('answers', val => {
                                            let formatted = { pairs: [] };
                                            for(let i=0; i<this.pairs.length; i++) {
                                                formatted.pairs.push({
                                                    left: this.pairs[i].left,
                                                    right: val[i] || ''
                                                });
                                            }
                                            @this.set('answers.{{ $index }}', formatted);
                                        });
                                        
                                        let existing = @this.get('answers.{{ $index }}');
                                        if (existing && existing.pairs) {
                                            existing.pairs.forEach((p, i) => {
                                                this.answers[i] = p.right;
                                            });
                                        }
                                    }
                                }">
                                    <template x-for="(pair, i) in pairs" :key="i">
                                        <div class="flex items-center gap-4">
                                            <div class="flex-1 p-3 bg-zinc-50 dark:bg-zinc-950 rounded border border-zinc-200 dark:border-zinc-800 text-sm" x-text="pair.left"></div>
                                            <div class="shrink-0 text-zinc-400">→</div>
                                            <div class="flex-1">
                                                <select x-model="answers[i]" class="w-full p-3 text-sm rounded bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer hover:border-gray-400 dark:hover:border-zinc-700 transition-colors">
                                                    <option value="">-- Select Match --</option>
                                                    <template x-for="r in rights">
                                                        <option :value="r" x-text="r"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                            @elseif (($q['question_type'] ?? '') === 'ordering')
                                <div class="space-y-3 mt-4" x-data="{
                                    items: @js($q['options_json']['items'] ?? []),
                                    selectedOrder: [],
                                    init() {
                                        this.$watch('selectedOrder', val => {
                                            let formatted = { correct_sequence: [] };
                                            val.forEach(item => {
                                                formatted.correct_sequence.push({ item: item.item });
                                            });
                                            @this.set('answers.{{ $index }}', formatted);
                                        });
                                        
                                        let existing = @this.get('answers.{{ $index }}');
                                        if (existing && existing.correct_sequence) {
                                            this.selectedOrder = existing.correct_sequence.map(s => s);
                                        }
                                    },
                                    toggleItem(item) {
                                        let idx = this.selectedOrder.findIndex(s => s.item === item.item);
                                        if (idx >= 0) {
                                            this.selectedOrder.splice(idx, 1);
                                        } else {
                                            this.selectedOrder.push(item);
                                        }
                                    }
                                }">
                                    <p class="text-xs text-zinc-500">Click the items below in the correct order:</p>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(item, i) in items" :key="i">
                                            <button type="button" 
                                                @click="toggleItem(item)"
                                                :class="selectedOrder.find(s => s.item === item.item) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-zinc-950 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-700 hover:border-indigo-500'"
                                                class="px-4 py-2 border rounded-full text-sm transition-colors relative">
                                                <span x-text="item.item"></span>
                                                <span x-show="selectedOrder.findIndex(s => s.item === item.item) >= 0" 
                                                      x-text="(selectedOrder.findIndex(s => s.item === item.item) + 1)"
                                                      class="absolute -top-2 -right-2 w-5 h-5 bg-emerald-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                    <div x-show="selectedOrder.length > 0" class="mt-4 p-3 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                                        <p class="text-xs font-semibold text-indigo-800 dark:text-indigo-400 mb-2">Your Order:</p>
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-indigo-900 dark:text-indigo-200">
                                            <template x-for="(sel, i) in selectedOrder">
                                                <div class="flex items-center gap-2">
                                                    <span x-text="sel.item" class="font-medium"></span>
                                                    <span x-show="i < selectedOrder.length - 1" class="text-indigo-300 dark:text-indigo-700">→</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if (!empty(trim($module->writing_prompt ?? '')))
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 p-6 space-y-4">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>✍️</span> Part 2: AI Writing Practice
                    </h2>
                    <div class="p-4 bg-zinc-900/50 dark:bg-zinc-950/80 rounded-xl text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line border border-zinc-200 dark:border-zinc-800">
                        <strong class="block mb-1 text-indigo-600 dark:text-indigo-400 font-semibold">Writing Prompt / Instructions:</strong>
                        {{ $module->writing_prompt }}
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-zinc-700 dark:text-zinc-300">Your Response (English):</label>
                        <textarea
                            wire:model="writingSubmission"
                            rows="6"
                            class="w-full rounded-xl border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 text-sm p-4 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-zinc-400 dark:placeholder-zinc-600 transition"
                            placeholder="Type your response here..."></textarea>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div wire:loading wire:target="submit" class="text-sm text-indigo-600 dark:text-indigo-400 animate-pulse font-medium flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Mengevaluasi jawaban dengan AI Groq...
                </div>
                <div class="ml-auto">
                    <flux:button type="submit" variant="primary" x-bind:disabled="isOffline" wire:loading.attr="disabled">
                        Submit Answers
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
    function exercisePersist(userId, moduleId) {
        const storageKey = `apels_ex_${userId}_${moduleId}`;

        return {
            isOffline: false,

            init() {
                // Restore saved answers from localStorage on page load
                try {
                    const saved = localStorage.getItem(storageKey);
                    if (saved) {
                        const data = JSON.parse(saved);
                        const qTypes = @js(array_column($this->questions, 'question_type'));
                        if (data.answers) {
                            data.answers.forEach((val, idx) => {
                                if (val !== null && val !== undefined) {
                                    if (qTypes[idx] === 'short_answer' && typeof val === 'object') {
                                        val = '';
                                    }
                                    @this.set(`answers.${idx}`, val);
                                }
                            });
                        }
                    }
                } catch(e) {}

                // Watch for answer changes and auto-save
                this.$watch('$wire.answers', (val) => {
                    try {
                        localStorage.setItem(storageKey, JSON.stringify({
                            answers: val,
                            savedAt: new Date().toISOString(),
                        }));
                    } catch(e) {}
                });

                // Detect offline/online status
                this.isOffline = !navigator.onLine;
                window.addEventListener('offline', () => { this.isOffline = true; });
                window.addEventListener('online',  () => { this.isOffline = false; });

                // Clear storage when exercise is submitted and passed
                this.$watch('$wire.passed', (passed) => {
                    if (passed) {
                        try { localStorage.removeItem(storageKey); } catch(e) {}
                    }
                });
            },
        };
    }
</script>
