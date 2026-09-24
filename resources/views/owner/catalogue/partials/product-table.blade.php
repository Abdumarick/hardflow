<div class="overflow-hidden rounded-2xl border bg-white shadow-sm" x-data="{ selected: [] }">
    <div x-show="selected.length" x-cloak class="flex items-center justify-between border-b bg-blue-50 px-4 py-3 text-sm"><span><strong x-text="selected.length"></strong> {{ __('catalogue.selected') }}</span><a :href="@js(route('owner.catalogue.export')).concat('?products=', encodeURIComponent(selected.join(',')))" class="font-bold text-blue-700">{{ __('catalogue.export_selected') }}</a></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-4"><span class="sr-only">{{ __('catalogue.select') }}</span></th><th class="px-3 py-4">{{ __('catalogue.product_name') }}</th><th class="px-3 py-4">{{ __('catalogue.sku') }}</th><th class="px-3 py-4">{{ __('catalogue.category') }}</th><th class="px-3 py-4">{{ __('catalogue.brand') }}</th><th class="px-3 py-4">{{ __('catalogue.base_unit') }}</th><th class="px-3 py-4">{{ __('catalogue.retail_price') }}</th><th class="px-3 py-4">{{ __('catalogue.wholesale_price') }}</th><th class="px-3 py-4">{{ __('catalogue.cost_price') }}</th><th class="px-3 py-4">{{ __('catalogue.stock') }}</th><th class="px-3 py-4">{{ __('catalogue.status') }}</th><th class="px-3 py-4">{{ __('catalogue.actions') }}</th></tr></thead>
            <tbody class="divide-y">
                @forelse($products as $product)
                    @php
                        $baseUnit = $product->productUnits->firstWhere('is_base', true);
                        $retail = $baseUnit?->prices->first(fn($price) => $price->is_active && $price->priceLevel->code === 'RETAIL');
                        $wholesale = $baseUnit?->prices->first(fn($price) => $price->is_active && $price->priceLevel->code === 'WHOLESALE');
                        $stock = (float) ($product->available_stock ?? 0);
                        $minimum = (float) ($product->branchSettings->first()?->minimum_stock ?? 0);
                        $stockState = $stock <= 0 ? 'out' : ($minimum > 0 && $stock <= $minimum ? 'low' : 'in');
                    @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-4"><input type="checkbox" value="{{ $product->public_id }}" x-model="selected" class="rounded border-slate-300 text-blue-700"></td>
                        <td class="whitespace-nowrap px-3 py-4"><div class="flex items-center gap-2">@if($product->image_path)<img src="{{ Storage::url($product->image_path) }}" alt="" class="h-9 w-9 rounded-lg object-cover">@else<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">◇</span>@endif<div><p class="font-bold text-slate-950">{{ $product->name }}</p>@if($product->barcode)<p class="font-mono text-[10px] text-slate-400">{{ $product->barcode }}</p>@endif</div></div></td>
                        <td class="whitespace-nowrap px-3 py-4 font-mono text-xs text-slate-500">{{ $product->sku }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $product->category?->name ?? __('catalogue.uncategorized') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $product->brand?->name ?? __('catalogue.no_brand') }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $baseUnit?->unit->symbol ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 font-bold">{{ $retail ? $business->currency.' '.number_format((float)$retail->amount, 2) : '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-4">{{ $wholesale ? $business->currency.' '.number_format((float)$wholesale->amount, 2) : '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-slate-500">{{ $business->currency }} {{ number_format((float)($product->average_cost ?? 0), 2) }}</td>
                        <td class="whitespace-nowrap px-3 py-4"><p class="font-black {{ $stockState === 'out' ? 'text-rose-600' : ($stockState === 'low' ? 'text-amber-600' : 'text-slate-950') }}">{{ number_format($stock, 2) }} <small class="font-normal text-slate-400">{{ $baseUnit?->unit->symbol }}</small></p><p class="text-[11px] {{ $stockState === 'out' ? 'text-rose-600' : ($stockState === 'low' ? 'text-amber-600' : 'text-emerald-600') }}">{{ __('catalogue.stock_'.$stockState) }}</p></td>
                        <td class="px-3 py-4"><span class="font-bold {{ $product->is_active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $product->is_active ? __('catalogue.active') : __('catalogue.inactive') }}</span></td>
                        <td class="px-3 py-4"><button type="button" @click="panel='pricing'; $nextTick(() => document.getElementById('product-{{ $product->public_id }}')?.scrollIntoView({behavior:'smooth'}))" class="rounded-lg border px-3 py-2 text-xs font-bold text-blue-700">{{ __('catalogue.manage') }}</button></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-5 py-16 text-center text-slate-500">{{ __('catalogue.no_products') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-col justify-between gap-3 border-t bg-slate-50/60 p-4 sm:flex-row sm:items-center"><form method="GET" class="flex items-center gap-2 text-sm text-slate-500">@foreach(request()->except(['per_page','page']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<span>{{ __('catalogue.show') }}</span><select name="per_page" onchange="this.form.submit()" class="rounded-lg border-slate-200 py-1 text-xs">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected($products->perPage()===$size)>{{ $size }}</option>@endforeach</select><span>{{ __('catalogue.per_page') }}</span></form>{{ $products->links() }}</div>
</div>
