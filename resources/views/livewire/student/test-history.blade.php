<?php

use App\Models\DiagnosticResult;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'histories' => DiagnosticResult::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }
}; ?>

<div class="relative w-full min-w-0 px-4 py-6 space-y-6">
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold text-zinc-800 dark:text-zinc-100 tracking-tight">Reports</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">List of your Diagnostic Test history.</p>
    </div>

    {{-- Content --}}
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
        @if ($histories->isEmpty())
            <div class="flex flex-col items-center justify-center text-center py-16 px-6">
                <div class="w-16 h-16 mb-4 rounded-2xl bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-2">Belum ada riwayat test</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6 max-w-xs">Kamu belum mengerjakan Diagnostic Test apa pun. Coba mulai test pertamamu!</p>
                <a href="{{ route('diagnostic') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md transition-all hover:-translate-y-0.5">
                    Mulai Test Sekarang
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Date & Time</th>
                            <th class="px-6 py-4 font-semibold text-center">Speaking</th>
                            <th class="px-6 py-4 font-semibold text-center">Grammar</th>
                            <th class="px-6 py-4 font-semibold text-center">Vocabulary</th>
                            <th class="px-6 py-4 font-semibold text-center">Overall</th>
                            <th class="px-6 py-4 font-semibold text-center">Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($histories as $history)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $history->created_at->format('d M Y') }}
                                            </div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $history->created_at->format('H:i') }} WIB
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    @if($history->is_speaking_processed)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                            {{ $history->speaking }}%
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800" title="Sedang diproses">
                                            Processing
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                        {{ $history->grammar }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                        {{ $history->vocabulary }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $history->overall }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php
                                        $level = match(true) {
                                            $history->overall >= 70 => ['label' => 'Advanced', 'color' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300 border-indigo-200 dark:border-indigo-500/30'],
                                            $history->overall >= 55 => ['label' => 'Intermediate', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border-amber-200 dark:border-amber-500/30'],
                                            default                 => ['label' => 'Basic', 'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border-rose-200 dark:border-rose-500/30'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold border {{ $level['color'] }}">
                                        {{ $level['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if ($histories->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900">
                    {{ $histories->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
