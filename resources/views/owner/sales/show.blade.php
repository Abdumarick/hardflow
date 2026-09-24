<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-bold uppercase tracking-widest text-blue-700">Sales / POS</p><h1 class="mt-1 text-2xl font-bold">Sale {{ $sale->sale_number }}</h1></div>
            <a href="{{ route('owner.sales.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm font-bold text-slate-700">Back to sales</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-3 py-5 sm:px-5">
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif

        <section class="rounded-xl border bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-4">
                <div><p class="text-sm text-slate-500">Customer</p><p class="font-bold">{{ $sale->customer?->name ?? 'Walk-in customer' }}</p>@if($sale->customer?->phone)<p class="text-sm text-slate-500">{{ $sale->customer->phone }}</p>@endif</div>
                <div class="flex gap-2"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase">{{ $sale->status->value }}</span><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase text-blue-700">{{ str($sale->fulfillment_status->value)->replace('_', ' ') }}</span></div>
            </div>
            <div class="mt-4 space-y-3">
                @foreach($sale->items as $item)
                    <div class="flex items-center justify-between gap-4 text-sm"><div><p class="font-semibold">{{ $item->product->name }}</p><p class="text-xs text-slate-500">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }} {{ $item->productUnit->unit->symbol }} × {{ $business->currency }} {{ number_format((float) $item->applied_unit_price, 2) }}</p></div><strong>{{ $business->currency }} {{ number_format((float) $item->line_total, 2) }}</strong></div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between border-t pt-4 text-xl font-bold"><span>Total</span><span>{{ $business->currency }} {{ number_format((float) $sale->total_amount, 2) }}</span></div>
        </section>

        @if($sale->status === \App\Enums\SaleStatus::Draft)
            <section x-data="saleCheckout({{ (float) $sale->total_amount }})" class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm">
                <div><h2 class="text-lg font-bold">Confirm sale & payment</h2><p class="mt-1 text-sm text-slate-500">Choose the payment method. Tick <span class="font-semibold">Customer paid the full amount</span> to fill the balance automatically. For a partial or split payment, leave it unticked and enter each amount.</p></div>
                @if($accounts->isEmpty())
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">No active payment accounts are set up for this branch. The sale can still be confirmed as unpaid.</p>
                @else
                    <form method="POST" action="{{ route('owner.sales.checkout', $sale) }}" class="mt-5 space-y-3">@csrf
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
                            <input x-model="fullAmountPaid" @change="applyFullAmount()" type="checkbox" class="mt-0.5 rounded border-emerald-400 text-emerald-600 focus:ring-emerald-500">
                            <span><span class="block font-bold">Customer paid the full amount</span><span class="mt-0.5 block text-xs text-emerald-800">Select one payment method below; the full {{ $business->currency }} {{ number_format((float) $sale->total_amount, 2) }} will be entered automatically.</span></span>
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach($accounts as $account)
                                <label class="rounded-xl border p-4 transition" :class="selected({{ $account->id }}) ? 'border-blue-600 bg-blue-50' : 'border-slate-200'">
                                    <div class="flex items-center justify-between gap-3"><span class="font-bold">{{ $account->method->name }}</span><input type="checkbox" @change="toggle({{ $account->id }})" :checked="selected({{ $account->id }})"></div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $account->name }}{{ $account->method->requires_reference ? ' · Reference required' : '' }}</p>
                                    <template x-if="selected({{ $account->id }})">
                                        <div class="mt-3 space-y-2">
                                            <input type="hidden" name="payments[{{ $account->id }}][payment_account_id]" value="{{ $account->id }}">
                                            <input name="payments[{{ $account->id }}][amount]" x-model.number="amounts[{{ $account->id }}]" type="number" min="0.01" step="0.01" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Amount received">
                                            <input {{ $account->method->requires_reference ? 'required' : '' }} name="payments[{{ $account->id }}][external_reference]" type="text" maxlength="255" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Transaction reference{{ $account->method->requires_reference ? ' (required)' : ' (optional)' }}">
                                        </div>
                                    </template>
                                </label>
                            @endforeach
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4 text-sm"><div class="flex justify-between"><span>Payment entered</span><strong x-text="money(paid)"></strong></div><div class="mt-1 flex justify-between" :class="remaining < 0 ? 'text-rose-600' : 'text-slate-600'"><span>Balance after payment</span><strong x-text="money(remaining)"></strong></div></div>
                        <button :disabled="remaining < 0" class="w-full rounded-lg bg-blue-700 px-4 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">Confirm sale & record payment</button>
                    </form>
                @endif
                @if($accounts->isEmpty())<form method="POST" action="{{ route('owner.sales.checkout', $sale) }}" class="mt-4">@csrf<button class="rounded-lg bg-blue-700 px-4 py-3 font-bold text-white">Confirm sale without payment</button></form>@endif
            </section>
        @else
            <section class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-emerald-700">Sale complete</h2><p class="mt-1 text-sm text-slate-500">Payment status: <span class="font-semibold uppercase">{{ str($sale->payment_status->value)->replace('_', ' ') }}</span></p></div><a target="_blank" href="{{ route('owner.sales.print', $sale) }}" class="rounded-lg border px-4 py-2 text-sm font-bold">Print sale</a></div>
                @if($sale->paymentAllocations->isNotEmpty())<div class="mt-4 divide-y rounded-lg border">@foreach($sale->paymentAllocations as $allocation)<div class="flex justify-between p-3 text-sm"><span>{{ $allocation->payment->account->method->name }} · {{ $allocation->payment->account->name }}</span><strong>{{ $business->currency }} {{ number_format((float) $allocation->amount, 2) }}</strong></div>@endforeach</div>@endif
            </section>

            @if($sale->fulfillment_status !== \App\Enums\FulfillmentStatus::Released)
                @php($draftRelease = $sale->releases->firstWhere('status', 'draft'))
                <section class="rounded-xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm"><h2 class="text-lg font-bold">Release goods and deduct stock</h2><p class="mt-1 text-sm text-slate-600">Create the release only when the customer receives the items. Confirming that release is the step that deducts stock.</p>
                <details class="mt-4 rounded-lg border border-amber-200 bg-white p-3"><summary class="cursor-pointer text-sm font-bold text-amber-800">Stock shortage? Record stock borrowed from a neighbour</summary><p class="mt-2 text-xs text-slate-600">This adds the borrowed stock to inventory with the neighbour and return date recorded, before the release is confirmed.</p><form method="POST" action="{{ route('owner.sales.borrow-neighbour-stock', $sale) }}" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf<select name="sale_item_id" required class="rounded-lg border-amber-200 text-sm"><option value="">Product to borrow</option>@foreach($sale->items as $item)@php($outstandingBorrow = bcsub((string) $item->quantity, (string) $item->released_quantity, 4))@if(bccomp($outstandingBorrow, '0', 4) > 0)<option value="{{ $item->id }}">{{ $item->product->name }} · {{ rtrim(rtrim($outstandingBorrow, '0'), '.') }} remaining</option>@endif@endforeach</select><input name="quantity" required type="number" min="0.0001" step="0.0001" class="rounded-lg border-amber-200 text-sm" placeholder="Quantity borrowed"><input name="neighbour_name" required class="rounded-lg border-amber-200 text-sm" placeholder="Neighbour name"><input name="neighbour_phone" class="rounded-lg border-amber-200 text-sm" placeholder="Neighbour phone (optional)"><input name="return_due_date" type="date" class="rounded-lg border-amber-200 text-sm"><input name="notes" class="rounded-lg border-amber-200 text-sm" placeholder="Borrow notes (optional)"><button class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-bold text-white sm:col-span-2">Record borrowed stock</button></form></details>
                @if($neighbourBorrows->isNotEmpty())<div class="mt-4 rounded-lg border bg-white p-3"><p class="text-sm font-bold">Neighbour stock records</p><div class="mt-2 space-y-2">@foreach($neighbourBorrows as $borrow)<div class="rounded-lg bg-slate-50 p-3 text-xs"><div class="flex flex-wrap items-center justify-between gap-2"><span class="font-bold">{{ $borrow->neighbour_name }} · {{ $borrow->quantity }} borrowed</span><span class="font-bold uppercase {{ $borrow->status === 'returned' ? 'text-emerald-700' : 'text-amber-700' }}">{{ str($borrow->status)->replace('_',' ') }}</span></div><p class="mt-1 text-slate-500">Return due: {{ $borrow->return_due_date?->format('d M Y') ?? 'Not set' }} · Remaining: {{ rtrim(rtrim(bcsub((string)$borrow->quantity, (string)$borrow->returned_quantity, 4), '0'), '.') }}</p>@if($borrow->status !== 'returned')<form method="POST" action="{{ route('owner.sales.neighbour-borrows.return', $borrow) }}" class="mt-2 grid gap-2 sm:grid-cols-[120px_1fr_auto]">@csrf<input name="quantity" required type="number" min="0.0001" step="0.0001" class="rounded-lg border-slate-200 text-sm" placeholder="Return qty"><input name="notes" class="rounded-lg border-slate-200 text-sm" placeholder="Return notes (optional)"><button class="rounded-lg bg-slate-700 px-3 py-2 text-xs font-bold text-white">Record return</button></form>@endif</div>@endforeach</div></div>@endif
                @if($draftRelease)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-white p-4"><p class="font-bold">{{ $draftRelease->release_number }} is ready for confirmation</p><p class="mt-1 text-sm text-slate-500">Confirming it will deduct the listed quantities from available stock.</p><form method="POST" action="{{ route('owner.sales.releases.confirm', $draftRelease) }}" class="mt-3">@csrf<button class="rounded-lg bg-emerald-600 px-4 py-3 text-sm font-bold text-white">Confirm release & deduct stock</button></form></div>
                @else
                <form method="POST" action="{{ route('owner.sales.releases.store', $sale) }}" class="mt-4 space-y-3">@csrf
                    @foreach($sale->items as $item)@php($outstanding = bcsub((string) $item->quantity, (string) $item->released_quantity, 4))@if(bccomp($outstanding, '0', 4) > 0)<div class="grid items-center gap-2 sm:grid-cols-[1fr_150px]"><label class="text-sm font-semibold">{{ $item->product->name }} <span class="font-normal text-slate-500">({{ rtrim(rtrim($outstanding, '0'), '.') }} remaining)</span></label><input type="hidden" name="items[{{ $loop->index }}][sale_item_id]" value="{{ $item->id }}"><input name="items[{{ $loop->index }}][quantity]" type="number" min="0.0001" max="{{ rtrim(rtrim($outstanding, '0'), '.') }}" step="0.0001" value="{{ rtrim(rtrim($outstanding, '0'), '.') }}" class="rounded-lg border-amber-200 text-sm"></div>@endif@endforeach
                    <button class="rounded-lg bg-amber-600 px-4 py-3 text-sm font-bold text-white">Create goods release</button>
                </form>
                @endif</section>
            @endif
        @endif
    </div>

    <script>
        function saleCheckout(total) { return { total, amounts: {}, active: [], fullAmountPaid: false, selected(id) { return this.active.includes(id); }, toggle(id) { if (this.selected(id)) { this.active = this.active.filter(item => item !== id); delete this.amounts[id]; if (!this.active.length) this.fullAmountPaid = false; } else { if (this.fullAmountPaid) { this.active = [id]; this.amounts = { [id]: this.total }; } else { this.active.push(id); } } }, applyFullAmount() { if (!this.fullAmountPaid) return; if (this.active.length > 1) this.active = [this.active[0]]; if (this.active.length) this.amounts = { [this.active[0]]: this.total }; }, get paid() { return this.active.reduce((sum, id) => sum + Number(this.amounts[id] || 0), 0); }, get remaining() { return this.total - this.paid; }, money(value) { return @js($business->currency) + ' ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } }; }
    </script>
</x-app-layout>
