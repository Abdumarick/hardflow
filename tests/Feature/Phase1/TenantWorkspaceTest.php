<?php

namespace Tests\Feature\Phase1;

use App\Actions\CreateBusinessAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_tenant_page_selects_the_users_default_business_and_branch(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($superAdmin, $owner, [
            'name' => 'Mlimani Hardware',
            'code' => 'MLM',
        ]);
        $branch = $business->branches->first();

        $this->actingAs($owner)
            ->get(route('owner.sales.index'))
            ->assertOk();

        $this->assertSame($business->id, session('tenant.business_id'));
        $this->assertSame($branch->id, session('tenant.branch_id'));
    }
}
