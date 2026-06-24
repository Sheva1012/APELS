<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @stack('styles')
        {{-- Dark mode: set class before paint to avoid flash --}}
        <script>
            (function() {
                const theme = localStorage.getItem('apels-theme');
                if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    // default: dark
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('home') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Platform" class="grid">
                    @if(auth()->user()->hasRole('mahasiswa'))
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Dashboard</flux:navlist.item>
                        <flux:navlist.item icon="book-open-text" :href="route('learning-path')" :current="request()->routeIs('learning-path') || request()->routeIs('module-detail') || request()->routeIs('exercises')" wire:navigate>Roadmap Belajar</flux:navlist.item>
                        <flux:navlist.item icon="pencil" :href="route('diagnostic')" :current="request()->routeIs('diagnostic')" wire:navigate>Diagnostic Test</flux:navlist.item>
                    @elseif(auth()->user()->hasRole('dosen'))
                        <flux:navlist.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>Laporan Mahasiswa</flux:navlist.item>
                    @elseif(auth()->user()->hasRole('admin'))
                        <flux:navlist.item icon="cog" href="/admin" target="_blank">Admin Panel</flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            {{-- Dark / Light Mode Toggle --}}
            <div class="px-2 pb-1">
                <button
                    id="theme-toggle-btn"
                    onclick="toggleTheme()"
                    type="button"
                    class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all duration-200"
                    aria-label="Toggle dark/light mode"
                >
                    {{-- Sun icon (visible in dark mode → click to go light) --}}
                    <svg id="icon-sun" class="hidden dark:block w-5 h-5 text-amber-400 group-hover:text-amber-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                    </svg>
                    {{-- Moon icon (visible in light mode → click to go dark) --}}
                    <svg id="icon-moon" class="block dark:hidden w-5 h-5 text-indigo-500 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span id="theme-toggle-label" class="select-none">
                        <span class="hidden dark:inline">Mode Terang</span>
                        <span class="inline dark:hidden">Mode Gelap</span>
                    </span>
                </button>
            </div>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @stack('scripts')

        <script>
            function toggleTheme() {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                if (isDark) {
                    html.classList.remove('dark');
                    localStorage.setItem('apels-theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('apels-theme', 'dark');
                }
                // Reinitialise chart when theme changes (student dashboard)
                if (typeof initChart === 'function') {
                    setTimeout(initChart, 50);
                }
            }
        </script>
    </body>
</html>
