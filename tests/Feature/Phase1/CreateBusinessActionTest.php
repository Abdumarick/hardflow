<?php

namespace Tests\Feature\Phase1;

use App\Actions\CreateBusinessAction;
use App\Enums\DefaultRole;
use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CreateBusinessActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_two_complete_businesses(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $action = app(CreateBusinessAction::class);

        $businessA = $action->execute($superAdmin, $ownerA, [
            'name' => 'Business A',
            'code' => 'BSA',
            'branch_name' => 'Business A Main',
        ]);
        $businessB = $action->execute($superAdmin, $ownerB, [
            'name' => 'Business B',
            'code' => 'BSB',
            'branch_name' => 'Business B Main',
        ]);

        $this->assertCount(2, Business::all());
        $this->assertNotSame($businessA->id, $businessB->id);

        foreach ([[$businessA, $ownerA], [$businessB, $ownerB]] as [$business, $owner]) {
            $this->assertCount(1, $business->branches);
            $this->assertTrue($business->branches->first()->is_main);
            $this->assertTrue($owner->hasActiveMembership($business));
            $this->assertTrue($owner->hasActiveBranchAccess($business->branches->first()));
            $this->assertCount(count(DefaultRole::cases()), $business->roles);
            $this->assertEqualsCanonicalizing(['allow_selling_below_cost', 'credit_limit_policy', 'currency', 'invoice_template', 'locale', 'timezone'], $business->settings->pluck('key')->all());
            $this->assertDatabaseCount('number_sequences', 10);
            $this->assertTrue($owner->hasPermissionInBusiness(PermissionName::UsersManageRoles, $business));
        }

        $this->assertDatabaseCount('permissions', count(PermissionName::cases()));
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertTrue(AuditLog::query()->where('action', 'business.created')->exists());
    }

    public function test_non_super_admin_cannot_create_a_business(): void
    {
        $actor = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(CreateBusinessAction::class)->execute($actor, $actor, [
            'name' => 'Forbidden Business',
            'code' => 'NOPE',
        ]);
    }

    public function test_business_creation_rolls_back_everything_when_provisioning_fails(): void
    {
        $permissionCount = DB::table('permissions')->count();
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();

        Role::creating(function (): never {
            throw new RuntimeException('Simulated role provisioning failure.');
        });

        try {
            app(CreateBusinessAction::class)->execute($superAdmin, $owner, [
                'name' => 'Rolled Back Business',
                'code' => 'ROLL',
            ]);
            $this->fail('The simulated provisioning failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated role provisioning failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('businesses', 0);
        $this->assertDatabaseCount('branches', 0);
        $this->assertDatabaseCount('business_users', 0);
        $this->assertDatabaseCount('branch_users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('permissions', $permissionCount);
        $this->assertDatabaseCount('business_settings', 0);
        $this->assertDatabaseCount('number_sequences', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
