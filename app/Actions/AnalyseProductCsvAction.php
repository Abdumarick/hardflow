<?php

namespace App\Actions;

use App\Enums\PermissionName;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductBranchSetting;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Support\ProductCsvMatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SplFileObject;

class AnalyseProductCsvAction
{
    /** @var list<string> */
    private const COLUMNS = ['name', 'sku', 'barcode', 'category', 'brand', 'unit', 'retail_price', 'wholesale_price', 'opening_stock', 'minimum_stock', 'description'];

    public function __construct(private ProductCsvMatcher $matcher) {}

    /** @return array<string,mixed> */
    public function execute(User $actor, Business $business, UploadedFile $file, ?int $branchId): array
    {
        $csv = new SplFileObject($file->getRealPath());
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $rawHeader = $csv->fgetcsv();
        if (! is_array($rawHeader)) {
            throw ValidationException::withMessages(['file' => 'The CSV file is empty or unreadable.']);
        }
        $header = array_map(fn ($value) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), $rawHeader);
        $this->validateHeader($header);

        $result = ['total' => 0, 'new' => 0, 'updates' => 0, 'unchanged' => 0, 'complete' => 0, 'incomplete' => 0, 'blocked' => 0, 'complete_rows' => [], 'update_rows' => [], 'warnings' => [], 'errors' => [], 'missing_references' => ['units' => []]];
        $seenSkus = [];
        $seenBarcodes = [];
        $seenProducts = [];

        foreach ($csv as $index => $values) {
            if ($index === 0 || ! is_array($values) || collect($values)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }
            if ($result['total'] >= 5000) {
                throw ValidationException::withMessages(['file' => 'A single import can contain at most 5,000 products.']);
            }
            $line = $index + 1;
            $result['total']++;
            if (count($values) > count($header) && collect(array_slice($values, count($header)))->contains(fn ($value) => trim((string) $value) !== '')) {
                $this->block($result, $line, 'The row contains more values than the CSV header.');

                continue;
            }
            $row = array_merge(array_fill_keys(self::COLUMNS, ''), array_combine($header, array_slice(array_pad($values, count($header), null), 0, count($header))));
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
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
                $this->block($result, $line, $validator->errors()->first());

                continue;
            }
            if (! $branchId && (filled($row['opening_stock']) || filled($row['minimum_stock']))) {
                $this->block($result, $line, 'Select a branch before importing opening or minimum stock.');

                continue;
            }
            $unit = Unit::query()->where('business_id', $business->id)->where(fn ($query) => $query->where('name', $row['unit'])->orWhere('symbol', $row['unit']))->first();
            if (! $unit) {
                $result['missing_references']['units'][mb_strtolower($row['unit'])] = $row['unit'];
                $this->block($result, $line, "Unit '{$row['unit']}' does not exist in this business.");

                continue;
            }
            foreach (['sku' => &$seenSkus, 'barcode' => &$seenBarcodes] as $column => &$seen) {
                if (blank($row[$column])) {
                    continue;
                }
                $key = strtoupper((string) $row[$column]);
                if (isset($seen[$key])) {
                    $this->block($result, $line, ucfirst($column).' is repeated in this CSV file.');

                    continue 2;
                }
                $seen[$key] = true;
            }

            try {
                $product = $this->matcher->match($business, $row);
            } catch (ValidationException $exception) {
                $this->block($result, $line, collect($exception->errors())->flatten()->first());

                continue;
            }
            if ($product && isset($seenProducts[$product->id])) {
                $this->block($result, $line, 'This existing product is represented by more than one CSV row.');

                continue;
            }
            if ($product) {
                $seenProducts[$product->id] = true;
                $baseUnit = $product->productUnits->firstWhere('is_base', true);
                if (! $baseUnit || $baseUnit->unit_id !== $unit->id) {
                    $this->block($result, $line, 'The CSV unit differs from the existing base unit. Change units from Product Management.');

                    continue;
                }
                $changes = $this->changes($product, $row, $branchId);
                $metadataChanges = array_diff($changes, ['retail price', 'wholesale price', 'quantity (approval required)']);
                if ($metadataChanges !== [] && ! $actor->hasPermissionInBusiness(PermissionName::ProductsUpdate, $business)) {
                    $this->block($result, $line, 'You do not have permission to update existing product details.');

                    continue;
                }
                if (array_intersect($changes, ['retail price', 'wholesale price']) && ! $actor->hasPermissionInBusiness(PermissionName::ProductsChangePrice, $business)) {
                    $this->block($result, $line, 'You do not have permission to change existing product prices.');

                    continue;
                }
                if (in_array('quantity (approval required)', $changes, true) && ! $actor->hasPermissionInBusiness(PermissionName::InventoryAdjustRequest, $business)) {
                    $this->block($result, $line, 'You do not have permission to request a stock quantity adjustment.');

                    continue;
                }
                if ($changes === []) {
                    $result['unchanged']++;
                } else {
                    $result['updates']++;
                    if (count($result['update_rows']) < 100) {
                        $result['update_rows'][] = ['row' => $line, 'name' => $product->name, 'changes' => $changes];
                    }
                }
            } else {
                if ((filled($row['retail_price']) || filled($row['wholesale_price'])) && ! $actor->hasPermissionInBusiness(PermissionName::ProductsChangePrice, $business)) {
                    $this->block($result, $line, 'You do not have permission to import product prices.');

                    continue;
                }
                if ((float) $row['opening_stock'] > 0 && ! $actor->hasPermissionInBusiness(PermissionName::InventoryOpening, $business)) {
                    $this->block($result, $line, 'You do not have permission to import opening stock.');

                    continue;
                }
                $result['new']++;
            }

            $missing = collect(['retail_price' => 'RETAIL', 'wholesale_price' => 'WHOLESALE'])->filter(function ($code, $column) use ($row, $product): bool {
                if (filled($row[$column])) {
                    return false;
                }

                return ! $product?->productUnits->firstWhere('is_base', true)?->prices->contains(fn ($price) => $price->is_active && $price->priceLevel->code === $code);
            })->keys()->values()->all();
            if ($missing !== []) {
                $result['incomplete']++;
                if (count($result['warnings']) < 100) {
                    $result['warnings'][] = ['row' => $line, 'name' => $row['name'], 'missing' => $missing];
                }
            } else {
                $result['complete']++;
                if (count($result['complete_rows']) < 100) {
                    $result['complete_rows'][] = ['row' => $line] + $row;
                }
            }
        }

        if ($result['total'] === 0) {
            throw ValidationException::withMessages(['file' => 'The CSV does not contain any product rows.']);
        }

        $result['missing_references']['units'] = array_values($result['missing_references']['units']);

        return $result;
    }

    /** @param array<string,mixed> $row
     * @return list<string>
     */
    private function changes(Product $product, array $row, ?int $branchId): array
    {
        $changes = [];
        foreach (['name', 'barcode', 'description'] as $field) {
            if (filled($row[$field]) && trim((string) $row[$field]) !== trim((string) $product->{$field})) {
                $changes[] = str_replace('_', ' ', $field);
            }
        }
        foreach (['category', 'brand'] as $relation) {
            if (filled($row[$relation]) && strcasecmp(trim($row[$relation]), (string) $product->{$relation}?->name) !== 0) {
                $changes[] = $relation;
            }
        }
        $baseUnit = $product->productUnits->firstWhere('is_base', true);
        foreach (['retail_price' => 'RETAIL', 'wholesale_price' => 'WHOLESALE'] as $column => $code) {
            $current = $baseUnit?->prices->first(fn ($price) => $price->is_active && $price->priceLevel->code === $code)?->amount;
            if (filled($row[$column]) && bccomp((string) $row[$column], (string) ($current ?? 0), 2) !== 0) {
                $changes[] = str_replace('_', ' ', $column);
            }
        }
        if ($branchId && filled($row['minimum_stock'])) {
            $current = ProductBranchSetting::query()->where('branch_id', $branchId)->where('product_id', $product->id)->value('minimum_stock') ?? 0;
            if (bccomp((string) $row['minimum_stock'], (string) $current, 4) !== 0) {
                $changes[] = 'minimum stock';
            }
        }
        if ($branchId && filled($row['opening_stock'])) {
            $current = StockBalance::query()->where('branch_id', $branchId)->where('product_id', $product->id)->where('stock_status', 'available')->value('quantity') ?? 0;
            $alreadyPending = DB::table('stock_adjustment_items')->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.adjustment_id')
                ->where('stock_adjustments.branch_id', $branchId)->where('stock_adjustments.status', 'pending')->where('stock_adjustment_items.product_id', $product->id)
                ->where('stock_adjustment_items.stock_status', 'available')->where('stock_adjustment_items.physical_quantity', $row['opening_stock'])->exists();
            if (! $alreadyPending && bccomp((string) $row['opening_stock'], (string) $current, 4) !== 0) {
                $changes[] = 'quantity (approval required)';
            }
        }

        return $changes;
    }

    /** @param list<string> $header */
    private function validateHeader(array $header): void
    {
        if (in_array('', $header, true)) {
            throw ValidationException::withMessages(['file' => 'CSV column names cannot be empty.']);
        }
        if (array_diff(['name', 'unit'], $header)) {
            throw ValidationException::withMessages(['file' => 'CSV must contain the name and unit columns.']);
        }
        if (count($header) !== count(array_unique($header))) {
            throw ValidationException::withMessages(['file' => 'CSV column names must be unique.']);
        }
        $unsupported = array_diff($header, self::COLUMNS);
        if ($unsupported !== []) {
            throw ValidationException::withMessages(['file' => 'Unsupported CSV column(s): '.implode(', ', $unsupported).'.']);
        }
    }

    /** @param array<string,mixed> $result */
    private function block(array &$result, int $row, string $message): void
    {
        $result['blocked']++;
        if (count($result['errors']) < 100) {
            $result['errors'][] = ['row' => $row, 'message' => $message];
        }
    }
}
