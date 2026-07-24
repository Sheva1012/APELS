<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @stack('styles')
        {{-- Dark mode script --}}
        <script>
            (function() {
                const theme = localStorage.getItem('apels-theme');
                if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                } else {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
    </head>
    <body class="bg-white dark:bg-[#121212] text-zinc-900 dark:text-zinc-100 font-sans antialiased m-0 p-0 overflow-hidden flex flex-col h-screen">
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
            document.addEventListener('livewire:navigated', applyTheme);
        </script>
    </body>
</html>
