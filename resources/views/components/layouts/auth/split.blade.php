<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r dark:border-neutral-800 bg-neutral-900 overflow-hidden animate-fade-in">
                <div class="absolute inset-0 bg-linear-to-br from-indigo-900/90 via-purple-900/80 to-neutral-950/90"></div>
                
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-bold tracking-wider animate-fade-in-up" wire:navigate style="animation-duration: 0.8s; animation-delay: 0.3s; animation-fill-mode: both;">
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-white/10 backdrop-blur-md shadow-2xl ring-1 ring-white/20 mr-3">
                        <x-app-logo-icon class="h-6 fill-current text-white" />
                    </span>
                    <span class="text-transparent bg-clip-text bg-linear-to-r from-white to-white/70">
                        {{ config('app.name', 'APELS Portal') }}
                    </span>
                </a>

                <div class="relative z-20 mt-auto backdrop-blur-sm bg-black/20 p-6 rounded-2xl border border-white/10 shadow-2xl animate-fade-in-up" style="animation-duration: 0.8s; animation-delay: 0.6s; animation-fill-mode: both;">
                    <blockquote class="space-y-3">
                        <p class="text-2xl font-semibold leading-tight tracking-tight text-white">
                            Selamat Datang di Portal Mahasiswa
                        </p>
                        <p class="text-sm font-medium text-white/70">
                            Platform evaluasi dan pembelajaran bahasa adaptif berbasis AI.
                        </p>
                    </blockquote>
                </div>
            </div>
            
            <div class="relative w-full h-full lg:p-8 bg-slate-50 dark:bg-[#09090b] flex items-center justify-center overflow-hidden">

                <div class="relative z-10 mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[450px] animate-fade-in-up" style="animation-duration: 0.8s;">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
