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
    public ?string $nextGoal         = null;
    public ?string $speakingTranscript = null;
    public int    $speakingWordCount  = 0;
    public ?string $speakingReference = null;

    // New analytics properties
    public array $strongestSkill = ['name' => '-', 'score' => 0];
    public array $weakestSkill   = ['name' => '-', 'score' => 0];
    public array $nextTarget     = ['name' => '-', 'score' => 0];
    public int $learningGrowth   = 15; // Mock data as discussed
    public int $totalModules     = 0;
    public int $completedModules = 0;
    public int $remainingModules = 0;
    public array $performanceChartData = [];

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

        if (!$diagnostic->is_speaking_processed) {
            $this->hasTest            = true;
            $this->speakingProcessing = true;
            $this->skills = [
                'speaking'   => 0,
                'grammar'    => $diagnostic->grammar ?? 0,
                'vocabulary' => $diagnostic->vocabulary ?? 0,
            ];
            return;
        }

        $this->hasTest           = true;
        $this->speakingProcessing = false;

        // Speaking transcript for pronunciation feedback
        $this->speakingTranscript = $diagnostic->speaking_transcript ?? null;
        $this->speakingReference  = $diagnostic->speaking_reference  ?? null;
        $this->speakingWordCount  = $this->speakingTranscript
            ? str_word_count(trim($this->speakingTranscript))
            : 0;

        // Skills for radar chart (Req 13.1)
        $this->skills = [
            'speaking'   => $diagnostic->speaking   ?? 0,
            'grammar'    => $diagnostic->grammar     ?? 0,
            'vocabulary' => $diagnostic->vocabulary  ?? 0,
        ];

        // Overall readiness label based on score
        $overall = $diagnostic->overall ?? 0;
        $this->overallReadiness = match (true) {
            $overall >= 70 => 'Advanced',
            $overall >= 55 => 'Intermediate',
            default        => 'Basic',
        };

        $this->nextGoal = match ($this->overallReadiness) {
            'Basic'        => 'Intermediate Level Mastery',
            'Intermediate' => 'Advanced Level Proficiency',
            default        => 'Business Communication Ready',
        };

        // Analytics Calculations
        $sScore = $this->skills['speaking'];
        $gScore = $this->skills['grammar'];
        $vScore = $this->skills['vocabulary'];
        
        $skillScores = [
            'Speaking' => $sScore,
            'Grammar' => $gScore,
            'Vocabulary' => $vScore,
        ];

        // Strongest Skill
        $maxScore = max($skillScores);
        $maxSkill = array_search($maxScore, $skillScores);
        $this->strongestSkill = ['name' => $maxSkill, 'score' => $maxScore];

        // Weakest Skill
        $minScore = min($skillScores);
        $minSkill = array_search($minScore, $skillScores);
        $this->weakestSkill = ['name' => $minSkill, 'score' => $minScore];

        // Next Target (Closest to 80, but below 80. If all >= 80, just pick the lowest)
        $closestToTarget = null;
        $closestScore = -1;
        foreach ($skillScores as $name => $score) {
            if ($score < 80 && $score > $closestScore) {
                $closestScore = $score;
                $closestToTarget = $name;
            }
        }
        if (!$closestToTarget) {
            $closestToTarget = $minSkill;
            $closestScore = $minScore;
        }
        $this->nextTarget = ['name' => $closestToTarget, 'score' => $closestScore];

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

        // Synchronize module unlock state based on current diagnostic scores
        app(\App\Services\AdaptiveEngineService::class)->run($userId, [
            'speaking'   => $this->skills['speaking'],
            'grammar'    => $this->skills['grammar'],
            'vocabulary' => $this->skills['vocabulary'],
            'overall'    => $diagnostic->overall ?? 0,
        ]);

        // Module progress (Req 13.5, 13.6)
        $moduleProgress = UserModuleProgress::where('user_id', $userId)
            ->with('module.lessons')
            ->get()
            ->sortBy(fn($mp) => $mp->module?->order_index ?? 999);

        $completedLessonIds = \App\Models\UserLessonProgress::where('user_id', $userId)
            ->pluck('lesson_id')
            ->toArray();

        $this->modules = $moduleProgress->map(function ($mp) use ($completedLessonIds) {
            $moduleLessons = $mp->module?->lessons ?? collect();
            $activeLessons = $moduleLessons->where('is_active', true);

            $totalItems = $activeLessons->count() > 0 ? $activeLessons->count() + 1 : 0;
            $completedReadingLessons = $activeLessons->whereIn('id', $completedLessonIds)->count();
            $completedItems = $completedReadingLessons + ($mp->is_completed ? 1 : 0);

            $progressPercent = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;

            return [
                'title'            => $mp->module?->title       ?? 'Unknown',
                'description'      => $mp->module?->description ?? '',
                'level'            => $mp->module?->level       ?? 'basic',
                'tag'              => $mp->module?->tag         ?? '',
                'is_unlocked'      => $mp->is_unlocked,
                'is_completed'     => $mp->is_completed,
                'score'            => $mp->score,
                'progress_percent' => $progressPercent,
                'status'           => $this->resolveStatus($mp),
            ];
        })->toArray();

        $unlockedModules        = collect($this->modules)->where('is_unlocked', true);
        $this->totalModules     = $unlockedModules->count();
        $this->completedModules = $unlockedModules->where('is_completed', true)->count();
        $this->remainingModules = max(0, $this->totalModules - $this->completedModules);
        $this->progress         = $this->totalModules > 0 ? (int) round($this->completedModules / $this->totalModules * 100) : 0;

        // Re-evaluation suggestion (Req 16.2)
        $reEval               = app(ReEvaluationService::class);
        $this->shouldReEvaluate = $reEval->shouldReEvaluateByProgress($userId)
            && $reEval->canTakeTestToday($userId);

        // History for performance chart
        $histories = DiagnosticResult::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->take(15)
            ->get();
            
        $this->performanceChartData = [
            'labels' => $histories->map(fn($h) => $h->created_at->format('d M'))->toArray(),
            'overall' => $histories->pluck('overall')->map(fn($v) => $v ?? 0)->toArray(),
            'speaking' => $histories->pluck('speaking')->map(fn($v) => $v ?? 0)->toArray(),
            'grammar' => $histories->pluck('grammar')->map(fn($v) => $v ?? 0)->toArray(),
            'vocabulary' => $histories->pluck('vocabulary')->map(fn($v) => $v ?? 0)->toArray(),
        ];
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
<div class="relative w-full min-w-0 px-4 py-4 space-y-6">

    {{-- Speaking processing banner (Req 13.4) --}}
    {{-- Speaking processing banner (Req 13.4) --}}
    @if ($speakingProcessing)
        <div wire:poll.5s="loadData"
             class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/40 text-amber-800 dark:text-amber-300 text-sm font-medium shadow-sm">
            <svg class="animate-spin h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
            </svg>
            Your speaking evaluation is being processed by AI. The page will update automatically…
        </div>
    @endif

    {{-- Re-evaluation banner (Req 16.2) --}}
    @if ($shouldReEvaluate)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/40 text-emerald-800 dark:text-emerald-300 text-sm shadow-sm">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🎉</span>
                <div>
                    <p class="font-semibold text-sm">Outstanding Progress!</p>
                    <p class="text-xs opacity-80">Recommended to retake the Diagnostic Test to update your skill level.</p>
                </div>
            </div>
            <a href="{{ route('diagnostic') }}" wire:navigate
               class="inline-flex items-center gap-1.5 shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow transition-all hover:scale-[1.02] active:scale-[0.98]">
                Retake Test
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
            <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-2">No Diagnostic Test Results Yet</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-8 max-w-xs">Take your first Diagnostic Test to receive a personalized learning roadmap.</p>
            <a href="{{ route('diagnostic') }}" wire:navigate
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-indigo-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Start Diagnostic Test
            </a>
        </div>

    @else
    {{-- ── MAIN DASHBOARD ─────────────────────────────────────── --}}

        {{-- ── Page heading ─────────────────────────────────────── --}}
        <div class="mb-2">
            @php
                $hour = (int) now(config('app.timezone', 'Asia/Jakarta'))->format('H');
                $greeting = match(true) {
                    $hour < 12 => 'Good morning',
                    $hour < 17 => 'Good afternoon',
                    $hour < 20 => 'Good afternoon',
                    default    => 'Good evening',
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
             Right : Learning Path + Industry Ready
             ─────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

            {{-- ══════════════════════════════════
                 LEFT COLUMN
                 ══════════════════════════════════ --}}
            <div class="flex flex-col gap-5">

                {{-- ── Skill Performance Overview ─────────────────── --}}
                <div x-data="{ showCard: false }" x-init="setTimeout(() => showCard = true, 100)" 
                     class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 transition-all duration-700 transform"
                     :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                    
                    {{-- Header with Glassmorphism Tooltip --}}
                    <div class="flex items-start justify-between mb-2" x-data="{ showInfo: false }">
                        <div class="relative">
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">Skill Performance Overview</h2>
                        </div>
                        <div class="relative">
                            <button @mouseenter="showInfo = true" @mouseleave="showInfo = false" @click="showInfo = !showInfo" class="p-1 text-zinc-400 hover:text-orange-500 transition-colors cursor-pointer" title="Info">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                            </button>
                            
                            {{-- Info Popup --}}
                            <div x-show="showInfo" x-transition.opacity.duration.300ms style="display: none;"
                                 class="absolute right-0 top-7 z-50 w-64 bg-zinc-900/90 backdrop-blur-md border-t border-orange-500/80 rounded-xl p-4 shadow-[0_4px_20px_rgba(249,115,22,0.15)] pointer-events-none text-left">
                                <div class="flex items-center gap-2 mb-2 border-b border-zinc-700/60 pb-2">
                                    <span class="text-lg">📊</span>
                                    <h3 class="text-sm font-bold text-white">Skill Performance Overview</h3>
                                </div>
                                <p class="text-[11px] text-zinc-300 mb-2 leading-relaxed">
                                    Menampilkan perkembangan kemampuan berdasarkan:
                                </p>
                                <ul class="text-[11px] text-zinc-400 mb-3 space-y-0.5 list-disc pl-4 font-medium">
                                    <li>Speaking</li>
                                    <li>Grammar</li>
                                    <li>Vocabulary</li>
                                </ul>
                                <div class="space-y-1.5 text-[11px] pt-1 border-t border-zinc-700/60">
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-0.5 bg-orange-400"></span><span class="text-zinc-300">Orange line = Current Level</span></div>
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-0.5 border-t border-dashed border-zinc-400"></span><span class="text-zinc-300">Dashed line = Target Level</span></div>
                                    <p class="text-[10px] text-zinc-400 italic mt-1">Digunakan untuk melihat kemampuan yang perlu ditingkatkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Radar Chart Container --}}
                    @php
                        // Score calculation for radar chart
                        $sScore = $skills['speaking'] ?? 0;
                        $gScore = $skills['grammar'] ?? 0;
                        $vScore = $skills['vocabulary'] ?? 0;

                        // Points definition (clockwise from top)
                        $points = [
                            ['label' => 'Speaking', 'score' => $sScore, 'target' => 80],
                            ['label' => 'Grammar', 'score' => $gScore, 'target' => 85],
                            ['label' => 'Vocabulary', 'score' => $vScore, 'target' => 80],
                        ];

                        $numPoints = count($points);
                        
                        $getPentagonPath = function($radius) use ($numPoints) {
                            $path = '';
                            for ($i = 0; $i < $numPoints; $i++) {
                                $angle = -M_PI / 2 + ($i * 2 * M_PI / $numPoints);
                                $x = 150 + $radius * cos($angle);
                                $y = 150 + $radius * sin($angle);
                                $path .= ($i === 0 ? 'M' : 'L') . " {$x},{$y} ";
                            }
                            return $path . 'Z';
                        };

                        $getUserLevelPath = function() use ($points, $numPoints) {
                            $path = '';
                            foreach ($points as $i => $pt) {
                                $radius = 120 * ($pt['score'] / 100);
                                $angle = -M_PI / 2 + ($i * 2 * M_PI / $numPoints);
                                $x = 150 + $radius * cos($angle);
                                $y = 150 + $radius * sin($angle);
                                $path .= ($i === 0 ? 'M' : 'L') . " {$x},{$y} ";
                            }
                            return $path . 'Z';
                        };

                        $getTargetLevelPath = function() use ($points, $numPoints) {
                            $path = '';
                            foreach ($points as $i => $pt) {
                                $radius = 120 * ($pt['target'] / 100);
                                $angle = -M_PI / 2 + ($i * 2 * M_PI / $numPoints);
                                $x = 150 + $radius * cos($angle);
                                $y = 150 + $radius * sin($angle);
                                $path .= ($i === 0 ? 'M' : 'L') . " {$x},{$y} ";
                            }
                            return $path . 'Z';
                        };
                    @endphp

                    <div x-data="{
                            initChart() {
                                if (typeof Chart === 'undefined') {
                                    setTimeout(() => this.initChart(), 100);
                                    return;
                                }
                                
                                fetch('/api/dashboard', { 
                                    headers: { 
                                        'Accept': 'application/json' 
                                    } 
                                })
                                .then(r => r.json())
                                .then(res => {
                                    if(!res.data || !res.data.skills) return;
                                    const data = res.data.skills;
                                    
                                    if (this.chartInstance) {
                                        this.chartInstance.destroy();
                                    }
                                    
                                    this.chartInstance = new Chart(this.$refs.canvas, {
                                        type: 'radar',
                                        data: {
                                            labels: ['Speaking', 'Grammar', 'Vocabulary'],
                                            datasets: [{
                                                label: 'Current Skill Level',
                                                data: [data.speaking, data.grammar, data.vocabulary],
                                                backgroundColor: 'rgba(249, 115, 22, 0.2)',
                                                borderColor: 'rgba(249, 115, 22, 1)',
                                                pointBackgroundColor: 'rgba(249, 115, 22, 1)',
                                                pointBorderColor: '#fff',
                                                pointHoverBackgroundColor: '#fff',
                                                pointHoverBorderColor: 'rgba(249, 115, 22, 1)'
                                            },
                                            {
                                                label: 'Target Level',
                                                data: [80, 85, 80],
                                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                                borderColor: 'rgba(16, 185, 129, 0.5)',
                                                borderDash: [5, 5],
                                                pointRadius: 0
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            scales: {
                                                r: {
                                                    angleLines: { color: 'rgba(161, 161, 170, 0.3)' },
                                                    grid: { color: 'rgba(161, 161, 170, 0.3)' },
                                                    pointLabels: {
                                                        color: '#a1a1aa',
                                                        font: { size: 11, weight: 'bold' }
                                                    },
                                                    ticks: {
                                                        display: false,
                                                        min: 0,
                                                        max: 100,
                                                        stepSize: 20
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: { display: false }
                                            }
                                        }
                                    });
                                })
                                .catch(err => console.error('Error fetching dashboard API:', err));
                            }
                        }"
                        x-init="initChart()"
                        class="relative w-full max-w-[380px] h-[300px] mx-auto flex items-center justify-center mt-2 mb-2"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>

                    {{-- Legend --}}
                    <div class="flex items-center justify-center gap-4 mt-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 h-1 bg-orange-400 rounded-full inline-block"></span>
                            <span>Current</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 border-t border-dashed border-zinc-400 inline-block"></span>
                            <span>Target</span>
                        </div>
                    </div>
                </div>

                {{-- ── AI-Assisted Feedback ───────────────────── --}}
                <div x-data="{ showCard: false }" x-init="setTimeout(() => showCard = true, 300)" 
                     class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 transition-all duration-700 transform hover:shadow-md"
                     :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                    
                    <div class="flex items-start justify-between mb-4" x-data="{ showInfo: false }">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-indigo-500/10 flex items-center justify-center animate-[pulse_3s_ease-in-out_infinite]">
                                <span class="text-lg">🤖</span>
                            </div>
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">AI Insight</h2>
                        </div>
                        <div class="relative">
                            <button @mouseenter="showInfo = true" @mouseleave="showInfo = false" @click="showInfo = !showInfo" class="p-1 text-zinc-400 hover:text-indigo-400 transition-colors cursor-pointer" title="Info">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                            </button>
                            {{-- Info Popup --}}
                            <div x-show="showInfo" x-transition.opacity.duration.300ms style="display: none;"
                                 class="absolute right-0 top-6 z-50 w-64 bg-zinc-900/90 backdrop-blur-md border border-indigo-500/50 rounded-xl p-3.5 shadow-xl shadow-indigo-500/10 pointer-events-none text-left">
                                <p class="text-[11px] text-zinc-300 leading-relaxed">
                                    AI akan menganalisis hasil tes Anda dan memberikan umpan balik (feedback) khusus serta menyarankan fokus belajar selanjutnya secara personal.
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($feedback)
                        <div class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 uppercase border border-indigo-500/30">Priority Insight</span>
                                @if ($feedbackAge)
                                    <span class="text-[10px] text-zinc-500">{{ $feedbackAge }}</span>
                                @endif
                            </div>
                            <p class="text-[13px] text-zinc-600 dark:text-zinc-300 leading-relaxed">"{{ $feedback }}"</p>
                        </div>

                        <div class="bg-zinc-100 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700/50 rounded-xl p-4 mb-4">
                            <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-3">Fokus berikutnya:</p>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs">🎯</span>
                                            <span class="text-[11px] font-bold text-zinc-800 dark:text-white">Speaking Fluency</span>
                                        </div>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $skills['speaking'] ?? 10 }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-black/10 dark:bg-black/40 rounded-full overflow-hidden">
                                        <div class="h-full bg-orange-400 rounded-full" style="width: {{ $skills['speaking'] ?? 10 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs">📚</span>
                                            <span class="text-[11px] font-bold text-zinc-800 dark:text-white">Grammar Structure</span>
                                        </div>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $skills['grammar'] ?? 20 }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-black/10 dark:bg-black/40 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-400 rounded-full" style="width: {{ $skills['grammar'] ?? 20 }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs">📖</span>
                                            <span class="text-[11px] font-bold text-zinc-800 dark:text-white">Vocabulary Range</span>
                                        </div>
                                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $skills['vocabulary'] ?? 20 }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-black/10 dark:bg-black/40 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-400 rounded-full" style="width: {{ $skills['vocabulary'] ?? 20 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('learning-path') }}" wire:navigate
                           class="w-full flex items-center justify-center gap-2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-[13px] font-bold rounded-lg shadow-sm transition-all hover:-translate-y-0.5">
                            View Learning Plan
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="w-10 h-10 rounded-xl bg-zinc-800 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <p class="text-xs text-zinc-500">Belum ada feedback dari AI. Selesaikan Diagnostic Test.</p>
                        </div>
                    @endif
                </div>



            </div>


            {{-- ══════════════════════════════════
                 RIGHT COLUMN
                 ══════════════════════════════════ --}}
            <div class="flex flex-col gap-5">

                {{-- ── Learning Path ─────────────────────────────── --}}
                <div x-data="{ showCard: false }" x-init="setTimeout(() => showCard = true, 200)" 
                     class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 flex flex-col transition-all duration-700 transform hover:shadow-md"
                     :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                    
                    <div class="flex items-start justify-between mb-4" x-data="{ showInfo: false }">
                        <div>
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">Learning Path</h2>
                            <p class="text-[11px] text-zinc-500 mt-0.5">Modul pembelajaran yang disesuaikan dengan level Anda</p>
                        </div>
                        <div class="relative">
                            <button @mouseenter="showInfo = true" @mouseleave="showInfo = false" @click="showInfo = !showInfo" class="p-1 text-zinc-400 hover:text-emerald-400 transition-colors cursor-pointer" title="Info">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01"/></svg>
                            </button>
                            {{-- Info Popup --}}
                            <div x-show="showInfo" x-transition.opacity.duration.300ms style="display: none;"
                                 class="absolute right-0 top-6 z-50 w-64 bg-zinc-900/90 backdrop-blur-md border border-emerald-500/50 rounded-xl p-3.5 shadow-xl shadow-emerald-500/10 pointer-events-none text-left">
                                <p class="text-[11px] text-zinc-300 leading-relaxed">
                                    Roadmap ini menampilkan urutan modul yang direkomendasikan. Beberapa modul tingkat lanjut akan tetap terkunci hingga Anda menyelesaikan prasyaratnya.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 space-y-2 overflow-y-auto pr-1" style="max-height: 380px;">
                        @forelse ($modules as $mod)
                            @php
                                $isCompleted  = $mod['status'] === 'Completed';
                                $isInProgress = $mod['status'] === 'In Progress';
                                $isLocked     = $mod['status'] === 'Locked';
                            @endphp
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 hover:-translate-y-0.5
                                @if ($isCompleted) bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-100 dark:border-emerald-800/30 hover:border-emerald-500/50
                                @elseif ($isInProgress) bg-indigo-50 dark:bg-indigo-900/15 border border-indigo-100 dark:border-indigo-800/30 hover:border-indigo-500/50
                                @else bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-100 dark:border-zinc-800 opacity-70 hover:opacity-100 @endif">

                                {{-- Icon --}}
                                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                    @if($isCompleted) bg-emerald-500/15 text-emerald-400
                                    @elseif($isInProgress) bg-indigo-500/15 text-indigo-400
                                    @else bg-zinc-800 text-zinc-500 @endif">
                                    @if ($isCompleted)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @elseif ($isInProgress)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    @endif
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold truncate
                                            @if($isLocked) text-zinc-400 dark:text-zinc-500
                                            @else text-zinc-800 dark:text-zinc-200 @endif">
                                            {{ $mod['title'] }}
                                        </p>
                                        @if ($isInProgress)
                                            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">{{ $mod['progress_percent'] ?? 0 }}%</span>
                                        @endif
                                    </div>
                                    @if ($isInProgress)
                                        <div class="mt-1.5 w-full bg-black/10 dark:bg-black/40 rounded-full h-1">
                                            <div class="bg-indigo-500 dark:bg-indigo-400 h-1 rounded-full transition-all duration-700" style="width: {{ $mod['progress_percent'] ?? 0 }}%"></div>
                                        </div>
                                    @elseif ($isCompleted)
                                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">Completed</p>
                                    @else
                                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">Locked</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-zinc-500 italic">
                                Belum ada modul yang tersedia.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ── Industry Readiness ───────────────────────── --}}
                <div x-data="{ showCard: false }" x-init="setTimeout(() => showCard = true, 400)" 
                     class="bg-gradient-to-br from-white to-indigo-50/50 dark:from-zinc-900 dark:to-indigo-900/30 border border-indigo-200 dark:border-indigo-500/20 rounded-2xl shadow-sm p-5 relative overflow-hidden transition-all duration-700 transform hover:shadow-md"
                     :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                    <div class="absolute top-0 right-0 p-4 opacity-[0.04]">
                        <svg class="w-32 h-32 text-indigo-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <h2 class="text-base font-bold text-zinc-800 dark:text-white tracking-wide">Industry Readiness</h2>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between items-end mb-1.5">
                                <span class="text-[11px] text-indigo-600 dark:text-indigo-200 uppercase tracking-widest font-bold">Overall Progress</span>
                                <span class="text-lg font-black text-zinc-800 dark:text-white leading-none">{{ $progress }}%</span>
                            </div>
                            <div class="w-full h-2.5 bg-zinc-200 dark:bg-black/40 rounded-full overflow-hidden mb-3">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-emerald-400 rounded-full transition-all duration-1000" style="width: {{ $progress }}%"></div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-y-3 text-[11px] bg-zinc-50 dark:bg-black/20 p-3 rounded-xl border border-zinc-200 dark:border-white/5">
                                <div>
                                    <span class="block text-zinc-500 dark:text-indigo-300/70 mb-0.5">Current:</span>
                                    <span class="font-bold text-zinc-800 dark:text-white">{{ $overallReadiness ?? 'Basic' }}</span>
                                </div>
                                <div>
                                    <span class="block text-zinc-500 dark:text-indigo-300/70 mb-0.5">Completed:</span>
                                    <span class="font-bold text-zinc-800 dark:text-white">{{ $completedModules }} / {{ $totalModules }} Modules</span>
                                </div>
                                <div class="col-span-2 pt-2 border-t border-zinc-200 dark:border-indigo-500/20">
                                    <span class="block text-zinc-500 dark:text-indigo-300/70 mb-0.5">Next Goal:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $nextGoal ?? 'Business Communication Ready' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Mini Statistics --}}
                        <div class="grid grid-cols-3 gap-2">
                            <div class="bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 rounded-lg p-2 hover:bg-zinc-200 dark:hover:bg-white/10 transition-colors">
                                <div class="flex items-center justify-center mb-0.5">
                                    <span class="text-[10px]">🔥 Strongest</span>
                                </div>
                                <p class="text-[10px] text-center font-semibold text-zinc-700 dark:text-white truncate px-1">{{ $strongestSkill['name'] }}</p>
                                <p class="text-xs text-center font-black text-orange-500 dark:text-orange-400 mt-0.5">{{ $strongestSkill['score'] }}%</p>
                            </div>
                            <div class="bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 rounded-lg p-2 hover:bg-zinc-200 dark:hover:bg-white/10 transition-colors">
                                <div class="flex items-center justify-center mb-0.5">
                                    <span class="text-[10px]">⚡ Improve</span>
                                </div>
                                <p class="text-[10px] text-center font-semibold text-zinc-700 dark:text-white truncate px-1">{{ $weakestSkill['name'] }}</p>
                                <p class="text-xs text-center font-black text-red-500 dark:text-red-400 mt-0.5">{{ $weakestSkill['score'] }}%</p>
                            </div>
                            <div class="bg-zinc-100 dark:bg-white/5 border border-zinc-200 dark:border-white/10 rounded-lg p-2 hover:bg-zinc-200 dark:hover:bg-white/10 transition-colors">
                                <div class="flex items-center justify-center mb-0.5">
                                    <span class="text-[10px]">🎯 Target</span>
                                </div>
                                <p class="text-[10px] text-center font-semibold text-zinc-700 dark:text-white truncate px-1">Ready</p>
                                <p class="text-xs text-center font-black text-emerald-600 dark:text-emerald-400 mt-0.5">80%</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>{{-- end grid --}}
                {{-- ── Speaking Analysis (Transcript + Word Diff) ─── --}}
                @if ($speakingTranscript !== null && strlen(trim($speakingTranscript)) > 0)
                    @php
                        $spScore = $skills['speaking'] ?? 0;
                        $spTip = match(true) {
                            $spScore >= 80 => [
                                'level'     => 'Excellent',
                                'emoji'     => '🏆',
                                'badge'     => 'text-emerald-600 dark:text-emerald-400',
                                'score'     => 'text-emerald-500',
                                'container' => 'bg-emerald-500/10 dark:bg-emerald-500/15 border-emerald-500/20 dark:border-emerald-500/30',
                                'title'     => 'text-emerald-700 dark:text-emerald-400',
                                'text'      => 'text-emerald-900 dark:text-emerald-200',
                                'tip'       => 'Pelafalan Anda sangat baik! AI mendeteksi kefasihan tinggi. Pertahankan dengan latihan percakapan profesional dan diskusi akademik.'
                            ],
                            $spScore >= 60 => [
                                'level'     => 'Good',
                                'emoji'     => '👍',
                                'badge'     => 'text-indigo-600 dark:text-indigo-400',
                                'score'     => 'text-indigo-500',
                                'container' => 'bg-indigo-500/10 dark:bg-indigo-500/15 border-indigo-500/20 dark:border-indigo-500/30',
                                'title'     => 'text-indigo-700 dark:text-indigo-400',
                                'text'      => 'text-indigo-900 dark:text-indigo-200',
                                'tip'       => 'Pelafalan Anda sudah cukup baik. Fokus pada intonasi dan ritme kalimat. Coba baca teks bahasa Inggris dengan lantang setiap hari.'
                            ],
                            $spScore >= 40 => [
                                'level'     => 'Fair',
                                'emoji'     => '📈',
                                'badge'     => 'text-amber-600 dark:text-amber-400',
                                'score'     => 'text-amber-500',
                                'container' => 'bg-amber-500/10 dark:bg-amber-500/15 border-amber-500/20 dark:border-amber-500/30',
                                'title'     => 'text-amber-700 dark:text-amber-400',
                                'text'      => 'text-amber-900 dark:text-amber-200',
                                'tip'       => 'Ada beberapa bagian yang perlu ditingkatkan. Latih pronunciation dengan mengikuti audio native speaker dan rekam suara Anda sendiri.'
                            ],
                            default => [
                                'level'     => 'Needs Practice',
                                'emoji'     => '💪',
                                'badge'     => 'text-red-600 dark:text-red-400',
                                'score'     => 'text-red-500',
                                'container' => 'bg-red-500/10 dark:bg-red-500/15 border-red-500/20 dark:border-red-500/30',
                                'title'     => 'text-red-700 dark:text-red-400',
                                'text'      => 'text-red-900 dark:text-red-200',
                                'tip'       => 'Mulai dari dasar: latih pelafalan kata per kata dengan kamus audio online, lalu tingkatkan ke kalimat pendek secara bertahap.'
                            ],
                        };

                        // ── Word-diff computation ────────────────────────────────
                        // Normalize: lowercase, strip punctuation, split to words
                        $normalizeWords = function(string $text): array {
                            $text = mb_strtolower(trim($text));
                            $text = preg_replace('/[^\w\s\']/u', ' ', $text);
                            return array_values(array_filter(preg_split('/\s+/', $text), fn($w) => $w !== ''));
                        };

                        // Simple Levenshtein-based similarity (0..1)
                        $wordSimilarity = function(string $a, string $b): float {
                            if ($a === $b) return 1.0;
                            $maxLen = max(strlen($a), strlen($b));
                            if ($maxLen === 0) return 1.0;
                            $dist = levenshtein($a, $b);
                            return 1 - ($dist / $maxLen);
                        };

                        $transcriptWords = $normalizeWords($speakingTranscript);
                        $hasReference    = $speakingReference && strlen(trim($speakingReference)) > 0;
                        $diffResult      = []; // [['word' => string, 'status' => 'correct'|'wrong'|'extra', 'reference' => string]]

                        if ($hasReference) {
                            $referenceWords = $normalizeWords($speakingReference);
                            $refSet         = array_flip($referenceWords); // fast lookup

                            foreach ($transcriptWords as $tw) {
                                if (isset($refSet[$tw])) {
                                    // Exact match in reference
                                    $diffResult[] = ['word' => $tw, 'status' => 'correct', 'reference' => ''];
                                } else {
                                    // Find closest reference word
                                    $bestSim   = 0.0;
                                    $bestRef   = '';
                                    foreach ($referenceWords as $rw) {
                                        $sim = $wordSimilarity($tw, $rw);
                                        if ($sim > $bestSim) {
                                            $bestSim = $sim;
                                            $bestRef = $rw;
                                        }
                                    }
                                    if ($bestSim >= 0.65) {
                                        // Similar — likely mispronounced
                                        $diffResult[] = ['word' => $tw, 'status' => 'wrong', 'reference' => $bestRef];
                                    } else {
                                        // Not in reference at all — extra word
                                        $diffResult[] = ['word' => $tw, 'status' => 'extra', 'reference' => ''];
                                    }
                                }
                            }

                            $wrongCount   = count(array_filter($diffResult, fn($d) => $d['status'] === 'wrong'));
                            $correctCount = count(array_filter($diffResult, fn($d) => $d['status'] === 'correct'));
                            $totalRef     = count($referenceWords);
                            $rawAccuracy  = $totalRef > 0 ? (int) round($correctCount / $totalRef * 100) : 0;
                            $accuracyPct  = min($rawAccuracy, 100); // Capped at 100%
                        }
                    @endphp

                    <div x-data="{ showCard: false, showFull: false }"
                         x-init="setTimeout(() => showCard = true, 500)"
                         class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 transition-all duration-700 transform hover:shadow-md mt-5"
                         :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

                        {{-- Header --}}
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-full bg-violet-500/10 dark:bg-violet-500/20 flex items-center justify-center">
                                <span class="text-lg">🎙️</span>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Analisis Speaking AI</h2>
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Transkripsi & koreksi pelafalan dari Whisper AI</p>
                            </div>
                        </div>

                        {{-- Score badge --}}
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold {{ $spTip['badge'] }}">{{ $spTip['emoji'] }} {{ $spTip['level'] }}</span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-500 border border-zinc-200 dark:border-zinc-700 rounded-full px-2 py-0.5">{{ $speakingWordCount }} words detected</span>
                            </div>
                            <span class="text-lg font-black {{ $spTip['score'] }}">{{ $spScore }}%</span>
                        </div>

                        {{-- Score progress bar --}}
                        <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden mb-4">
                            <div class="h-full rounded-full transition-all duration-1000
                                @if($spScore >= 80) bg-gradient-to-r from-emerald-400 to-emerald-500
                                @elseif($spScore >= 60) bg-gradient-to-r from-indigo-400 to-indigo-500
                                @elseif($spScore >= 40) bg-gradient-to-r from-amber-400 to-amber-500
                                @else bg-gradient-to-r from-red-400 to-red-500 @endif"
                                 style="width: {{ $spScore }}%">
                            </div>
                        </div>

                        @if ($hasReference && count($diffResult) > 0)
                            {{-- ── Accuracy badge (with reference) ──────────── --}}
                            <div class="flex items-center gap-3 mb-3 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700/50">
                                <div class="flex-1 flex flex-wrap gap-2 text-[10px]">
                                    <span class="flex items-center gap-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-full px-2 py-0.5 font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                        {{ count(array_filter($diffResult, fn($d) => $d['status'] === 'correct')) }} correct
                                    </span>
                                    <span class="flex items-center gap-1 bg-red-500/10 text-red-700 dark:text-red-400 rounded-full px-2 py-0.5 font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                        {{ $wrongCount }} mispronounced
                                    </span>
                                    <span class="flex items-center gap-1 bg-amber-500/10 text-amber-700 dark:text-amber-400 rounded-full px-2 py-0.5 font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                                        {{ count(array_filter($diffResult, fn($d) => $d['status'] === 'extra')) }} extra words
                                    </span>
                                </div>
                            </div>

                            {{-- ── Word-diff transcript ──────────────────────── --}}
                            <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 mb-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-violet-500 animate-pulse inline-block"></span>
                                        <span class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Whisper AI Transcript</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[9px] text-zinc-400">
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-emerald-500/30 border border-emerald-500 inline-block"></span>Correct</span>
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-red-500/30 border border-red-500 inline-block"></span>Wrong</span>
                                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-500/30 border border-amber-500 inline-block"></span>Extra</span>
                                    </div>
                                </div>
                                <div class="text-[13px] leading-loose flex flex-wrap gap-x-1 gap-y-0.5"
                                     x-show="showFull || {{ count($diffResult) <= 40 ? 'true' : 'false' }}">
                                    @foreach($diffResult as $dw)
                                        @if($dw['status'] === 'correct')
                                            <span class="px-1 py-0.5 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-medium" title="✓ Correct">{{ $dw['word'] }}</span>
                                        @elseif($dw['status'] === 'wrong')
                                            <span class="relative inline-block group">
                                                <span class="px-1 py-0.5 rounded bg-red-500/15 text-red-700 dark:text-red-300 font-semibold underline decoration-red-500 decoration-dotted cursor-help">{{ $dw['word'] }}</span>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:flex flex-col items-center z-50 pointer-events-none">
                                                    <span class="bg-zinc-900 text-white text-[10px] font-semibold rounded-lg px-2 py-1 whitespace-nowrap shadow-lg">
                                                        Intended: <span class="text-emerald-400">{{ $dw['reference'] }}</span>
                                                    </span>
                                                    <span class="w-2 h-2 bg-zinc-900 rotate-45 -mt-1"></span>
                                                </span>
                                            </span>
                                        @else
                                            <span class="px-1 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-400 italic text-[12px]" title="Extra word">{{ $dw['word'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                @if(count($diffResult) > 40)
                                    <div x-show="!showFull" class="text-[13px] leading-loose flex flex-wrap gap-x-1 gap-y-0.5">
                                        @foreach(array_slice($diffResult, 0, 40) as $dw)
                                            @if($dw['status'] === 'correct')
                                                <span class="px-1 py-0.5 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-medium">{{ $dw['word'] }}</span>
                                            @elseif($dw['status'] === 'wrong')
                                                <span class="relative inline-block group">
                                                    <span class="px-1 py-0.5 rounded bg-red-500/15 text-red-700 dark:text-red-300 font-semibold underline decoration-red-500 decoration-dotted cursor-help">{{ $dw['word'] }}</span>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:flex flex-col items-center z-50 pointer-events-none">
                                                        <span class="bg-zinc-900 text-white text-[10px] font-semibold rounded-lg px-2 py-1 whitespace-nowrap shadow-lg">
                                                            Intended: <span class="text-emerald-400">{{ $dw['reference'] }}</span>
                                                        </span>
                                                        <span class="w-2 h-2 bg-zinc-900 rotate-45 -mt-1"></span>
                                                    </span>
                                                </span>
                                            @else
                                                <span class="px-1 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-400 italic text-[12px]">{{ $dw['word'] }}</span>
                                            @endif
                                        @endforeach
                                        <span class="text-zinc-400 italic">…</span>
                                    </div>
                                    <button @click="showFull = !showFull"
                                            class="mt-2 text-[11px] text-violet-600 dark:text-violet-400 font-semibold hover:underline focus:outline-none"
                                            x-text="showFull ? 'Show less ↑' : 'Show all ↓'">
                                    </button>
                                @endif
                            </div>

                            {{-- ── Mispronounced words detail ────────────────── --}}
                            @php $wrongWords = array_values(array_filter($diffResult, fn($d) => $d['status'] === 'wrong')); @endphp
                            @if(count($wrongWords) > 0)
                                <div class="mb-3 p-3 bg-red-500/5 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 rounded-xl">
                                    <p class="text-[10px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wide mb-2">⚠️ Words Needing Pronunciation Improvement</p>
                                    <div class="space-y-1.5">
                                        @foreach($wrongWords as $ww)
                                            <div class="flex items-center gap-2 text-[11px]">
                                                <span class="px-2 py-0.5 bg-red-500/15 text-red-700 dark:text-red-300 rounded font-mono font-bold min-w-[80px]">{{ $ww['word'] }}</span>
                                                <span class="text-zinc-400">→</span>
                                                <span class="px-2 py-0.5 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 rounded font-mono font-bold">{{ $ww['reference'] }}</span>
                                                <span class="text-zinc-500 text-[10px]">(intended)</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        @else
                            {{-- ── Plain transcript (no reference) ──────────── --}}
                            <div class="bg-zinc-50 dark:bg-zinc-950/60 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 mb-3">
                                <div class="flex items-center gap-1.5 mb-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-violet-500 animate-pulse inline-block"></span>
                                    <span class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-widest">Whisper AI Transcript</span>
                                </div>
                                <p class="text-[13px] text-zinc-700 dark:text-zinc-300 leading-relaxed italic"
                                   x-show="showFull || {{ strlen(trim($speakingTranscript)) <= 200 ? 'true' : 'false' }}">"{{ trim($speakingTranscript) }}"</p>
                                @if(strlen(trim($speakingTranscript)) > 200)
                                    <p class="text-[13px] text-zinc-700 dark:text-zinc-300 leading-relaxed italic"
                                       x-show="!showFull">"{{ mb_substr(trim($speakingTranscript), 0, 200) }}…"</p>
                                    <button @click="showFull = !showFull"
                                            class="mt-2 text-[11px] text-violet-600 dark:text-violet-400 font-semibold hover:underline focus:outline-none"
                                            x-text="showFull ? 'Show less ↑' : 'Show all ↓'">
                                    </button>
                                @endif
                                <p class="text-[10px] text-zinc-400 italic mt-3 border-t border-zinc-200 dark:border-zinc-800 pt-2">
                                    💡 Fill in <strong>Reference Text</strong> in your next Diagnostic Test to see word-by-word pronunciation feedback.
                                </p>
                            </div>
                        @endif

                        {{-- Pronunciation tip --}}
                        <div class="flex gap-2.5 p-3 rounded-xl border {{ $spTip['container'] }}">
                            <span class="text-base shrink-0 mt-0.5">💡</span>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wide mb-0.5 {{ $spTip['title'] }}">Pronunciation Tip</p>
                                <p class="text-[12px] leading-relaxed {{ $spTip['text'] }}">{{ $spTip['tip'] }}</p>
                            </div>
                        </div>
                    </div>

                @elseif (!$speakingProcessing && ($skills['speaking'] ?? 0) === 0)
                    {{-- No audio was submitted --}}
                    <div x-data="{ showCard: false }" x-init="setTimeout(() => showCard = true, 500)"
                         class="bg-white dark:bg-zinc-900 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 transition-all duration-700 transform mt-5"
                         :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-lg">🎙️</span>
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100">AI Speaking Analysis</h2>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">You did not submit an audio recording during the last Diagnostic Test, so there is no transcript to display.</p>
                        <a href="{{ route('diagnostic') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all hover:scale-[1.02]">
                            🎙️ Take Test with Audio
                        </a>
                    </div>
                @endif

                {{-- ── Performance History Chart ──────────────────── --}}
                <div x-data="{
                        showCard: false,
                        initHistoryChart() {
                            if (typeof Chart === 'undefined') {
                                setTimeout(() => this.initHistoryChart(), 100);
                                return;
                            }
                            
                            const data = @js($performanceChartData);
                            if(!data || !data.labels || data.labels.length === 0) return;
                            
                            new Chart(this.$refs.historyCanvas, {
                                type: 'line',
                                data: {
                                    labels: data.labels,
                                    datasets: [
                                        {
                                            label: 'Overall',
                                            data: data.overall,
                                            borderColor: 'rgba(167, 139, 250, 1)', // violet-400
                                            backgroundColor: 'rgba(167, 139, 250, 0.1)',
                                            borderWidth: 3,
                                            tension: 0,
                                            fill: true,
                                            pointBackgroundColor: 'rgba(167, 139, 250, 1)'
                                        },
                                        {
                                            label: 'Speaking',
                                            data: data.speaking,
                                            borderColor: 'rgba(249, 115, 22, 1)', // orange-500
                                            borderWidth: 2,
                                            tension: 0,
                                            hidden: true,
                                            pointBackgroundColor: 'rgba(249, 115, 22, 1)'
                                        },
                                        {
                                            label: 'Grammar',
                                            data: data.grammar,
                                            borderColor: 'rgba(16, 185, 129, 1)', // emerald-500
                                            borderWidth: 2,
                                            tension: 0,
                                            hidden: true,
                                            pointBackgroundColor: 'rgba(16, 185, 129, 1)'
                                        },
                                        {
                                            label: 'Vocabulary',
                                            data: data.vocabulary,
                                            borderColor: 'rgba(34, 211, 238, 1)', // cyan-400
                                            borderWidth: 2,
                                            tension: 0,
                                            hidden: true,
                                            pointBackgroundColor: 'rgba(34, 211, 238, 1)'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                            labels: {
                                                color: '#a1a1aa',
                                                usePointStyle: true,
                                                padding: 20,
                                                font: { size: 12, weight: '600' }
                                            }
                                        },
                                        tooltip: {
                                            mode: 'index',
                                            intersect: false,
                                            backgroundColor: 'rgba(24, 24, 27, 0.9)', // zinc-900
                                            titleColor: '#fff',
                                            bodyColor: '#e4e4e7', // zinc-200
                                            borderColor: 'rgba(63, 63, 70, 0.5)', // zinc-700
                                            borderWidth: 1,
                                            padding: 10
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100,
                                            grid: {
                                                color: 'rgba(161, 161, 170, 0.1)',
                                                drawBorder: false
                                            },
                                            ticks: {
                                                color: '#a1a1aa',
                                                stepSize: 20
                                            }
                                        },
                                        x: {
                                            grid: {
                                                display: false,
                                                drawBorder: false
                                            },
                                            ticks: {
                                                color: '#a1a1aa'
                                            }
                                        }
                                    },
                                    interaction: {
                                        mode: 'nearest',
                                        axis: 'x',
                                        intersect: false
                                    }
                                }
                            });
                        }
                    }"
                    x-init="setTimeout(() => showCard = true, 600); initHistoryChart()"
                    class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm p-5 transition-all duration-700 transform mt-5"
                    :class="showCard ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                >
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-full bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center">
                            <span class="text-lg">📈</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-zinc-800 dark:text-zinc-100 leading-tight">Diagnostic Progress History</h2>
                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400">Your test score progress over time</p>
                        </div>
                    </div>
                    
                    <div class="w-full h-[300px]">
                        <canvas x-ref="historyCanvas"></canvas>
                    </div>
                </div>

    @endif

    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets
</div>
