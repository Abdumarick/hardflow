<?php

namespace Tests\Feature\Phase8;

use App\Actions\CreateBusinessAction;
use App\Actions\CreateRoleAction;
use App\Actions\CreateStaffAction;
use App\Enums\PermissionName;
use App\Models\Permission;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_every_quick_create_action_and_destinations_open_in_create_mode(): void
    {
        [$business, $owner, $branch] = $this->fixture();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        $this->actingAs($owner)->withSession($session)->get(route('dashboard'))->assertOk()
            ->assertSeeText('Quick Create')->assertSeeText('New Sale')->assertSeeText('Create Quotation')
            ->assertSeeText('New Purchase')->assertSeeText('Receive Stock')->assertSeeText('Record Expense')
            ->assertSeeText('Receive Payment')->assertSeeText('Add Product');

        $this->actingAs($owner)->withSession($session)->get(route('owner.sales.index', ['mode' => 'quotation']))
            ->assertOk()->assertSeeText('Current Quotation')->assertSee('name="document_type" value="quotation"', false);
        $this->actingAs($owner)->withSession($session)->get(route('owner.purchases.index', ['tab' => 'new']))
            ->assertOk()->assertSee("tab: 'new'", false);
        $this->actingAs($owner)->withSession($session)->get(route('owner.catalogue.index', ['create' => 'product']))
            ->assertOk()->assertSee('addOpen: true', false);
    }

    public function test_quick_create_only_shows_actions_allowed_by_the_users_role(): void
    {
        [$business, $owner, $branch] = $this->fixture();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $permissionIds = Permission::query()->whereIn('slug', [PermissionName::SalesView->value, PermissionName::SalesCreate->value])->pluck('id')->all();
        $role = app(CreateRoleAction::class)->execute($owner, $business, 'Sales Entry Only', $permissionIds);
        $staff = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Restricted Seller', 'email' => 'seller@example.com', 'password' => 'restricted-password', 'branch_ids' => [$branch->id], 'role_ids' => [$role->id]]);

        $this->actingAs($staff)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->get(route('dashboard'))->assertOk()->assertSeeText('Quick Create')->assertSeeText('New Sale')
            ->assertDontSeeText('Create Quotation')->assertDontSeeText('New Purchase')->assertDontSeeText('Receive Stock')
            ->assertDontSeeText('Record Expense')->assertDontSeeText('Receive Payment')->assertDontSeeText('Add Product');
    }

    private function fixture(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Quick Hardware', 'code' => 'QUICK']);

        return [$business, $owner, $business->branches->first()];
    }
}
