<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sms.enabled', true);
        config()->set('sms.token', 'test-token');
        config()->set('sms.url', 'https://sms.example.test/api/v1');
        Http::fake(['https://sms.example.test/*' => Http::response(['success' => true])]);
    }

    public function test_password_reset_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertStatus(200);
    }

    public function test_temporary_password_can_be_requested_using_an_email_address(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);

        Volt::test('pages.auth.forgot-password')
            ->set('identifier', $user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors()
            ->assertSee('Go to Login');

        $this->assertFalse(Hash::check('password', $user->fresh()->password));
        Http::assertSent(fn ($request) => $request['recipients'] === '255712345678'
            && str_contains($request['message'], 'Temporary Password: '));
    }

    public function test_temporary_password_can_be_requested_using_a_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);

        Volt::test('pages.auth.forgot-password')
            ->set('identifier', '0712345678')
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();

        Http::assertSent(fn ($request) => $request['recipients'] === $user->phone);
    }

    public function test_password_is_not_changed_when_the_reset_sms_cannot_be_delivered(): void
    {
        config()->set('sms.enabled', false);
        $user = User::factory()->create(['phone' => '255712345678']);

        Volt::test('pages.auth.forgot-password')
            ->set('identifier', $user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors()
            ->assertSee('Go to Login');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        Http::assertNothingSent();
    }

    public function test_password_reset_requests_are_rate_limited_without_permanently_locking_the_account(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);

        foreach (range(1, 3) as $_) {
            Volt::test('pages.auth.forgot-password')
                ->set('identifier', $user->email)
                ->call('sendPasswordResetLink')
                ->assertHasNoErrors();
        }

        Volt::test('pages.auth.forgot-password')
            ->set('identifier', $user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors()
            ->assertSee('Go to Login');

        $this->assertNull($user->fresh()->password_reset_blocked_at);
        Http::assertSentCount(3);
    }
}
