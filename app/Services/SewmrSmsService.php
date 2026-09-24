<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SewmrSmsService
{
    public function sendStaffCredentials(User $staff, Business $business, string $plainTextPassword): void
    {
        $role = $staff->roles->firstWhere('pivot.business_id', $business->id)?->name ?? 'Staff';

        $this->send($staff, sprintf(
            "Welcome to HardFlow!\n\nCompany: %s\n\nUsername: %s\n\nPhone: %s\n\nRole: %s\n\nTemporary Password: %s\n\nLogin: %s/login\n\nPlease change your password after login.",
            $business->name,
            $staff->username ?? $staff->email,
            PhoneNumber::display($staff->phone),
            $role,
            $plainTextPassword,
            rtrim((string) config('app.url'), '/'),
        ), 'staff credentials');
    }

    public function sendPasswordReset(User $staff, string $plainTextPassword): void
    {
        $this->send($staff, sprintf(
            'Your HardFlow password has been reset. Username: %s Temporary Password: %s Please sign in, then change your password from your profile.',
            $staff->username ?? $staff->email,
            $plainTextPassword,
        ), 'password reset');
    }

    private function send(User $staff, string $message, string $purpose): void
    {
        if (! config('sms.enabled') || blank(config('sms.token')) || blank($staff->phone)) {
            return;
        }

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('sms.token'))
                ->timeout(10)
                ->post(config('sms.url').'/sms/quick-send', [
                    'sender_id' => config('sms.sender_id'),
                    'message' => $message,
                    'recipients' => $staff->phone,
                    'schedule' => false,
                ]);

            if ($response->failed() || $response->json('success') === false) {
                Log::warning('SMS notification could not be delivered.', [
                    'purpose' => $purpose,
                    'user_id' => $staff->id,
                    'phone' => $staff->phone,
                    'status' => $response->status(),
                ]);
            }
        } catch (ConnectionException $exception) {
            Log::warning('SMS notification request failed.', [
                'purpose' => $purpose,
                'user_id' => $staff->id,
                'phone' => $staff->phone,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
