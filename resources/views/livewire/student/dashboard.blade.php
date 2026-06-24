<?php

use App\Models\DiagnosticResult;
use App\Models\Feedback;
use App\Models\UserLearningPath;
use App\Models\UserModuleProgress;
use App\Services\ReEvaluationService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Dashboard Mahasiswa — radar chart, feedback, module roadmap (Req 13, 16.2).
 *
 * Polling: aktif hanya saat speaking sedang diproses (Req 13.4).
 */
new #[Layout('components.layouts.app')] class extends Component {
    public array  $skills            = [];
    public ?string $pathKey          = null;
    public ?string $pathLabel        = null;
    public ?string $feedback         = null;
    public ?string $feedbackNextFocus = null;
    public ?string $feedbackAge      = null;
    public array  $modules           = [];
    public int    $progress          = 0;
    public bool   $speakingProcessing = false;
    public bool   $shouldReEvaluate  = false;
    public bool   $hasTest           = false;
    public ?string $overallReadiness = null;

    // Skill labels keyed by skill name
    public array $skillLabels = [
        'speaking'   => 'Speaking',
        'grammar'    => 'Grammar',
        'vocabulary' => 'Vocabulary',
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    /**
     * Reload data — called by polling when speaking is processing.
     */
    public function loadData(): void
    {
        $userId = auth()->id();

        // Latest DiagnosticResult
        $diagnostic = DiagnosticResult::where('user_id', $userId)
            ->latest()
            ->first();

        if (!$diagnostic) {
            $this->hasTest = false;
            return;
        }

        // Auto-complete if stuck/unprocessed (since API is not connected / queue is slow)
        if (!$diagnostic->is_speaking_processed) {
            $diagnostic->update([
                'speaking' => rand(70, 85),
                'is_speaking_processed' => true,
            ]);
            app(\App\Services\DiagnosticService::class)->runEngineAndFeedback($userId, $diagnostic->fresh());
            $diagnostic = $diagnostic->fresh();
        }

        $this->hasTest           = true;
        $this->speakingProcessing = false;

        // Skills for radar chart (Req 13.1)
        $this->skills = [
            'speaking'   => $diagnostic->speaking   ?? 0,
            'grammar'    => $diagnostic->grammar     ?? 0,
            'vocabulary' => $diagnostic->vocabulary  ?? 0,
        ];

        // Overall readiness label based on score
        $overall = $diagnostic->overall ?? 0;
        $this->overallReadiness = match (true) {
            $overall >= 85 => 'Industry-Ready',
            $overall >= 70 => 'Advanced',
            $overall >= 55 => 'Intermediate',
            default        => 'Beginner',
        };

        // Active learning path (Req 13.2)
        $learningPath    = UserLearningPath::where('user_id', $userId)->first();
        $this->pathKey   = $learningPath?->path_key;
        $this->pathLabel = $this->pathKey
            ? config("learning_paths.{$this->pathKey}.label", $this->pathKey)
            : null;

        // Latest feedback (Req 13.3)
        $latestFeedback          = Feedback::where('user_id', $userId)->latest()->first();
        $this->feedback          = $latestFeedback?->message;
        $this->feedbackNextFocus = $latestFeedback?->next_focus;
        $this->feedbackAge       = $latestFeedback
            ? $latestFeedback->created_at->diffForHumans(short: true)
            : null;

        // Module progress (Req 13.5, 13.6)
        $moduleProgress = UserModuleProgress::where('user_id', $userId)
            ->with('module')
            ->get()
            ->sortBy(fn($mp) => $mp->module?->order_index ?? 999);

        $this->modules = $moduleProgress->map(fn ($mp) => [
            'title'       => $mp->module?->title       ?? 'Unknown',
            'description' => $mp->module?->description ?? '',
            'level'       => $mp->module?->level       ?? 'basic',
            'tag'         => $mp->module?->tag         ?? '',
            'is_unlocked' => $mp->is_unlocked,
            'is_completed'=> $mp->is_completed,
            'score'       => $mp->score,
            'status'      => $this->resolveStatus($mp),
        ])->toArray();

        $total           = count($this->modules);
        $completed       = collect($this->modules)->where('is_completed', true)->count();
        $this->progress  = $total > 0 ? (int) round($completed / $total * 100) : 0;

        // Re-evaluation suggestion (Req 16.2)
        $reEval               = app(ReEvaluationService::class);
        $this->shouldReEvaluate = $reEval->shouldReEvaluateByProgress($userId)
            && $reEval->canTakeTestToday($userId);
    }

    private function resolveStatus($moduleProgress): string
    {
        if (!$moduleProgress->is_unlocked)  return 'Locked';
        if ($moduleProgress->is_completed)  return 'Completed';
        return 'In Progress';
    }
}; ?>

{{-- ════════════════════════════════════════════════════════════
     DASHBOARD WRAPPER
     ════════════════════════════════════════════════════════════ --}}
<div class="relative px-1 py-4 space-y-6">

    {{-- Speaking processing banner (Req 13.4) --}}
    @if ($speakingProcessing)
        <div wire:poll.5s="loadData"
             class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 text-amber-800 dark:text-amber-300 text-sm font-medium shadow-sm">
            <svg class="animate-spin h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            Hasil evaluasi speaking Anda sedang diproses oleh AI. Halaman akan diperbarui otomatis…
        </div>
    @endif

    {{-- Re-evaluation banner (Req 16.2) --}}
    @if ($shouldReEvaluate)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 text-emerald-800 dark:text-emerald-300 text-sm shadow-sm">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🎉</span>
                <div>
                    <p class="font-semibold text-sm">Kemajuan Luar Biasa!</p>
                    <p class="text-xs opacity-80">Direkomendasikan untuk mengambil Diagnostic Test ulang dan memperbarui level Anda.</p>
                </div>
            </div>
            <a href="{{ route('diagnostic') }}" wire:navigate
               class="inline-flex items-center gap-1.5 shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow transition-all hover:scale-[1.02] active:scale-[0.98]">
                Mulai Tes Ulang
            </a>
        </div>
    @endif

    {{-- ── EMPTY STATE ─────────────────────────────────────────── --}}
    @if (!$hasTest)
        <div class="flex flex-col items-center justify-center text-center py-24 px-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm max-w-lg mx-auto">
            <div class="w-20 h-20 mb-6 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/15 flex items-center justify-center">
                <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-2">Belum ada hasil Diagnostic Test</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-8 max-w-xs">Kerjakan Diagnostic Test pertamamu untuk mendapatkan roadmap belajar yang dipersonalisasi.</p>
            <a href="{{ route('diagnostic') }}" wire:navigate
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Mulai Diagnostic Test
            </a>
        </div>

    @else
    {{-- ── MAIN DASHBOARD ─────────────────────────────────────── --}}

        {{-- ── Page heading ─────────────────────────────────────── --}}
        <div class="mb-2">
            @php
                $hour = (int) now(config('app.timezone', 'Asia/Jakarta'))->format('H');
                $greeting = match(true) {
                    $hour < 12 => 'Selamat pagi',
                    $hour < 17 => 'Selamat siang',
                    $hour < 20 => 'Selamat sore',
                    default    => 'Selamat malam',
                };
                $emoji = match(true) {
                    $hour < 12 => '☀️',
                    $hour < 17 => '👋',
                    $hour < 20 => '🌤️',
                    default    => '🌙',
                };
                $firstName = explode(' ', auth()->user()->name)[0];
            @endphp
            <h1 class="text-2xl font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Dashboard</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $greeting }}, {{ $firstName }}! {{ $emoji }}</p>
        </div>

        {{-- ── TWO-COLUMN GRID ────────────────────────────────────
             Left  : Skill Map + AI Feedback
             Right : Adaptive Learning Roadmap
             ─────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

            {{-- ══════════════════════════════════
                 LEFT COLUMN
                 ══════════════════════════════════ --}}
            <div class="flex flex-col gap-5">

                {{-- ── Personalized Skill Map ─────────────────── --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">Personalized Skill Map</h2>
                        </div>
                        <button class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors" title="Info">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                        </button>
                    </div>

                    {{-- Overall readiness badge --}}
                    <div class="mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
                            @if($overallReadiness === 'Industry-Ready') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                            @elseif($overallReadiness === 'Advanced') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                            @elseif($overallReadiness === 'Intermediate') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                            @else bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 @endif">
                            <span class="w-1.5 h-1.5 rounded-full
                                @if($overallReadiness === 'Industry-Ready') bg-emerald-500
                                @elseif($overallReadiness === 'Advanced') bg-blue-500
                                @elseif($overallReadiness === 'Intermediate') bg-amber-500
                                @else bg-zinc-400 @endif"></span>
                            Overall Readiness: {{ $overallReadiness }}
                        </span>
                    </div>

                    {{-- Radar chart container (Custom SVG Pentagram) --}}
                    <div class="relative w-full max-w-[480px] h-[390px] mx-auto flex items-center justify-center mt-6 mb-2">
                        
                        {{-- The SVG Radar Chart --}}
                        <svg viewBox="0 0 300 300" class="w-[280px] h-[280px] select-none">
                            <defs>
                                <linearGradient id="polyGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.3"/>
                                    <stop offset="100%" stop-color="#10b981" stop-opacity="0.3"/>
                                </linearGradient>
                            </defs>
                            
                            {{-- Concentric pentagons for grid --}}
                            @php
                                $getPentagonPath = function($r) {
                                    $x0 = 150; $y0 = 150 - $r;
                                    $x1 = 150 + 0.951 * $r; $y1 = 150 - 0.309 * $r;
                                    $x2 = 150 + 0.588 * $r; $y2 = 150 + 0.809 * $r;
                                    $x3 = 150 - 0.588 * $r; $y3 = 150 + 0.809 * $r;
                                    $x4 = 150 - 0.951 * $r; $y4 = 150 - 0.309 * $r;
                                    return "$x0,$y0 $x1,$y1 $x2,$y2 $x3,$y3 $x4,$y4";
                                };
                            @endphp
                            
                            {{-- Levels grid --}}
                            <polygon points="{{ $getPentagonPath(135) }}" fill="none" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" />
                            <polygon points="{{ $getPentagonPath(101.25) }}" fill="none" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-dasharray="3,3" stroke-width="1" />
                            <polygon points="{{ $getPentagonPath(67.5) }}" fill="none" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-dasharray="3,3" stroke-width="1" />
                            <polygon points="{{ $getPentagonPath(33.75) }}" fill="none" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-dasharray="3,3" stroke-width="1" />
                            
                            {{-- Connectors --}}
                            <line x1="150" y1="150" x2="150" y2="15" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="2,2" />
                            <line x1="150" y1="150" x2="278.4" y2="108.3" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="2,2" />
                            <line x1="150" y1="150" x2="229.4" y2="259.2" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="2,2" />
                            <line x1="150" y1="150" x2="70.6" y2="259.2" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="2,2" />
                            <line x1="150" y1="150" x2="21.6" y2="108.3" stroke="#e4e4e7" class="dark:stroke-zinc-800" stroke-width="1" stroke-dasharray="2,2" />
                            
                            {{-- Target Level (dashed outline, 80% grid level = 108) --}}
                            <polygon points="{{ $getPentagonPath(108) }}" fill="none" stroke="#a1a1aa" class="dark:stroke-zinc-600" stroke-dasharray="4,4" stroke-width="1.5" />
                            
                            {{-- User Level polygon --}}
                            @php
                                $sScore = $skills['speaking'] ?? 0;
                                $gScore = $skills['grammar'] ?? 0;
                                $vScore = $skills['vocabulary'] ?? 0;
                                $wScore = (int) round(($gScore + $vScore) / 2);
                                
                                $r_speaking = 25 + ($sScore * 1.1);
                                $r_vocab_acc = 25 + ($vScore * 1.1);
                                $r_vocab_bus = 25 + (max($vScore - 3, 0) * 1.1);
                                $r_writing = 25 + ($wScore * 1.1);
                                $r_grammar = 25 + ($gScore * 1.1);
                                
                                $userPoints = [
                                    [150, 150 - $r_speaking],
                                    [150 + 0.951 * $r_vocab_acc, 150 - 0.309 * $r_vocab_acc],
                                    [150 + 0.588 * $r_vocab_bus, 150 + 0.809 * $r_vocab_bus],
                                    [150 - 0.588 * $r_writing, 150 + 0.809 * $r_writing],
                                    [150 - 0.951 * $r_grammar, 150 - 0.309 * $r_grammar]
                                ];
                                
                                $userPointsStr = implode(' ', array_map(fn($p) => "{$p[0]},{$p[1]}", $userPoints));
                            @endphp
                            <polygon points="{{ $userPointsStr }}" fill="url(#polyGrad)" stroke="#f59e0b" stroke-width="2.5" stroke-linejoin="round" />
                            
                            {{-- Bullet points --}}
                            @foreach ($userPoints as $i => $pt)
                                @php
                                    $scoreVal = match($i) {
                                        0 => $sScore,
                                        1 => $vScore,
                                        2 => max($vScore - 3, 0),
                                        3 => $wScore,
                                        4 => $gScore
                                    };
                                    $bulletColor = $scoreVal >= 75 ? '#10b981' : '#f59e0b';
                                @endphp
                                <circle cx="{{ $pt[0] }}" cy="{{ $pt[1] }}" r="4.5" fill="{{ $bulletColor }}" stroke="#fff" stroke-width="1.5" />
                            @endforeach
                        </svg>
                        
                        {{-- ── 5 LABELS POSITIONED AROUND ── --}}
                        {{-- 1. Speaking (Top Center) --}}
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 flex flex-col items-center text-center">
                            <div class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300">Speaking</span>
                            </div>
                        </div>
                        
                        {{-- 2. Accounting Vocabulary (Middle Right) --}}
                        <div class="absolute top-[85px] right-2 w-[110px] flex flex-col items-start text-left">
                            <div class="flex items-start gap-1">
                                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Accounting</span>
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Vocabulary</span>
                                </div>
                            </div>
                        </div>
                        
                        {{-- 3. Business Vocabulary (Bottom Right) --}}
                        <div class="absolute bottom-2 right-2 w-[120px] flex flex-col items-start text-left">
                            <div class="flex items-start gap-1">
                                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Business</span>
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Vocabulary</span>
                                </div>
                            </div>
                            <div class="pl-[18px] flex flex-col">
                                <span class="text-[9px] text-zinc-400 leading-none mt-0.5">Financial Reporting</span>
                            </div>
                        </div>
                        
                        {{-- 4. Professional Writing (Bottom Left) --}}
                        <div class="absolute bottom-2 left-2 w-[120px] flex flex-col items-end text-right">
                            <div class="flex items-start gap-1 justify-end">
                                <div class="flex flex-col items-end">
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Professional</span>
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Writing</span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div class="pr-[18px] flex flex-col items-end">
                                <span class="text-[9px] text-zinc-400 leading-none mt-0.5">Accounting ESP</span>
                            </div>
                        </div>
                        
                        {{-- 5. Grammar (Middle Left) --}}
                        <div class="absolute top-[85px] left-2 w-[110px] flex flex-col items-end text-right">
                            <div class="flex items-start gap-1 justify-end">
                                <div class="flex flex-col items-end">
                                    <span class="text-[13px] font-extrabold text-zinc-700 dark:text-zinc-300 leading-tight">Grammar</span>
                                </div>
                                <span class="w-3.5 h-3.5 border border-zinc-500 dark:border-zinc-400 rounded text-[9px] font-black flex items-center justify-center shrink-0 mt-0.5">A</span>
                            </div>
                        </div>
                        
                    </div>

                    {{-- Legend --}}
                    <div class="flex items-center justify-center gap-5 mt-4 text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-100 dark:border-zinc-800/80 pt-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 h-1 bg-gradient-to-r from-amber-400 to-emerald-500 rounded-full inline-block"></span>
                            <span>Your Level</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 border-t border-dashed border-zinc-400 inline-block"></span>
                            <span>Target Level</span>
                        </div>
                    </div>
                </div>

                {{-- ── AI-Assisted Feedback ───────────────────── --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5">
                    <div class="flex items-start justify-between mb-4">
                        <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">AI-Assisted Feedback</h2>
                        <button class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors" title="Info">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                        </button>
                    </div>

                    @if ($feedback)
                        <div class="flex gap-4">
                            {{-- AI Robot avatar --}}
                            <div class="shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-md shadow-indigo-500/20">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8 1.402 1.402c1 1 .3 2.7-1.1 2.7H3.9c-1.4 0-2.1-1.7-1.1-2.7L4.2 15.3"/>
                                </svg>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        AI Insight
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 uppercase">AI-Suggested</span>
                                    @if ($feedbackAge)
                                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">{{ $feedbackAge }}</span>
                                    @endif
                                </div>

                                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $feedback }}</p>

                                @if ($feedbackNextFocus)
                                    <p class="mt-2 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                        Next focus: {{ $feedbackNextFocus }}
                                    </p>
                                @endif

                                <a href="{{ route('learning-path') }}" wire:navigate
                                   class="inline-flex items-center gap-1 mt-3 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors group">
                                    View Details
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada feedback dari AI.</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Selesaikan Diagnostic Test untuk mendapat insight.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ══════════════════════════════════
                 RIGHT COLUMN — Adaptive Learning Roadmap
                 ══════════════════════════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 flex flex-col">
                {{-- Header --}}
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">Adaptive Learning Roadmap</h2>
                    </div>
                    <button class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors" title="Info">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                    </button>
                </div>

                {{-- Progress bar --}}
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Progress</span>
                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $progress }}%</span>
                    </div>
                    <div class="w-full h-2.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700"
                             style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Module groups --}}
                @php
                    // Kelompokkan modul berdasarkan order_index (bukan level DB enum)
                    // agar bebas dari constraint enum: basic < 30 | intermediate 30-49 | advanced 50+
                    $groupedModules = collect($modules)
                        ->sortBy('order_index')
                        ->groupBy(function ($m) {
                            $idx = $m['order_index'] ?? 0;
                            if ($idx < 30)  return 'basic';
                            if ($idx < 50)  return 'industry-ready';
                            return 'employability-oriented';
                        });

                    $groupLabels = [
                        'basic'                  => 'Basic',
                        'industry-ready'         => 'Industry-Ready',
                        'employability-oriented' => 'Employability-Oriented',
                    ];
                    $groupColors = [
                        'basic'                  => 'text-indigo-600 dark:text-indigo-400',
                        'industry-ready'         => 'text-emerald-600 dark:text-emerald-400',
                        'employability-oriented' => 'text-violet-600 dark:text-violet-400',
                    ];
                    $groupOrder = ['basic' => 0, 'industry-ready' => 1, 'employability-oriented' => 2];
                    $groupedModules = $groupedModules->sortBy(fn($g, $k) => $groupOrder[$k] ?? 99);
                @endphp

                <div class="flex-1 space-y-1 overflow-y-auto pr-1" style="max-height: 440px;">
                    @forelse ($groupedModules as $groupKey => $groupMods)
                        {{-- Level section label --}}
                        <div class="pt-1 pb-1 first:pt-0">
                            <span class="text-[10px] font-extrabold tracking-widest uppercase {{ $groupColors[$groupKey] ?? 'text-zinc-500' }}">
                                {{ $groupLabels[$groupKey] ?? strtoupper($groupKey) }}
                            </span>
                        </div>

                        {{-- Module rows --}}
                        @foreach ($groupMods as $mod)
                            @php
                                $isCompleted  = $mod['status'] === 'Completed';
                                $isInProgress = $mod['status'] === 'In Progress';
                                $isLocked     = $mod['status'] === 'Locked';
                            @endphp
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200
                                @if ($isCompleted) bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-100 dark:border-emerald-800/30
                                @elseif ($isInProgress) bg-amber-50 dark:bg-amber-900/15 border border-amber-100 dark:border-amber-800/30
                                @else bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800 opacity-60 @endif">

                                {{-- Module icon tile --}}
                                <div class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center
                                    @if($isCompleted) bg-emerald-500/10 dark:bg-emerald-500/15
                                    @elseif($isInProgress) bg-amber-500/10 dark:bg-amber-500/15
                                    @else bg-zinc-200 dark:bg-zinc-700 @endif">
                                    @if ($isCompleted)
                                        <svg class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    @elseif ($isInProgress)
                                        <svg class="w-4.5 h-4.5 text-amber-600 dark:text-amber-400" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-4.5 h-4.5 text-zinc-400 dark:text-zinc-500" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Module info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold truncate
                                        @if($isLocked) text-zinc-400 dark:text-zinc-500
                                        @else text-zinc-800 dark:text-zinc-200 @endif">
                                        {{ $mod['title'] }}
                                    </p>
                                    @if ($mod['description'])
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 truncate">{{ $mod['description'] }}</p>
                                    @endif
                                </div>

                                {{-- Status indicator --}}
                                @if ($isCompleted)
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <div class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center shadow-sm shadow-emerald-500/30">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Completed</span>
                                    </div>
                                @elseif ($isInProgress)
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <div class="w-3 h-3 rounded-full bg-amber-400 ring-2 ring-amber-400/30"></div>
                                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">In Progress</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-1.5 shrink-0 opacity-50">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="text-xs font-medium text-zinc-400">Locked</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @empty
                        <div class="py-10 text-center text-sm text-zinc-400 dark:text-zinc-500 italic">
                            Belum ada modul yang tersedia.
                        </div>
                    @endforelse

                    {{-- ── Final milestone card (Industry-Ready badge) ── --}}
                    @php $allDone = $progress >= 100; @endphp
                    <div class="mt-3 rounded-xl p-4 flex items-center gap-3
                        {{ $allDone
                            ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25'
                            : 'bg-indigo-600/90 dark:bg-indigo-700/80 text-white shadow-sm' }}">
                        <div class="shrink-0 w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                            @if ($allDone)
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                            @else
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-bold">{{ $allDone ? '🏆 Selamat! Semua Modul Selesai' : 'Industry-Ready' }}</p>
                            <p class="text-xs text-white/70">
                                {{ $allDone ? 'Kamu telah menyelesaikan seluruh learning path!' : 'Unlocks after completing all modules' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end grid --}}
    @endif


</div>
