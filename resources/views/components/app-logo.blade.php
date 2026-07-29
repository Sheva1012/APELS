@props(['forceWhite' => false])
<div class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-500/20">
    <x-app-logo-icon class="size-5 text-white" />
</div>
<div class="ml-2 grid flex-1 text-left">
    <span class="text-sm font-extrabold tracking-wider {{ $forceWhite ? 'text-white' : 'text-zinc-900 dark:text-zinc-100' }}">APELS</span>
    <span class="text-[9px] font-medium tracking-tight {{ $forceWhite ? 'text-zinc-300' : 'text-zinc-500 dark:text-zinc-400' }}">Student Learning System</span>
</div>
