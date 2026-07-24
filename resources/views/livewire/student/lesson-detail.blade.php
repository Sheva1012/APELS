<?php

use App\Models\Lesson;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public ?Lesson $lesson = null;

    public function mount(Lesson $lesson): void
    {
        $this->lesson = $lesson;

        // Check if module is unlocked for this user
        $progress = \App\Models\UserModuleProgress::where('user_id', auth()->id())
            ->where('module_id', $this->lesson->module_id)
            ->first();

        if (!$progress || !$progress->is_unlocked) {
            session()->flash('error', 'Materi ini masih terkunci.');
            $this->redirect(route('learning-path'), navigate: true);
            return;
        }
    }
}; ?>

<div class="max-w-3xl mx-auto py-8 px-4 text-zinc-900 dark:text-zinc-100">
    @if ($lesson)
        <div class="mb-6">
            <a href="{{ route('module-detail', $lesson->module_id) }}" wire:navigate class="text-blue-600 dark:text-blue-400 text-sm hover:underline">← Kembali ke Detail Modul</a>
        </div>

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
                $previousLesson = \App\Models\Lesson::where('module_id', $lesson->module_id)
                    ->where('is_active', true)
                    ->where('order_index', '<', $lesson->order_index)
                    ->orderBy('order_index', 'desc')
                    ->first();
                
                $nextLesson = \App\Models\Lesson::where('module_id', $lesson->module_id)
                    ->where('is_active', true)
                    ->where('order_index', '>', $lesson->order_index)
                    ->orderBy('order_index', 'asc')
                    ->first();
            @endphp

            @if($previousLesson)
                <a href="{{ route('lesson-detail', $previousLesson->id) }}" wire:navigate>
                    <flux:button variant="outline">Materi Sebelumnya</flux:button>
                </a>
            @else
                <div></div>
            @endif

            @if($nextLesson)
                <a href="{{ route('lesson-detail', $nextLesson->id) }}" wire:navigate>
                    <flux:button variant="primary">Materi Selanjutnya</flux:button>
                </a>
            @else
                <a href="{{ route('exercises', $lesson->module_id) }}" wire:navigate>
                    <flux:button variant="primary">Selesai & Mulai Latihan</flux:button>
                </a>
            @endif
        </div>
    @endif
</div>
