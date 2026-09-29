<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public int $otherSessions = 0;

    public function mount(): void
    {
        $this->refreshOtherSessions();
    }

    public function logoutOtherDevices(): void
    {
        $this->validate(['password' => ['required', 'string', 'current_password']]);

        if (config('session.driver') !== 'database') {
            $this->addError('password', 'Session management is unavailable with the current session storage.');

            return;
        }

        DB::table(config('session.table'))
            ->where('user_id', Auth::id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->reset('password');
        $this->refreshOtherSessions();
        $this->dispatch('other-sessions-logged-out');
    }

    private function refreshOtherSessions(): void
    {
        $this->otherSessions = config('session.driver') === 'database'
            ? DB::table(config('session.table'))->where('user_id', Auth::id())->where('id', '!=', session()->getId())->count()
            : 0;
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-black text-slate-950">Active sessions</h2>
        <p class="mt-1 text-sm leading-6 text-slate-500">Sign out this account from other browsers and devices.</p>
    </header>

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-sm font-bold text-slate-900">{{ $otherSessions }} other active {{ \Illuminate\Support\Str::plural('session', $otherSessions) }}</p>
        <p class="mt-1 text-xs text-slate-500">Your current device will stay signed in.</p>
    </div>

    <form wire:submit="logoutOtherDevices" class="mt-5 space-y-4">
        <div>
            <x-input-label for="sessions_password" value="Current password" />
            <input wire:model="password" id="sessions_password" name="password" type="password" class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" autocomplete="current-password" required />
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center gap-4">
            <button class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-rose-700 hover:bg-rose-100">Sign out other devices</button>
            <x-action-message class="text-sm font-medium text-emerald-600" on="other-sessions-logged-out">Other sessions signed out.</x-action-message>
        </div>
    </form>
</section>
