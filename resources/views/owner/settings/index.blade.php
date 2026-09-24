<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-blue-700">{{ __('ui.system') }} / {{ __('ui.settings') }}</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950">{{ __('settings.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('settings.subtitle') }}</p>
        </div>
    </x-slot>

    @php
        $field = 'mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500';
        $tabs = ['company' => __('settings.company'), 'operations' => __('settings.operations'), 'branches' => __('settings.branches')];
    @endphp

    <div class="space-y-5 py-6">
        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        <nav class="flex gap-1 overflow-x-auto rounded-2xl border bg-white p-2 shadow-sm">
            @foreach($tabs as $value => $label)
                <a href="{{ route('owner.settings.index', ['tab' => $value]) }}" @class(['whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-bold', 'bg-blue-700 text-white' => $tab === $value, 'text-slate-500 hover:bg-slate-100' => $tab !== $value])>{{ $label }}</a>
            @endforeach
        </nav>

        @if($tab === 'company')
            <form method="POST" action="{{ route('owner.settings.business.update') }}" class="space-y-5">
                @csrf @method('PUT')
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="font-black text-slate-950">Business identity</h2><p class="text-sm text-slate-500">Legal identity and registration details shown on business documents.</p></div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-semibold">Display name<input name="name" value="{{ old('name', $business->name) }}" required class="{{ $field }}"></label>
                        <label class="text-sm font-semibold">TIN<input name="tin" value="{{ old('tin', $business->tin) }}" class="{{ $field }}"></label>
                        <label class="text-sm font-semibold">VRN<input name="vrn" value="{{ old('vrn', $business->vrn) }}" class="{{ $field }}"></label>
                    </div>
                </section>
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="font-black text-slate-950">Contact information</h2><p class="text-sm text-slate-500">Primary customer and supplier contact details.</p></div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-semibold">Phone<input name="phone" value="{{ old('phone', $business->phone) }}" class="{{ $field }}"></label>
                        <label class="text-sm font-semibold">Email<input type="email" name="email" value="{{ old('email', $business->email) }}" class="{{ $field }}"></label>
                        <label class="text-sm font-semibold md:col-span-2">Address<textarea name="address" rows="3" class="{{ $field }}">{{ old('address', $business->address) }}</textarea></label>
                    </div>
                </section>
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <h2 class="font-black text-slate-950">Receipt branding</h2>
                    <label class="mt-4 block text-sm font-semibold">Receipt footer<textarea name="receipt_footer" rows="3" maxlength="500" class="{{ $field }}">{{ old('receipt_footer', $settings->get('receipt_footer')) }}</textarea><span class="mt-1 block text-xs font-normal text-slate-500">Printed at the bottom of receipts.</span></label>
                </section>
                <div class="flex justify-end"><button class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Save company information</button></div>
            </form>
        @elseif($tab === 'operations')
            <form method="POST" action="{{ route('owner.settings.business.update') }}" class="space-y-5">
                @csrf @method('PUT')
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="font-black text-slate-950">Currency and localization</h2><p class="text-sm text-slate-500">Defaults used throughout this business.</p></div>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <label class="text-sm font-semibold">Base currency<select name="currency" class="{{ $field }}">@foreach(['TZS','USD','KES','UGX','EUR','GBP'] as $currency)<option @selected(old('currency', $business->currency) === $currency)>{{ $currency }}</option>@endforeach</select></label>
                        <label class="text-sm font-semibold">Timezone<select name="timezone" class="{{ $field }}">@foreach(['Africa/Dar_es_Salaam','Africa/Nairobi','UTC'] as $timezone)<option @selected(old('timezone', $business->timezone) === $timezone)>{{ $timezone }}</option>@endforeach</select></label>
                        <label class="text-sm font-semibold">Language<select name="locale" class="{{ $field }}"><option value="en" @selected(old('locale', $business->locale) === 'en')>English</option><option value="sw" @selected(old('locale', $business->locale) === 'sw')>Kiswahili</option></select></label>
                        <label class="text-sm font-semibold">Fiscal year starts<select name="fiscal_year_start" class="{{ $field }}">@foreach(range(1,12) as $month)<option value="{{ $month }}" @selected((int)old('fiscal_year_start', $settings->get('fiscal_year_start', 1)) === $month)>{{ now()->month($month)->format('F') }}</option>@endforeach</select></label>
                    </div>
                </section>
                <section class="rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="mb-5"><h2 class="font-black text-slate-950">Tax and selling controls</h2><p class="text-sm text-slate-500">Rules affecting prices, receipts, and sales authorization.</p></div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="text-sm font-semibold">VAT enabled<select name="vat_enabled" class="{{ $field }}"><option value="1" @selected((bool)$settings->get('vat_enabled', false))>Enabled</option><option value="0" @selected(!(bool)$settings->get('vat_enabled', false))>Disabled</option></select></label>
                        <label class="text-sm font-semibold">VAT rate (%)<input type="number" min="0" max="100" step="0.01" name="vat_rate" value="{{ old('vat_rate', $settings->get('vat_rate', 18)) }}" class="{{ $field }}"></label>
                        <label class="text-sm font-semibold">Prices include tax<select name="prices_include_tax" class="{{ $field }}"><option value="1" @selected((bool)$settings->get('prices_include_tax', false))>Yes</option><option value="0" @selected(!(bool)$settings->get('prices_include_tax', false))>No</option></select></label>
                        <label class="text-sm font-semibold">Selling below cost<select name="allow_selling_below_cost" class="{{ $field }}"><option value="0" @selected(!(bool)$settings->get('allow_selling_below_cost', false))>Blocked</option><option value="1" @selected((bool)$settings->get('allow_selling_below_cost', false))>Allowed</option></select></label>
                    </div>
                </section>
                <div class="flex justify-end"><button class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white hover:bg-blue-800">Save operating rules</button></div>
            </form>
        @else
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">Branches inherit global settings unless an override is enabled. Identity fields always remain branch-specific.</div>
            <div class="space-y-4">
                @foreach($business->branches as $branch)
                    @php($branchSettings = $branch->settings->pluck('value', 'key'))
                    <details class="group rounded-2xl border bg-white shadow-sm" @if($errors->any()) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-3 p-5"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 font-black text-blue-700">B</span><div class="min-w-0 flex-1"><h2 class="truncate font-black">{{ $branch->name }}</h2><p class="truncate text-xs text-slate-500">{{ $branch->code }} · {{ $branch->address ?: 'No address provided' }}</p></div><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $branchSettings->get('override_global', false) ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">{{ $branchSettings->get('override_global', false) ? 'Custom overrides' : 'Uses global settings' }}</span></summary>
                        <form method="POST" action="{{ route('owner.settings.branches.update', $branch) }}" class="space-y-5 border-t bg-slate-50/60 p-5">
                            @csrf @method('PUT')
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <label class="text-sm font-semibold">Branch name<input name="name" value="{{ old('name', $branch->name) }}" required class="{{ $field }}"></label>
                                <label class="text-sm font-semibold">Code<input name="code" value="{{ old('code', $branch->code) }}" required class="{{ $field }}"></label>
                                <label class="text-sm font-semibold">Phone<input name="phone" value="{{ old('phone', $branch->phone) }}" class="{{ $field }}"></label>
                                <label class="text-sm font-semibold">Email<input type="email" name="email" value="{{ old('email', $branch->email) }}" class="{{ $field }}"></label>
                                <label class="text-sm font-semibold md:col-span-2">Address<input name="address" value="{{ old('address', $branch->address) }}" class="{{ $field }}"></label>
                            </div>
                            <div class="grid gap-4 border-t pt-5 md:grid-cols-2 lg:grid-cols-4">
                                <label class="text-sm font-semibold">Override global settings<select name="override_global" class="{{ $field }}"><option value="0" @selected(!$branchSettings->get('override_global', false))>No</option><option value="1" @selected($branchSettings->get('override_global', false))>Yes</option></select></label>
                                <label class="text-sm font-semibold">Currency<select name="currency" class="{{ $field }}"><option value="">Inherit {{ $business->currency }}</option>@foreach(['TZS','USD','KES','UGX','EUR','GBP'] as $currency)<option @selected($branchSettings->get('currency') === $currency)>{{ $currency }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">VAT rate (%)<input type="number" min="0" max="100" step="0.01" name="vat_rate" value="{{ $branchSettings->get('vat_rate') }}" placeholder="Inherit" class="{{ $field }}"></label>
                                <label class="text-sm font-semibold">Low-stock default<input type="number" min="0" step="0.01" name="low_stock_alert" value="{{ $branchSettings->get('low_stock_alert') }}" placeholder="Inherit" class="{{ $field }}"></label>
                                <label class="text-sm font-semibold md:col-span-2">Default payment method<select name="default_payment_method_id" class="{{ $field }}"><option value="">Inherit global default</option>@foreach($paymentMethods as $method)<option value="{{ $method->id }}" @selected((int)$branchSettings->get('default_payment_method_id') === $method->id)>{{ $method->name }}</option>@endforeach</select></label>
                            </div>
                            <div class="flex justify-end"><button class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Save {{ $branch->name }}</button></div>
                        </form>
                    </details>
                @endforeach
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-bold">Please correct the following:</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
    </div>
</x-app-layout>
