<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased bg-slate-50 dark:bg-[#0a0a0a] relative overflow-hidden text-slate-900 dark:text-slate-100">
        <style>
            @keyframes float {
                0%, 100% { transform: translateY(0) scale(1); }
                50% { transform: translateY(-20px) scale(1.05); }
            }
            .animate-float { animation: float 7s ease-in-out infinite; }
            .animate-float-delayed { animation: float 9s ease-in-out infinite; animation-delay: 2s; }
            
            @keyframes fade-in-up {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in-up { animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        </style>

        <!-- Animated Background Elements -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute top-[-10%] left-[-10%] w-[50vw] h-[50vw] rounded-full bg-blue-400/30 dark:bg-blue-600/15 blur-[100px] animate-float mix-blend-multiply dark:mix-blend-screen"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[60vw] h-[60vw] rounded-full bg-teal-400/30 dark:bg-teal-600/15 blur-[120px] animate-float-delayed mix-blend-multiply dark:mix-blend-screen"></div>
            <div class="absolute top-[30%] left-[20%] w-[40vw] h-[40vw] rounded-full bg-cyan-400/20 dark:bg-cyan-600/10 blur-[90px] animate-pulse mix-blend-multiply dark:mix-blend-screen" style="animation-duration: 4s;"></div>
        </div>

        <div class="relative z-10 flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6 animate-fade-in-up">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/50 dark:bg-white/5 backdrop-blur-md shadow-sm border border-black/5 dark:border-white/10 transition-transform duration-300 hover:scale-105">
                        <x-app-logo-icon class="size-8 fill-current text-indigo-600 dark:text-indigo-400" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="rounded-2xl border border-white/40 dark:border-white/10 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl text-stone-800 dark:text-stone-200 shadow-2xl shadow-indigo-500/10 dark:shadow-none">
                        <div class="px-8 py-8 sm:px-10">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
