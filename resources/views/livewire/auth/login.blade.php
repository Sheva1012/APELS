<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $user = Auth::user();
        $defaultRoute = match (true) {
            $user->hasRole('admin')     => route('filament.admin.pages.dashboard', absolute: false),
            $user->hasRole('dosen')     => route('filament.admin.pages.dashboard', absolute: false), // /reports belum ada (Task 20.1) — sementara redirect ke admin panel
            $user->hasRole('mahasiswa') => route('dashboard', absolute: false),
            default                     => route('dashboard', absolute: false),
        };

        $this->redirectIntended(default: $defaultRoute, navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="text-center space-y-2">
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Sign in to Portal</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Enter your email and password to continue</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-5" id="loginForm">
        <!-- Email Address -->
        <div class="form-element group">
            <flux:input wire:model="email" label="Email Address" type="email" name="email" required autofocus autocomplete="email" placeholder="student@gmail.com" class="transition-transform duration-300 group-focus-within:scale-[1.02]" />
        </div>

        <!-- Password -->
        <div class="form-element group relative" x-data="{ show: false }">
            <flux:input
                wire:model="password"
                label="Password"
                x-bind:type="show ? 'text' : 'password'"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter password"
                class="transition-transform duration-300 group-focus-within:scale-[1.02]"
            />
            
            <button type="button" class="absolute right-3 top-[32px] text-gray-500 hover:text-indigo-500 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors duration-300" @click="show = !show" title="Show/Hide Password">
                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                <svg x-cloak x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
            </button>

            @if (Route::has('password.request'))
                <x-text-link class="absolute right-0 top-0 text-indigo-600 dark:text-indigo-400 hover:text-indigo-500" href="{{ route('password.request') }}">
                    Forgot password?
                </x-text-link>
            @endif
        </div>

        <!-- Remember Me -->
        <div class="form-element">
            <flux:checkbox wire:model="remember" label="Remember me" />
        </div>

        <div class="form-element flex items-center justify-end mt-2">
            <flux:button variant="primary" type="submit" class="w-full bg-linear-to-r from-blue-600 to-teal-600 hover:from-blue-500 hover:to-teal-500 text-white border-none shadow-lg shadow-blue-500/30 transition-all duration-300 hover:scale-[1.02] active:scale-95">
                Sign In
            </flux:button>
        </div>
    </form>

    <div class="form-element space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
        Don't have an account?
        <x-text-link href="{{ route('register') }}" class="font-semibold hover:text-indigo-500 transition-colors">Sign up now</x-text-link>
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
