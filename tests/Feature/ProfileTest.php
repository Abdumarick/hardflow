<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('username', 'testuser')
            ->set('email', 'test@example.com')
            ->set('phone', '0712345678')
            ->set('current_password', 'password')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('testuser', $user->username);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('255712345678', $user->phone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_sensitive_profile_details_require_the_current_password(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);
        $this->actingAs($user);

        Volt::test('profile.update-profile-information-form')
            ->set('name', $user->name)
            ->set('username', 'adminuser')
            ->set('email', $user->email)
            ->set('phone', '0712345678')
            ->call('updateProfileInformation')
            ->assertHasErrors('current_password');

        Volt::test('profile.update-profile-information-form')
            ->set('name', $user->name)
            ->set('username', 'adminuser')
            ->set('email', $user->email)
            ->set('phone', '0712345678')
            ->set('current_password', 'password')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('adminuser', $user->refresh()->username);
    }

    public function test_super_admin_can_sign_out_other_sessions(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        config()->set('session.driver', 'database');
        DB::table('sessions')->insert(['id' => 'another-device-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Test browser', 'payload' => 'payload', 'last_activity' => now()->timestamp]);

        Volt::test('profile.manage-sessions-form')
            ->set('password', 'password')
            ->call('logoutOtherDevices')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'another-device-session']);
    }
}
