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

        $this->loadQuestions();
    }

    private function loadQuestions(): void
    {
        // Req 15.1: questions matching module tag, active only
        $questions = Question::active()
            ->byTag($this->module->tag)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $this->questions = $questions->map(fn ($q) => [
            'id'        => $q->id,
            'question'  => $q->question,
            'option_a'  => $q->option_a,
            'option_b'  => $q->option_b,
            'option_c'  => $q->option_c,
            'option_d'  => $q->option_d,
            'correct'   => $q->correct_answer,
        ])->toArray();

        // Initialize answers
        $this->answers = array_fill(0, count($this->questions), null);
    }

    /**
     * Submit answers and calculate score (Req 15.3-15.5).
     */
    public function submit(): void
    {
        if (empty($this->questions)) {
            return;
        }

        $correct = 0;
        $total = count($this->questions);

        foreach ($this->questions as $index => $q) {
            if (isset($this->answers[$index]) && $this->answers[$index] === $q['correct']) {
                $correct++;
            }
        }

        $this->score = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $this->submitted = true;
        $this->passed = $this->score >= 70;

        // Update module progress
        $userId = auth()->id();

        if ($this->passed) {
            // Req 15.4: mark completed
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
            // Req 15.5: save score only, allow retry
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

    @if (empty($questions))
        <div class="text-center py-12 text-gray-500 dark:text-zinc-400">
            <p>No questions available for this module yet. Please contact your lecturer.</p>
        </div>
    @elseif ($submitted)
        {{-- Result --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow border border-transparent dark:border-zinc-800 p-6 mb-6">
            <h2 class="text-xl font-semibold mb-2 text-zinc-800 dark:text-zinc-100">Exercise Result</h2>
            <div class="text-4xl font-bold mb-2 {{ $passed ? 'text-green-600 dark:text-green-500' : 'text-red-600 dark:text-red-500' }}">
                {{ $score }}%
            </div>
            <p class="text-gray-600 dark:text-zinc-400">
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
        {{-- Questions --}}
        <form wire:submit="submit" class="space-y-6">
            @foreach ($questions as $index => $q)
                <div class="bg-white dark:bg-zinc-900 rounded-lg shadow border border-transparent dark:border-zinc-800 p-4">
                    <p class="font-medium mb-3 text-zinc-800 dark:text-zinc-100">{{ $index + 1 }}. {{ $q['question'] }}</p>
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
                </div>
            @endforeach

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" x-bind:disabled="isOffline">Submit Answers</flux:button>
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
                        if (data.answers) {
                            data.answers.forEach((val, idx) => {
                                if (val !== null && val !== undefined) {
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
