<?php

namespace Tests\Feature\Phase1;

use App\Actions\CreateBusinessAction;
use App\Actions\DisableBusinessAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_view_another_business_by_changing_url_id(): void
    {
        [$businessA, $ownerA] = $this->createBusiness('Business A', 'BSA');
        [$businessB] = $this->createBusiness('Business B', 'BSB');

        $this->actingAs($ownerA)
            ->get(route('super-admin.businesses.show', $businessB))
            ->assertForbidden();

        $this->actingAs($ownerA)
            ->post(route('tenant.select', $businessB), [
                'branch' => $businessA->branches->first()->public_id,
            ])
            ->assertForbidden()
            ->assertSeeText('You do not have access to this page.')
            ->assertSeeText('Go to Dashboard');
    }

    public function test_member_without_permission_cannot_bypass_staff_endpoint(): void
    {
        [$business] = $this->createBusiness('Business A', 'BSA');
        $member = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->withSession(['tenant.business_id' => $business->id])
            ->post(route('owner.staff.store'), [
                'name' => 'Forbidden Staff',
                'email' => 'forbidden@example.com',
                'phone' => '255712345678',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'branch_ids' => [$business->branches->first()->id],
                'role_ids' => [$business->roles->first()->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.com']);
    }

    public function test_disabled_user_is_rejected_from_protected_endpoint(): void
    {
        [$business, $owner] = $this->createBusiness('Business A', 'BSA');
        $owner->update(['is_active' => false, 'disabled_at' => now()]);

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id])
            ->get(route('owner.staff.index'))
            ->assertForbidden();
    }

    public function test_disabled_business_is_rejected_from_protected_endpoint(): void
    {
        [$business, $owner, $superAdmin] = $this->createBusiness('Business A', 'BSA');
        app(DisableBusinessAction::class)->execute($superAdmin, $business, 'Account suspended.');

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id])
            ->get(route('owner.staff.index'))
            ->assertForbidden();
    }

    public function test_super_admin_and_owner_management_pages_render(): void
    {
        [$business, $owner, $superAdmin] = $this->createBusiness('Business A', 'BSA');
        $branch = $business->branches->first();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.businesses.index'))
            ->assertOk()
            ->assertSee('Business A');

        $this->actingAs($owner)
            ->post(route('tenant.select', $business), ['branch' => $branch->public_id])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($owner)
            ->withSession([
                'tenant.business_id' => $business->id,
                'tenant.branch_id' => $branch->id,
            ])
            ->get(route('owner.staff.index'))
            ->assertOk()
            ->assertSee('Staff management');

        $this->actingAs($owner)
            ->withSession([
                'tenant.business_id' => $business->id,
                'tenant.branch_id' => $branch->id,
            ])
            ->get(route('owner.roles.index'))
            ->assertOk()
            ->assertSee('Roles and permissions');
    }

    /**
     * @return array{0:Business, 1:User, 2:User}
     */
    private function createBusiness(string $name, string $code): array
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, [
            'name' => $name,
            'code' => $code,
        ]);

        return [$business, $owner, $superAdmin];
    }
}
