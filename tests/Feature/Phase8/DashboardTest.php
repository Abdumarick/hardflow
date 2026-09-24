<?php

namespace Tests\Feature\Phase8;

use App\Actions\CreateBusinessAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_branch_renders_the_live_business_dashboard(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Dashboard Hardware', 'code' => 'DASH']);
        $branch = $business->branches->first();

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Business dashboard')
            ->assertSeeText('Seven-day sales trend')
            ->assertSeeText('Recent sales')
            ->assertSee('Toggle light and dark mode')
            ->assertSee("\$dispatch('dashboard-appearance')", false)
            ->assertSee('@dashboard-appearance.window="appearanceOpen = true"', false)
            ->assertSeeText('EN');
    }

    public function test_dashboard_uses_the_selected_business_locale(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Biashara Hardware', 'code' => 'SWA']);
        $business->update(['locale' => 'sw', 'timezone' => 'Africa/Nairobi', 'currency' => 'USD']);
        $branch = $business->branches->first();

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="sw"', false)
            ->assertSeeText('Dashibodi ya biashara')
            ->assertSeeText('Mauzo ya hivi karibuni')
            ->assertSeeText('USD');
    }

    public function test_workspace_chooser_uses_translated_structural_labels(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Chooser Hardware', 'code' => 'CHOOSE']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Choose your workspace')
            ->assertSeeText('1 active branch')
            ->assertSeeText($business->name);
    }

    public function test_user_can_save_and_restore_each_dashboard_appearance(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Layout Hardware', 'code' => 'LAYOUT']);
        $branch = $business->branches->first();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        $this->actingAs($owner)->withSession($session)
            ->put(route('dashboard.appearance.update'), ['dashboard_layout' => 'smartflow'])
            ->assertRedirect();

        $this->assertSame('smartflow', $owner->refresh()->dashboard_layout);
        $this->actingAs($owner)->withSession($session)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Dashboard appearance')
            ->assertSeeText('Complete business flow')
            ->assertSeeText('Products')
            ->assertSeeText('Inventory')
            ->assertSeeText('Purchases')
            ->assertSeeText('Payments')
            ->assertSeeText('Reports')
            ->assertSeeText('Settings');

        $this->actingAs($owner)->withSession($session)
            ->put(route('dashboard.appearance.update'), ['dashboard_layout' => 'cards'])
            ->assertRedirect();
        $this->assertSame('cards', $owner->refresh()->dashboard_layout);
        $this->actingAs($owner)->withSession($session)->get(route('dashboard'))
            ->assertOk()->assertSeeText('All business components')->assertSeeText('Component Cards');

        $this->actingAs($owner)->withSession($session)
            ->put(route('dashboard.appearance.update'), ['dashboard_layout' => 'classic'])
            ->assertRedirect();
        $this->assertSame('classic', $owner->refresh()->dashboard_layout);

        $this->actingAs($owner)->put(route('dashboard.appearance.update'), ['dashboard_layout' => 'unknown'])
            ->assertSessionHasErrors('dashboard_layout');
    }

    public function test_user_can_override_interface_language_from_the_top_navigation(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Language Hardware', 'code' => 'LANG']);
        $branch = $business->branches->first();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        $this->actingAs($owner)->withSession($session)->put(route('interface.locale.update'), ['locale' => 'sw'])->assertRedirect();
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSeeText('Dashibodi ya biashara')->assertSeeText('SW');
        $this->actingAs($owner)->put(route('interface.locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
    }
}
