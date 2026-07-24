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
    <body class="min-h-screen w-full bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r-0 bg-[#0a192f] text-white w-64">
            <flux:sidebar.toggle class="lg:hidden text-white" icon="x-mark" />

            <!-- Logo Section -->
            <a href="{{ route('home') }}" class="flex flex-col items-center mt-4 mb-4 w-full" wire:navigate>
                <div class="flex items-center space-x-2 mb-1">
                    <x-app-logo class="size-7 text-blue-500"></x-app-logo>
                </div>
                <div class="text-center text-[10px] text-blue-200/70 font-medium tracking-wide leading-tight">
                    AI-Powered English<br>Learning System
                </div>
            </a>

            <!-- AI Assistant Icon (Above Dashboard Menu) -->
            <div class="flex flex-col items-center mb-4 w-full">
                <div class="w-16 h-16 mb-1 flex items-center justify-center">
                    <img src="{{ asset('images/robot.png') }}" alt="AI Assistant" class="w-full h-full object-contain drop-shadow-[0_0_15px_rgba(59,130,246,0.3)]" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=apels&backgroundColor=transparent'">
                </div>
                <span class="text-[13px] font-semibold text-white tracking-wide">AI Assistant</span>
                <div class="flex items-center space-x-1.5 mt-0.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></div>
                    <span class="text-[11px] text-gray-300">Online</span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex flex-col gap-1.5 px-3 w-full">
                @if(auth()->user()->hasRole('mahasiswa'))
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-[#1e3a8a] text-white shadow-lg' : 'text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white' }}">
                        <x-heroicon-s-home class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Dashboard</span>
                    </a>
                    <a href="{{ route('learning-path') }}" wire:navigate class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('learning-path') || request()->routeIs('module-detail') ? 'bg-[#1e3a8a] text-white shadow-lg' : 'text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white' }}">
                        <x-heroicon-o-book-open class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Learning Path</span>
                    </a>
                    <a href="{{ route('diagnostic') }}" wire:navigate class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('diagnostic') || request()->routeIs('exercises') ? 'bg-[#1e3a8a] text-white shadow-lg' : 'text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white' }}">
                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Exercises</span>
                    </a>
                    <a href="{{ route('test-history') }}" wire:navigate class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('test-history') ? 'bg-[#1e3a8a] text-white shadow-lg' : 'text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white' }}">
                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Reports</span>
                    </a>
                @elseif(auth()->user()->hasRole('dosen'))
                    <a href="{{ route('reports') }}" wire:navigate class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('reports') ? 'bg-[#1e3a8a] text-white shadow-lg' : 'text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white' }}">
                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Laporan Mahasiswa</span>
                    </a>
                @elseif(auth()->user()->hasRole('admin'))
                    <a href="/admin" target="_blank" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition-all duration-200 text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white">
                        <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Admin Panel</span>
                    </a>
                @endif
            </nav>

            <flux:spacer />

            <!-- User Profile Card -->
            <div class="px-3 mt-4 mb-3 w-full">
                <a href="{{ route('settings.profile') }}" wire:navigate class="bg-[#0f2444] hover:bg-[#1e3a8a]/30 transition-colors border border-[#1e3a8a]/50 rounded-2xl p-3 flex items-center space-x-3 shadow-lg shadow-black/20 group cursor-pointer">
                    <div class="w-10 h-10 rounded-full bg-[#1e3a8a] group-hover:bg-[#1e3a8a]/80 transition-colors flex items-center justify-center shrink-0 border border-blue-400/30">
                        <x-heroicon-s-user class="w-5 h-5 text-white" />
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <span class="text-[13px] font-semibold text-white truncate w-full group-hover:text-blue-100 transition-colors">{{ auth()->user()->name }}</span>
                        <span class="text-[11px] text-blue-300/80 truncate w-full">Accounting Student</span>
                    </div>
                </a>
            </div>

            <!-- Dark / Light Mode Toggle -->
            <div class="px-3 w-full"
                 x-data="{ isDark: document.documentElement.classList.contains('dark') }">
                <button
                    @click="toggleTheme(); isDark = document.documentElement.classList.contains('dark')"
                    class="flex items-center justify-between w-full px-3 py-2.5 rounded-xl text-gray-300 hover:bg-[#1e3a8a]/50 hover:text-white transition-all duration-200 group">
                    <div class="flex items-center space-x-3">
                        <!-- Sun icon (light mode) -->
                        <svg x-show="!isDark" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-9h-1M4.34 12h-1m15.07-6.07-.707.707M5.636 18.364l-.707.707M18.364 18.364l-.707-.707M5.636 5.636l-.707-.707M12 5a7 7 0 100 14A7 7 0 0012 5z"/>
                        </svg>
                        <!-- Moon icon (dark mode) -->
                        <svg x-show="isDark" class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        <span class="font-medium text-[14px]" x-text="isDark ? 'Mode Gelap' : 'Mode Terang'"></span>
                    </div>
                    <!-- Toggle pill -->
                    <div class="relative w-10 h-5 rounded-full transition-colors duration-300"
                         :class="isDark ? 'bg-blue-600' : 'bg-yellow-400'">
                        <div class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all duration-300"
                             :class="isDark ? 'left-5' : 'left-0.5'"></div>
                    </div>
                </button>
            </div>

            <!-- Logout Button -->
            <div class="px-3 pb-4 w-full border-t border-blue-900/40 pt-3">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center space-x-3 px-3 py-2 w-full text-gray-300 hover:text-white transition-colors">
                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />
                        <span class="font-medium text-[14px]">Logout</span>
                    </button>
                </form>
            </div>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden bg-[#0a192f] border-b border-[#1e3a8a]/50">
            <flux:sidebar.toggle class="lg:hidden text-white" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    class="text-white"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-[#1e3a8a] text-white">
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
            function applyTheme() {
                const theme = localStorage.getItem('apels-theme');
                if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            }

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

            // Re-apply theme on every Livewire SPA navigation
            document.addEventListener('livewire:navigated', applyTheme);
        </script>
    </body>
</html>
