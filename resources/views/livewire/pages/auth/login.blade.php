<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        // Authentication changes the session ID. Use a full-page redirect so the
        // next request always carries the regenerated session cookie reliably,
        // including on mobile browsers and behind production proxies/CDNs.
        $this->redirectIntended(default: route('dashboard', absolute: false));
    }
}; ?>

<div x-data="{ submitting: false }">
    <div class="mb-10">
        <div class="mb-8 hidden items-center gap-3 lg:flex">
            <img src="{{ asset('images/hardflow-logo.png') }}" alt="HardFlow" class="size-11 rounded-xl shadow-lg shadow-blue-700/20">
            <span class="text-2xl font-extrabold tracking-tight">HardFlow</span>
        </div>
        <p class="text-sm font-bold uppercase tracking-[.18em] text-blue-700">Welcome to HardFlow</p>
        <h2 class="mt-3 text-4xl font-extrabold tracking-[-.035em] text-slate-950 sm:text-5xl">Welcome back</h2>
        <p class="mt-3 text-lg text-slate-500">Sign in to your account to continue.</p>
    </div>

    <x-auth-session-status class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6" @submit="submitting = true" novalidate>
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address or phone number</label>
            <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@hardflow.co or 255712345678" class="block h-14 w-full rounded-2xl border-slate-200 bg-white px-5 text-base text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-4">
                <label for="password" class="block text-sm font-semibold text-slate-800">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-semibold text-blue-700 transition hover:text-blue-900 focus:outline-none focus:underline" href="{{ route('password.request') }}" wire:navigate>Forgot password?</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" class="block h-14 w-full rounded-2xl border-slate-200 bg-white px-5 pr-14 text-base text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600" />
                <button type="button" onclick="const input=this.previousElementSibling; input.type=input.type === 'password' ? 'text' : 'password'; this.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');" class="absolute inset-y-0 right-0 flex w-14 items-center justify-center text-slate-500 transition hover:text-blue-700 focus:outline-none" aria-label="Show password">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember" class="flex w-fit cursor-pointer items-center gap-3 text-sm font-medium text-slate-600">
            <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
            Keep me signed in
        </label>

        <button type="submit" :disabled="submitting" class="flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-blue-700 px-6 font-bold text-white shadow-xl shadow-blue-700/20 transition hover:-translate-y-0.5 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
            <svg x-show="! submitting" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 8l4 4-4 4M18 12H8M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/></svg>
            <svg x-show="submitting" x-cloak style="display: none" class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 1-4 4H4Z"/></svg>
            <span x-show="! submitting">Sign in</span>
            <span x-show="submitting" x-cloak style="display: none">Signing in&hellip;</span>
        </button>
    </form>

    <p class="mt-8 text-center text-xs leading-5 text-slate-400">Protected access for authorised HardFlow users.</p>
</div>
