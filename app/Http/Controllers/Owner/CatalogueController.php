<?php

namespace App\Http\Controllers\Owner;

use App\Actions\AnalyseProductCsvAction;
use App\Actions\AssignProductUnitAction;
use App\Actions\ChangeProductPriceAction;
use App\Actions\CreateProductAction;
use App\Actions\ImportProductsFromCsvAction;
use App\Actions\UpdateCategoryAction;
use App\Actions\UpdateProductAction;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\PriceLevel;
use App\Models\Product;
use App\Models\ProductBranchSetting;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogueController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        Gate::authorize('viewAny', Product::class);
        $business = $tenant->businessOrFail();

        $branch = $tenant->branch();
        $query = Product::query()->where('business_id', $business->id)
            ->with(['category', 'brand', 'productUnits.unit', 'productUnits.prices.priceLevel'])
            ->withSum(['stockBalances as available_stock' => fn ($stock) => $stock->when($branch, fn ($q) => $q->where('branch_id', $branch->id))->where('stock_status', 'available')], 'quantity')
            ->withAvg(['stockBalances as average_cost' => fn ($stock) => $stock->when($branch, fn ($q) => $q->where('branch_id', $branch->id))->where('stock_status', 'available')], 'average_cost')
            ->with(['branchSettings' => fn ($settings) => $settings->when($branch, fn ($q) => $q->where('branch_id', $branch->id))])
            ->search($request->string('search')->toString())
            ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->integer('brand'), fn ($q, $id) => $q->where('brand_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->integer('price_level'), fn ($q, $id) => $q->whereHas('productUnits.prices', fn ($prices) => $prices->where('price_level_id', $id)->where('is_active', true)))
            ->orderBy('name');

        $allProducts = Product::query()->where('business_id', $business->id);
        $totalProducts = (clone $allProducts)->count();
        $activeProducts = (clone $allProducts)->where('is_active', true)->count();
        $stockRows = $branch ? StockBalance::query()->where('branch_id', $branch->id)->where('stock_status', 'available')->get()->keyBy('product_id') : collect();
        $minimums = $branch ? ProductBranchSetting::query()->where('branch_id', $branch->id)->pluck('minimum_stock', 'product_id') : collect();
        $lowStockProducts = $minimums->filter(fn ($minimum, $productId) => (float) ($stockRows->get($productId)?->quantity ?? 0) <= (float) $minimum)->count();
        $stockValue = $stockRows->sum(fn ($stock) => (float) $stock->quantity * (float) $stock->average_cost);
        if ($request->filled('stock_status')) {
            $matchingIds = (clone $allProducts)->pluck('id')->filter(function ($productId) use ($request, $stockRows, $minimums): bool {
                $quantity = (float) ($stockRows->get($productId)?->quantity ?? 0);
                $minimum = (float) ($minimums->get($productId) ?? 0);

                return match ($request->string('stock_status')->toString()) {
                    'out' => $quantity <= 0,
                    'low' => $quantity > 0 && $minimum > 0 && $quantity <= $minimum,
                    'in' => $quantity > 0 && ($minimum <= 0 || $quantity > $minimum),
                    default => true,
                };
            });
            $query->whereIn('id', $matchingIds);
        }

        return view('owner.catalogue.index', [
            'business' => $business,
            'branch' => $branch,
            'products' => $query->paginate($request->integer('per_page') && in_array($request->integer('per_page'), [10, 15, 25, 50, 100], true) ? $request->integer('per_page') : 15)->withQueryString(),
            'productMetrics' => compact('totalProducts', 'activeProducts', 'lowStockProducts', 'stockValue'),
            'categories' => Category::query()->where('business_id', $business->id)->with('parent')->orderBy('name')->get(),
            'brands' => Brand::query()->where('business_id', $business->id)->orderBy('name')->get(),
            'units' => Unit::query()->where('business_id', $business->id)->orderBy('name')->get(),
            'priceLevels' => PriceLevel::query()->where('business_id', $business->id)->orderBy('name')->get(),
            'importPreview' => $request->session()->get('product_import_preview'),
        ]);
    }

    public function import(Request $request, TenantContext $tenant, AnalyseProductCsvAction $action): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $file = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']])['file'];
        $business = $tenant->businessOrFail();
        $analysis = $action->execute($request->user(), $business, $file, $tenant->branchId());
        $this->discardPendingImport($request);
        $path = $file->storeAs('product-imports', Str::uuid().'.csv');
        $request->session()->put('product_import_preview', $analysis + [
            'token' => (string) Str::uuid(),
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'business_id' => $business->id,
            'branch_id' => $tenant->branchId(),
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', 'CSV analysed. Review the summary before approving the import.');
    }

    public function approveImport(Request $request, TenantContext $tenant, ImportProductsFromCsvAction $action): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $preview = $this->pendingImportOrFail($request, $tenant);
        if ((int) $preview['blocked'] > 0) {
            throw ValidationException::withMessages(['file' => 'Correct the blocked CSV rows and upload the file again before approval.']);
        }
        $path = Storage::path($preview['path']);
        abort_unless(is_file($path), 410, 'The pending import file has expired. Upload it again.');
        $file = new UploadedFile($path, $preview['filename'], 'text/csv', null, true);
        $summary = $action->execute($request->user(), $tenant->businessOrFail(), $file);
        $this->discardPendingImport($request);

        return back()->with('status', "CSV approved: {$summary['created']} created, {$summary['updated']} updated, {$summary['unchanged']} already current".($summary['adjustments'] ? ", {$summary['adjustments']} quantity adjustments sent for approval." : '.'));
    }

    public function rejectImport(Request $request, TenantContext $tenant): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $this->pendingImportOrFail($request, $tenant);
        $this->discardPendingImport($request);

        return back()->with('status', 'The pending CSV import was rejected. No products were created.');
    }

    public function storeImportUnit(Request $request, TenantContext $tenant, AnalyseProductCsvAction $action): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $preview = $this->pendingImportOrFail($request, $tenant);
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units')->where('business_id', $business->id)],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('units')->where('business_id', $business->id)],
        ]);
        $unit = Unit::query()->create($data + ['business_id' => $business->id, 'allows_decimal' => $request->boolean('allows_decimal')]);
        $this->audit($request, $business->id, 'unit.created_from_csv_import', $unit, ['name' => $unit->name, 'symbol' => $unit->symbol]);

        $this->refreshPendingImport($request, $tenant, $action, $preview);

        return back()->with('status', "Unit '{$unit->name}' registered. The saved CSV was checked again.");
    }

    public function continueImportWithMissingUnits(Request $request, TenantContext $tenant, AnalyseProductCsvAction $analysisAction, ImportProductsFromCsvAction $importAction): RedirectResponse
    {
        Gate::authorize('create', Product::class);
        $preview = $this->pendingImportOrFail($request, $tenant);
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $missingUnits = collect($preview['missing_references']['units'] ?? [])->filter(fn ($unit) => is_string($unit) && filled($unit))->values();
        abort_if($missingUnits->isEmpty(), 422, 'There are no missing units to register for this import.');

        $registered = $this->registerMissingImportUnits($request, $business, $missingUnits->all());
        $this->refreshPendingImport($request, $tenant, $analysisAction, $preview);
        $preview = $request->session()->get('product_import_preview');

        if ((int) ($preview['blocked'] ?? 0) > 0) {
            return back()->with('status', "{$registered} missing unit(s) were registered. Review the remaining rows requiring correction.");
        }

        $path = Storage::path($preview['path']);
        abort_unless(is_file($path), 410, 'The pending import file has expired. Upload it again.');
        $file = new UploadedFile($path, $preview['filename'], 'text/csv', null, true);
        $summary = $importAction->execute($request->user(), $business, $file);
        $this->discardPendingImport($request);

        return back()->with('status', "CSV continued: {$registered} missing unit(s) registered; {$summary['created']} created, {$summary['updated']} updated, {$summary['unchanged']} already current.");
    }

    public function export(Request $request, TenantContext $tenant): StreamedResponse
    {
        Gate::authorize('viewAny', Product::class);
        $business = $tenant->businessOrFail();
        $selected = collect(explode(',', $request->string('products')->toString()))->filter()->take(500);
        $products = Product::query()->where('business_id', $business->id)
            ->when($selected->isNotEmpty(), fn ($query) => $query->whereIn('public_id', $selected))
            ->with(['category', 'brand', 'productUnits.unit', 'productUnits.prices.priceLevel'])->orderBy('name')->get();

        return response()->streamDownload(function () use ($business, $products): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [$business->name]);
            fputcsv($output, ['HardFlow']);
            fputcsv($output, []);
            fputcsv($output, ['name', 'sku', 'barcode', 'category', 'brand', 'unit', 'retail_price', 'wholesale_price', 'description']);
            foreach ($products as $product) {
                $unit = $product->productUnits->firstWhere('is_base', true);
                $price = fn (string $code) => $unit?->prices->first(fn ($item) => $item->is_active && $item->priceLevel->code === $code)?->amount;
                fputcsv($output, array_map($this->csvSafe(...), [$product->name, $product->sku, $product->barcode, $product->category?->name, $product->brand?->name, $unit?->unit->symbol, $price('RETAIL'), $price('WHOLESALE'), $product->description]));
            }
            fclose($output);
        }, 'hardflow-products-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importTemplate(): StreamedResponse
    {
        Gate::authorize('create', Product::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['name', 'sku', 'barcode', 'category', 'brand', 'unit', 'retail_price', 'wholesale_price', 'opening_stock', 'minimum_stock', 'description']);
            fputcsv($output, ['Portland Cement 50kg', 'CEM-50', '6001234560001', 'Cement', 'Bamburi', 'bag', '28500', '27000', '50', '10', 'Example row - remove before importing']);
            fclose($output);
        }, 'hardflow-product-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvSafe(mixed $value): mixed
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    /** @return array<string,mixed> */
    private function pendingImportOrFail(Request $request, TenantContext $tenant): array
    {
        $preview = $request->session()->get('product_import_preview');
        abort_unless(is_array($preview)
            && hash_equals((string) ($preview['token'] ?? ''), $request->string('token')->toString())
            && (int) ($preview['business_id'] ?? 0) === $tenant->businessId()
            && (int) ($preview['branch_id'] ?? 0) === (int) $tenant->branchId()
            && (int) ($preview['user_id'] ?? 0) === $request->user()->id, 403);

        return $preview;
    }

    private function discardPendingImport(Request $request): void
    {
        $preview = $request->session()->pull('product_import_preview');
        if (is_array($preview) && isset($preview['path'])) {
            Storage::delete($preview['path']);
        }
    }

    /** @param array<string,mixed> $preview */
    private function refreshPendingImport(Request $request, TenantContext $tenant, AnalyseProductCsvAction $action, array $preview): void
    {
        $path = Storage::path($preview['path']);
        abort_unless(is_file($path), 410, 'The pending import file has expired. Upload it again.');
        $file = new UploadedFile($path, $preview['filename'], 'text/csv', null, true);
        $analysis = $action->execute($request->user(), $tenant->businessOrFail(), $file, $tenant->branchId());

        $request->session()->put('product_import_preview', array_merge($preview, $analysis));
    }

    /** @param list<string> $missingUnits */
    private function registerMissingImportUnits(Request $request, Business $business, array $missingUnits): int
    {
        return DB::transaction(function () use ($request, $business, $missingUnits): int {
            $registered = 0;
            foreach ($missingUnits as $name) {
                if (Unit::query()->where('business_id', $business->id)->where('name', $name)->exists()) {
                    continue;
                }
                $baseSymbol = Str::limit(Str::slug($name), 20, '') ?: 'unit';
                $symbol = $baseSymbol;
                $number = 2;
                while (Unit::query()->where('business_id', $business->id)->where('symbol', $symbol)->exists()) {
                    $suffix = '-'.$number++;
                    $symbol = Str::limit($baseSymbol, 20 - strlen($suffix), '').$suffix;
                }
                $unit = Unit::query()->create(['business_id' => $business->id, 'name' => $name, 'symbol' => $symbol, 'allows_decimal' => false]);
                $this->audit($request, $business->id, 'unit.created_from_csv_import', $unit, ['name' => $unit->name, 'symbol' => $unit->symbol]);
                $registered++;
            }

            return $registered;
        });
    }

    public function storeCategory(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer'], 'description' => ['nullable', 'string', 'max:2000']]);
        if ($data['parent_id'] ?? null) {
            Category::query()->where('business_id', $business->id)->findOrFail($data['parent_id']);
        }
        $duplicate = Category::query()->where('business_id', $business->id)->where('name', trim($data['name']))
            ->where(fn ($query) => isset($data['parent_id']) ? $query->where('parent_id', $data['parent_id']) : $query->whereNull('parent_id'))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'This category name already exists at the selected level.']);
        }
        $category = Category::query()->create($data + ['business_id' => $business->id]);
        $this->audit($request, $business->id, 'category.created', $category, ['name' => $category->name]);

        return back()->with('status', 'Category created.');
    }

    public function storeBrand(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('brands')->where('business_id', $business->id)], 'description' => ['nullable', 'string', 'max:2000']]);
        $brand = Brand::query()->create($data + ['business_id' => $business->id]);
        $this->audit($request, $business->id, 'brand.created', $brand, ['name' => $brand->name]);

        return back()->with('status', 'Brand created.');
    }

    public function storeUnit(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units')->where('business_id', $business->id)],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('units')->where('business_id', $business->id)],
        ]);
        $unit = Unit::query()->create($data + ['business_id' => $business->id, 'allows_decimal' => $request->boolean('allows_decimal')]);
        $this->audit($request, $business->id, 'unit.created', $unit, ['name' => $unit->name, 'symbol' => $unit->symbol]);

        return back()->with('status', 'Unit created.');
    }

    public function storePriceLevel(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->authorizeReferenceUpdate($request, $tenant);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('price_levels')->where('business_id', $business->id)],
            'code' => ['required', 'string', 'max:30', Rule::unique('price_levels')->where('business_id', $business->id)],
        ]);
        $level = PriceLevel::query()->create(['business_id' => $business->id, 'name' => $data['name'], 'code' => Str::upper($data['code'])]);
        $this->audit($request, $business->id, 'price_level.created', $level, ['name' => $level->name, 'code' => $level->code]);

        return back()->with('status', 'Price level created.');
    }

    public function storeProduct(Request $request, TenantContext $tenant, CreateProductAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer'], 'brand_id' => ['nullable', 'integer'], 'unit_id' => ['required', 'integer'],
        ]);
        $data['is_stock_tracked'] = $request->boolean('is_stock_tracked');
        $data['tracks_expiry'] = $request->boolean('tracks_expiry');
        $action->execute($request->user(), $tenant->businessOrFail(), $data);

        return back()->with('status', 'Product created.');
    }

    public function updateProduct(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'sku' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer'], 'brand_id' => ['nullable', 'integer'],
        ]);
        $data['is_stock_tracked'] = $request->boolean('is_stock_tracked');
        $data['tracks_expiry'] = $request->boolean('tracks_expiry');
        $action->execute($request->user(), $product, $data);

        return back()->with('status', 'Product updated.');
    }

    public function updateProductPicture(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);
        $request->validate(['picture' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);
        $oldPath = $product->image_path;
        $path = $request->file('picture')->store("products/{$product->business_id}", 'public');
        $product->update(['image_path' => $path]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
        $this->audit($request, $product->business_id, 'product.picture_updated', $product, ['image_path' => $path]);

        return back()->with('status', 'Product picture updated.');
    }

    public function setProductActive(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:1000']]);
        $action->setActive($request->user(), $product, (bool) $data['is_active'], $data['reason']);

        return back()->with('status', $data['is_active'] ? 'Product activated.' : 'Product deactivated.');
    }

    public function updateCategory(Request $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'parent_id' => ['nullable', 'integer'], 'description' => ['nullable', 'string', 'max:2000']]);
        $parent = isset($data['parent_id']) ? Category::query()->findOrFail($data['parent_id']) : null;
        $action->execute($request->user(), $category, $data['name'], $parent, $data['description'] ?? null);

        return back()->with('status', 'Category updated.');
    }

    public function storeProductUnit(Request $request, Product $product, AssignProductUnitAction $action): RedirectResponse
    {
        $data = $request->validate(['unit_id' => ['required', 'integer'], 'conversion_factor' => ['required', 'numeric', 'gt:0']]);
        $action->execute($request->user(), $product, Unit::query()->findOrFail($data['unit_id']), (string) $data['conversion_factor'], $request->boolean('can_purchase'), $request->boolean('can_sell'));

        return back()->with('status', 'Product unit saved.');
    }

    public function storePrice(Request $request, Product $product, ChangeProductPriceAction $action): RedirectResponse
    {
        $data = $request->validate(['product_unit_id' => ['required', 'integer'], 'price_level_id' => ['required', 'integer'], 'amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'], 'reason' => ['required', 'string', 'max:1000']]);
        $action->execute($request->user(), $product, ProductUnit::query()->findOrFail($data['product_unit_id']), PriceLevel::query()->findOrFail($data['price_level_id']), (string) $data['amount'], $data['reason']);

        return back()->with('status', 'Price updated with history preserved.');
    }

    public function storeMinimumStock(Request $request, Product $product, TenantContext $tenant): RedirectResponse
    {
        Gate::authorize('update', $product);
        $branch = $tenant->branch();
        abort_if($branch === null, 422, 'Select a branch first.');
        $data = $request->validate(['minimum_stock' => ['required', 'numeric', 'min:0', 'decimal:0,4']]);
        ProductBranchSetting::query()->updateOrCreate(['branch_id' => $branch->id, 'product_id' => $product->id], ['business_id' => $tenant->businessId(), 'minimum_stock' => $data['minimum_stock']]);
        $this->audit($request, $tenant->businessId(), 'product.minimum_stock_changed', $product, ['branch_id' => $branch->id, 'minimum_stock' => $data['minimum_stock']]);

        return back()->with('status', 'Minimum stock setting saved.');
    }

    private function authorizeReferenceUpdate(Request $request, TenantContext $tenant): Business
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::ProductsUpdate, $business), 403);

        return $business;
    }

    private function audit(Request $request, int $businessId, string $action, object $subject, array $values): void
    {
        AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $businessId, 'branch_id' => app(TenantContext::class)->branchId(), 'action' => $action, 'subject_type' => $subject::class, 'subject_id' => $subject->id, 'new_values' => $values, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
    }
}
