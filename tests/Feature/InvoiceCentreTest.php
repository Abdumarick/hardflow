<?php

namespace Tests\Feature;

use App\Actions\CreateBusinessAction;
use App\Models\PaymentAccount;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_appear_as_printable_invoices_before_payment(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Invoice Hardware', 'code' => 'INVOICE']);
        $branch = $business->branches->first();
        $sale = Sale::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'sale_number' => 'INVOICE-SAL-00001', 'status' => 'draft', 'payment_status' => 'unpaid', 'fulfillment_status' => 'on_hold', 'walk_in_name' => 'Asha Builder', 'walk_in_phone' => '0712345678', 'sale_date' => now()->toDateString(), 'due_date' => now()->addDays(7)->toDateString(), 'subtotal' => 100, 'discount_amount' => 0, 'total_amount' => 100, 'created_by' => $owner->id]);
        $method = PaymentMethod::query()->where('business_id', $business->id)->firstOrFail();
        PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'payment_method_id' => $method->id, 'name' => 'Main payment account', 'account_reference' => '0712 000 000', 'is_active' => true]);
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        $this->actingAs($owner)->withSession($session)->get(route('owner.invoices.index'))
            ->assertOk()->assertSeeText('Invoice centre')->assertSeeText($sale->sale_number)->assertSeeText('Asha Builder');
        $this->actingAs($owner)->withSession($session)->get(route('owner.invoices.show', $sale))
            ->assertOk()->assertSeeText('INVOICE')->assertSeeText('DRAFT')->assertSeeText('Payment details')->assertSeeText('Main payment account');
        $this->actingAs($owner)->withSession($session)->get(route('owner.invoices.print', $sale))
            ->assertOk()->assertSeeText('Print / Save as PDF');
    }

    public function test_owner_can_choose_the_invoice_template(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Template Hardware', 'code' => 'TEMP']);
        $branch = $business->branches->first();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        $this->actingAs($owner)->withSession($session)->get(route('owner.settings.index', ['tab' => 'invoices']))
            ->assertOk()->assertSeeText('Choose the invoice your customers receive')->assertSeeText('Classic')->assertSeeText('Modern')->assertSeeText('Compact');
        $this->actingAs($owner)->withSession($session)->put(route('owner.settings.business.update'), ['invoice_template' => 'modern'])
            ->assertRedirect();
        $this->assertDatabaseHas('business_settings', ['business_id' => $business->id, 'key' => 'invoice_template']);
    }

    public function test_invoice_centre_handles_every_sales_and_payment_stage(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Stage Hardware', 'code' => 'STAGE']);
        $branch = $business->branches->first();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id];

        foreach ([
            ['STAGE-SAL-001', 'draft', 'unpaid', 'on_hold'],
            ['STAGE-SAL-002', 'confirmed', 'unpaid', 'on_hold'],
            ['STAGE-SAL-003', 'confirmed', 'partially_paid', 'partially_released'],
            ['STAGE-SAL-004', 'confirmed', 'paid', 'on_hold'],
            ['STAGE-SAL-005', 'confirmed', 'paid', 'released'],
        ] as [$number, $status, $paymentStatus, $fulfillmentStatus]) {
            Sale::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'sale_number' => $number, 'status' => $status, 'payment_status' => $paymentStatus, 'fulfillment_status' => $fulfillmentStatus, 'walk_in_name' => 'Mobile test customer', 'sale_date' => now()->toDateString(), 'subtotal' => 100, 'discount_amount' => 0, 'total_amount' => 100, 'created_by' => $owner->id]);
        }

        $this->actingAs($owner)->withSession($session)->get(route('owner.invoices.index'))
            ->assertOk()
            ->assertSeeText('STAGE-SAL-001')
            ->assertSeeText('STAGE-SAL-002')
            ->assertSeeText('STAGE-SAL-003')
            ->assertSeeText('STAGE-SAL-004')
            ->assertSeeText('STAGE-SAL-005')
            ->assertSeeText('partially paid')
            ->assertSeeText('partially released')
            ->assertSeeText('released');
    }
}
