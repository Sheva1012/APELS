<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $nim = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'nim' => ['required', 'string', 'max:20', 'unique:' . User::class . ',nim'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        // Req 1.1: setiap user baru lewat form publik = mahasiswa.
        $user->assignRole('mahasiswa');

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Create an account" description="Enter your details below to create your account" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6" id="registerForm">
        <!-- Name -->
        <div class="form-element grid gap-2 group">
            <flux:input wire:model="name" id="name" label="{{ __('Name') }}" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" class="transition-transform duration-300 group-focus-within:scale-[1.02]" />
        </div>

        <!-- Email Address -->
        <div class="form-element grid gap-2 group">
            <flux:input wire:model="email" id="email" label="{{ __('Email address') }}" type="email" name="email" required autocomplete="email" placeholder="email@example.com" class="transition-transform duration-300 group-focus-within:scale-[1.02]" />
        </div>

        <!-- NIM -->
        <div class="form-element grid gap-2 group">
            <flux:input wire:model="nim" id="nim" label="{{ __('NIM') }}" type="text" name="nim" required autocomplete="off" placeholder="Nomor Induk Mahasiswa" class="transition-transform duration-300 group-focus-within:scale-[1.02]" />
        </div>

        <!-- Password -->
        <div class="form-element grid gap-2 group">
            <flux:input
                wire:model="password"
                id="password"
                label="{{ __('Password') }}"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
                class="transition-transform duration-300 group-focus-within:scale-[1.02]"
            />
        </div>

        <!-- Confirm Password -->
        <div class="form-element grid gap-2 group">
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
                class="transition-transform duration-300 group-focus-within:scale-[1.02]"
            />
        </div>

        <div class="form-element flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full bg-linear-to-r from-blue-600 to-teal-600 hover:from-blue-500 hover:to-teal-500 text-white border-none shadow-lg shadow-blue-500/30 transition-all duration-300 hover:scale-[1.02] active:scale-95">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="form-element space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}" class="font-semibold hover:text-indigo-500 transition-colors">Log in</x-text-link>
    </div>
</div>

<!-- JS Animation Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const elements = document.querySelectorAll('.form-element');
        elements.forEach((el, index) => {
            // Initial state
            el.style.opacity = '0';
            el.style.transform = 'translateY(15px)';
            el.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            
            // Animate with stagger
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 + (index * 80));
        });
    });
</script>
