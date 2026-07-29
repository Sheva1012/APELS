<x-layouts.app.sidebar>
    <flux:main :container="false" class="w-full max-w-none !p-0 !m-0 overflow-hidden h-[calc(100vh)] lg:h-screen">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
