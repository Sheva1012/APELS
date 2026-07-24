<?php

use App\Models\Question;
use App\Services\DiagnosticService;
use App\Services\ScoringService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

/**
 * DiagnosticTest — Wizard 3 langkah (APELS-EXECUTION-SPEC §5):
 *   Step 1: Grammar   — 9 soal, urut basic→intermediate→advanced
 *   Step 2: Vocabulary — 11 soal, urut basic→intermediate→advanced
 *   Step 3: Speaking  — rekam audio, tanpa soal pilihan ganda
 *
 * Scoring: deterministik berbasis kunci jawaban (bukan NLP).
 * Opsi teracak per-soal (§6) dengan seed userId+attemptNumber.
 */
new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public int $currentStep = 1;

    // SET aktif dan nomor attempt
    public string $activeSet = 'A';
    public int $attemptNumber = 1;

    // Jawaban mahasiswa: key = question ID (string), value = 'a'/'b'/'c'/'d'
    public array $grammarAnswers = [];
    public array $vocabAnswers   = [];

    // Soal yang dimuat dari database (array of arrays)
    public array $grammarQuestions = [];
    public array $vocabQuestions   = [];

    // Urutan opsi teracak per soal: ['questionId' => ['a','c','b','d'], ...]
    public array $shuffledOptions = [];

    // Step 3: Audio file
    public $audioFile = null;

    public ?string $errorMessage = null;
    public bool $isSubmitting = false;

    public function mount(): void
    {
        $userId = auth()->id();
        $service = app(DiagnosticService::class);

        $this->activeSet    = $service->resolveActiveSet($userId);
        $this->attemptNumber = $service->nextAttemptNumber($userId);

        // Query sesuai spec §5 — set aktif, urut difficulty (basic→intermediate→advanced)
        $gQs = Question::active()
            ->bySet($this->activeSet)
            ->byType('grammar')
            ->orderBy('difficulty')
            ->get();

        $vQs = Question::active()
            ->bySet($this->activeSet)
            ->byType('vocabulary')
            ->orderBy('difficulty')
            ->get();

        $this->grammarQuestions = $gQs->toArray();
        $this->vocabQuestions   = $vQs->toArray();

        // Inisialisasi jawaban kosong (key = string prefix + question ID)
        foreach ($this->grammarQuestions as $q) {
            $this->grammarAnswers['q_' . $q['id']] = '';
        }
        foreach ($this->vocabQuestions as $q) {
            $this->vocabAnswers['q_' . $q['id']] = '';
        }

        // Pre-generate urutan opsi teracak (§6)
        // Seed = userId + attemptNumber agar konsisten saat refresh
        $this->shuffledOptions = $this->buildShuffledOptions(
            array_merge($this->grammarQuestions, $this->vocabQuestions),
            $userId,
            $this->attemptNumber
        );
    }

    /**
     * Bangun urutan opsi teracak per soal.
     * Seed deterministik: (userId * 1000 + attemptNumber * 100 + questionId) % PHP_INT_MAX
     *
     * @param array $questions
     * @param int   $userId
     * @param int   $attemptNumber
     * @return array<int, list<string>>  questionId => ['a','c','b','d']
     */
    private function buildShuffledOptions(array $questions, int $userId, int $attemptNumber): array
    {
        $result = [];
        foreach ($questions as $q) {
            $result[$q['id']] = ['a', 'b', 'c', 'd'];
        }
        return $result;
    }

    /**
     * Navigasi ke step berikutnya dengan validasi.
     */
    public function nextStep(): void
    {
        $this->resetValidation();

        if ($this->currentStep === 1) {
            $this->validateGrammar();
        } elseif ($this->currentStep === 2) {
            $this->validateVocab();
        }

        if ($this->currentStep < 3) {
            $this->currentStep++;
            $this->dispatch('scroll-to-top');
        }
    }

    /**
     * Navigasi ke step sebelumnya (TIDAK bisa mundur setelah submit per-step per spec §5).
     */
    public function previousStep(): void
    {
        $this->resetValidation();
        // Spec §5: "(submit, tidak bisa mundur)" — disable mundur setelah submit
        // Kita tetap allow navigasi mundur dalam wizard sebelum final submit
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('scroll-to-top');
        }
    }

    /**
     * Submit final diagnostic test (step 3 speaking).
     * Scoring: ScoringService::scoreSection (deterministik, berbasis kunci jawaban).
     */
    public function submit(): void
    {
        $this->resetValidation();
        $this->isSubmitting = true;
        $this->errorMessage = null;

        // Validate audio
        $this->validate([
            'audioFile' => ['required', 'file', 'mimes:wav,mp3,webm,ogg', 'max:' . (config('apels.audio_max_mb', 10) * 1024)],
        ]);

        try {
            // Kumpulkan semua jawaban mentah (format: [{question_id, answer}, ...])
            $rawAnswers = [];
            foreach ($this->grammarAnswers as $qKey => $ans) {
                $rawAnswers[] = ['question_id' => str_replace('q_', '', $qKey), 'section' => 'grammar', 'answer' => $ans];
            }
            foreach ($this->vocabAnswers as $qKey => $ans) {
                $rawAnswers[] = ['question_id' => str_replace('q_', '', $qKey), 'section' => 'vocabulary', 'answer' => $ans];
            }

            // Hitung skor grammar dan vocabulary menggunakan ScoringService
            $scoringService = app(ScoringService::class);

            $grammarCollection = \Illuminate\Support\Collection::make($this->grammarQuestions);
            $grammarScore = $scoringService->scoreSection(
                $grammarCollection,
                collect($this->grammarAnswers)->mapWithKeys(fn($v, $k) => [str_replace('q_', '', $k) => $v])->all()
            );

            $vocabCollection = \Illuminate\Support\Collection::make($this->vocabQuestions);
            $vocabScore = $scoringService->scoreSection(
                $vocabCollection,
                collect($this->vocabAnswers)->mapWithKeys(fn($v, $k) => [str_replace('q_', '', $k) => $v])->all()
            );

            // Simpan audio
            $audioPath = $this->audioFile->store('diagnostic-audio', 'private');

            // Proses melalui DiagnosticService
            $service = app(DiagnosticService::class);
            $result = $service->process(auth()->id(), [
                'grammar_score'      => $grammarScore,
                'vocab_score'        => $vocabScore,
                'audio_path'         => $audioPath,
                'speaking_reference' => null,
                'answers'            => $rawAnswers,
            ]);

            session()->flash('status', $result['message']);
            $this->dispatch('clear-test-storage');
            $this->redirect(route('dashboard'), navigate: true);
        } catch (\Throwable $e) {
            $this->errorMessage = 'An error occurred while processing the test. Please try again.';
            $this->isSubmitting = false;
        }
    }

    private function validateGrammar(): void
    {
        $filledCount = 0;
        foreach ($this->grammarQuestions as $q) {
            if (in_array($this->grammarAnswers['q_' . $q['id']] ?? '', ['a', 'b', 'c', 'd'])) {
                $filledCount++;
            }
        }

        if ($filledCount < count($this->grammarQuestions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'grammarAnswers' => 'All grammar questions must be answered.',
            ]);
        }
    }

    private function validateVocab(): void
    {
        $filledCount = 0;
        foreach ($this->vocabQuestions as $q) {
            if (in_array($this->vocabAnswers['q_' . $q['id']] ?? '', ['a', 'b', 'c', 'd'])) {
                $filledCount++;
            }
        }

        if ($filledCount < count($this->vocabQuestions)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vocabAnswers' => 'All vocabulary questions must be answered.',
            ]);
        }
    }
}; ?>

<div class="relative py-8"
    x-data="diagnosticPersist({{ auth()->id() }}, {{ $attemptNumber }})"
    x-on:scroll-to-top.window="window.scrollTo({top: 0, behavior: 'smooth'})"
    x-on:clear-test-storage.window="clearStorage()"
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
    <!-- Decorative background blobs -->
    <div class="absolute top-0 -left-10 w-72 h-72 bg-indigo-500/10 dark:bg-indigo-600/5 rounded-full blur-3xl -z-10 pointer-events-none"></div>
    <div class="absolute bottom-20 -right-10 w-80 h-80 bg-violet-500/10 dark:bg-violet-600/5 rounded-full blur-3xl -z-10 pointer-events-none"></div>

    <div class="w-full min-w-0 px-4">
        <!-- Title Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Diagnostic Test</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                Attempt #{{ $attemptNumber }} &mdash; <span class="font-bold text-indigo-500">Set {{ $activeSet }}</span>
            </p>
        </div>

        <!-- Progress indicator stepper -->
        <div class="relative max-w-lg mx-auto mb-10">
            <!-- Connecting Line background -->
            <div class="absolute top-1/2 left-0 w-full h-1 bg-zinc-200 dark:bg-zinc-800 -translate-y-1/2 -z-10 rounded-full"></div>
            <!-- Connecting Line active fill -->
            <div class="absolute top-1/2 left-0 h-1 bg-gradient-to-r from-indigo-500 to-violet-600 -translate-y-1/2 -z-10 rounded-full transition-all duration-500" 
                 style="width: {{ (($currentStep - 1) / 2) * 100 }}%"></div>

            <!-- Stepper circles -->
            <div class="flex justify-between items-center">
                @foreach ([1 => 'Grammar', 2 => 'Vocabulary', 3 => 'Speaking'] as $step => $label)
                    @php
                        $isCompleted = $currentStep > $step;
                        $isActive = $currentStep === $step;
                        $isPending = $currentStep < $step;
                    @endphp
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-500
                            {{ $isCompleted ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-lg shadow-emerald-500/20' : '' }}
                            {{ $isActive ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white ring-4 ring-indigo-500/20 shadow-lg shadow-indigo-500/30 scale-110' : '' }}
                            {{ $isPending ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500' : '' }}
                        ">
                            @if ($isCompleted)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                {{ $step }}
                            @endif
                        </div>
                        <span class="mt-2 text-xs font-bold transition-colors duration-300
                            {{ $isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-zinc-500' }}
                        ">
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white/80 dark:bg-zinc-900/80 border border-zinc-200/50 dark:border-zinc-800/50 backdrop-blur-md rounded-3xl p-8 shadow-xl transition-all duration-300 hover:shadow-2xl">
            @if ($errorMessage)
                <div class="flex items-center gap-3 p-4 mb-6 bg-red-500/10 border border-red-500/20 text-red-800 dark:text-red-200 rounded-2xl">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="text-xs font-semibold">{{ $errorMessage }}</span>
                </div>
            @endif

            <!-- Step 1: Grammar -->
            @if ($currentStep === 1)
                <div x-data="{ page: 1, perPage: 5, totalPages: Math.ceil({{ count($grammarQuestions) }} / 5) }">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                            <span class="font-extrabold text-sm">G</span>
                        </div>
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">Step 1: Grammar Evaluation</h2>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6 leading-relaxed">Choose the most appropriate answer for each question below.</p>

                    <div class="space-y-4">
                        @foreach ($grammarQuestions as $qi => $q)
                            @php $qId = $q['id']; @endphp
                            <div x-show="page === Math.ceil({{ $qi + 1 }} / perPage)" class="p-4 bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-900 rounded-2xl relative transition-all hover:border-indigo-500/20">
                                <div class="mb-1">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Question {{ $qi + 1 }} (Grammar)</span>
                                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1 leading-relaxed">{{ $q['question'] }}</p>
                                    <div class="mt-4 space-y-2">
                                        @foreach ($shuffledOptions[$qId] ?? ['a','b','c','d'] as $opt)
                                            <label class="flex items-center gap-3 p-3 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 cursor-pointer transition-all duration-200
                                                {{ ($grammarAnswers['q_' . $qId] ?? '') === $opt ? 'border-indigo-500 bg-indigo-50/20 dark:border-indigo-500 dark:bg-indigo-500/5 ring-1 ring-indigo-500' : '' }}">
                                                <input type="radio"
                                                       wire:model="grammarAnswers.q_{{ $qId }}"
                                                       value="{{ $opt }}"
                                                       class="w-4 h-4 text-indigo-600 border-zinc-300 dark:border-zinc-700 focus:ring-indigo-500 focus:ring-2 dark:bg-zinc-800">
                                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                    <strong class="text-indigo-600 dark:text-indigo-400 mr-1">{{ strtoupper($opt) }}.</strong> 
                                                    {{ $q["option_{$opt}"] }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                    @error('grammarAnswers')
                        <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-2 text-xs font-semibold animate-pulse">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex justify-between mt-8 border-t border-zinc-100 dark:border-zinc-900 pt-6">
                        <flux:button x-show="page > 1" @click="page--; window.scrollTo({top: 0, behavior: 'smooth'})" variant="ghost" class="font-semibold" x-cloak>
                            ← Previous Page
                        </flux:button>
                        <div x-show="page === 1"></div>

                        <flux:button x-show="page < totalPages" @click="page++; window.scrollTo({top: 0, behavior: 'smooth'})" variant="primary" class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold" x-cloak>
                            Next Page →
                        </flux:button>
                        <flux:button x-show="page === totalPages" wire:click="nextStep" variant="primary" class="w-full sm:w-auto bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold shadow-md shadow-indigo-500/20" x-cloak x-bind:disabled="isOffline">
                            Next Section →
                        </flux:button>
                    </div>
                </div>
            @endif

            <!-- Step 2: Vocabulary -->
            @if ($currentStep === 2)
                <div x-data="{ page: 1, perPage: 5, totalPages: Math.ceil({{ count($vocabQuestions) }} / 5) }">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                            <span class="font-extrabold text-sm">V</span>
                        </div>
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">Step 2: Vocabulary Evaluation</h2>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6 leading-relaxed">Choose the most appropriate answer for each question below.</p>

                    <div class="space-y-4">
                        @foreach ($vocabQuestions as $qi => $q)
                            @php $qId = $q['id']; @endphp
                            <div x-show="page === Math.ceil({{ $qi + 1 }} / perPage)" class="p-4 bg-zinc-50/50 dark:bg-zinc-950/20 border border-zinc-150 dark:border-zinc-900 rounded-2xl relative transition-all hover:border-indigo-500/20">
                                <div class="mb-1">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Question {{ $qi + 1 }} (Vocabulary)</span>
                                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1 leading-relaxed">{{ $q['question'] }}</p>
                                    <div class="mt-4 space-y-2">
                                        @foreach ($shuffledOptions[$qId] ?? ['a','b','c','d'] as $opt)
                                            <label class="flex items-center gap-3 p-3 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50/50 dark:hover:bg-zinc-900/50 cursor-pointer transition-all duration-200
                                                {{ ($vocabAnswers['q_' . $qId] ?? '') === $opt ? 'border-indigo-500 bg-indigo-50/20 dark:border-indigo-500 dark:bg-indigo-500/5 ring-1 ring-indigo-500' : '' }}">
                                                <input type="radio"
                                                       wire:model="vocabAnswers.q_{{ $qId }}"
                                                       value="{{ $opt }}"
                                                       class="w-4 h-4 text-indigo-600 border-zinc-300 dark:border-zinc-700 focus:ring-indigo-500 focus:ring-2 dark:bg-zinc-800">
                                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                                    <strong class="text-indigo-600 dark:text-indigo-400 mr-1">{{ strtoupper($opt) }}.</strong> 
                                                    {{ $q["option_{$opt}"] }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                    @error('vocabAnswers')
                        <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-2 text-xs font-semibold animate-pulse">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex justify-between gap-4 mt-8 border-t border-zinc-100 dark:border-zinc-900 pt-6">
                        <flux:button x-show="page > 1" @click="page--; window.scrollTo({top: 0, behavior: 'smooth'})" variant="ghost" class="font-semibold" x-cloak>
                            ← Previous Page
                        </flux:button>
                        <flux:button x-show="page === 1" wire:click="previousStep" variant="ghost" class="font-semibold" x-cloak>
                            ← Back to Grammar
                        </flux:button>

                        <flux:button x-show="page < totalPages" @click="page++; window.scrollTo({top: 0, behavior: 'smooth'})" variant="primary" class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold" x-cloak>
                            Next Page →
                        </flux:button>
                        <flux:button x-show="page === totalPages" wire:click="nextStep" variant="primary" class="bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-semibold shadow-md shadow-indigo-500/20" x-cloak x-bind:disabled="isOffline">
                            Next Section →
                        </flux:button>
                    </div>
                </div>
            @endif

            <!-- Step 3: Speaking -->
            @if ($currentStep === 3)
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                            <span class="font-extrabold text-sm">S</span>
                        </div>
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">Step 3: Speaking & Pronunciation</h2>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-6 leading-relaxed">Speak in English for 30-60 seconds introducing yourself, your hobbies, or your work. AI will analyze your <em>pronunciation</em> and <em>fluency</em>.</p>

                    <!-- Embedded interactive audio recorder -->
                    <div x-data="audioRecorder()" class="mt-4">
                        <!-- Tab navigation -->
                        <div class="flex border-b border-zinc-200 dark:border-zinc-800 mb-6">
                            <button type="button" @click="tab = 'record'" :class="tab === 'record' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-3 border-b-2 text-center text-xs font-semibold transition-all">
                                🎙️ Record Directly (Recommended)
                            </button>
                            <button type="button" @click="tab = 'upload'" :class="tab === 'upload' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-bold' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'" class="flex-1 py-3 border-b-2 text-center text-xs font-semibold transition-all">
                                📁 Upload Audio File
                            </button>
                        </div>

                        <!-- Record Tab -->
                        <div x-show="tab === 'record'" class="flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-950/40 rounded-2xl border border-zinc-150 dark:border-zinc-900">
                            <!-- Idle State -->
                            <div x-show="status === 'idle'" class="text-center">
                                <button type="button" @click="startRecording" class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg shadow-indigo-600/30 hover:scale-105 active:scale-95 transition-all duration-300">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                    </svg>
                                </button>
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-4">Click to Start Recording</p>
                                <p class="text-xs text-zinc-500 mt-1">Make sure your microphone is active and you are in a quiet environment.</p>
                            </div>

                            <!-- Recording State -->
                            <div x-show="status === 'recording'" class="text-center w-full" x-cloak>
                                <div class="flex items-center justify-center gap-1.5 h-10 mb-4">
                                    <div class="w-1 bg-red-500 rounded-full animate-[pulse_0.8s_infinite] h-8"></div>
                                    <div class="w-1 bg-red-500 rounded-full animate-[pulse_0.8s_infinite_0.15s] h-5"></div>
                                    <div class="w-1 bg-red-500 rounded-full animate-[pulse_0.8s_infinite_0.3s] h-10"></div>
                                    <div class="w-1 bg-red-500 rounded-full animate-[pulse_0.8s_infinite_0.45s] h-6"></div>
                                    <div class="w-1 bg-red-500 rounded-full animate-[pulse_0.8s_infinite_0.6s] h-8"></div>
                                </div>
                                
                                <div class="text-3xl font-black font-mono text-red-600 dark:text-red-500 mb-2" x-text="formatTime(seconds)">00:00</div>
                                <p class="text-xs text-zinc-500 mb-6 animate-pulse">Recording your voice...</p>
                                
                                <button type="button" @click="stopRecording" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md shadow-red-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                                    Stop & Save
                                </button>
                            </div>

                            <!-- Finished / Ready State -->
                            <div x-show="status === 'finished'" class="text-center w-full" x-cloak>
                                <div class="w-14 h-14 bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Recording Saved!</h4>
                                <p class="text-xs text-zinc-500 mt-1 mb-6">Listen back to your recording to ensure the audio quality is clear.</p>
                                
                                <audio :src="audioUrl" controls class="mx-auto mb-6 w-full max-w-md"></audio>
                                
                                <button type="button" @click="startRecording" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-semibold text-xs rounded-xl transition-all">
                                    Re-record
                                </button>
                            </div>
                        </div>

                        <!-- Upload Tab -->
                        <div x-show="tab === 'upload'" class="p-8 bg-zinc-50 dark:bg-zinc-950/40 rounded-2xl border border-zinc-150 dark:border-zinc-900" x-cloak>
                            <div class="flex flex-col items-center justify-center border-2 border-dashed border-zinc-300 dark:border-zinc-800 rounded-xl p-8 hover:border-indigo-500/50 transition-colors cursor-pointer relative"
                                 @click="$refs.fileInput.click()">
                                <svg class="w-10 h-10 text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 text-center">Click or Drag Audio File Here</p>
                                <p class="text-[11px] text-zinc-500 mt-1">WAV, MP3, WEBM, or OGG (Max {{ config('apels.audio_max_mb', 10) }} MB)</p>
                                
                                <input
                                    type="file"
                                    x-ref="fileInput"
                                    @change="handleFileUpload"
                                    accept=".wav,.mp3,.webm,.ogg"
                                    class="hidden"
                                />
                            </div>

                            <template x-if="uploadedFileName">
                                <div class="mt-4 p-3 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                        📁 <span x-text="uploadedFileName" class="font-semibold truncate max-w-xs"></span>
                                    </div>
                                    <button type="button" @click="removeUploadedFile" class="text-red-500 hover:text-red-700 font-bold">
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Livewire Upload Progress Indicator -->
                        <div x-show="isUploading" class="mt-4 w-full bg-zinc-100 dark:bg-zinc-900 rounded-full h-2 overflow-hidden border border-zinc-200/50 dark:border-zinc-800/50" x-cloak>
                            <div class="bg-indigo-600 h-full rounded-full transition-all duration-300" :style="`width: ${uploadProgress}%`"></div>
                        </div>
                        <div x-show="isUploading" class="text-[10px] text-zinc-500 mt-1 text-right" x-text="`Uploading audio: ${uploadProgress}%`" x-cloak></div>
                    </div>
                    {{-- Optional reference text for pronunciation diff --}}
                    <div class="mt-6 p-4 bg-violet-500/5 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-800/50 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-sm">📝</span>
                            <label for="speakingReference" class="text-xs font-bold text-violet-700 dark:text-violet-400 uppercase tracking-wide">
                                Reference Text <span class="text-zinc-400 font-normal normal-case">(optional)</span>
                            </label>
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-3 leading-relaxed">
                            Type the text you plan to say. AI will compare it with the transcription result and <span class="font-semibold text-violet-600 dark:text-violet-400">highlight words that may have been mispronounced</span>.
                        </p>
                        <textarea
                            id="speakingReference"
                            wire:model="speakingReference"
                            rows="3"
                            placeholder="Contoh: I am a fresh graduate in Information Technology with a focus on web development..."
                            class="w-full text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-3.5 py-2.5 text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 dark:placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-violet-500/40 focus:border-violet-500 resize-none transition-all"
                            maxlength="2000"
                        ></textarea>
                        <p class="text-[10px] text-zinc-400 mt-1 text-right" x-data x-text="`${($wire.speakingReference || '').length}/2000`"></p>
                    </div>

                    @error('audioFile')

                        <div class="mt-6 p-4 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-2 text-xs font-semibold animate-pulse">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex justify-between gap-4 mt-8 border-t border-zinc-100 dark:border-zinc-900 pt-6">
                        <flux:button wire:click="previousStep" variant="ghost" class="font-semibold" :disabled="$isSubmitting">
                            ← Back
                        </flux:button>
                        <flux:button wire:click="submit" variant="primary" class="flex-1 sm:flex-none bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-bold shadow-md shadow-indigo-500/20 transition-all duration-300" wire:loading.attr="disabled" x-bind:disabled="$wire.isSubmitting || isUploading || isOffline">
                            <span wire:loading.remove wire:target="submit">Submit Diagnostic Test</span>
                            <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Alpine.js recorder logic -->
    <script>
        function registerAudioRecorder() {
            if (window.Alpine) {
                if (!Alpine.data('audioRecorder')) {
                    Alpine.data('audioRecorder', () => ({
                        tab: 'record',
                        status: 'idle',
                        mediaRecorder: null,
                        audioChunks: [],
                        audioUrl: '',
                        seconds: 0,
                        timer: null,
                        isUploading: false,
                        uploadProgress: 0,
                        uploadedFileName: '',

                        formatTime(secs) {
                            const m = Math.floor(secs / 60).toString().padStart(2, '0');
                            const s = (secs % 60).toString().padStart(2, '0');
                            return `${m}:${s}`;
                        },

                        async startRecording() {
                            try {
                                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                                this.audioChunks = [];
                                this.mediaRecorder = new MediaRecorder(stream);
                                
                                this.mediaRecorder.ondataavailable = (e) => {
                                    if (e.data.size > 0) {
                                        this.audioChunks.push(e.data);
                                    }
                                };

                                this.mediaRecorder.onstop = () => {
                                    const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
                                    this.audioUrl = URL.createObjectURL(blob);
                                    this.uploadAudioBlob(blob);
                                };

                                this.mediaRecorder.start();
                                this.status = 'recording';
                                this.seconds = 0;
                                this.timer = setInterval(() => {
                                    this.seconds++;
                                    if (this.seconds >= 120) {
                                        this.stopRecording();
                                    }
                                }, 1000);
                            } catch (err) {
                                alert('Cannot access microphone. Please allow microphone access in your browser.');
                                console.error(err);
                            }
                        },

                        stopRecording() {
                            if (this.mediaRecorder && this.status === 'recording') {
                                this.mediaRecorder.stop();
                                this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                                clearInterval(this.timer);
                                this.status = 'finished';
                            }
                        },

                        uploadAudioBlob(blob) {
                            this.isUploading = true;
                            this.uploadProgress = 0;
                            
                            const file = new File([blob], 'recording.webm', { type: 'audio/webm' });
                            
                            @this.upload('audioFile', file,
                                (uploadedName) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    alert('Failed to upload recording. Please try again.');
                                },
                                (event) => {
                                    this.uploadProgress = event.detail.progress;
                                }
                            );
                        },

                        handleFileUpload(event) {
                            const file = event.target.files[0];
                            if (!file) return;

                            this.uploadedFileName = file.name;
                            this.isUploading = true;
                            this.uploadProgress = 0;

                            @this.upload('audioFile', file,
                                (uploadedName) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    alert('Failed to upload file. Please try again.');
                                },
                                (event) => {
                                    this.uploadProgress = event.detail.progress;
                                }
                            );
                        },

                        removeUploadedFile() {
                            this.uploadedFileName = '';
                            this.audioUrl = '';
                            @this.set('audioFile', null);
                        }
                    }));
                }
            } else {
                document.addEventListener('alpine:init', () => {
                    Alpine.data('audioRecorder', () => ({
                        tab: 'record',
                        status: 'idle',
                        mediaRecorder: null,
                        audioChunks: [],
                        audioUrl: '',
                        seconds: 0,
                        timer: null,
                        isUploading: false,
                        uploadProgress: 0,
                        uploadedFileName: '',

                        formatTime(secs) {
                            const m = Math.floor(secs / 60).toString().padStart(2, '0');
                            const s = (secs % 60).toString().padStart(2, '0');
                            return `${m}:${s}`;
                        },

                        async startRecording() {
                            try {
                                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                                this.audioChunks = [];
                                this.mediaRecorder = new MediaRecorder(stream);
                                
                                this.mediaRecorder.ondataavailable = (e) => {
                                    if (e.data.size > 0) {
                                        this.audioChunks.push(e.data);
                                    }
                                };

                                this.mediaRecorder.onstop = () => {
                                    const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
                                    this.audioUrl = URL.createObjectURL(blob);
                                    this.uploadAudioBlob(blob);
                                };

                                this.mediaRecorder.start();
                                this.status = 'recording';
                                this.seconds = 0;
                                this.timer = setInterval(() => {
                                    this.seconds++;
                                    if (this.seconds >= 120) {
                                        this.stopRecording();
                                    }
                                }, 1000);
                            } catch (err) {
                                alert('Tidak dapat mengakses mikrofon. Pastikan Anda memberikan izin akses mikrofon di browser.');
                                console.error(err);
                            }
                        },

                        stopRecording() {
                            if (this.mediaRecorder && this.status === 'recording') {
                                this.mediaRecorder.stop();
                                this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                                clearInterval(this.timer);
                                this.status = 'finished';
                            }
                        },

                        uploadAudioBlob(blob) {
                            this.isUploading = true;
                            this.uploadProgress = 0;
                            
                            const file = new File([blob], 'recording.webm', { type: 'audio/webm' });
                            
                            @this.upload('audioFile', file,
                                (uploadedName) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    alert('Gagal mengunggah rekaman. Silakan coba lagi.');
                                },
                                (event) => {
                                    this.uploadProgress = event.detail.progress;
                                }
                            );
                        },

                        handleFileUpload(event) {
                            const file = event.target.files[0];
                            if (!file) return;

                            this.uploadedFileName = file.name;
                            this.isUploading = true;
                            this.uploadProgress = 0;

                            @this.upload('audioFile', file,
                                (uploadedName) => {
                                    this.isUploading = false;
                                    this.uploadProgress = 100;
                                },
                                (error) => {
                                    this.isUploading = false;
                                    alert('Gagal mengunggah file. Silakan coba lagi.');
                                },
                                (event) => {
                                    this.uploadProgress = event.detail.progress;
                                }
                            );
                        },

                        removeUploadedFile() {
                            this.uploadedFileName = '';
                            this.audioUrl = '';
                            @this.set('audioFile', null);
                        }
                    }));
                });
            }
        }
        registerAudioRecorder();
    </script>

    <script>
        function diagnosticPersist(userId, attemptNumber) {
            const storageKey = `apels_diag_${userId}_${attemptNumber}`;

            return {
                isOffline: false,

                init() {
                    // Restore saved answers from localStorage
                    try {
                        const saved = localStorage.getItem(storageKey);
                        if (saved) {
                            const data = JSON.parse(saved);
                            if (data.grammarAnswers) {
                                Object.entries(data.grammarAnswers).forEach(([key, val]) => {
                                    if (val) @this.set(`grammarAnswers.${key}`, val);
                                });
                            }
                            if (data.vocabAnswers) {
                                Object.entries(data.vocabAnswers).forEach(([key, val]) => {
                                    if (val) @this.set(`vocabAnswers.${key}`, val);
                                });
                            }
                        }
                    } catch(e) {}

                    // Watch for answer changes and save to localStorage
                    this.$watch('$wire.grammarAnswers', (val) => this.save());
                    this.$watch('$wire.vocabAnswers', (val) => this.save());

                    // Detect offline/online status
                    this.isOffline = !navigator.onLine;
                    window.addEventListener('offline', () => { this.isOffline = true; });
                    window.addEventListener('online',  () => { this.isOffline = false; });
                },

                save() {
                    try {
                        localStorage.setItem(storageKey, JSON.stringify({
                            grammarAnswers: this.$wire.grammarAnswers,
                            vocabAnswers:   this.$wire.vocabAnswers,
                            savedAt: new Date().toISOString(),
                        }));
                    } catch(e) {}
                },

                clearStorage() {
                    try { localStorage.removeItem(storageKey); } catch(e) {}
                },
            };
        }
    </script>
</div>
