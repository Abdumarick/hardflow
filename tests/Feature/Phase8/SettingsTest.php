<?php

namespace Tests\Feature\Phase8;

use App\Actions\CreateBusinessAction;
use App\Models\AuditLog;
use App\Models\BranchSetting;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_reference_aligned_settings_workspace(): void
    {
        [$owner, $business, $branch] = $this->tenant('Settings Hardware', 'SET');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSeeText('Business settings')
            ->assertSeeText('Company info')
            ->assertSeeText('Currency & operations')
            ->assertSeeText('Branch overrides');
    }

    public function test_owner_can_update_business_operating_rules_with_an_audit_record(): void
    {
        [$owner, $business, $branch] = $this->tenant('Settings Hardware', 'SET');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->put(route('owner.settings.business.update'), [
                'currency' => 'TZS',
                'timezone' => 'Africa/Nairobi',
                'locale' => 'sw',
                'fiscal_year_start' => 7,
                'vat_enabled' => 1,
                'vat_rate' => 18,
                'prices_include_tax' => 1,
                'allow_selling_below_cost' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('sw', $business->refresh()->locale);
        $this->assertSame(7, BusinessSetting::query()->whereBelongsTo($business)->where('key', 'fiscal_year_start')->firstOrFail()->value);
        $this->assertDatabaseHas('audit_logs', ['business_id' => $business->id, 'action' => 'business.settings.updated']);
    }

    public function test_branch_overrides_are_scoped_and_audited(): void
    {
        [$owner, $business, $branch] = $this->tenant('Settings Hardware', 'SET');
        [, $otherBusiness, $otherBranch] = $this->tenant('Other Hardware', 'OTH');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->put(route('owner.settings.branches.update', $branch), [
                'name' => 'Kariakoo Main',
                'code' => 'MAIN',
                'phone' => '+255700000000',
                'email' => 'branch@example.test',
                'address' => 'Kariakoo, Dar es Salaam',
                'override_global' => 1,
                'currency' => 'USD',
                'vat_rate' => 18,
                'low_stock_alert' => 10,
                'default_payment_method_id' => null,
            ])
            ->assertRedirect();

        $this->assertSame('Kariakoo Main', $branch->refresh()->name);
        $this->assertTrue((bool) BranchSetting::query()->whereBelongsTo($branch)->where('key', 'override_global')->firstOrFail()->value);
        $this->assertSame(1, AuditLog::query()->where('action', 'branch.settings.updated')->where('branch_id', $branch->id)->count());

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->put(route('owner.settings.branches.update', $otherBranch), [
                'name' => 'Tampered',
                'code' => 'TAMPER',
                'override_global' => 0,
            ])
            ->assertNotFound();

        $this->assertSame('Other Hardware', $otherBusiness->name);
        $this->assertNotSame('Tampered', $otherBranch->refresh()->name);
    }

    public function test_selected_business_locale_is_applied_to_tenant_pages(): void
    {
        [$owner, $business, $branch] = $this->tenant('Swahili Hardware', 'SWA');
        $business->update(['locale' => 'sw', 'timezone' => 'Africa/Nairobi']);

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.settings.index'))
            ->assertOk()
            ->assertSee('lang="sw"', false)
            ->assertSeeText('Mipangilio ya biashara')
            ->assertSeeText('Sarafu na uendeshaji');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.reports.index'))
            ->assertOk()
            ->assertSeeText('Tumia kipindi')
            ->assertSeeText('Madeni ya wateja');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.sales.index'))
            ->assertOk()
            ->assertSeeText('Mauzo mapya')
            ->assertSeeText('Kikapu ni tupu')
            ->assertSeeText('Tengeneza mauzo');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.purchases.index'))
            ->assertOk()
            ->assertSeeText('Manunuzi na upokeaji')
            ->assertSeeText('Oda za manunuzi')
            ->assertSeeText('Tengeneza rasimu ya ununuzi');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.inventory.index'))
            ->assertOk()
            ->assertSeeText('Udhibiti wa stoo')
            ->assertSeeText('Stoo ya kuanzia')
            ->assertSeeText('Hesabu halisi ya stoo');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.catalogue.index'))
            ->assertOk()
            ->assertSeeText('Katalogi ya bidhaa')
            ->assertSeeText('Mpangilio wa katalogi')
            ->assertSeeText('Ongeza bidhaa');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.payments.index'))
            ->assertOk()
            ->assertSeeText('Malipo na madeni ya wateja')
            ->assertSeeText('Madeni ya wateja')
            ->assertSeeText('Vipindi vya fedha taslimu');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.expenses.index'))
            ->assertOk()
            ->assertSeeText('Usimamizi wa matumizi')
            ->assertSeeText('Daftari la matumizi');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.expenses.create'))
            ->assertOk()
            ->assertSeeText('Rekodi matumizi mapya')
            ->assertSeeText('Mchakato wa idhini');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.staff.index'))
            ->assertOk()
            ->assertSeeText('Usimamizi wa wafanyakazi')
            ->assertSeeText('Ongeza mfanyakazi');

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.roles.index'))
            ->assertOk()
            ->assertSeeText('Majukumu na ruhusa')
            ->assertSeeText('Tengeneza jukumu maalumu');

        foreach ([
            ['route' => 'owner.approvals.index', 'heading' => 'Kituo cha idhini'],
            ['route' => 'owner.notifications.index', 'heading' => 'Arifa'],
            ['route' => 'owner.audit.index', 'heading' => 'Historia ya ukaguzi'],
        ] as $page) {
            $this->actingAs($owner)
                ->withSession($this->tenantSession($business->id, $branch->id))
                ->get(route($page['route']))
                ->assertOk()
                ->assertSeeText($page['heading']);
        }
    }

    private function tenant(string $name, string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, compact('name', 'code'));

        return [$owner, $business, $business->branches->first()];
    }

    private function tenantSession(int $businessId, int $branchId): array
    {
        return ['tenant.business_id' => $businessId, 'tenant.branch_id' => $branchId];
    }
}
