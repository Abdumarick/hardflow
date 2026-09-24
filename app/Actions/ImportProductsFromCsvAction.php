<?php

namespace App\Actions;

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\PriceLevel;
use App\Models\ProductBranchSetting;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\ProductCsvMatcher;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class ImportProductsFromCsvAction
{
    /** @var list<string> */
    private const COLUMNS = ['name', 'sku', 'barcode', 'category', 'brand', 'unit', 'retail_price', 'wholesale_price', 'opening_stock', 'minimum_stock', 'description'];

    public function __construct(
        private CreateProductAction $createProduct,
        private ChangeProductPriceAction $changePrice,
        private RecordOpeningStockAction $openingStock,
        private UpdateProductAction $updateProduct,
        private RequestStockAdjustmentAction $requestAdjustment,
        private ProductCsvMatcher $matcher,
    ) {}

    /** @return array{created:int,updated:int,unchanged:int,adjustments:int} */
    public function execute(User $actor, Business $business, UploadedFile $file): array
    {
        $branch = app(TenantContext::class)->branch();
        $csv = new SplFileObject($file->getRealPath());
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $rawHeader = $csv->fgetcsv();
        if (! is_array($rawHeader)) {
            throw ValidationException::withMessages(['file' => 'The CSV file is empty or unreadable.']);
        }
        $header = array_map(function ($value): string {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

            return strtolower(trim($value));
        }, $rawHeader);
        if (in_array('', $header, true)) {
            throw ValidationException::withMessages(['file' => 'CSV column names cannot be empty. Download the current template and try again.']);
        }
        $required = ['name', 'unit'];
        if (array_diff($required, $header)) {
            throw ValidationException::withMessages(['file' => 'CSV must contain the name and unit columns. Download the template and try again.']);
        }
        if (count($header) !== count(array_unique($header))) {
            throw ValidationException::withMessages(['file' => 'CSV column names must be unique.']);
        }
        $unsupported = array_diff($header, self::COLUMNS);
        if ($unsupported !== []) {
            throw ValidationException::withMessages(['file' => 'Unsupported CSV column(s): '.implode(', ', $unsupported).'. Download the current template and try again.']);
        }
        $processed = 0;
        $summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'adjustments' => 0];

        return DB::transaction(function () use ($actor, $business, $branch, $csv, $header, &$processed, &$summary): array {
            foreach ($csv as $index => $values) {
                if ($index === 0) {
                    continue;
                }
                if (! is_array($values) || collect($values)->every(fn ($value) => trim((string) $value) === '')) {
                    continue;
                }
                if ($processed >= 5000) {
                    throw ValidationException::withMessages(['file' => 'A single import can contain at most 5,000 products.']);
                }
                if (count($values) > count($header) && collect(array_slice($values, count($header)))->contains(fn ($value) => trim((string) $value) !== '')) {
                    throw ValidationException::withMessages(['file' => 'Row '.($index + 1).' contains more values than the CSV header.']);
                }
                $values = array_pad($values, count($header), null);
                $row = array_combine($header, array_slice($values, 0, count($header)));
                $line = $index + 1;
                $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
                $this->validateRow($row, $line, $branch !== null);
                $name = $row['name'];
                $unitName = $row['unit'];

                $unit = Unit::query()->where('business_id', $business->id)->where(fn ($query) => $query->where('name', $unitName)->orWhere('symbol', $unitName))->first();
                if (! $unit) {
                    throw ValidationException::withMessages(['file' => "Row {$line}: unit '{$unitName}' does not exist in this business."]);
                }
                try {
                    $category = $this->reference(Category::class, $business->id, $row['category'] ?? null);
                    $brand = $this->reference(Brand::class, $business->id, $row['brand'] ?? null);
                    $product = $this->matcher->match($business, $row);
                    $isExisting = $product !== null;
                    $changed = false;
                    if ($product) {
                        $baseUnit = $product->productUnits->firstWhere('is_base', true);
                        if (! $baseUnit || $baseUnit->unit_id !== $unit->id) {
                            throw ValidationException::withMessages(['unit' => 'The CSV unit differs from the existing base unit.']);
                        }
                        $attributes = [
                            'name' => $name,
                            'sku' => $row['sku'] ?: $product->sku,
                            'barcode' => filled($row['barcode']) ? $row['barcode'] : $product->barcode,
                            'description' => filled($row['description']) ? $row['description'] : $product->description,
                            'category_id' => filled($row['category']) ? $category?->id : $product->category_id,
                            'brand_id' => filled($row['brand']) ? $brand?->id : $product->brand_id,
                            'is_stock_tracked' => $product->is_stock_tracked,
                            'tracks_expiry' => $product->tracks_expiry,
                        ];
                        if ($product->only(['name', 'sku', 'barcode', 'description', 'category_id', 'brand_id']) !== collect($attributes)->only(['name', 'sku', 'barcode', 'description', 'category_id', 'brand_id'])->all()) {
                            $product = $this->updateProduct->execute($actor, $product, $attributes);
                            $changed = true;
                        }
                    } else {
                        $product = $this->createProduct->execute($actor, $business, [
                            'name' => $name, 'sku' => $row['sku'] ?: null, 'barcode' => $row['barcode'] ?: null,
                            'description' => $row['description'] ?: null, 'category_id' => $category?->id,
                            'brand_id' => $brand?->id, 'unit_id' => $unit->id, 'is_stock_tracked' => true,
                        ]);
                        $summary['created']++;
                    }
                    $productUnit = $product->productUnits->firstWhere('is_base', true);
                    foreach (['retail_price' => 'RETAIL', 'wholesale_price' => 'WHOLESALE'] as $column => $code) {
                        if (filled($row[$column])) {
                            $level = PriceLevel::query()->where('business_id', $business->id)->where('code', $code)->first();
                            if (! $level) {
                                throw ValidationException::withMessages(['price' => "{$code} price level is not configured."]);
                            }
                            $current = $productUnit->prices->first(fn ($price) => $price->is_active && $price->priceLevel->code === $code)?->amount;
                            if ($current === null || bccomp((string) $row[$column], (string) $current, 2) !== 0) {
                                $this->changePrice->execute($actor, $product, $productUnit, $level, (string) $row[$column], 'Product CSV import');
                                $changed = true;
                            }
                        }
                    }
                    if ($branch && filled($row['minimum_stock'])) {
                        $setting = ProductBranchSetting::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->first();
                        if (! $setting || bccomp((string) $setting->minimum_stock, (string) $row['minimum_stock'], 4) !== 0) {
                            if ($isExisting) {
                                Gate::forUser($actor)->authorize('update', $product);
                            }
                            ProductBranchSetting::query()->updateOrCreate(['branch_id' => $branch->id, 'product_id' => $product->id], ['business_id' => $business->id, 'minimum_stock' => $row['minimum_stock']]);
                            $changed = true;
                        }
                    }
                    if ($branch && ! $isExisting && (float) $row['opening_stock'] > 0) {
                        $this->openingStock->execute($actor, $branch, $product, (string) $row['opening_stock']);
                    } elseif ($branch && $isExisting && filled($row['opening_stock'])) {
                        $current = StockBalance::query()->where('branch_id', $branch->id)->where('product_id', $product->id)->where('stock_status', StockStatus::Available->value)->value('quantity') ?? 0;
                        $alreadyPending = DB::table('stock_adjustment_items')->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.adjustment_id')
                            ->where('stock_adjustments.branch_id', $branch->id)->where('stock_adjustments.status', 'pending')->where('stock_adjustment_items.product_id', $product->id)
                            ->where('stock_adjustment_items.stock_status', StockStatus::Available->value)->where('stock_adjustment_items.physical_quantity', $row['opening_stock'])->exists();
                        if (! $alreadyPending && bccomp((string) $current, (string) $row['opening_stock'], 4) !== 0) {
                            $this->requestAdjustment->execute($actor, $branch, $product, StockStatus::Available, (string) $row['opening_stock'], 'Physical quantity proposed by approved product CSV import');
                            $summary['adjustments']++;
                            $changed = true;
                        }
                    }
                    if ($isExisting) {
                        $summary[$changed ? 'updated' : 'unchanged']++;
                    }
                } catch (ValidationException $exception) {
                    $message = collect($exception->errors())->flatten()->first() ?? 'The row is invalid.';
                    throw ValidationException::withMessages(['file' => "Row {$line}: {$message}"]);
                }
                $processed++;
            }

            if ($processed === 0) {
                throw ValidationException::withMessages(['file' => 'The CSV does not contain any product rows.']);
            }

            return $summary;
        });
    }

    /** @param array<string, mixed> $row */
    private function validateRow(array &$row, int $line, bool $hasBranch): void
    {
        $row = array_merge(array_fill_keys(self::COLUMNS, ''), $row);
        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'], 'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:255'],
            'retail_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'opening_stock' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['file' => "Row {$line}: ".$validator->errors()->first()]);
        }
        if (! $hasBranch && ((float) $row['opening_stock'] > 0 || (float) $row['minimum_stock'] > 0)) {
            throw ValidationException::withMessages(['file' => "Row {$line}: select a branch before importing opening or minimum stock."]);
        }
    }

    private function reference(string $model, int $businessId, mixed $name): mixed
    {
        $name = trim((string) $name);

        return $name === '' ? null : $model::query()->firstOrCreate(['business_id' => $businessId, 'name' => $name]);
    }
}
