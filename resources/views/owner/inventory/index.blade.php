<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-700">{{ $business->name }} · {{ $branch->name }}</p>
        <h1 class="mt-1 text-2xl font-bold">{{ __('inventory.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('inventory.subtitle') }}</p>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="{ tab: @js(in_array(request('tab'), ['balances','opening','adjust','counts','movements'], true) ? request('tab') : 'balances') }">
        @if(session('status'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
        @if($lowStockCount > 0)<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">{{ trans_choice('inventory.low_stock', $lowStockCount, ['count' => $lowStockCount]) }}</div>@endif

        <div class="flex gap-2 overflow-x-auto rounded-2xl border bg-white p-2">
            @foreach(__('inventory.tabs') as $key => $label)
                <button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-blue-700 text-white' : 'text-slate-600'" class="whitespace-nowrap rounded-xl px-4 py-3 text-sm font-bold">{{ $label }}</button>
            @endforeach
        </div>

        <section x-show="tab === 'balances'" class="overflow-x-auto rounded-3xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">{{ __('inventory.product') }}</th>@foreach(\App\Enums\StockStatus::cases() as $status)<th class="p-4">{{ $status->label() }}</th>@endforeach<th class="p-4">{{ __('inventory.minimum') }}</th></tr></thead>
                <tbody class="divide-y">@forelse($products as $product)<tr><td class="p-4"><p class="font-bold">{{ $product->name }}</p><p class="text-xs text-slate-500">{{ $product->sku }}</p></td>
                    @foreach(\App\Enums\StockStatus::cases() as $status)
                        @php($quantity = $balances->get($product->id)?->firstWhere('stock_status', $status)?->quantity ?? '0.0000')
                        <td class="p-4 font-mono {{ $status === \App\Enums\StockStatus::Available ? 'font-bold text-blue-700' : 'text-slate-500' }}">{{ $quantity }}</td>
                    @endforeach
                    <td class="p-4">{{ $product->branchSettings->first()?->minimum_stock ?? '—' }}</td></tr>
                @empty<tr><td colspan="7" class="p-12 text-center text-slate-500">{{ __('inventory.no_products') }}</td></tr>@endforelse</tbody>
            </table>
        </section>

        <section x-show="tab === 'opening'" x-cloak class="rounded-3xl border bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">{{ __('inventory.opening_title') }}</h2><p class="mt-1 text-sm text-amber-700">{{ __('inventory.opening_help') }}</p>
            <form method="POST" action="{{ route('owner.inventory.opening.store') }}" class="mt-5 grid gap-4 md:grid-cols-4">@csrf
                <select name="product_id" required class="rounded-xl border-slate-200"><option value="">{{ __('inventory.product') }}</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
                <select name="stock_status" class="rounded-xl border-slate-200">@foreach(\App\Enums\StockStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>
                <input name="quantity" type="number" min="0.0001" step="0.0001" required class="rounded-xl border-slate-200" placeholder="{{ __('inventory.quantity') }}">
                <button class="rounded-xl bg-blue-700 px-5 py-3 font-bold text-white">{{ __('inventory.record_movement') }}</button>
            </form>
        </section>

        <section x-show="tab === 'adjust'" x-cloak class="space-y-5">
            <div class="rounded-3xl border bg-white p-6 shadow-sm"><h2 class="text-lg font-bold">{{ __('inventory.request_adjustment') }}</h2>
                <form method="POST" action="{{ route('owner.inventory.adjustments.store') }}" class="mt-5 grid gap-4 md:grid-cols-5">@csrf
                    <select name="product_id" required class="rounded-xl border-slate-200"><option value="">{{ __('inventory.product') }}</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>
                    <select name="stock_status" class="rounded-xl border-slate-200">@foreach(\App\Enums\StockStatus::cases() as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>
                    <input name="physical_quantity" type="number" min="0" step="0.0001" required class="rounded-xl border-slate-200" placeholder="{{ __('inventory.physical_quantity') }}">
                    <input name="reason" required minlength="10" class="rounded-xl border-slate-200" placeholder="{{ __('inventory.reason_evidence') }}">
                    <button class="rounded-xl bg-slate-900 px-5 py-3 font-bold text-white">{{ __('inventory.request_approval') }}</button>
                </form>
            </div>
            @foreach($adjustments as $adjustment)<div class="rounded-2xl border bg-white p-5"><div class="flex justify-between"><div><p class="font-bold">{{ $adjustment->items->first()?->product?->name }}</p><p class="text-sm text-slate-500">{{ __('inventory.system_physical', ['system' => $adjustment->items->first()?->system_quantity, 'physical' => $adjustment->items->first()?->physical_quantity]) }}</p></div><span class="text-sm font-bold uppercase">{{ $adjustment->status->value }}</span></div>
                @if($adjustment->status === \App\Enums\StockAdjustmentStatus::Pending && $adjustment->requested_by !== auth()->id())<form method="POST" action="{{ route('owner.inventory.adjustments.decide', $adjustment) }}" class="mt-4 flex gap-3">@csrf @method('PUT')<input name="reason" required class="flex-1 rounded-xl border-slate-200" placeholder="{{ __('inventory.decision_reason') }}"><button name="decision" value="approve" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">{{ __('inventory.approve') }}</button><button name="decision" value="reject" class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700">{{ __('inventory.reject') }}</button></form>@endif
            </div>@endforeach
        </section>

        <section x-show="tab === 'counts'" x-cloak class="space-y-5">
            <form method="POST" action="{{ route('owner.inventory.counts.store') }}" class="rounded-3xl border bg-white p-6 shadow-sm">@csrf<h2 class="text-lg font-bold">{{ __('inventory.count_title') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('inventory.count_help') }}</p><button class="mt-4 rounded-xl bg-blue-700 px-5 py-3 font-bold text-white">{{ __('inventory.start_count') }}</button></form>
            @foreach($counts->where('status', 'draft') as $count)<form method="POST" action="{{ route('owner.inventory.counts.complete', $count) }}" class="rounded-3xl border bg-white p-6 shadow-sm">@csrf @method('PUT')<h3 class="font-bold">{{ __('inventory.open_count', ['name' => $count->creator->name]) }}</h3><div class="mt-4 grid gap-3 md:grid-cols-2">@foreach($count->items as $item)<label class="rounded-xl bg-slate-50 p-3 text-sm"><span class="font-semibold">{{ $item->product->name }} · {{ $item->stock_status->label() }}</span><span class="ml-2 text-xs text-slate-500">{{ __('inventory.system_quantity', ['quantity' => $item->system_quantity]) }}</span><input name="quantities[{{ $item->id }}]" type="number" min="0" step="0.0001" class="mt-2 block w-full rounded-xl border-slate-200" placeholder="{{ __('inventory.physical_quantity') }}"></label>@endforeach</div><div class="mt-4 flex gap-3"><input name="reason" required minlength="10" class="flex-1 rounded-xl border-slate-200" placeholder="{{ __('inventory.count_reason') }}"><button class="rounded-xl bg-slate-900 px-5 py-3 font-bold text-white">{{ __('inventory.complete_count') }}</button></div></form>@endforeach
        </section>

        <section x-show="tab === 'movements'" x-cloak class="overflow-x-auto rounded-3xl border bg-white shadow-sm"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-4">{{ __('inventory.time') }}</th><th class="p-4">{{ __('inventory.product') }}</th><th class="p-4">{{ __('inventory.type') }}</th><th class="p-4">{{ __('inventory.change') }}</th><th class="p-4">{{ __('inventory.balance') }}</th><th class="p-4">{{ __('inventory.actor') }}</th></tr></thead><tbody class="divide-y">@foreach($movements as $movement)<tr><td class="p-4 text-xs">{{ $movement->occurred_at->format('d M Y H:i') }}</td><td class="p-4 font-semibold">{{ $movement->product->name }}</td><td class="p-4">{{ str($movement->movement_type->value)->replace('_', ' ')->headline() }}</td><td class="p-4 font-mono">{{ $movement->quantity_delta }}</td><td class="p-4 font-mono">{{ $movement->balance_after }}</td><td class="p-4">{{ $movement->actor->name }}</td></tr>@endforeach</tbody></table><div class="p-4">{{ $movements->links() }}</div></section>
    </div>
</x-app-layout>
