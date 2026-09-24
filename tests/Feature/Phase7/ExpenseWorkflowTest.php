<?php

namespace Tests\Feature\Phase7;

use App\Actions\CreateBusinessAction;
use App\Actions\ExpenseWorkflowAction;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_uses_maker_checker_approval_and_is_audited(): void
    {
        [$business, $maker, $expense] = $this->fixture();
        $action = app(ExpenseWorkflowAction::class);
        $action->submit($maker, $expense, 'Monthly operating cost');
        $approval = $expense->approvals()->firstOrFail();
        try {
            $action->decide($maker, $approval, true, 'Self approval');
            $this->fail('Maker was allowed to approve their own request.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $checker = $this->addOwner($business);
        app(TenantContext::class)->setForUser($checker, $business, $business->branches->first());
        $action->decide($checker, $approval->refresh(), true, 'Receipt checked');

        $this->assertSame(ExpenseStatus::Approved, $expense->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['subject_id' => $expense->id, 'action' => 'expense.approved']);
    }

    public function test_owner_can_open_reference_aligned_expense_workspace(): void
    {
        [$business, $owner] = $this->fixture();
        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->get(route('owner.expenses.index'))->assertOk()->assertSeeText('Expense management')->assertSeeText('Expense register');
        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->get(route('owner.expenses.create'))->assertOk()->assertSeeText('Record a new expense')->assertSeeText('Approval workflow');
        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->get(route('owner.expenses.export'))->assertOk()->assertDownload();
        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])->get(route('owner.expenses.export'))->assertOk()->assertDownload();
    }

    public function test_new_expense_page_can_create_and_submit_an_expense_atomically(): void
    {
        [$business, $owner, $existing] = $this->fixture();
        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id];
        $this->actingAs($owner)->withSession($session)->post(route('owner.expenses.store'), [
            'expense_category_id' => $existing->expense_category_id,
            'payment_account_id' => $existing->payment_account_id,
            'title' => 'Warehouse cleaning',
            'vendor' => 'Clean Team',
            'amount' => '25000',
            'expense_date' => now()->toDateString(),
            'intent' => 'submit',
            'approval_reason' => 'Monthly warehouse cleaning service',
        ])->assertRedirect(route('owner.expenses.index'));

        $created = Expense::query()->where('title', 'Warehouse cleaning')->firstOrFail();
        $this->assertSame(ExpenseStatus::Pending, $created->status);
        $this->assertDatabaseHas('approval_requests', ['subject_id' => $created->id, 'status' => 'pending']);
    }

    public function test_approval_activity_notifies_reviewer_and_requester_and_governance_pages_render(): void
    {
        [$business, $maker, $expense] = $this->fixture();
        $checker = $this->addOwner($business);
        $action = app(ExpenseWorkflowAction::class);
        $action->submit($maker, $expense, 'Approval notification test');

        $this->assertCount(1, $checker->notifications()->get());
        app(TenantContext::class)->setForUser($checker, $business, $business->branches->first());
        $action->decide($checker, $expense->approvals()->firstOrFail(), true, 'Reviewed');
        $this->assertCount(1, $maker->notifications()->get());

        $session = ['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id];
        $this->actingAs($checker)->withSession($session)->get(route('owner.approvals.index'))->assertOk()->assertSeeText('Approval centre');
        $this->actingAs($checker)->withSession($session)->get(route('owner.notifications.index'))->assertOk()->assertSeeText('Notifications');
        $this->actingAs($checker)->withSession($session)->get(route('owner.audit.index'))->assertOk()->assertSeeText('Audit trail');
    }

    private function fixture(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Phase Seven Business', 'code' => 'PHASE7']);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $method = PaymentMethod::query()->where('business_id', $business->id)->firstOrFail();
        $account = PaymentAccount::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'payment_method_id' => $method->id, 'name' => 'Operating Till']);
        $expense = Expense::query()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'expense_category_id' => ExpenseCategory::query()->where('business_id', $business->id)->firstOrFail()->id, 'payment_account_id' => $account->id, 'expense_number' => 'PHASE7-EXP-00001', 'status' => ExpenseStatus::Draft, 'title' => 'Electricity', 'amount' => 100, 'expense_date' => now(), 'created_by' => $owner->id]);

        return [$business, $owner, $expense];
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
