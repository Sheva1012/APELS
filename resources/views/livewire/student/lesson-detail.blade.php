<?php

use App\Models\Lesson;
use App\Models\Module;
use App\Models\UserLessonProgress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app-lesson')] class extends Component {
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
        $userId = auth()->id();
        $unlockedModuleIds = \App\Models\UserModuleProgress::where('user_id', $userId)
            ->where('is_unlocked', true)
            ->pluck('module_id')
            ->toArray();

        $allowedModuleIds = array_unique(array_merge($unlockedModuleIds, [$this->lesson->module_id]));

        // Get only unlocked modules for the same learning path as the current lesson's module
        return Module::whereIn('id', $allowedModuleIds)
            ->where('path_key', $this->lesson->module->path_key)
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

        // Check if this was the last lesson of the CURRENT module
        $currentModuleLessons = $this->lesson->module->lessons()->where('is_active', true)->orderBy('order_index')->get();
        $isLastLessonInModule = $currentModuleLessons->last()->id === $this->lesson->id;

        // If last lesson in module, redirect to quiz/exercise page
        if ($isLastLessonInModule) {
            $this->redirect(route('exercises', $this->lesson->module_id), navigate: true);
            return;
        }

        if ($currentIndex !== false && $currentIndex < $allLessons->count() - 1) {
            $nextLesson = $allLessons[$currentIndex + 1];
            $this->redirect(route('lesson-detail', $nextLesson->id), navigate: true);
        } else {
            // End of learning path
            $this->redirect(route('learning-path'), navigate: true);
        }
    }

    public function markCompleteAndGoToQuiz()
    {
        // Mark current lesson complete
        UserLessonProgress::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $this->lesson->id,
        ], [
            'completed_at' => now(),
        ]);

        // Instead of unlocking next module here, we just go to the quiz
        // The quiz completion will unlock the next module.
        $this->redirect(route('exercises', $this->lesson->module_id), navigate: true);
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

<div class="flex flex-col h-full overflow-hidden bg-white dark:bg-[#121212]" x-data="{ showMobileModules: false }">
    <!-- Top Header (Fixed) -->
    <header class="h-14 lg:h-16 flex items-center justify-between px-4 lg:px-6 border-b border-gray-200 dark:border-zinc-800 bg-white dark:bg-[#121212] shrink-0 z-30">
        <a href="{{ route('learning-path') }}" wire:navigate class="flex items-center gap-3 text-sm font-bold text-zinc-700 dark:text-zinc-200 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
            <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span class="hidden sm:inline">Belajar {{ $this->lesson->module->title ?? 'Modul' }}</span>
        </a>

        <!-- Hamburger for Mobile Modules -->
        <button @click="showMobileModules = true" class="lg:hidden p-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
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
                
                <style>
                    .lesson-content p {
                        margin-bottom: 1.25rem !important;
                    }
                    .lesson-content p:last-child {
                        margin-bottom: 0 !important;
                    }
                    .lesson-content ul {
                        list-style-type: disc !important;
                        padding-left: 1.5rem !important;
                        margin-bottom: 1.25rem !important;
                    }
                    .lesson-content ol {
                        list-style-type: decimal !important;
                        padding-left: 1.5rem !important;
                        margin-bottom: 1.25rem !important;
                    }
                    .lesson-content li {
                        margin-bottom: 0.5rem !important;
                    }
                </style>
                <div class="lesson-content prose prose-zinc max-w-none dark:prose-invert text-zinc-900 dark:text-white leading-relaxed">
                    {!! nl2br($lesson->content) !!}
                </div>
            </div>
        </main>
        
        <!-- Bottom Nav (Fixed to Main Content Area) -->
        <div class="absolute bottom-0 left-0 right-0 lg:right-[320px] xl:right-[350px] h-16 bg-white dark:bg-[#121212] border-t border-gray-200 dark:border-zinc-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-all duration-300">
            @php
                $allLessons = $this->getAllLessonsInPath();
                $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
                $hasPrev = $currentIndex !== false && $currentIndex > 0;
                $hasNext = $currentIndex !== false && $currentIndex < $allLessons->count() - 1;
                
                $currentModuleLessons = $lesson->module->lessons()->where('is_active', true)->orderBy('order_index')->get();
                $isLastLessonInModule = $currentModuleLessons->isNotEmpty() && $currentModuleLessons->last()->id === $lesson->id;
            @endphp
            
            <!-- Left: Previous Lesson / Disabled Current if first -->
            <div class="flex-1 flex justify-start">
                @if($hasPrev)
                    <button wire:click="goToPrevious" class="flex items-center gap-2 text-[13px] font-semibold text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition group">
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span class="truncate max-w-[150px] sm:max-w-[200px] hidden sm:inline">{{ $allLessons[$currentIndex - 1]->title }}</span>
                        <span class="sm:hidden">Sebelumnya</span>
                    </button>
                @else
                    <div class="flex items-center gap-2 text-[13px] font-medium text-zinc-400 dark:text-zinc-600 select-none">
                        <span class="truncate max-w-[150px] sm:max-w-[200px] hidden sm:inline">{{ $lesson->title }}</span>
                        <span class="sm:hidden">Awal Modul</span>
                    </div>
                @endif
            </div>

            <!-- Right: Next Lesson / Quiz / Finish -->
            <div class="flex-1 flex justify-end">
                @if($isLastLessonInModule)
                    <button wire:click="markCompleteAndGoToQuiz" class="flex items-center gap-2 text-[13px] font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition group">
                        <span class="truncate max-w-[150px] sm:max-w-[200px] hidden sm:inline">Mulai Quiz Modul</span>
                        <span class="sm:hidden">Quiz Modul</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @elseif($hasNext)
                    <button wire:click="markCompleteAndNext" class="flex items-center gap-2 text-[13px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition group">
                        <span class="truncate max-w-[150px] sm:max-w-[250px] hidden sm:inline">{{ $allLessons[$currentIndex + 1]->title }}</span>
                        <span class="sm:hidden">Selanjutnya</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button wire:click="markCompleteAndNext" class="flex items-center gap-2 text-[13px] font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition group">
                        <span>Selesai Pembelajaran</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                @endif
            </div>
        </div>
        
        <!-- Mobile Overlay Background -->
        <div x-show="showMobileModules" 
             x-transition:enter="transition-opacity ease-linear duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="transition-opacity ease-linear duration-300" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             @click="showMobileModules = false"
             class="fixed inset-0 bg-black/50 z-40 lg:hidden" x-cloak></div>

        <!-- Right Sidebar (Fixed Desktop / Drawer Mobile) -->
        <aside class="flex flex-col w-[300px] sm:w-[320px] xl:w-[350px] bg-[#FAFAFA] dark:bg-[#18181b] border-l border-gray-200 dark:border-zinc-800 shrink-0 z-50 transition-transform duration-300 fixed inset-y-0 right-0 lg:relative lg:translate-x-0"
               :class="showMobileModules ? 'translate-x-0 shadow-2xl' : 'translate-x-full lg:shadow-none'">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-800 pt-5 px-6">
                <h3 class="pb-3 text-[14px] font-bold text-zinc-800 dark:text-zinc-200">
                    Daftar Modul
                </h3>
                <button @click="showMobileModules = false" class="pb-3 lg:hidden text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Progress Section -->
            @php
                $user = auth()->user();
                $completedModuleIds = \App\Models\UserModuleProgress::where('user_id', $user->id)
                    ->where('is_completed', true)
                    ->pluck('module_id')
                    ->toArray();

                $sidebarLessonIds = $allLessons->pluck('id')->toArray();
                $completedSidebarLessonsCount = count(array_intersect($completedLessonIds, $sidebarLessonIds));

                $totalItems = count($sidebarLessonIds) + $this->modules->count();
                $completedItems = $completedSidebarLessonsCount + count(array_intersect($this->modules->pluck('id')->toArray(), $completedModuleIds));
                
                $rawPercentage = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;
                $percentage = min(100, max(0, $rawPercentage));
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
                                <!-- Check if module is complete in DB (all lessons + quiz passed) -->
                                @php
                                    $modLessonIds = $mod->lessons->pluck('id')->toArray();
                                    $allModComplete = count(array_diff($modLessonIds, $completedLessonIds)) === 0;
                                    $modIsCompleted = in_array($mod->id, $completedModuleIds);
                                @endphp
                                @if($modIsCompleted)
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
                                <!-- Quiz & Writing Task Step -->
                                <li>
                                    @if($allModComplete)
                                        <a href="{{ route('exercises', $mod->id) }}" wire:navigate class="flex items-start py-2 px-6 hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-r-2 border-transparent">
                                            <div class="mt-[3px] flex-shrink-0 w-6 flex justify-start">
                                                @php
                                                    $modProgress = \App\Models\UserModuleProgress::where('user_id', auth()->id())->where('module_id', $mod->id)->first();
                                                    $isModCompleted = $modProgress && $modProgress->is_completed;
                                                @endphp
                                                @if($isModCompleted)
                                                    <svg class="w-4 h-4 text-emerald-500 drop-shadow-sm" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.492-3.053c.24-.294.59-.444.954-.444H18.75m-7.33-6.52l3.181 3.183m3.181-3.183l-3.181 3.183m-3.181-3.183c-.845-.845-2.222-.845-3.067 0l-3.181 3.183c-.845.845-.845 2.222 0 3.067l3.181 3.183c.845.845 2.222.845 3.067 0l3.181-3.183c.845-.845.845-2.222 0-3.067z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <span class="text-[12px] leading-relaxed flex-1 text-zinc-600 dark:text-zinc-400 font-bold">
                                                Adaptive Quiz & Task
                                            </span>
                                        </a>
                                    @else
                                        <div class="flex items-start py-2 px-6 transition-colors border-r-2 border-transparent opacity-50 cursor-not-allowed">
                                            <div class="mt-[3px] flex-shrink-0 w-6 flex justify-start">
                                                <svg class="w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                                </svg>
                                            </div>
                                            <span class="text-[12px] leading-relaxed flex-1 text-zinc-500 dark:text-zinc-500 font-medium">
                                                Adaptive Quiz & Task
                                            </span>
                                        </div>
                                    @endif
                                </li>
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
