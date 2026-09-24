<?php

namespace Tests\Feature\Phase1;

use App\Actions\CreateBusinessAction;
use App\Actions\SwitchTenantContextAction;
use App\Enums\PermissionName;
use App\Models\BranchUser;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owners_only_access_their_own_business_and_branch(): void
    {
        [$businessA, $ownerA, $businessB, $ownerB] = $this->createTwoBusinesses();

        $this->assertTrue($ownerA->can('view', $businessA));
        $this->assertFalse($ownerA->can('view', $businessB));
        $this->assertTrue($ownerB->can('view', $businessB));
        $this->assertFalse($ownerB->can('view', $businessA));
        $this->assertTrue($ownerA->can('view', $businessA->branches->first()));
        $this->assertFalse($ownerA->can('view', $businessB->branches->first()));
    }

    public function test_tenant_context_rejects_cross_tenant_id_tampering(): void
    {
        [$businessA, $ownerA, $businessB] = $this->createTwoBusinesses();

        $this->expectException(AuthorizationException::class);

        app(TenantContext::class)->setForUser($ownerA, $businessB, $businessA->branches->first());
    }

    public function test_user_cannot_select_a_branch_without_explicit_branch_access(): void
    {
        [$business, $owner] = $this->createBusiness('Business A', 'BSA');
        $staff = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'joined_at' => now(),
        ]);

        $this->expectException(AuthorizationException::class);

        app(TenantContext::class)->setForUser($staff, $business, $business->branches->first());
    }

    public function test_tenant_switch_stores_only_an_authorized_context(): void
    {
        [$business, $owner] = $this->createBusiness('Business A', 'BSA');
        $branch = $business->branches->first();

        app(SwitchTenantContextAction::class)->execute($owner, $business, $branch);

        $this->assertSame($business->id, session('tenant.business_id'));
        $this->assertSame($branch->id, session('tenant.branch_id'));
        $this->assertTrue(app(TenantContext::class)->business()->is($business));
        $this->assertTrue(app(TenantContext::class)->branch()->is($branch));
    }

    public function test_disabled_user_business_membership_or_branch_is_rejected(): void
    {
        [$business, $owner] = $this->createBusiness('Business A', 'BSA');
        $branch = $business->branches->first();

        $owner->update(['is_active' => false, 'disabled_at' => now()]);
        $this->assertFalse($owner->fresh()->hasActiveMembership($business));

        $owner->update(['is_active' => true, 'disabled_at' => null]);
        $business->update(['is_active' => false, 'disabled_at' => now()]);
        $this->assertFalse($owner->fresh()->hasActiveMembership($business));

        $business->update(['is_active' => true, 'disabled_at' => null]);
        BranchUser::query()->where('user_id', $owner->id)->update(['is_active' => false]);
        $this->assertFalse($owner->fresh()->hasActiveBranchAccess($branch));
    }

    public function test_permissions_are_scoped_to_the_role_business(): void
    {
        [$businessA, $ownerA, $businessB] = $this->createTwoBusinesses();

        $this->assertTrue($ownerA->hasPermissionInBusiness(PermissionName::UsersView, $businessA));
        $this->assertFalse($ownerA->hasPermissionInBusiness(PermissionName::UsersView, $businessB));
    }

    /**
     * @return array{0:Business, 1:User, 2:Business, 3:User}
     */
    private function createTwoBusinesses(): array
    {
        [$businessA, $ownerA] = $this->createBusiness('Business A', 'BSA');
        [$businessB, $ownerB] = $this->createBusiness('Business B', 'BSB');

        return [$businessA, $ownerA, $businessB, $ownerB];
    }

    /**
     * @return array{0:Business, 1:User}
     */
    private function createBusiness(string $name, string $code): array
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, [
            'name' => $name,
            'code' => $code,
        ]);

        return [$business, $owner];
    }
}
