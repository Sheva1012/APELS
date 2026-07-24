<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\UserLessonProgress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.learning')] class extends Component {
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
            
            // Buka kunci modul selanjutnya secara otomatis
            $nextModule = \App\Models\Module::where('path_key', $this->lesson->module->path_key)
                ->where('order_index', '>', $this->lesson->module->order_index)
                ->orderBy('order_index')
                ->first();
                
            if ($nextModule) {
                \App\Models\UserModuleProgress::updateOrCreate(
                    ['user_id' => auth()->id(), 'module_id' => $nextModule->id],
                    ['is_unlocked' => true]
                );
            }
            
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

<div class="flex flex-col h-screen overflow-hidden bg-white dark:bg-[#121212]">
    <!-- Top Header (Fixed) -->
    <header class="h-14 lg:h-16 flex items-center justify-between px-4 lg:px-6 border-b border-gray-200 dark:border-zinc-800 bg-white dark:bg-[#121212] shrink-0 z-30">
        <a href="{{ route('learning-path') }}" wire:navigate class="flex items-center gap-3 text-sm font-bold text-zinc-700 dark:text-zinc-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span class="hidden sm:inline">Belajar {{ $this->lesson->module->title ?? 'Modul' }}</span>
        </a>
        <div class="flex items-center gap-5">

            <!-- Dark mode toggle mock -->
            <button class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
            
            <!-- Profile mock -->
            <div class="w-8 h-8 bg-orange-500 text-white font-bold flex items-center justify-center rounded-full text-[11px] shadow-sm">
                {{ auth()->user()->initials() }}
            </div>
        </div>
    </header>

    <!-- Main Body Layout -->
    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- Left/Center Content Area (Scrollable) -->
        <!-- pb-20 to account for bottom fixed nav -->
        <main class="flex-1 overflow-y-auto relative bg-white dark:bg-[#121212] custom-scrollbar">
            <div class="max-w-[850px] mx-auto py-10 px-6 sm:px-10 lg:px-16 pb-32">
                @if (session('error'))
                    <div class="mb-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-400 border border-red-200 dark:border-red-800">
                        {{ session('error') }}
                    </div>
                @endif
                
                <h1 class="text-3xl font-bold mb-6 text-zinc-900 dark:text-white leading-tight">{{ $lesson->title }}</h1>
                
                <div class="prose prose-zinc max-w-none dark:prose-invert prose-p:leading-relaxed prose-headings:font-bold prose-a:text-indigo-600 prose-img:rounded-xl">
                    {!! $lesson->content !!}
                </div>
            </div>
        </main>
        
        <!-- Bottom Nav (Fixed to Main Content Area) -->
        <div class="absolute bottom-0 left-0 right-0 lg:right-[320px] xl:right-[350px] h-16 bg-white/95 dark:bg-[#121212]/95 backdrop-blur-sm border-t border-gray-200 dark:border-zinc-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-all duration-300">
            @php
                $allLessons = $this->getAllLessonsInPath();
                $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
                $hasPrev = $currentIndex !== false && $currentIndex > 0;
                $hasNext = $currentIndex !== false && $currentIndex < $allLessons->count() - 1;
            @endphp
            
            <div class="flex-1 flex justify-start">
                @if($hasPrev)
                    <button wire:click="goToPrevious" class="flex items-center gap-2 text-[13px] font-semibold text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 transition group">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span class="truncate max-w-[150px] sm:max-w-[200px] hidden sm:inline">{{ $allLessons[$currentIndex - 1]->title }}</span>
                        <span class="sm:hidden">Sebelumnya</span>
                    </button>
                @endif
            </div>
            
            <div class="flex-1 flex justify-center">
                <div class="text-[13px] font-bold text-zinc-800 dark:text-zinc-200 truncate px-4">
                    {{ $lesson->title }}
                </div>
            </div>

            <div class="flex-1 flex justify-end">
                @if($hasNext)
                    <button wire:click="markCompleteAndNext" class="flex items-center gap-2 text-[13px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition group">
                        <span class="truncate max-w-[150px] sm:max-w-[200px] hidden sm:inline">{{ $allLessons[$currentIndex + 1]->title }}</span>
                        <span class="sm:hidden">Selanjutnya</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button wire:click="markCompleteAndNext" class="flex items-center gap-2 text-[13px] font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 transition group">
                        <span>Selesai Pembelajaran</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Right Sidebar (Fixed) -->
        <aside class="hidden lg:flex w-[320px] xl:w-[350px] bg-[#FAFAFA] dark:bg-[#18181b] border-l border-gray-200 dark:border-zinc-800 flex-col shrink-0 relative z-20">
            <!-- Header -->
            <div class="flex items-center border-b border-gray-200 dark:border-zinc-800 pt-5 px-6">
                <h3 class="pb-3 text-[14px] font-bold text-zinc-800 dark:text-zinc-200">
                    Daftar Modul
                </h3>
            </div>
            
            <!-- Progress Section -->
            @php
                $totalLessons = $allLessons->count();
                $completedCount = count($completedLessonIds);
                $percentage = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;
            @endphp
            <div class="px-6 py-5 border-b border-gray-200 dark:border-zinc-800">
                <div class="flex justify-between items-center mb-2.5">
                    <span class="text-[12px] font-bold text-zinc-700 dark:text-zinc-300">{{ $percentage }}% Selesai</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
            
            <!-- Accordion List -->
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                @foreach($this->modules as $mod)
                    <div class="border-b border-gray-200/50 dark:border-zinc-800/80 last:border-0" x-data="{ open: {{ $mod->id === $lesson->module_id ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 hover:bg-black/5 dark:hover:bg-white/5 transition-colors text-left focus:outline-none">
                            <span class="font-bold text-[13px] text-zinc-800 dark:text-zinc-200 pr-2 flex-1">{{ $mod->title }}</span>
                            <div class="flex items-center gap-2">
                                <!-- Check if all lessons in module are complete -->
                                @php
                                    $modLessonIds = $mod->lessons->pluck('id')->toArray();
                                    $allModComplete = count(array_diff($modLessonIds, $completedLessonIds)) === 0;
                                @endphp
                                @if($allModComplete && count($modLessonIds) > 0)
                                    <svg class="w-[18px] h-[18px] text-emerald-500 shrink-0 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                                <svg class="w-4 h-4 transition-transform text-zinc-400" x-bind:class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        
                        <div x-show="open" x-collapse>
                            <ul class="pb-3 pt-1">
                                @foreach($mod->lessons as $idx => $l)
                                    @php
                                        $isCurrent = $l->id === $lesson->id;
                                        $isCompleted = in_array($l->id, $completedLessonIds);
                                    @endphp
                                    <li>
                                        <a href="{{ route('lesson-detail', $l->id) }}" wire:navigate class="flex items-start py-2 px-6 hover:bg-black/5 dark:hover:bg-white/5 transition-colors {{ $isCurrent ? 'bg-indigo-50/70 dark:bg-indigo-900/10 border-r-2 border-indigo-600 dark:border-indigo-400' : 'border-r-2 border-transparent' }}">
                                            <div class="mt-[3px] flex-shrink-0 w-6 flex justify-start">
                                                @if($isCompleted)
                                                    <svg class="w-4 h-4 text-emerald-500 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <!-- Outline circle -->
                                                    <svg class="w-4 h-4 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <span class="text-[12px] leading-relaxed flex-1 {{ $isCurrent ? 'text-indigo-700 dark:text-indigo-400 font-bold' : 'text-zinc-600 dark:text-zinc-400 font-medium' }}">
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
        </aside>
    </div>
    <style>
        /* Custom Scrollbar for better appearance */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(156, 163, 175, 0.3);
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(82, 82, 91, 0.4);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(156, 163, 175, 0.5);
        }
    </style>
</div>
