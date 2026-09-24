<x-app-layout>
    @php($dashboardLayout = in_array(auth()->user()->dashboard_layout, ['classic', 'smartflow', 'cards'], true) ? auth()->user()->dashboard_layout : 'classic')
    <div x-data="{ appearanceOpen: false }" @dashboard-appearance.window="appearanceOpen = true" @keydown.escape.window="appearanceOpen = false">
    @if ($dashboardData)
        <x-slot name="header">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.2em] text-blue-700">{{ __('dashboard.business_dashboard') }}</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950">{{ $currentBusiness->name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $currentBranch->name }} · {{ now()->format('l, d M Y') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$dispatch('dashboard-appearance')" class="flex h-10 w-10 items-center justify-center rounded-xl border bg-white text-blue-700 shadow-sm hover:border-blue-300" aria-label="{{ __('dashboard.appearance') }}" title="{{ __('dashboard.appearance') }}"><span class="grid grid-cols-2 gap-0.5"><i class="h-1.5 w-1.5 rounded-sm border border-current"></i><i class="h-1.5 w-1.5 rounded-sm border border-current"></i><i class="h-1.5 w-1.5 rounded-sm border border-current"></i><i class="h-1.5 w-1.5 rounded-sm border border-current"></i></span></button>
                    <div class="flex rounded-xl border bg-slate-100 p-1">@foreach (['today', 'week', 'month'] as $value)<a href="{{ route('dashboard', ['range' => $value]) }}" @class(['rounded-lg px-3 py-2 text-xs font-bold', 'bg-white text-slate-950 shadow-sm' => $dashboardData['range'] === $value, 'text-slate-500' => $dashboardData['range'] !== $value])>{{ __('dashboard.'.$value) }}</a>@endforeach</div>
                </div>
            </div>
        </x-slot>

        <div class="space-y-5 py-6">
            @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>@endif
            @include('dashboard.partials.module-workspace')
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="col-span-2 rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="flex justify-between"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('dashboard.sales') }}</p><span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">{{ __('dashboard.transactions', ['count' => $dashboardData['salesCount']]) }}</span></div>
                    <p class="mt-3 text-3xl font-black">{{ $currentBusiness->currency }} {{ number_format($dashboardData['salesTotal'], 2) }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ __('dashboard.confirmed_period') }}</p>
                </div>
                @foreach ([[__('dashboard.gross_profit'), $dashboardData['grossProfit'], 'text-emerald-600'], [__('dashboard.expenses'), $dashboardData['expenses'], 'text-rose-600'], [__('dashboard.outstanding_debt'), $dashboardData['debt'], 'text-amber-600'], [__('dashboard.stock_value'), $dashboardData['stockValue'], 'text-slate-950'], [__('dashboard.account_balance'), $dashboardData['cashBalance'], 'text-emerald-600']] as [$label, $amount, $colour])
                    <div class="rounded-2xl border bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p><p class="mt-3 text-xl font-black {{ $colour }}">{{ $currentBusiness->currency }} {{ number_format($amount, 2) }}</p></div>
                @endforeach
                @if($dashboardData['canViewApprovals'])<a href="{{ route('owner.approvals.index') }}" class="rounded-2xl border bg-white p-5 shadow-sm hover:border-blue-300"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('dashboard.approvals') }}</p><p class="mt-3 text-2xl font-black text-blue-700">{{ $dashboardData['pendingApprovals'] }}</p><p class="mt-2 text-xs text-slate-500">{{ __('dashboard.waiting_decisions') }}</p></a>@endif
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="mb-5 flex items-center justify-between"><div><h2 class="font-bold">{{ __('dashboard.sales_trend') }}</h2><p class="text-xs text-slate-500">{{ __('dashboard.confirmed_revenue') }}</p></div><a href="{{ route('owner.sales.index') }}" class="text-xs font-bold text-blue-700">{{ __('dashboard.view_sales') }} →</a></div>
                    @php($maxTrend = max(1, $dashboardData['trend']->max('amount')))
                    <div class="flex h-48 items-end gap-3 border-b border-slate-200">
                        @foreach ($dashboardData['trend'] as $day)
                            <div class="flex h-full flex-1 flex-col justify-end gap-2"><div title="{{ $currentBusiness->currency }} {{ number_format($day['amount'], 2) }}" class="min-h-1 rounded-t-lg bg-blue-600 hover:bg-blue-700" style="height: {{ max(2, ($day['amount'] / $maxTrend) * 100) }}%"></div><p class="pb-2 text-center text-[10px] font-bold text-slate-500">{{ $day['label'] }}</p></div>
                        @endforeach
                    </div>
                </div>
                <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b bg-amber-50 px-5 py-4"><div><h2 class="font-bold">{{ __('dashboard.low_stock') }}</h2><p class="text-xs text-slate-500">{{ __('dashboard.below_minimum') }}</p></div><span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">{{ $dashboardData['lowStock']->count() }}</span></div>
                    <div class="divide-y">@forelse ($dashboardData['lowStock'] as $item)<div class="flex justify-between gap-3 px-5 py-3"><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $item->name }}</p><p class="text-[11px] text-slate-500">{{ $item->sku }}</p></div><div class="text-right"><p class="text-sm font-black text-rose-600">{{ number_format($item->quantity, 2) }}</p><p class="text-[10px] text-slate-400">{{ __('dashboard.minimum_short', ['quantity' => number_format($item->minimum_stock, 2)]) }}</p></div></div>@empty<div class="p-8 text-center text-sm text-slate-500">{{ __('dashboard.no_low_stock') }}</div>@endforelse</div>
                    <a href="{{ route('owner.inventory.index') }}" class="block border-t px-5 py-3 text-xs font-bold text-blue-700">{{ __('dashboard.open_inventory') }} →</a>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4"><div><h2 class="font-bold">{{ __('dashboard.recent_sales') }}</h2><p class="text-xs text-slate-500">{{ __('dashboard.latest_activity') }}</p></div><a href="{{ route('owner.sales.index') }}" class="text-xs font-bold text-blue-700">{{ __('dashboard.view_all') }} →</a></div>
                <div class="overflow-x-auto"><table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">{{ __('dashboard.invoice') }}</th><th class="px-5 py-3">{{ __('dashboard.customer') }}</th><th class="px-5 py-3">{{ __('dashboard.items') }}</th><th class="px-5 py-3">{{ __('dashboard.amount') }}</th><th class="px-5 py-3">{{ __('dashboard.payment') }}</th></tr></thead>
                    <tbody class="divide-y">@forelse ($dashboardData['recentSales'] as $sale)<tr class="hover:bg-slate-50"><td class="px-5 py-4 font-mono text-xs font-bold text-blue-700">{{ $sale->sale_number }}</td><td class="px-5 py-4">{{ $sale->customer?->name ?? $sale->walk_in_name ?? __('dashboard.walk_in') }}</td><td class="px-5 py-4">{{ $sale->items_count }}</td><td class="px-5 py-4 font-bold">{{ $currentBusiness->currency }} {{ number_format($sale->total_amount, 2) }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ str($sale->payment_status->value)->replace('_', ' ')->headline() }}</span></td></tr>@empty<tr><td colspan="5" class="p-10 text-center text-slate-500">{{ __('dashboard.no_sales') }}</td></tr>@endforelse</tbody>
                </table></div>
            </section>
        </div>
    @else
        <x-slot name="header"><div><p class="text-xs font-semibold uppercase tracking-wider text-blue-700">HardFlow</p><h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('dashboard.choose_workspace') }}</h1></div></x-slot>
        <div class="py-8">
            @if (auth()->user()->is_super_admin)<div class="mb-6 flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 p-5"><div><p class="font-semibold text-blue-950">{{ __('dashboard.platform_admin') }}</p><p class="text-sm text-blue-700">{{ __('dashboard.platform_admin_help') }}</p></div><a href="{{ route('super-admin.businesses.index') }}" class="rounded-lg bg-blue-800 px-4 py-2 text-sm font-semibold text-white">{{ __('dashboard.open_admin') }}</a></div>@endif
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">@forelse ($businesses as $business)<article class="rounded-xl border bg-white p-6 shadow-sm"><p class="font-mono text-xs font-semibold text-blue-700">{{ $business->code }}</p><h2 class="mt-1 text-lg font-bold">{{ $business->name }}</h2>@php($activeBranchCount = $business->branches->where('is_active', true)->count())<p class="mt-2 text-sm text-slate-500">{{ trans_choice('dashboard.active_branches', $activeBranchCount, ['count' => $activeBranchCount]) }}</p><div class="mt-5 space-y-2">@foreach ($business->branches->where('is_active', true) as $branch)<form method="POST" action="{{ route('tenant.select', $business) }}">@csrf<input type="hidden" name="branch" value="{{ $branch->public_id }}"><button class="flex w-full justify-between rounded-lg border px-3 py-2.5 text-sm font-medium hover:border-blue-300 hover:bg-blue-50">{{ $branch->name }}<span>→</span></button></form>@endforeach</div></article>@empty<div class="rounded-xl border border-dashed p-8 text-slate-500">{{ __('dashboard.no_workspace') }}</div>@endforelse</div>
        </div>
    @endif
        <div x-show="appearanceOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/40 p-3 sm:flex sm:items-center sm:justify-center sm:p-4" @click.self="appearanceOpen = false">
            <section class="mx-auto max-h-[calc(100dvh-1.5rem)] w-full max-w-3xl overflow-y-auto rounded-2xl border bg-white shadow-2xl sm:max-h-[calc(100dvh-2rem)]" role="dialog" aria-modal="true" aria-labelledby="appearance-title">
                <header class="flex items-start justify-between border-b px-6 py-4"><div><h2 id="appearance-title" class="font-black">{{ __('dashboard.appearance') }}</h2><p class="mt-1 text-xs text-slate-500">{{ __('dashboard.appearance_help') }}</p></div><button type="button" @click="appearanceOpen = false" class="text-xl text-slate-400">×</button></header>
                <div class="grid gap-4 p-6 sm:grid-cols-3">
                    @foreach ([['classic', 'classic', 'classic_help'], ['smartflow', 'smartflow', 'smartflow_help'], ['cards', 'cards', 'cards_help']] as [$value, $label, $help])
                        <form method="POST" action="{{ route('dashboard.appearance.update') }}">@csrf @method('PUT')<input type="hidden" name="dashboard_layout" value="{{ $value }}"><button @class(['h-full w-full rounded-xl border-2 p-4 text-left transition hover:border-blue-400', 'border-blue-700 bg-blue-50/50' => $dashboardLayout === $value, 'border-slate-200' => $dashboardLayout !== $value])><div class="mb-4 flex h-20 items-center justify-center gap-2 overflow-hidden rounded-lg bg-slate-100">@if($value === 'classic')<span class="h-full w-8 bg-white"></span><span class="h-10 flex-1 rounded bg-slate-200"></span>@elseif($value === 'smartflow')@foreach(['P','→','I','→','S','→','$'] as $node)<span class="text-xs font-bold text-blue-700">{{ $node }}</span>@endforeach @else<div class="w-full space-y-2 px-3"><span class="block h-3 rounded bg-white"></span><span class="block h-8 rounded bg-slate-200"></span></div>@endif</div><span class="flex items-start justify-between gap-2"><span><strong class="block text-sm">{{ __('dashboard.'.$label) }}</strong><small class="mt-1 block leading-relaxed text-slate-500">{{ __('dashboard.'.$help) }}</small></span>@if($dashboardLayout === $value)<span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs text-white">✓</span>@endif</span></button></form>
                    @endforeach
                </div>
                <footer class="flex items-center justify-between gap-3 px-6 pb-5"><p class="text-xs text-slate-500">{{ __('dashboard.preference_saved') }}</p><button type="button" @click="appearanceOpen = false" class="rounded-lg border bg-slate-50 px-4 py-2 text-xs font-bold">{{ __('dashboard.close') }}</button></footer>
            </section>
        </div>
    </div>
</x-app-layout>
