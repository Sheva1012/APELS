<?php

use App\Models\Module;
use App\Models\UserModuleProgress;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
 * Module Detail — deskripsi, konten, tombol mulai latihan (Req 14.5).
 * Guard: redirect jika modul locked (Req 14.4).
 */
new #[Layout('components.layouts.app')] class extends Component {
    public ?Module $module = null;
    public bool $isUnlocked = false;
    public bool $isCompleted = false;
    public ?int $score = null;

    public function mount(Module $module): void
    {
        $this->module = $module;

        $progress = UserModuleProgress::where('user_id', auth()->id())
            ->where('module_id', $this->module->id)
            ->first();

        if (!$progress || !$progress->is_unlocked) {
            // Guard: modul locked → redirect (Req 14.4)
            session()->flash('error', 'Modul ini masih terkunci.');
            $this->redirect(route('learning-path'), navigate: true);
            return;
        }

        $this->isUnlocked = $progress->is_unlocked;
        $this->isCompleted = $progress->is_completed;
        $this->score = $progress->score;
    }

    #[Computed]
    public function lessons()
    {
        return $this->module->lessons()->where('is_active', true)->orderBy('order_index')->get();
    }
}; ?>

<div class="max-w-3xl mx-auto py-8 px-4 text-zinc-900 dark:text-zinc-100">
    @if ($module)
        <div class="mb-6">
            <a href="{{ route('learning-path') }}" wire:navigate class="text-blue-600 dark:text-blue-400 text-sm hover:underline">← Kembali ke Roadmap</a>
        </div>

        <h1 class="text-2xl font-bold mb-2">{{ $module->title }}</h1>
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 capitalize">{{ $module->level }}</span>
            @if ($isCompleted)
                <span class="text-xs px-2 py-1 rounded bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">Completed {{ $score ? "({$score}%)" : '' }}</span>
            @else
                <span class="text-xs px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">In Progress</span>
            @endif
        </div>

        @if ($module->description)
            <div class="prose prose-sm max-w-none mb-6 dark:prose-invert">
                <p>{{ $module->description }}</p>
            </div>
        @endif

        {{-- Render list of lessons --}}
        @if ($this->lessons->isNotEmpty())
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-3">Materi Pembelajaran</h3>
                <div class="space-y-2">
                    @foreach ($this->lessons as $lesson)
                        <a href="{{ route('lesson-detail', $lesson->id) }}" wire:navigate class="block">
                            <div class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-900 dark:hover:bg-zinc-800 border border-transparent dark:border-zinc-800 rounded transition-colors cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs px-2 py-1 bg-gray-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded uppercase">{{ $lesson->type }}</span>
                                    <span class="text-zinc-800 dark:text-zinc-200">{{ $lesson->title }}</span>
                                </div>
                                @if ($lesson->estimated_minutes)
                                    <span class="text-xs text-gray-500 dark:text-zinc-400">~{{ $lesson->estimated_minutes }} menit</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Belum ada materi untuk modul ini.
            </div>
        @endif

        {{-- Tombol mulai latihan --}}
        <div class="mt-6">
            <a href="{{ route('exercises', $module->id) }}" wire:navigate>
                <flux:button variant="primary">
                    {{ $isCompleted ? 'Ulangi Latihan' : 'Mulai Latihan' }}
                </flux:button>
            </a>
        </div>
    @endif
</div>
