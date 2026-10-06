<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login')
            ->assertSee('id="password" type="password"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('<style>[x-cloak]{display:none!important}</style>', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_authenticate_using_the_standard_login_post(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_authenticate_using_phone_with_the_standard_login_post(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);

        $response = $this->post(route('login.store'), [
            'email' => '0712345678',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_authenticate_using_their_tanzanian_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '255712345678']);

        Volt::test('pages.auth.login')
            ->set('form.email', '0712345678')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation')
            ->assertSee('data-mobile-navigation-backdrop style="display: none"', false)
            ->assertSee('onclick="window.hardflowSetMobileNavigation(true)"', false)
            ->assertSee('onclick="window.hardflowSetMobileNavigation(false)"', false)
            ->assertSee('w-64 -translate-x-full', false)
            ->assertSee('lg:translate-x-0', false)
            ->assertSee('window.hardflowSetMobileNavigation', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('onclick="window.hardflowToggleTheme()"', false)
            ->assertSee('window.hardflowApplyTheme', false);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_users_can_log_out_using_the_standard_logout_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
