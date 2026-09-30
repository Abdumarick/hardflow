<?php

namespace Tests\Feature;

use App\Actions\CreateBusinessAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_every_audit_log_including_super_admin_activity(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, ['name' => 'Audit Hardware', 'code' => 'AUDIT']);
        AuditLog::query()->create(['user_id' => $owner->id, 'business_id' => $business->id, 'action' => 'sale.created', 'ip_address' => '203.0.113.15', 'user_agent' => 'HardFlow Browser Test']);
        AuditLog::query()->create(['user_id' => $superAdmin->id, 'business_id' => $business->id, 'action' => 'platform.business_reviewed']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.audit.index'))
            ->assertOk()
            ->assertSeeText('Sale Created')
            ->assertSeeText('203.0.113.15')
            ->assertSeeText('HardFlow Browser Test')
            ->assertSeeText('Platform Business Reviewed');
    }

    public function test_business_audit_view_hides_super_admin_activity(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, ['name' => 'Private Audit Hardware', 'code' => 'PRAUDIT']);
        $branch = $business->branches->first();
        AuditLog::query()->create(['user_id' => $owner->id, 'business_id' => $business->id, 'branch_id' => $branch->id, 'action' => 'sale.created']);
        AuditLog::query()->create(['user_id' => $superAdmin->id, 'business_id' => $business->id, 'branch_id' => $branch->id, 'action' => 'platform.business_reviewed']);

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->get(route('owner.audit.index'))
            ->assertOk()
            ->assertSeeText('Sale Created')
            ->assertDontSeeText('Platform Business Reviewed');
    }
}
