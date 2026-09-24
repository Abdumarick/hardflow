<?php

namespace Tests\Feature\Phase2;

use App\Actions\AssignProductUnitAction;
use App\Actions\ChangeProductPriceAction;
use App\Actions\CreateBusinessAction;
use App\Actions\CreateProductAction;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\PriceLevel;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogueActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_provisioning_creates_catalogue_defaults(): void
    {
        [$business] = $this->business('CAT');

        $this->assertSame(['Retail', 'Wholesale'], $business->priceLevels()->orderBy('name')->pluck('name')->all());
        $this->assertDatabaseHas('number_sequences', ['business_id' => $business->id, 'type' => 'product', 'next_number' => 1]);
    }

    public function test_product_is_created_atomically_with_business_scoped_sku_and_base_unit(): void
    {
        [$business, $owner] = $this->business('ONE');
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());

        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Claw Hammer', 'unit_id' => $unit->id]);

        $this->assertSame('ONE-PRD-000001', $product->sku);
        $this->assertCount(1, $product->productUnits);
        $this->assertTrue($product->productUnits->first()->is_base);
        $this->assertSame('1.000000', $product->productUnits->first()->conversion_factor);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.created', 'subject_id' => $product->id]);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_cross_tenant_catalogue_relationships_are_rejected(): void
    {
        [$businessA, $ownerA] = $this->business('AAA');
        [$businessB] = $this->business('BBB');
        $foreignUnit = Unit::query()->create(['business_id' => $businessB->id, 'name' => 'Bag', 'symbol' => 'bag']);
        app(TenantContext::class)->setForUser($ownerA, $businessA, $businessA->branches->first());

        $this->expectException(ValidationException::class);
        app(CreateProductAction::class)->execute($ownerA, $businessA, ['name' => 'Cement', 'unit_id' => $foreignUnit->id]);
    }

    public function test_alternative_units_are_product_specific_and_positive(): void
    {
        [$business, $owner] = $this->business('UNT');
        $piece = Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        $box = Unit::query()->create(['business_id' => $business->id, 'name' => 'Box', 'symbol' => 'box']);
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Nails', 'unit_id' => $piece->id]);
        $alternative = app(AssignProductUnitAction::class)->execute($owner, $product, $box, '24.500000');

        $this->assertSame('24.500000', $alternative->conversion_factor);
        $this->assertFalse($alternative->is_base);

        $this->expectException(ValidationException::class);
        app(AssignProductUnitAction::class)->execute($owner, $product, $box, '0');
    }

    public function test_price_changes_require_reason_and_preserve_history(): void
    {
        [$business, $owner] = $this->business('PRC');
        $unit = Unit::query()->create(['business_id' => $business->id, 'name' => 'Bag', 'symbol' => 'bag']);
        app(TenantContext::class)->setForUser($owner, $business, $business->branches->first());
        $product = app(CreateProductAction::class)->execute($owner, $business, ['name' => 'Cement', 'unit_id' => $unit->id]);
        $productUnit = $product->productUnits->first();
        $retail = PriceLevel::query()->where('business_id', $business->id)->where('code', 'RETAIL')->firstOrFail();
        $action = app(ChangeProductPriceAction::class);
        $action->execute($owner, $product, $productUnit, $retail, '18500.00', 'Initial selling price');
        $latest = $action->execute($owner, $product, $productUnit, $retail, '19000.00', 'Supplier market increase');

        $this->assertDatabaseCount('product_prices', 2);
        $this->assertSame(1, ProductPrice::query()->where('is_active', true)->count());
        $this->assertSame('19000.00', $latest->amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.price_changed', 'reason' => 'Supplier market increase']);
    }

    public function test_search_is_tenant_scoped_and_finds_supported_fields(): void
    {
        [$businessA] = $this->business('SRA');
        [$businessB] = $this->business('SRB');
        $category = Category::query()->create(['business_id' => $businessA->id, 'name' => 'Power Tools']);
        $brand = Brand::query()->create(['business_id' => $businessA->id, 'name' => 'Makita']);
        Product::query()->create(['business_id' => $businessA->id, 'category_id' => $category->id, 'brand_id' => $brand->id, 'sku' => 'DRL-001', 'barcode' => '12345', 'name' => 'Cordless Drill']);
        Product::query()->create(['business_id' => $businessB->id, 'sku' => 'SECRET', 'name' => 'Cordless Drill']);

        foreach (['Cordless', 'DRL-001', '12345', 'Power Tools', 'Makita'] as $term) {
            $this->assertSame(1, Product::query()->where('business_id', $businessA->id)->search($term)->count());
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
