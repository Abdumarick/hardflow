<?php

namespace Tests\Feature\Phase6;

use App\Actions\CancelSaleAction;
use App\Actions\ChangeProductPriceAction;
use App\Actions\CloseCashSessionAction;
use App\Actions\ConfirmSaleAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateCustomerAction;
use App\Actions\CreateProductAction;
use App\Actions\CreateSaleAction;
use App\Actions\DecideAccountTransferAction;
use App\Actions\OpenCashSessionAction;
use App\Actions\PaymentAccountBalanceAction;
use App\Actions\RecordCustomerPaymentAction;
use App\Actions\RecordSalePaymentAction;
use App\Actions\RequestAccountTransferAction;
use App\Actions\ReverseCustomerPaymentAction;
use App\Models\BusinessSetting;
use App\Models\PaymentAccount;
use App\Models\PaymentMethod;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentsDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_customer_sale_posts_debt_and_partial_payment_allocates_it(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Karibu Construction', 'is_credit_customer' => true, 'credit_limit' => '1000']);
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'due_date' => now()->addDays(14)->toDateString(), 'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '2', 'applied_unit_price' => '150']]]);
        app(ConfirmSaleAction::class)->execute($owner, $sale);

        $this->assertDatabaseHas('customer_ledger_entries', ['customer_id' => $customer->id, 'entry_type' => 'sale', 'debit' => 300, 'credit' => 0]);
        $method = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'cash')->firstOrFail();
        $account = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'payment_method_id' => $method->id, 'name' => 'Main Till']);
        app(RecordCustomerPaymentAction::class)->execute($owner, $customer, $account, ['amount' => '120', 'allocations' => [['sale_id' => $sale->id, 'amount' => '120']]]);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'payment_status' => 'partially_paid']);
        $this->assertDatabaseHas('payment_allocations', ['sale_id' => $sale->id, 'amount' => 120]);
        $this->assertDatabaseHas('customer_ledger_entries', ['customer_id' => $customer->id, 'entry_type' => 'payment', 'debit' => 0, 'credit' => 120]);
    }

    public function test_owner_can_open_the_payments_workspace(): void
    {
        [$business, $owner] = $this->fixture();

        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->get(route('owner.payments.index'))->assertOk()->assertSeeText('Payments')->assertSeeText('Customer debts');
    }

    public function test_payment_reversal_restores_invoice_debt_without_deleting_history(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        [$customer, $sale, $account] = $this->receivable($business, $owner, $productUnit, $level);
        $payment = app(RecordCustomerPaymentAction::class)->execute($owner, $customer, $account, ['amount' => '300', 'allocations' => [['sale_id' => $sale->id, 'amount' => '300']]]);
        app(ReverseCustomerPaymentAction::class)->execute($owner, $payment, 'Bank receipt was entered twice');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'reversed']);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('customer_ledger_entries', ['source_id' => $payment->id, 'entry_type' => 'payment_reversal', 'debit' => 300]);
        $this->assertDatabaseCount('payment_allocations', 1);
    }

    public function test_transfer_approval_and_cash_session_reconciliation_are_audited(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        [$customer, $sale, $cash] = $this->receivable($business, $owner, $productUnit, $level);
        $session = app(OpenCashSessionAction::class)->execute($owner, $cash, '50');
        app(RecordCustomerPaymentAction::class)->execute($owner, $customer, $cash, ['amount' => '300', 'allocations' => [['sale_id' => $sale->id, 'amount' => '300']]]);
        app(CloseCashSessionAction::class)->execute($owner, $session, '350', null);
        $bankMethod = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'bank')->firstOrFail();
        $bank = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'payment_method_id' => $bankMethod->id, 'name' => 'Main Bank']);
        $transfer = app(RequestAccountTransferAction::class)->execute($owner, $cash, $bank, '100', 'Deposit daily cash to bank');
        $approver = $this->addOwner($business);
        app(TenantContext::class)->setForUser($approver, $business, $business->branches->first());
        app(DecideAccountTransferAction::class)->execute($approver, $transfer, true, 'Deposit slip checked and approved');

        $this->assertDatabaseHas('cash_sessions', ['id' => $session->id, 'expected_closing_cash' => 350, 'difference_amount' => 0]);
        $this->assertSame('200.00', app(PaymentAccountBalanceAction::class)->execute($cash));
        $this->assertSame('100.00', app(PaymentAccountBalanceAction::class)->execute($bank));
    }

    public function test_walk_in_sale_payment_generates_a_printable_receipt(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'walk_in_name' => 'Walk-in Builder', 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '1', 'applied_unit_price' => '150']]]);
        app(ConfirmSaleAction::class)->execute($owner, $sale);
        $method = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'cash')->firstOrFail();
        $account = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'payment_method_id' => $method->id, 'name' => 'Walk-in Till']);
        $payment = app(RecordSalePaymentAction::class)->execute($owner, $sale, $account, '150');

        $this->assertNull($payment->customer_id);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'payment_status' => 'paid']);
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id];
        $this->actingAs($owner)->withSession($session)->get(route('owner.payments.receipt', $payment))->assertOk()->assertSeeText('Payment complete')->assertSeeText('Walk-in Builder');

        $business->update(['locale' => 'sw']);
        $this->actingAs($owner)->withSession($session)->get(route('owner.payments.receipt', $payment))->assertOk()->assertSeeText('Malipo yamekamilika')->assertSeeText('IMELIPWA');
        $this->actingAs($owner)->withSession($session)->get(route('owner.sales.print', $sale))->assertOk()->assertSeeText('Mteja')->assertSeeText('Chapisha');
    }

    public function test_customer_statement_uses_the_business_locale_and_currency(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        [$customer] = $this->receivable($business, $owner, $productUnit, $level);
        $business->update(['locale' => 'sw', 'currency' => 'USD']);

        $this->actingAs($owner)
            ->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])
            ->get(route('owner.payments.customers.statement', $customer))
            ->assertOk()
            ->assertSeeText('Taarifa ya mteja')
            ->assertSeeText('Salio linalodaiwa')
            ->assertSeeText('USD');
    }

    public function test_warn_credit_policy_allows_an_over_limit_sale_and_keeps_the_debt_visible(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        BusinessSetting::query()->where('business_id', $business->id)->where('key', 'credit_limit_policy')->firstOrFail()->update(['value' => 'warn']);
        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Limited Customer', 'is_credit_customer' => true, 'credit_limit' => '100']);
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '2', 'applied_unit_price' => '150']]]);
        app(ConfirmSaleAction::class)->execute($owner, $sale);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('customer_ledger_entries', ['customer_id' => $customer->id, 'debit' => 300]);
    }

    public function test_over_limit_sale_requires_an_independent_approval_before_confirmation(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        BusinessSetting::query()->where('business_id', $business->id)->where('key', 'credit_limit_policy')->firstOrFail()->update(['value' => 'require_approval']);
        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Approval Customer', 'is_credit_customer' => true, 'credit_limit' => '100']);
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '2', 'applied_unit_price' => '150']]]);
        $checker = $this->addOwner($business);

        try {
            app(ConfirmSaleAction::class)->execute($owner, $sale);
            $this->fail('The over-limit sale was confirmed without approval.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('approval_requests', ['subject_id' => $sale->id, 'action_type' => 'sale.credit_limit_override', 'status' => 'pending']);
        }

        $this->assertCount(1, $checker->notifications()->get());
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id];
        $approval = $sale->approvals()->firstOrFail();
        $this->actingAs($checker)->withSession($session)->put(route('owner.approvals.decision', $approval), ['decision' => 'approve', 'reason' => 'Customer account reviewed'])->assertRedirect();
        $this->assertCount(1, $owner->notifications()->get());
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());
        app(ConfirmSaleAction::class)->execute($owner, $sale->refresh());
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'confirmed']);
    }

    public function test_cash_difference_waits_for_independent_approval(): void
    {
        [$business, $owner] = $this->fixture();
        $method = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'cash')->firstOrFail();
        $account = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'payment_method_id' => $method->id, 'name' => 'Difference Till']);
        $cashSession = app(OpenCashSessionAction::class)->execute($owner, $account, '100');
        app(CloseCashSessionAction::class)->execute($owner, $cashSession, '80', 'Cash count is short by twenty');
        $this->assertDatabaseHas('cash_sessions', ['id' => $cashSession->id, 'status' => 'pending_approval', 'difference_amount' => -20]);

        $checker = $this->addOwner($business);
        $approval = $cashSession->approvals()->firstOrFail();
        $this->actingAs($checker)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->put(route('owner.approvals.decision', $approval), ['decision' => 'approve', 'reason' => 'Supervisor verified the cash count'])->assertRedirect();
        $this->assertDatabaseHas('cash_sessions', ['id' => $cashSession->id, 'status' => 'closed', 'closed_by' => $checker->id]);
    }

    public function test_cancelling_an_unpaid_customer_sale_credits_the_ledger(): void
    {
        [$business, $owner, $productUnit, $level] = $this->fixture();
        [$customer, $sale] = $this->receivable($business, $owner, $productUnit, $level);
        app(CancelSaleAction::class)->execute($owner, $sale->refresh(), 'Customer cancelled before collection');

        $this->assertDatabaseHas('customer_ledger_entries', ['customer_id' => $customer->id, 'entry_type' => 'sale_cancellation', 'credit' => 300]);
        $balance = (string) $customer->ledgerEntries()->selectRaw('SUM(debit) - SUM(credit) AS balance')->value('balance');
        $this->assertSame(0, bccomp($balance, '0', 2));
    }

    private function fixture(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Phase Six Business', 'code' => 'PHASE6']);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc-p6']);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Payment Product', 'unit_id' => $unit->id]);
        $productUnit = $product->productUnits()->first();
        $level = $business->priceLevels()->where('is_default', true)->first();
        app(ChangeProductPriceAction::class)->execute($owner, $product, $productUnit, $level, '150', 'Initial retail price');
        StockBalance::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'stock_status' => 'available', 'quantity' => 10, 'average_cost' => 100]);

        return [$business, $owner, $productUnit, $level];
    }

    private function receivable($business, User $owner, $productUnit, $level): array
    {
        $customer = app(CreateCustomerAction::class)->execute($owner, $business, ['name' => 'Payment Customer', 'is_credit_customer' => true, 'credit_limit' => '1000']);
        $sale = app(CreateSaleAction::class)->execute($owner, $business, ['branch_id' => $business->branches->first()->id, 'customer_id' => $customer->id, 'sale_date' => now()->toDateString(), 'items' => [['product_unit_id' => $productUnit->id, 'price_level_id' => $level->id, 'quantity' => '2', 'applied_unit_price' => '150']]]);
        app(ConfirmSaleAction::class)->execute($owner, $sale);
        $method = PaymentMethod::query()->where('business_id', $business->id)->where('type', 'cash')->firstOrFail();
        $account = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'payment_method_id' => $method->id, 'name' => 'Main Till']);

        return [$customer, $sale, $account];
    }

    private function addOwner($business): User
    {
        $user = User::factory()->create();
        $branch = $business->branches->first();
        $role = $business->roles()->where('slug', 'owner')->firstOrFail();
        DB::table('business_users')->insert(['business_id' => $business->id, 'user_id' => $user->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branch_users')->insert(['business_id' => $business->id, 'branch_id' => $branch->id, 'user_id' => $user->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_roles')->insert(['business_id' => $business->id, 'user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);

        return $user;
    }
}
