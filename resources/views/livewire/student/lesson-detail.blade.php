<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\UserLessonProgress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Lesson $lesson = null;
    public array $completedLessonIds = [];

    public function mount(Lesson $lesson): void
    {
        $this->lesson = $lesson;
        $user = auth()->user();

        // Check if module is unlocked for this user
        $progress = \App\Models\UserModuleProgress::where('user_id', $user->id)
            ->where('module_id', $this->lesson->module_id)
            ->first();

        if (!$progress || !$progress->is_unlocked) {
            session()->flash('error', 'Modul ini masih terkunci.');
            $this->redirect(route('learning-path'), navigate: true);
            return;
        }

        // Get completed lessons
        $this->completedLessonIds = UserLessonProgress::where('user_id', $user->id)
            ->pluck('lesson_id')
            ->toArray();

        // Enforce sequential lock logic
        $allLessons = $this->getAllLessonsInPath();
        $currentIndex = $allLessons->search(fn($l) => $l->id === $this->lesson->id);

        if ($currentIndex !== false && $currentIndex > 0) {
            for ($i = 0; $i < $currentIndex; $i++) {
                if (!in_array($allLessons[$i]->id, $this->completedLessonIds)) {
                    session()->flash('error', 'Anda harus menyelesaikan materi secara berurutan.');
                    $this->redirect(route('lesson-detail', $allLessons[$i]->id), navigate: true);
                    return;
                }
            }
        }
    }

    #[Computed]
    public function modules()
    {
        // Get all modules for the same learning path as the current lesson's module
        return Module::where('path_key', $this->lesson->module->path_key)
            ->where('is_active', true)
            ->with(['lessons' => fn($q) => $q->where('is_active', true)->orderBy('order_index')])
            ->orderBy('order_index')
            ->get();
    }

    public function getAllLessonsInPath()
    {
        return $this->modules->flatMap->lessons;
    }

    public function markCompleteAndNext()
    {
        // Mark current lesson complete
        UserLessonProgress::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $this->lesson->id,
        ], [
            'completed_at' => now(),
        ]);

        $allLessons = $this->getAllLessonsInPath();
        $currentIndex = $allLessons->search(fn($l) => $l->id === $this->lesson->id);

        if ($currentIndex !== false && $currentIndex < $allLessons->count() - 1) {
            $nextLesson = $allLessons[$currentIndex + 1];
            $this->redirect(route('lesson-detail', $nextLesson->id), navigate: true);
        } else {
            // Mark the module as completed since there are no exercises
            \App\Models\UserModuleProgress::updateOrCreate(
                ['user_id' => auth()->id(), 'module_id' => $this->lesson->module_id],
                ['is_completed' => true, 'score' => 100]
            );
            
            // End of learning path or module, redirect to learning path
            $this->redirect(route('learning-path'), navigate: true);
        }
    }
    
    public function goToPrevious()
    {
        $allLessons = $this->getAllLessonsInPath();
        $currentIndex = $allLessons->search(fn($l) => $l->id === $this->lesson->id);

        if ($currentIndex !== false && $currentIndex > 0) {
            $prevLesson = $allLessons[$currentIndex - 1];
            $this->redirect(route('lesson-detail', $prevLesson->id), navigate: true);
        }
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 text-zinc-900 dark:text-zinc-100 flex flex-col lg:flex-row gap-8 align-top">
    <!-- Main Content -->
    <div class="flex-1 lg:max-w-3xl">
        @if ($lesson)
            <div class="mb-6">
                <a href="{{ route('module-detail', $lesson->module_id) }}" wire:navigate class="text-blue-600 dark:text-blue-400 text-sm hover:underline">← Kembali ke Detail Modul</a>
            </div>

            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-400 border border-red-200 dark:border-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <h1 class="text-2xl font-bold mb-4">{{ $lesson->title }}</h1>
            <div class="flex items-center gap-2 mb-6">
                <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 uppercase">{{ $lesson->type }}</span>
                @if ($lesson->estimated_minutes)
                    <span class="text-xs text-gray-500 dark:text-zinc-400">{{ $lesson->estimated_minutes }} menit waktu baca</span>
                @endif
            </div>

            <div class="prose prose-sm max-w-none mb-8 dark:prose-invert">
                {!! $lesson->content !!}
            </div>

            <div class="mt-8 flex justify-between items-center border-t border-gray-200 dark:border-zinc-800 pt-6">
                @php
                    $allLessons = $this->getAllLessonsInPath();
                    $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
                    $hasPrev = $currentIndex !== false && $currentIndex > 0;
                    $hasNext = $currentIndex !== false && $currentIndex < $allLessons->count() - 1;
                @endphp

                @if($hasPrev)
                    <flux:button wire:click="goToPrevious" variant="outline">Materi Sebelumnya</flux:button>
                @else
                    <div></div>
                @endif

                @if($hasNext)
                    <flux:button wire:click="markCompleteAndNext" variant="primary">Tandai Selesai & Lanjut</flux:button>
                @else
                    <flux:button wire:click="markCompleteAndNext" variant="primary">Selesai Pembelajaran</flux:button>
                @endif
            </div>
        @endif
    </div>

    <!-- Sidebar / Daftar Materi -->
    <div class="w-full lg:w-80 flex-shrink-0">
        <div class="sticky top-8 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-800 flex justify-between items-center">
                <h3 class="font-semibold text-lg">Daftar Materi</h3>
                <span class="text-xs text-gray-500">{{ count($completedLessonIds) }}/{{ $this->getAllLessonsInPath()->count() }} Selesai</span>
            </div>
            
            <div class="max-h-[calc(100vh-10rem)] overflow-y-auto">
                @foreach($this->modules as $mod)
                    <div class="border-b border-gray-100 dark:border-zinc-800 last:border-0" x-data="{ open: {{ $mod->id === $lesson->module_id ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors text-left focus:outline-none">
                            <span class="font-medium text-sm truncate pr-2">{{ $mod->title }}</span>
                            <svg class="w-4 h-4 transition-transform text-gray-500" x-bind:class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div x-show="open" x-collapse>
                            <ul class="pb-2">
                                @foreach($mod->lessons as $l)
                                    @php
                                        $isCurrent = $l->id === $lesson->id;
                                        $isCompleted = in_array($l->id, $completedLessonIds);
                                        
                                        // A lesson is accessible if it's completed, OR it's the current lesson, 
                                        // OR it's the immediate next lesson after all completed ones.
                                        // We simplify by just letting them click, and the backend mount() will block if invalid.
                                    @endphp
                                    <li>
                                        <a href="{{ route('lesson-detail', $l->id) }}" wire:navigate class="flex items-start gap-3 py-2 pl-6 pr-4 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors {{ $isCurrent ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                            <div class="mt-0.5 flex-shrink-0">
                                                @if($isCompleted)
                                                    <svg class="w-4 h-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                    </svg>
                                                @elseif($isCurrent)
                                                    <div class="w-4 h-4 rounded-full border-2 border-blue-500"></div>
                                                @else
                                                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-zinc-600"></div>
                                                @endif
                                            </div>
                                            <span class="text-sm {{ $isCurrent ? 'text-blue-700 dark:text-blue-400 font-medium' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                {{ $l->title }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
