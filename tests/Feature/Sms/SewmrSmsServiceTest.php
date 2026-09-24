<?php

namespace Tests\Feature\Sms;

use App\Models\Business;
use App\Models\User;
use App\Services\SewmrSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SewmrSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_staff_credentials_to_the_saved_mobile_number(): void
    {
        config()->set('sms.enabled', true);
        config()->set('sms.token', 'test-token');
        config()->set('sms.url', 'https://sms.example.test/api/v1');
        config()->set('sms.sender_id', 'HARDFLOW');
        Http::fake(['https://sms.example.test/*' => Http::response(['success' => true])]);

        $staff = User::factory()->create([
            'email' => 'cashier@example.com',
            'phone' => '+255712345678',
        ]);

        $business = Business::query()->create([
            'name' => 'Mlimani Hardware',
            'slug' => 'mlimani-hardware',
            'code' => 'MLM',
        ]);
        $staff->forceFill(['username' => 'cashier123'])->save();

        app(SewmrSmsService::class)->sendStaffCredentials($staff, $business, 'TemporaryPassword@2026');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://sms.example.test/api/v1/sms/quick-send'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['sender_id'] === 'HARDFLOW'
                && $request['recipients'] === '+255712345678'
                && str_contains($request['message'], 'Company: Mlimani Hardware')
                && str_contains($request['message'], 'Username: cashier123')
                && str_contains($request['message'], 'Temporary Password: TemporaryPassword@2026');
        });
    }

    public function test_it_does_not_contact_the_provider_when_sms_is_disabled(): void
    {
        config()->set('sms.enabled', false);
        Http::fake();

        app(SewmrSmsService::class)->sendPasswordReset(
            User::factory()->create(['phone' => '+255712345678']),
            'NewPassword@2026',
        );

        Http::assertNothingSent();
    }
}
