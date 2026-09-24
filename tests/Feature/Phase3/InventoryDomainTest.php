<?php

namespace Tests\Feature\Phase3;

use App\Actions\ApplyStockMovementAction;
use App\Actions\CompleteStockCountAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateProductAction;
use App\Actions\DecideStockAdjustmentAction;
use App\Actions\RecordOpeningStockAction;
use App\Actions\RequestStockAdjustmentAction;
use App\Actions\StartStockCountAction;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Models\Business;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class InventoryDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_stock_atomically_creates_movement_and_balance(): void
    {
        [$business, $owner, $product] = $this->fixture('OPEN');
        $branch = $business->branches->first();
        $movement = app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '25.5000');

        $this->assertSame('0.0000', $movement->balance_before);
        $this->assertSame('25.5000', $movement->balance_after);
        $this->assertDatabaseHas('stock_balances', ['branch_id' => $branch->id, 'product_id' => $product->id, 'stock_status' => 'available', 'quantity' => 25.5]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_movements_are_immutable_and_negative_stock_is_rejected(): void
    {
        [$business, $owner, $product] = $this->fixture('SAFE');
        $branch = $business->branches->first();
        $movement = app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '5');

        try {
            app(ApplyStockMovementAction::class)->execute($owner, $branch, $product, StockStatus::Available, StockMovementType::SaleRelease, '-6', 'Attempt oversell');
            $this->fail('Negative stock must be rejected.');
        } catch (ValidationException) {
            $this->assertSame('5.0000', StockBalance::query()->first()->quantity);
        }

        $this->expectException(LogicException::class);
        $movement->update(['reason' => 'Rewritten']);
    }

    public function test_adjustment_requires_another_authorized_user_and_creates_movement_only_on_approval(): void
    {
        [$business, $owner, $product] = $this->fixture('ADJ');
        $branch = $business->branches->first();
        app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '10');
        $adjustment = app(RequestStockAdjustmentAction::class)->execute($owner, $branch, $product, StockStatus::Available, '7', 'Three damaged items found during count');
        $this->assertDatabaseCount('stock_movements', 1);

        try {
            app(DecideStockAdjustmentAction::class)->approve($owner, $adjustment, 'Approve own request');
            $this->fail('Requester must not self-approve.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_movements', 1);
        }

        $approver = $this->addOwner($business);
        app(DecideStockAdjustmentAction::class)->approve($approver, $adjustment, 'Physical evidence accepted');
        $this->assertSame('7.0000', StockBalance::query()->first()->quantity);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => 'adjustment_out', 'quantity_delta' => -3]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_adjustment.approved', 'subject_id' => $adjustment->id]);
    }

    public function test_physical_count_creates_pending_adjustments_without_direct_stock_change(): void
    {
        [$business, $owner, $product] = $this->fixture('COUNT');
        $branch = $business->branches->first();
        app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '12');
        $count = app(StartStockCountAction::class)->execute($owner, $branch);
        $available = $count->items->firstWhere('stock_status', StockStatus::Available);

        $adjustments = app(CompleteStockCountAction::class)->execute($owner, $count, [$available->id => '10'], 'Full shelf count completed');

        $this->assertCount(1, $adjustments);
        $this->assertSame('10.0000', $adjustments[0]->items()->first()->physical_quantity);
        $this->assertSame('12.0000', StockBalance::query()->first()->quantity);
        $this->assertDatabaseHas('stock_counts', ['id' => $count->id, 'status' => 'completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_count.completed', 'subject_id' => $count->id]);
    }

    public function test_stale_adjustment_is_rejected_after_concurrent_stock_change(): void
    {
        [$business, $owner, $product] = $this->fixture('RACE');
        $branch = $business->branches->first();
        app(RecordOpeningStockAction::class)->execute($owner, $branch, $product, '10');
        $adjustment = app(RequestStockAdjustmentAction::class)->execute($owner, $branch, $product, StockStatus::Available, '8', 'Count found two missing');
        app(ApplyStockMovementAction::class)->execute($owner, $branch, $product, StockStatus::Available, StockMovementType::CustomerReturn, '1', 'Concurrent return');
        $approver = $this->addOwner($business);

        $this->expectException(ValidationException::class);
        try {
            app(DecideStockAdjustmentAction::class)->approve($approver, $adjustment, 'Approve stale request');
        } finally {
            $this->assertSame('11.0000', StockBalance::query()->first()->quantity);
            $this->assertDatabaseCount('stock_movements', 2);
        }
    }

    public function test_other_business_cannot_open_inventory_or_decide_adjustment_by_url(): void
    {
        [$businessA, $ownerA] = $this->fixture('ISOA');
        [$businessB, $ownerB, $productB] = $this->fixture('ISOB');
        $branchB = $businessB->branches->first();
        $adjustment = app(RequestStockAdjustmentAction::class)->execute($ownerB, $branchB, $productB, StockStatus::Available, '1', 'Initial physical count');

        $this->actingAs($ownerA)->withSession(['tenant.business_id' => $businessA->id, 'tenant.branch_id' => $businessA->branches->first()->id])
            ->put(route('owner.inventory.adjustments.decide', $adjustment), ['decision' => 'approve', 'reason' => 'Tamper attempt'])
            ->assertForbidden();
    }

    private function fixture(string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => "Business $code", 'code' => $code]);
        $branch = $business->branches->first();
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Inventory Product', 'unit_id' => $unit->id]);

        return [$business, $owner, $product];
    }

    private function addOwner(Business $business): User
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
