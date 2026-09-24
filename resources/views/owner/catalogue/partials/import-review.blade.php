@if(is_array($importPreview) && (int) ($importPreview['business_id'] ?? 0) === $business->id && (int) ($importPreview['user_id'] ?? 0) === auth()->id())
<div class="fixed inset-0 z-[60] overflow-y-auto bg-slate-950/50 p-2 sm:flex sm:items-center sm:justify-center sm:p-4">
    <div class="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
        <div class="sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-5">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-700">CSV import review</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $importPreview['filename'] }}</h2>
            <p class="mt-1 text-sm text-slate-500">Nothing has been added yet. Approve or reject this batch.</p>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-6 lg:grid-cols-3 xl:grid-cols-6">
            @foreach([['All rows',$importPreview['total'],'slate'],['New products',$importPreview['new'],'emerald'],['Existing updates',$importPreview['updates'],'blue'],['Already current',$importPreview['unchanged'],'slate'],['Incomplete but importable',$importPreview['incomplete'],'amber'],['Blocked',$importPreview['blocked'],'rose']] as [$label,$value,$tone])
                <div @class(['rounded-2xl p-4','bg-slate-50'=>$tone==='slate','bg-emerald-50'=>$tone==='emerald','bg-blue-50'=>$tone==='blue','bg-amber-50'=>$tone==='amber','bg-rose-50'=>$tone==='rose'])><p class="text-xs font-bold uppercase text-slate-600">{{ $label }}</p><p class="mt-1 text-3xl font-black">{{ $value }}</p></div>
            @endforeach
        </div>
        <div class="space-y-6 px-4 pb-4 sm:px-6 sm:pb-6">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">Rows without Retail or Wholesale prices are incomplete but may still be approved. Prices and product pictures can be added later in Product Management.</div>
            @if(($missingUnits = $importPreview['missing_references']['units'] ?? []) !== [])
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <h3 class="font-black text-amber-900">Register missing units to continue</h3>
                    <p class="mt-1 text-sm text-amber-900">These units are used by the CSV but are not yet registered for this business. Add each one below; the saved file will be checked again immediately, so you do not need to upload it again. Categories and brands in the CSV are created automatically when the batch is approved.</p>
                    <form method="POST" action="{{ route('owner.catalogue.import.continue') }}" class="mt-4">@csrf<input type="hidden" name="token" value="{{ $importPreview['token'] }}"><button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:from-amber-700 hover:to-orange-700 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-200 sm:w-auto"><span class="text-base">⇧</span><span>Continue import</span><span class="hidden font-medium text-amber-100 sm:inline">· Add missing units automatically</span></button></form>
                    <p class="mt-2 text-xs text-amber-800">This creates {{ count($missingUnits) }} unit(s) using the CSV names, then imports the batch. Use the individual forms below only if you want to change a unit symbol or allow decimal quantities.</p>
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        @foreach($missingUnits as $unitName)
                            <form method="POST" action="{{ route('owner.catalogue.import.units.store') }}" class="rounded-xl border border-amber-200 bg-white p-4">@csrf
                                <input type="hidden" name="token" value="{{ $importPreview['token'] }}">
                                <p class="font-bold text-slate-900">CSV unit: {{ $unitName }}</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label class="text-sm font-semibold text-slate-700">Unit name<input name="name" value="{{ $unitName }}" required maxlength="255" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                    <label class="text-sm font-semibold text-slate-700">Short symbol<input name="symbol" value="{{ \Illuminate\Support\Str::of($unitName)->lower()->replace(' ', '-')->limit(20, '') }}" required maxlength="20" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                </div>
                                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="allows_decimal" value="1" class="rounded border-slate-300"> This unit can use decimal quantities</label>
                                <button class="mt-4 rounded-lg bg-amber-700 px-4 py-2 text-sm font-bold text-white">Register unit and recheck</button>
                            </form>
                        @endforeach
                    </div>
                </section>
            @endif
            @if($importPreview['updates'] > 0)
                <section><h3 class="font-black text-blue-800">Existing products with updates: {{ $importPreview['updates'] }}</h3><p class="mt-1 text-sm text-slate-600">These products were matched by SKU, barcode, or an exact name + category + brand combination.</p><div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach($importPreview['update_rows'] as $update)<div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm"><span class="font-bold">Row {{ $update['row'] }} · {{ $update['name'] }}</span><br><span class="text-blue-800">Will update: {{ implode(', ', $update['changes']) }}</span></div>@endforeach</div></section>
            @endif
            @if($importPreview['unchanged'] > 0)
                <div class="rounded-2xl bg-slate-100 p-4 text-sm text-slate-700"><strong>{{ $importPreview['unchanged'] }} existing products are already current.</strong> They will be recognized and skipped without creating duplicates.</div>
            @endif
            @if($importPreview['complete'] > 0)
                <section><h3 class="font-black">Complete rows</h3><p class="mt-1 text-xs text-slate-500">Showing up to 100 rows with no problems.</p><div class="mt-3 overflow-x-auto rounded-2xl border"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Row</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Unit</th><th class="px-4 py-3">Retail</th><th class="px-4 py-3">Wholesale</th></tr></thead><tbody class="divide-y">@foreach($importPreview['complete_rows'] as $row)<tr><td class="px-4 py-3">{{ $row['row'] }}</td><td class="px-4 py-3 font-bold">{{ $row['name'] }}</td><td class="px-4 py-3">{{ $row['sku'] ?: 'Auto' }}</td><td class="px-4 py-3">{{ $row['unit'] }}</td><td class="px-4 py-3">{{ $row['retail_price'] }}</td><td class="px-4 py-3">{{ $row['wholesale_price'] }}</td></tr>@endforeach</tbody></table></div></section>
            @endif
            @if($importPreview['incomplete'] > 0)
                <section class="rounded-2xl bg-amber-50 p-4"><h3 class="font-black text-amber-800">Incomplete rows: {{ $importPreview['incomplete'] }}</h3><p class="mt-1 text-sm text-amber-900">Only the number is shown here. These rows are safe to import; missing prices can be entered later.</p></section>
            @endif
            @if($importPreview['blocked'] > 0)
                <section><h3 class="font-black text-rose-800">Rows requiring correction: {{ $importPreview['blocked'] }}</h3><div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach($importPreview['errors'] as $error)<div class="rounded-xl bg-rose-50 px-4 py-3 text-sm"><span class="font-bold">Row {{ $error['row'] }}</span><br><span class="text-rose-800">{{ $error['message'] }}</span></div>@endforeach</div></section>
            @endif
            <div class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end">
                <form method="POST" action="{{ route('owner.catalogue.import.reject') }}">@csrf @method('DELETE')<input type="hidden" name="token" value="{{ $importPreview['token'] }}"><button class="w-full rounded-xl border border-rose-200 px-5 py-3 font-bold text-rose-700 sm:w-auto">Reject batch</button></form>
                <form method="POST" action="{{ route('owner.catalogue.import.approve') }}">@csrf<input type="hidden" name="token" value="{{ $importPreview['token'] }}"><button @disabled($importPreview['blocked'] > 0) class="w-full rounded-xl bg-blue-700 px-6 py-3 font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto">Approve and process {{ $importPreview['total'] - $importPreview['blocked'] }} rows</button></form>
            </div>
        </div>
    </div>
</div>
@endif
