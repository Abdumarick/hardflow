<?php

namespace Tests\Feature\Phase2;

use App\Actions\CreateBusinessAction;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_aligned_product_dashboard_renders_live_metrics_and_controls(): void
    {
        [$business, $owner, $session] = $this->fixture();

        $this->actingAs($owner)->withSession($session)->get(route('owner.catalogue.index'))
            ->assertOk()
            ->assertSeeText('Product Management')
            ->assertSeeText('Total Products')
            ->assertSeeText('Import')
            ->assertSeeText('Export')
            ->assertSeeText('Add Product');
    }

    public function test_owner_can_import_and_export_products_using_the_csv_template(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Bag', 'symbol' => 'bag']);
        $csv = "name,sku,barcode,category,brand,unit,retail_price,wholesale_price,opening_stock,minimum_stock,description\nPortland Cement,CEM-001,60001,Cement,Bamburi,bag,28500,27000,25,5,Imported product\n";

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertRedirect()->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(1, $preview['complete']);
        $this->assertSame(0, $preview['blocked']);
        $this->assertDatabaseCount('products', 0);
        $this->actingAs($owner)->post(route('owner.catalogue.import.approve'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'CEM-001', 'name' => 'Portland Cement']);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'Cement']);
        $this->assertDatabaseHas('brands', ['business_id' => $business->id, 'name' => 'Bamburi']);
        $this->assertDatabaseHas('stock_balances', ['business_id' => $business->id, 'quantity' => 25]);
        $this->assertDatabaseCount('product_prices', 2);

        $response = $this->actingAs($owner)->withSession($session)->get(route('owner.catalogue.export'));
        $response->assertOk()->assertDownload();
        $csvExport = $response->streamedContent();
        $this->assertStringContainsString('Portland Cement', $csvExport);
        $this->assertStringContainsString('CEM-001', $csvExport);
    }

    public function test_invalid_import_rolls_back_every_product_row(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Bag', 'symbol' => 'bag']);
        $csv = "name,sku,unit\nValid Product,VALID-1,bag\nInvalid Product,INVALID-1,missing-unit\n";

        $this->actingAs($owner)->withSession($session)->from(route('owner.catalogue.index'))->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertRedirect(route('owner.catalogue.index'))->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(1, $preview['blocked']);
        $this->assertSame(1, $preview['incomplete']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_missing_units_can_be_registered_from_the_import_review_without_reuploading(): void
    {
        [$business, $owner, $session] = $this->fixture();
        $csv = "name,sku,unit\nClaw Hammer,HAM-1,piece\n";

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(1, $preview['blocked']);
        $this->assertSame(['piece'], $preview['missing_references']['units']);

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import.units.store'), [
            'token' => $preview['token'], 'name' => 'piece', 'symbol' => 'pc',
        ])->assertRedirect()->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(0, $preview['blocked']);
        $this->assertSame([], $preview['missing_references']['units']);
        $this->assertDatabaseHas('units', ['business_id' => $business->id, 'name' => 'piece', 'symbol' => 'pc']);

        $this->actingAs($owner)->post(route('owner.catalogue.import.approve'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'HAM-1']);
    }

    public function test_import_can_continue_by_automatically_registering_all_missing_units(): void
    {
        [$business, $owner, $session] = $this->fixture();
        $csv = "name,sku,unit\nClaw Hammer,HAM-1,piece\nCable Roll,CAB-1,roll\n";

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ]);
        $preview = $this->app['session']->get('product_import_preview');

        $this->actingAs($owner)->post(route('owner.catalogue.import.continue'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('units', ['business_id' => $business->id, 'name' => 'piece', 'symbol' => 'piece']);
        $this->assertDatabaseHas('units', ['business_id' => $business->id, 'name' => 'roll', 'symbol' => 'roll']);
        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'HAM-1']);
        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'CAB-1']);
        $this->assertNull($this->app['session']->get('product_import_preview'));
    }

    public function test_import_accepts_an_excel_utf8_bom_and_ignores_blank_lines(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        $csv = "\xEF\xBB\xBFname,sku,unit,retail_price\nHammer,HAM-1,pc,15000\n\n";

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('excel-products.csv', $csv),
        ])->assertRedirect()->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(1, $preview['incomplete']);
        $this->assertSame(0, $preview['blocked']);
        $this->actingAs($owner)->post(route('owner.catalogue.import.approve'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'HAM-1']);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_malformed_numeric_value_reports_its_row_and_rolls_back(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        $csv = "name,sku,unit,opening_stock\nValid Product,VALID-1,pc,4\nBroken Product,BROKEN-1,pc,many\n";

        $response = $this->actingAs($owner)->withSession($session)->from(route('owner.catalogue.index'))->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ]);

        $response->assertRedirect(route('owner.catalogue.index'))->assertSessionHas('status');
        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(1, $preview['blocked']);
        $this->assertSame(3, $preview['errors'][0]['row']);
        $this->assertSame('The opening stock field must be a number.', $preview['errors'][0]['message']);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_import_rejects_unknown_and_duplicate_columns(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('unknown.csv', "name,unit,cost_price\nHammer,pc,100\n"),
        ])->assertSessionHasErrors('file');

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('duplicate.csv', "name,unit,unit\nHammer,pc,pc\n"),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_picture_can_be_added_after_the_product_exists(): void
    {
        Storage::fake('public');
        [$business, $owner, $session] = $this->fixture();
        $product = Product::query()->create(['business_id' => $business->id, 'name' => 'Claw Hammer', 'sku' => 'HAMMER-1']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($owner)->withSession($session)->put(route('owner.catalogue.products.picture', $product), [
            'picture' => UploadedFile::fake()->createWithContent('hammer.png', $png),
        ])->assertRedirect()->assertSessionHas('status');

        $product->refresh();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertDatabaseHas('audit_logs', ['business_id' => $business->id, 'action' => 'product.picture_updated', 'subject_id' => $product->id]);
    }

    public function test_pending_import_can_be_rejected_without_creating_products(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', "name,unit\nHammer,pc\n"),
        ])->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertFileExists(Storage::path($preview['path']));
        $this->actingAs($owner)->delete(route('owner.catalogue.import.reject'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseCount('products', 0);
        $this->assertNull($this->app['session']->get('product_import_preview'));
        $this->assertFileDoesNotExist(Storage::path($preview['path']));
    }

    public function test_existing_product_is_classified_and_updated_without_duplication(): void
    {
        [$business, $owner, $session] = $this->fixture();
        Unit::query()->create(['business_id' => $business->id, 'name' => 'Piece', 'symbol' => 'pc']);
        $initial = "name,sku,category,brand,unit,retail_price,opening_stock\nClaw Hammer,HAM-1,Tools,Acme,pc,10000,5\n";
        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('initial.csv', $initial),
        ]);
        $preview = $this->app['session']->get('product_import_preview');
        $this->actingAs($owner)->post(route('owner.catalogue.import.approve'), ['token' => $preview['token']]);

        $update = "name,sku,category,brand,unit,retail_price,opening_stock\nHeavy Claw Hammer,HAM-1,Hand Tools,Acme,pc,12500,8\n";
        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('update.csv', $update),
        ])->assertSessionHas('status');

        $preview = $this->app['session']->get('product_import_preview');
        $this->assertSame(0, $preview['new']);
        $this->assertSame(1, $preview['updates']);
        $this->assertSame(0, $preview['unchanged']);
        $this->assertContains('name', $preview['update_rows'][0]['changes']);
        $this->assertContains('retail price', $preview['update_rows'][0]['changes']);
        $this->assertContains('quantity (approval required)', $preview['update_rows'][0]['changes']);

        $this->actingAs($owner)->post(route('owner.catalogue.import.approve'), ['token' => $preview['token']])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['business_id' => $business->id, 'sku' => 'HAM-1', 'name' => 'Heavy Claw Hammer']);
        $this->assertDatabaseHas('categories', ['business_id' => $business->id, 'name' => 'Hand Tools']);
        $this->assertDatabaseHas('product_prices', ['business_id' => $business->id, 'amount' => 12500, 'is_active' => true]);
        $this->assertDatabaseHas('stock_balances', ['business_id' => $business->id, 'quantity' => 5]);
        $this->assertDatabaseHas('stock_adjustments', ['business_id' => $business->id, 'status' => 'pending']);
        $this->assertDatabaseHas('stock_adjustment_items', ['business_id' => $business->id, 'physical_quantity' => 8, 'difference_quantity' => 3]);

        $this->actingAs($owner)->withSession($session)->post(route('owner.catalogue.import'), [
            'file' => UploadedFile::fake()->createWithContent('same-update.csv', $update),
        ]);
        $repeatPreview = $this->app['session']->get('product_import_preview');
        $this->assertSame(0, $repeatPreview['updates']);
        $this->assertSame(1, $repeatPreview['unchanged']);
        $this->actingAs($owner)->delete(route('owner.catalogue.import.reject'), ['token' => $repeatPreview['token']]);
        $this->assertDatabaseCount('stock_adjustments', 1);
    }

    private function fixture(): array
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $business = app(CreateBusinessAction::class)->execute($admin, $owner, ['name' => 'Product Hardware', 'code' => 'PRODUCTS']);
        $branch = $business->branches->first();

        return [$business, $owner, ['tenant.business_id' => $business->id, 'tenant.branch_id' => $branch->id]];
    }
}
