<?php

namespace Tests\Feature\Phase8;

use App\Actions\CreateBusinessAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_each_operational_report_for_the_selected_branch(): void
    {
        [$owner, $business, $branch] = $this->tenant();

        foreach (['sales', 'profit', 'purchases', 'inventory', 'movements', 'low-stock', 'fast-moving', 'slow-moving', 'expenses', 'payments', 'debt', 'salespeople', 'supplier-balances', 'outstanding-purchases'] as $section) {
            $this->actingAs($owner)
                ->withSession($this->tenantSession($business->id, $branch->id))
                ->get(route('owner.reports.index', ['section' => $section]))
                ->assertOk()
                ->assertSeeText('Reports')
                ->assertSeeText(ucfirst($section));
        }
    }

    public function test_owner_can_export_a_report_as_csv(): void
    {
        [$owner, $business, $branch] = $this->tenant();

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.reports.export', [
                'section' => 'sales',
                'from' => '2026-01-01',
                'to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertDownload('hardflow-sales-report-20260101-20260131.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_owner_can_open_an_a4_printable_report(): void
    {
        [$owner, $business, $branch] = $this->tenant();
        $session = $this->tenantSession($business->id, $branch->id);

        $this->actingAs($owner)
            ->withSession($session)
            ->get(route('owner.reports.print', ['section' => 'payments']))
            ->assertOk()
            ->assertSeeText('Print or save as PDF')
            ->assertSeeText('Payments by method')
            ->assertSeeText($business->name)
            ->assertSeeText('HardFlow');

        $business->update(['locale' => 'sw', 'currency' => 'USD']);
        $this->actingAs($owner)
            ->withSession($session)
            ->get(route('owner.reports.print', ['section' => 'payments']))
            ->assertOk()
            ->assertSee('lang="sw"', false)
            ->assertSeeText('Chapisha au hifadhi kama PDF')
            ->assertSeeText('Maelezo ya ripoti')
            ->assertSeeText('Sarafu: USD');
    }

    public function test_owner_can_download_pdf_and_excel_reports(): void
    {
        [$owner, $business, $branch] = $this->tenant();
        $session = $this->tenantSession($business->id, $branch->id);

        $this->actingAs($owner)->withSession($session)
            ->get(route('owner.reports.pdf', ['section' => 'sales', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertDownload('hardflow-sales-report-20260101-20260131.pdf');

        $this->actingAs($owner)->withSession($session)
            ->get(route('owner.reports.spreadsheet', ['section' => 'sales', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertDownload('hardflow-sales-report-20260101-20260131.xlsx');
    }

    public function test_report_rejects_an_invalid_reporting_window(): void
    {
        [$owner, $business, $branch] = $this->tenant();

        $this->actingAs($owner)
            ->withSession($this->tenantSession($business->id, $branch->id))
            ->get(route('owner.reports.index', [
                'from' => '2024-01-01',
                'to' => '2026-01-01',
            ]))
            ->assertUnprocessable();
    }

    private function tenant(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, [
            'name' => 'Report Hardware',
            'code' => 'RPT',
        ]);

        return [$owner, $business, $business->branches->first()];
    }

    private function tenantSession(int $businessId, int $branchId): array
    {
        return [
            'tenant.business_id' => $businessId,
            'tenant.branch_id' => $branchId,
        ];
    }
}
