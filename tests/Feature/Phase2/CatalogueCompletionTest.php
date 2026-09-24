<?php

namespace Tests\Feature\Phase2;

use App\Actions\CreateBusinessAction;
use App\Actions\CreateProductAction;
use App\Actions\UpdateCategoryAction;
use App\Actions\UpdateProductAction;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogueCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_hierarchy_rejects_self_cycles_descendant_cycles_and_cross_tenant_parent(): void
    {
        [$business, $owner] = $this->business('HRA');
        [$otherBusiness] = $this->business('HRB');
        $root = Category::query()->create(['business_id' => $business->id, 'name' => 'Building Materials']);
        $child = Category::query()->create(['business_id' => $business->id, 'parent_id' => $root->id, 'name' => 'Cement']);
        $foreign = Category::query()->create(['business_id' => $otherBusiness->id, 'name' => 'Foreign']);
        $action = app(UpdateCategoryAction::class);

        try {
            $action->execute($owner, $root, $root->name, $root);
            $this->fail('Self-parenting should fail.');
        } catch (ValidationException) {
            $this->assertNull($root->refresh()->parent_id);
        }

        try {
            $action->execute($owner, $root, $root->name, $child);
            $this->fail('Descendant parenting should fail.');
        } catch (ValidationException) {
            $this->assertNull($root->refresh()->parent_id);
        }

        $this->expectException(AuthorizationException::class);
        $action->execute($owner, $root, $root->name, $foreign);
    }

    public function test_product_update_and_deactivation_are_audited_without_deleting_history(): void
    {
        [$business, $owner] = $this->business('UPD');
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Old Hammer', 'unit_id' => $unit->id]);
        $action = app(UpdateProductAction::class);
        $action->execute($owner, $product, ['name' => 'Claw Hammer', 'sku' => $product->sku, 'barcode' => 'HAM-100']);
        $action->setActive($owner, $product, false, 'Discontinued by supplier');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Claw Hammer', 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.updated', 'subject_id' => $product->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.deactivated', 'subject_id' => $product->id, 'reason' => 'Discontinued by supplier']);
    }

    public function test_cross_tenant_product_url_tampering_is_forbidden(): void
    {
        [$businessA, $ownerA] = $this->business('URLA');
        [$businessB, $ownerB] = $this->business('URLB');
        $unitB = Unit::query()->create(['business_id' => $businessB->id, 'name' => 'Bag', 'symbol' => 'bag']);
        app(TenantContext::class)->setForUser($ownerB, $businessB, $businessB->branches->first());
        $productB = app(CreateProductAction::class)->execute($ownerB, $businessB, ['name' => 'Private Cement', 'unit_id' => $unitB->id]);

        $response = $this->actingAs($ownerA)->withSession(['tenant.business_id' => $businessA->id, 'tenant.branch_id' => $businessA->branches->first()->id])
            ->put(route('owner.catalogue.products.update', $productB), ['name' => 'Tampered', 'sku' => $productB->sku]);

        $response->assertForbidden();
        $this->assertSame('Private Cement', $productB->refresh()->name);
    }

    public function test_user_without_catalogue_permission_cannot_call_endpoint_directly(): void
    {
        [$business] = $this->business('DENY');
        $user = User::factory()->create();
        DB::table('business_users')->insert(['business_id' => $business->id, 'user_id' => $user->id, 'is_active' => true, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('branch_users')->insert(['business_id' => $business->id, 'branch_id' => $business->branches->first()->id, 'user_id' => $user->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($user)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $business->branches->first()->id])
            ->get(route('owner.catalogue.index'))->assertForbidden();
    }

    public function test_search_handles_ten_thousand_skus_with_bounded_pagination(): void
    {
        [$business] = $this->business('LOAD');
        $now = now();
        foreach (array_chunk(range(1, 10000), 1000) as $numbers) {
            DB::table('products')->insert(array_map(fn (int $number): array => [
                'business_id' => $business->id, 'public_id' => (string) Str::uuid(),
                'sku' => 'LOAD-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
                'name' => $number === 9999 ? 'Unique Search Drill' : 'Hardware Product '.$number,
                'is_stock_tracked' => true, 'tracks_expiry' => false, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ], $numbers));
        }

        $page = Product::query()->where('business_id', $business->id)->search('Unique Search Drill')->paginate(20);
        $this->assertSame(1, $page->total());
        $this->assertLessThanOrEqual(20, $page->count());
        $this->assertSame('LOAD-09999', $page->first()->sku);
    }

    public function test_minimum_stock_is_branch_scoped_and_does_not_create_inventory(): void
    {
        [$business, $owner] = $this->business('MIN');
        $branch = $business->branches->first();
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Metre', 'symbol' => 'm']);
        app(TenantContext::class)->setForUser($owner, $business, $branch);
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Pipe', 'unit_id' => $unit->id]);

        $this->actingAs($owner)->withSession(['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id])
            ->put(route('owner.catalogue.minimum-stock.store', $product), ['minimum_stock' => '12.5000'])
            ->assertRedirect();

        $this->assertDatabaseHas('product_branch_settings', ['business_id' => $business->id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'minimum_stock' => 12.5]);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_duplicate_sku_and_barcode_are_rejected_with_validation_errors(): void
    {
        [$business, $owner] = $this->business('DUP');
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());
        $action = app(CreateProductAction::class);
        $action->execute($owner, $business, ['name' => 'First', 'sku' => 'ABC-1', 'barcode' => '9988', 'unit_id' => $unit->id]);

        foreach ([['sku' => 'ABC-1'], ['barcode' => '9988']] as $duplicate) {
            try {
                $action->execute($owner, $business, ['name' => 'Duplicate', 'sku' => $duplicate['sku'] ?? 'ABC-2', 'barcode' => $duplicate['barcode'] ?? '9989', 'unit_id' => $unit->id]);
                $this->fail('Duplicate catalogue identity should be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(array_key_first($duplicate), $exception->errors());
            }
        }
    }

    /** @return array{Business, User} */
    private function business(string $code): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => "Business $code", 'code' => $code]);

        return [$business, $owner];
    }
}
