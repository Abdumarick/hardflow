<?php

namespace Tests\Feature\Phase1;

use App\Actions\CreateBusinessAction;
use App\Actions\CreateRoleAction;
use App\Actions\CreateStaffAction;
use App\Actions\DisableStaffAction;
use App\Actions\DisableUserAction;
use App\Actions\EnableStaffAction;
use App\Actions\ResetStaffPasswordAction;
use App\Actions\SyncRolePermissionsAction;
use App\Actions\UpdateStaffAccessAction;
use App\Actions\UpdateStaffDetailsAction;
use App\Enums\DefaultRole;
use App\Enums\PermissionName;
use App\Models\Business;
use App\Models\Permission;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ManagementActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_disable_and_reactivate_staff_without_deleting_identity(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $cashierRole = $business->roles->firstWhere('slug', DefaultRole::Cashier->value);

        $staff = app(CreateStaffAction::class)->execute($owner, $business, [
            'name' => 'Cashier One',
            'email' => 'cashier@example.com',
            'password' => 'secure-password',
            'branch_ids' => [$branch->id],
            'role_ids' => [$cashierRole->id],
        ]);

        $this->assertTrue($staff->hasActiveMembership($business));
        $this->assertTrue($staff->hasActiveBranchAccess($branch));
        $this->assertDatabaseHas('user_roles', [
            'business_id' => $business->id,
            'user_id' => $staff->id,
            'role_id' => $cashierRole->id,
        ]);

        app(DisableStaffAction::class)->execute($owner, $business, $staff, 'Employment ended.');

        $this->assertNotNull($staff->fresh());
        $this->assertTrue($staff->fresh()->is_active);
        $this->assertFalse($staff->fresh()->hasActiveMembership($business));
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'subject_id' => $staff->id,
            'action' => 'staff.disabled',
        ]);

        app(EnableStaffAction::class)->execute($owner, $business, $staff, 'Returned to employment.');

        $reactivated = $staff->fresh();
        $this->assertTrue($reactivated->hasActiveMembership($business));
        $this->assertTrue($reactivated->hasActiveBranchAccess($branch));
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'subject_id' => $staff->id,
            'action' => 'staff.enabled',
        ]);
    }

    public function test_owner_can_create_custom_role_and_add_or_remove_permissions(): void
    {
        [$business, $owner] = $this->createBusiness();
        $view = Permission::query()->where('slug', PermissionName::UsersView)->firstOrFail();
        $create = Permission::query()->where('slug', PermissionName::UsersCreate)->firstOrFail();

        $role = app(CreateRoleAction::class)->execute($owner, $business, 'Senior Cashier', [$view->id]);

        $this->assertFalse($role->is_system);
        $this->assertTrue($role->permissions->contains($view));

        $role = app(SyncRolePermissionsAction::class)->execute(
            $owner,
            $business,
            $role,
            [$create->id],
        );

        $this->assertFalse($role->permissions->contains($view));
        $this->assertTrue($role->permissions->contains($create));
    }

    public function test_user_without_permission_cannot_call_staff_action_directly(): void
    {
        [$business] = $this->createBusiness();
        $unauthorizedUser = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(CreateStaffAction::class)->execute($unauthorizedUser, $business, [
            'name' => 'Forbidden Staff',
            'email' => 'forbidden@example.com',
            'password' => 'secure-password',
            'branch_ids' => [$business->branches->first()->id],
            'role_ids' => [$business->roles->first()->id],
        ]);
    }

    public function test_staff_creator_cannot_assign_the_shop_owner_role(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $creator = User::factory()->create();
        $creatorRole = app(CreateRoleAction::class)->execute(
            $owner,
            $business,
            'Staff Creator',
            [Permission::query()->where('slug', PermissionName::UsersCreate)->firstOrFail()->id],
        );
        DB::table('business_users')->insert(['business_id' => $business->id, 'user_id' => $creator->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branch_users')->insert(['business_id' => $business->id, 'branch_id' => $branch->id, 'user_id' => $creator->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_roles')->insert(['business_id' => $business->id, 'user_id' => $creator->id, 'role_id' => $creatorRole->id, 'created_at' => now(), 'updated_at' => now()]);
        app(TenantContext::class)->setForUser($creator, $business, $branch);

        $this->expectException(ValidationException::class);

        app(CreateStaffAction::class)->execute($creator, $business, [
            'name' => 'Escalated User',
            'email' => 'escalated@example.com',
            'password' => 'secure-password',
            'branch_ids' => [$branch->id],
            'role_ids' => [$business->roles->firstWhere('slug', DefaultRole::Owner->value)->id],
        ]);
    }

    public function test_super_admin_can_disable_global_user_and_disabled_user_cannot_log_in(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create(['email' => 'disabled@example.com']);

        app(DisableUserAction::class)->execute($superAdmin, $user, 'Security review.');

        $this->assertFalse($user->fresh()->is_active);
        $this->assertNotNull($user->fresh());

        Volt::test('pages.auth.login')
            ->set('form.email', 'disabled@example.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_manager_can_reset_staff_password_and_assign_role_and_branch_access(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $managerRole = $business->roles()->where('slug', DefaultRole::Manager->value)->firstOrFail();
        $cashierRole = $business->roles()->where('slug', DefaultRole::Cashier->value)->firstOrFail();
        $salesRole = $business->roles()->where('slug', DefaultRole::Salesperson->value)->firstOrFail();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $manager = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Branch Manager', 'email' => 'manager@example.com', 'password' => 'manager-password', 'branch_ids' => [$branch->id], 'role_ids' => [$managerRole->id]]);
        $staff = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Managed Cashier', 'email' => 'managed@example.com', 'password' => 'original-password', 'branch_ids' => [$branch->id], 'role_ids' => [$cashierRole->id]]);
        app(TenantContext::class)->setForUser($manager, $business, $branch);

        app(ResetStaffPasswordAction::class)->execute($manager, $business, $staff, 'NewSecurePassword@2026', 'User forgot password.');
        app(UpdateStaffAccessAction::class)->execute($manager, $business, $staff, [$branch->id], [$salesRole->id], 'Moved to sales team.');

        $this->assertTrue(Hash::check('NewSecurePassword@2026', $staff->fresh()->password));
        $this->assertDatabaseHas('user_roles', ['business_id' => $business->id, 'user_id' => $staff->id, 'role_id' => $salesRole->id]);
        $this->assertDatabaseMissing('user_roles', ['business_id' => $business->id, 'user_id' => $staff->id, 'role_id' => $cashierRole->id]);
        $this->assertDatabaseHas('audit_logs', ['business_id' => $business->id, 'subject_id' => $staff->id, 'action' => 'staff.password_reset']);
        $this->assertDatabaseHas('audit_logs', ['business_id' => $business->id, 'subject_id' => $staff->id, 'action' => 'staff.access_updated']);
        $this->actingAs($manager)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->get(route('owner.staff.index'))->assertOk()->assertSeeText('Manage')->assertSeeText('Reset password');
    }

    public function test_manager_cannot_reset_shop_owner_password(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $managerRole = $business->roles()->where('slug', DefaultRole::Manager->value)->firstOrFail();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $manager = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Protected Manager', 'email' => 'protected-manager@example.com', 'password' => 'manager-password', 'branch_ids' => [$branch->id], 'role_ids' => [$managerRole->id]]);
        app(TenantContext::class)->setForUser($manager, $business, $branch);

        $this->expectException(ValidationException::class);
        app(ResetStaffPasswordAction::class)->execute($manager, $business, $owner, 'ForbiddenPassword@2026', 'Improper escalation attempt.');
    }

    public function test_password_reset_button_generates_and_sends_a_temporary_password_by_sms(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $managerRole = $business->roles()->where('slug', DefaultRole::Manager->value)->firstOrFail();
        $cashierRole = $business->roles()->where('slug', DefaultRole::Cashier->value)->firstOrFail();
        $manager = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Branch Manager', 'email' => 'manager@example.com', 'password' => 'manager-password', 'branch_ids' => [$branch->id], 'role_ids' => [$managerRole->id]]);
        $staff = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Managed Cashier', 'email' => 'cashier@example.com', 'phone' => '+255712345678', 'password' => 'original-password', 'branch_ids' => [$branch->id], 'role_ids' => [$cashierRole->id]]);
        config()->set('sms.enabled', true);
        config()->set('sms.token', 'test-token');
        config()->set('sms.url', 'https://sms.example.test/api/v1');
        Http::fake(['https://sms.example.test/*' => Http::response(['success' => true])]);

        $this->actingAs($manager)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->put(route('owner.staff.password.reset', $staff), ['reason' => 'Staff requested access help.'])
            ->assertRedirect()
            ->assertSessionHas('status', __('staff.password_reset_successfully'));

        $this->assertFalse(Hash::check('original-password', $staff->fresh()->password));
        Http::assertSent(fn ($request) => $request['recipients'] === '+255712345678'
            && str_contains($request['message'], 'Temporary Password: '));
    }

    public function test_manager_reset_does_not_change_a_password_when_the_sms_cannot_be_sent(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $managerRole = $business->roles()->where('slug', DefaultRole::Manager->value)->firstOrFail();
        $cashierRole = $business->roles()->where('slug', DefaultRole::Cashier->value)->firstOrFail();
        $manager = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Branch Manager', 'email' => 'manager@example.com', 'password' => 'manager-password', 'branch_ids' => [$branch->id], 'role_ids' => [$managerRole->id]]);
        $staff = app(CreateStaffAction::class)->execute($owner, $business, ['name' => 'Managed Cashier', 'email' => 'cashier@example.com', 'phone' => '+255712345678', 'password' => 'original-password', 'branch_ids' => [$branch->id], 'role_ids' => [$cashierRole->id]]);
        config()->set('sms.enabled', false);

        $this->actingAs($manager)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->put(route('owner.staff.password.reset', $staff), ['reason' => 'Staff requested access help.'])
            ->assertSessionHasErrors('staff');

        $this->assertTrue(Hash::check('original-password', $staff->fresh()->password));
    }

    public function test_staff_creation_normalizes_the_phone_and_sends_generated_credentials(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $cashierRole = $business->roles()->where('slug', DefaultRole::Cashier->value)->firstOrFail();
        config()->set('sms.enabled', true);
        config()->set('sms.token', 'test-token');
        config()->set('sms.url', 'https://sms.example.test/api/v1');
        Http::fake(['https://sms.example.test/*' => Http::response(['success' => true])]);

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->post(route('owner.staff.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '255712345678',
                'branch_ids' => [$branch->id],
                'role_ids' => [$cashierRole->id],
            ])
            ->assertRedirect();

        $staff = User::query()->where('email', 'john@example.com')->firstOrFail();
        $this->assertSame('255712345678', $staff->phone);
        $this->assertMatchesRegularExpression('/^john\d{3}$/', $staff->username);
        Http::assertSent(fn ($request) => $request['recipients'] === '255712345678'
            && str_contains($request['message'], 'Company: Action Test Business')
            && str_contains($request['message'], "Username: {$staff->username}")
            && str_contains($request['message'], 'Role: Cashier')
            && preg_match('/Temporary Password: John-[A-Za-z0-9]{12}/', $request['message']) === 1);
    }

    public function test_owner_can_update_an_employees_account_details(): void
    {
        [$business, $owner] = $this->createBusiness();
        $branch = $business->branches->first();
        $cashierRole = $business->roles()->where('slug', DefaultRole::Cashier->value)->firstOrFail();
        $staff = app(CreateStaffAction::class)->execute($owner, $business, [
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '255712345678',
            'password' => 'original-password',
            'branch_ids' => [$branch->id],
            'role_ids' => [$cashierRole->id],
        ]);

        app(UpdateStaffDetailsAction::class)->execute($owner, $business, $staff, [
            'name' => 'New Name',
            'username' => 'newname123',
            'email' => 'new@example.com',
            'phone' => '255756609498',
        ], 'Correcting employee contact details.');

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'New Name',
            'username' => 'newname123',
            'email' => 'new@example.com',
            'phone' => '255756609498',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'subject_id' => $staff->id,
            'action' => 'staff.details_updated',
        ]);
    }

    /**
     * @return array{0:Business, 1:User}
     */
    private function createBusiness(): array
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, [
            'name' => 'Action Test Business',
            'code' => 'ATB',
        ]);

        return [$business, $owner];
    }
}
