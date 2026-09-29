<?php

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public string $current_password = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->username = Auth::user()->username ?? '';
        $this->email = Auth::user()->email;
        $this->phone = Auth::user()->phone ? PhoneNumber::display(Auth::user()->phone) : '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'lowercase', 'min:3', 'max:30', 'regex:/^[a-z0-9]+$/', Rule::unique(User::class)->ignore($user->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $validated['username'] = blank($validated['username']) ? null : $validated['username'];
        $validated['phone'] = PhoneNumber::normalize($validated['phone']);
        if (filled($this->phone) && ! $validated['phone']) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Tanzanian mobile number.']);
        }
        if ($validated['phone'] && User::query()->where('phone', $validated['phone'])->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered.']);
        }
        $sensitiveDetailsChanged = $validated['username'] !== $user->username
            || $validated['email'] !== $user->email
            || $validated['phone'] !== $user->phone;
        if ($sensitiveDetailsChanged && ! Hash::check($this->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Enter your current password to change sign-in or recovery details.']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->reset('current_password');
        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-black text-slate-950">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-500">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" value="Username" />
            <input wire:model="username" id="username" name="username" type="text" class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" autocomplete="username" placeholder="Optional sign-in username" />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <input wire:model="email" id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-700">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="rounded text-sm font-semibold text-blue-700 underline hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" value="Phone number" />
            <input wire:model="phone" id="phone" name="phone" type="tel" inputmode="tel" class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" autocomplete="tel" placeholder="0712 345 678" />
            <p class="mt-1 text-xs text-slate-500">Use a Tanzanian mobile number for SMS account recovery.</p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
            <x-input-label for="profile_current_password" value="Current password" />
            <input wire:model="current_password" id="profile_current_password" name="current_password" type="password" class="mt-1 block w-full rounded-xl border-amber-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600" autocomplete="current-password" />
            <p class="mt-1 text-xs text-amber-800">Required only when changing your username, email address, or phone number.</p>
            <x-input-error class="mt-2" :messages="$errors->get('current_password')" />
        </div>

        <div class="flex items-center gap-4">
            <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white hover:bg-blue-800">{{ __('Save') }}</button>

            <x-action-message class="me-3" on="profile-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
