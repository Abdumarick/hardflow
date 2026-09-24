<?php

use App\Models\User;
use App\Services\SmsPasswordResetService;
use App\Support\PhoneNumber;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $identifier = '';

    /**
     * Send a temporary password by SMS to the registered mobile number.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate(['identifier' => ['required', 'string', 'max:255']]);

        $identifier = trim($this->identifier);
        $phone = PhoneNumber::normalize($identifier);
        $user = User::query()
            ->where('email', $identifier)
            ->when($phone, fn ($query) => $query->orWhere('phone', $phone))
            ->where('is_active', true)
            ->first();

        if (! $user || ! app(SmsPasswordResetService::class)->request($user)) {
            $this->addError('identifier', 'We could not send a password reset SMS. If you have already made three requests, contact your business owner or Super Admin.');

            return;
        }

        $this->reset('identifier');
        session()->flash('status', 'A temporary password was sent to your registered mobile number.');
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        Enter your email address or phone number. A temporary password will be sent only to the mobile number registered on your account.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('status'))
        <a href="{{ route('login') }}" wire:navigate class="mb-5 inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-800">
            Go to Login
        </a>
    @endif

    <form wire:submit="sendPasswordResetLink">
        <!-- Account identifier -->
        <div>
            <x-input-label for="identifier" value="Email address or phone number" />
            <x-text-input wire:model="identifier" id="identifier" class="block mt-1 w-full" type="text" name="identifier" required autofocus placeholder="you@example.com or 255712345678" />
            <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Send temporary password by SMS
            </x-primary-button>
        </div>
    </form>
</div>
