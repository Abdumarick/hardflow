<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $user = auth()->user();
    $business = session('tenant.business_id') ? \App\Models\Business::query()->find(session('tenant.business_id')) : null;
    if ($business && ! $user->is_super_admin && ! $user->hasActiveMembership($business)) $business = null;
    $branch = $business && session('tenant.branch_id') ? \App\Models\Branch::query()->where('business_id', $business->id)->find(session('tenant.branch_id')) : null;
    $can = fn (\App\Enums\PermissionName $permission) => $business && $user->hasPermissionInBusiness($permission, $business);
    $dashboardItem = [__('ui.dashboard'), 'dashboard', 'dashboard', true, 'D'];
    $groups = [
        __('ui.operations') => [
            [__('ui.sales'), 'owner.sales.index', 'owner.sales.*', $can(\App\Enums\PermissionName::SalesView), 'S'],
            ['Customers', 'owner.customers.index', 'owner.customers.*', $can(\App\Enums\PermissionName::CustomersView), 'C'],
            ['Layaway', 'owner.layaway.index', 'owner.layaway.*', $can(\App\Enums\PermissionName::CustomersView), 'L'],
            ['Projects', 'owner.projects.index', 'owner.projects.*', $can(\App\Enums\PermissionName::CustomersView), 'J'],
        ],
        __('ui.inventory') => [
            [__('ui.products'), 'owner.catalogue.index', 'owner.catalogue.*', $can(\App\Enums\PermissionName::ProductsView), 'P'],
            [__('ui.inventory'), 'owner.inventory.index', 'owner.inventory.*', $can(\App\Enums\PermissionName::InventoryView), 'I'],
        ],
        __('ui.procurement') => [
            [__('ui.purchases'), 'owner.purchases.index', 'owner.purchases.*', $can(\App\Enums\PermissionName::PurchasesView), 'P'],
        ],
        __('ui.finance') => [
            [__('ui.expenses'), 'owner.expenses.index', 'owner.expenses.*', $can(\App\Enums\PermissionName::ExpensesView), 'E'],
            [__('ui.payments'), 'owner.payments.index', 'owner.payments.*', $can(\App\Enums\PermissionName::PaymentsView), 'P'],
        ],
        __('ui.management') => [
            [__('ui.approvals'), 'owner.approvals.index', 'owner.approvals.*', $can(\App\Enums\PermissionName::ApprovalsView), 'A'],
            [__('ui.reports'), 'owner.reports.index', 'owner.reports.*', $can(\App\Enums\PermissionName::ReportsView), 'R'],
            [__('ui.staff'), 'owner.staff.index', 'owner.staff.*', $can(\App\Enums\PermissionName::UsersView), 'S'],
            [__('ui.users_roles'), 'owner.roles.index', 'owner.roles.*', $can(\App\Enums\PermissionName::UsersManageRoles), 'R'],
            [__('ui.audit_logs'), 'owner.audit.index', 'owner.audit.*', $can(\App\Enums\PermissionName::AuditView), 'A'],
        ],
        __('ui.system') => [
            [__('ui.settings'), 'owner.settings.index', 'owner.settings.*', $can(\App\Enums\PermissionName::SettingsView), 'S'],
            [__('ui.notifications'), 'owner.notifications.index', 'owner.notifications.*', $can(\App\Enums\PermissionName::NotificationsView), 'N'],
            [__('ui.profile'), 'profile', 'profile', true, 'P'],
        ],
    ];
    if ($user->is_super_admin) array_unshift($groups[__('ui.management')], [__('ui.businesses'), 'super-admin.businesses.index', 'super-admin.*', true, 'B']);
    $openGroups = collect($groups)->mapWithKeys(fn ($items, $group) => [$group => collect($items)->contains(fn ($item) => request()->routeIs($item[2]))]);
    $pendingApprovals = $business ? \App\Models\ApprovalRequest::query()->where('business_id', $business->id)->where('status', 'pending')->count() : 0;
    $unreadNotifications = $business ? $user->unreadNotifications()->where('data->business_id', $business->id)->count() : 0;
    $quickCreate = $business && $branch ? collect([
        [__('ui.quick_new_sale'), route('owner.sales.index'), 'N', $can(\App\Enums\PermissionName::SalesView) && $can(\App\Enums\PermissionName::SalesCreate)],
        [__('ui.quick_quotation'), route('owner.sales.index', ['mode' => 'quotation']), 'Q', $can(\App\Enums\PermissionName::SalesView) && $can(\App\Enums\PermissionName::QuotationsCreate)],
        [__('ui.quick_purchase'), route('owner.purchases.index', ['tab' => 'new']), 'P', $can(\App\Enums\PermissionName::PurchasesView) && $can(\App\Enums\PermissionName::PurchasesCreate)],
        [__('ui.quick_receive_stock'), route('owner.purchases.index', ['tab' => 'orders']), 'R', $can(\App\Enums\PermissionName::PurchasesView) && $can(\App\Enums\PermissionName::PurchasesReceive)],
        [__('ui.quick_expense'), route('owner.expenses.create'), 'E', $can(\App\Enums\PermissionName::ExpensesView) && $can(\App\Enums\PermissionName::ExpensesCreate)],
        [__('ui.quick_payment'), route('owner.payments.index', ['tab' => 'debts']), 'M', $can(\App\Enums\PermissionName::PaymentsView) && $can(\App\Enums\PermissionName::PaymentsCreate)],
        [__('ui.quick_product'), route('owner.catalogue.index', ['create' => 'product']), 'A', $can(\App\Enums\PermissionName::ProductsView) && $can(\App\Enums\PermissionName::ProductsCreate)],
    ])->filter(fn ($item) => $item[3])->values() : collect();
@endphp

<div x-data="{ quickCreateOpen: false, openGroups: @js($openGroups) }">
    <div x-show="mobileSidebarOpen" x-cloak @click="mobileSidebarOpen=false" class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-sm lg:hidden"></div>
    <aside :class="[mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', sidebarCollapsed ? 'lg:w-20' : 'lg:w-64']" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-all duration-200">
        <div class="flex h-16 items-center gap-3 border-b px-4" :class="sidebarCollapsed && 'lg:justify-center'">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2.5"><x-application-logo class="h-9 w-9 shrink-0 text-blue-700"/>@if($business)<span x-show="!sidebarCollapsed" class="min-w-0"><span class="block truncate text-base font-black tracking-tight text-slate-950">{{ $business->name }}</span><span class="block text-[10px] font-bold uppercase tracking-[.16em] text-blue-700">HardFlow</span></span>@else<span x-show="!sidebarCollapsed" class="text-lg font-black tracking-tight text-slate-950">HardFlow</span>@endif</a>
            <button @click="mobileSidebarOpen=false" class="ml-auto rounded-lg p-2 text-slate-500 lg:hidden" aria-label="Close navigation">×</button>
        </div>
        @if($business)
            <div x-show="!sidebarCollapsed" class="border-b p-3"><div class="rounded-xl border border-blue-100 bg-blue-50 p-3"><p class="truncate text-xs font-bold text-slate-900">{{ $business->name }}</p><p class="mt-1 truncate text-[11px] text-blue-700">{{ $branch?->name ?? 'Select a branch' }}</p></div></div>
        @endif
        <nav class="hardflow-scrollbar flex-1 overflow-y-auto px-2 py-3">
            @php([$label, $route, $active, $visible, $initial] = $dashboardItem)
            <a href="{{ route($route) }}" wire:navigate @class(['group mb-3 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition','bg-blue-50 text-blue-800'=>request()->routeIs($active),'text-slate-600 hover:bg-slate-100 hover:text-slate-950'=>!request()->routeIs($active)]) :class="sidebarCollapsed && 'lg:justify-center lg:px-2'" title="{{ $label }}">
                <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-[11px] font-black','bg-blue-700 text-white'=>request()->routeIs($active),'bg-slate-100 text-slate-500 group-hover:bg-white'=>!request()->routeIs($active)])>{{ $initial }}</span>
                <span x-show="!sidebarCollapsed" class="min-w-0 flex-1 truncate">{{ $label }}</span>
            </a>
            @foreach($groups as $group => $items)
                @if(collect($items)->contains(fn($item) => $item[3]))
                    <section x-show="!sidebarCollapsed" x-cloak class="mb-2 rounded-xl border border-transparent" :class="openGroups[@js($group)] && 'border-slate-100 bg-slate-50/70'">
                        <button type="button" @click="openGroups[@js($group)] = !openGroups[@js($group)]" :aria-expanded="openGroups[@js($group)]" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[.18em] text-slate-500 hover:bg-slate-100 hover:text-slate-800">
                            <span class="min-w-0 flex-1 truncate">{{ $group }}</span><span class="text-sm leading-none transition-transform" :class="openGroups[@js($group)] && 'rotate-180'">⌄</span>
                        </button>
                        <div x-show="openGroups[@js($group)]" x-cloak x-transition class="space-y-1 px-1 pb-1">@foreach($items as [$label,$route,$active,$visible,$initial]) @if($visible)
                        <a href="{{ route($route) }}" wire:navigate @class(['group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition','bg-blue-50 text-blue-800'=>request()->routeIs($active),'text-slate-600 hover:bg-slate-100 hover:text-slate-950'=>!request()->routeIs($active)]) :class="sidebarCollapsed && 'lg:justify-center lg:px-2'" title="{{ $label }}">
                            <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-[11px] font-black','bg-blue-700 text-white'=>request()->routeIs($active),'bg-slate-100 text-slate-500 group-hover:bg-white'=>!request()->routeIs($active)])>{{ $initial }}</span>
                            <span x-show="!sidebarCollapsed" class="min-w-0 flex-1 truncate">{{ $label }}</span>
                            @if($label==='Approvals' && $pendingApprovals)<span x-show="!sidebarCollapsed" class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">{{ $pendingApprovals }}</span>@endif
                            @if($label==='Notifications' && $unreadNotifications)<span x-show="!sidebarCollapsed" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800">{{ $unreadNotifications }}</span>@endif
                        </a>
                    @endif @endforeach</div></section>
                @endif
            @endforeach
        </nav>
        <div class="border-t p-3"><div class="flex items-center gap-3"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-black text-white">{{ str($user->name)->substr(0,1)->upper() }}</div><div x-show="!sidebarCollapsed" class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $user->name }}</p><p class="truncate text-[11px] text-slate-500">{{ $user->email }}</p></div><button x-show="!sidebarCollapsed" wire:click="logout" title="Sign out" class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600">↪</button></div></div>
    </aside>

    <header :class="sidebarCollapsed ? 'lg:left-20' : 'lg:left-64'" class="fixed inset-x-0 top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur transition-all duration-200">
        <button @click="mobileSidebarOpen=true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open navigation">☰</button>
        <button @click="sidebarCollapsed=!sidebarCollapsed; localStorage.setItem('hardflow-sidebar-collapsed', sidebarCollapsed)" class="hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:block" :aria-label="sidebarCollapsed ? 'Expand navigation' : 'Collapse navigation'" title="Collapse or expand navigation">☰</button>
        @if($business)<div class="min-w-0 flex-1 sm:hidden"><p class="truncate text-sm font-black text-slate-950">{{ $business->name }}</p><p class="text-[9px] font-bold uppercase tracking-[.14em] text-blue-700">HardFlow</p></div>@endif
        <div class="hidden max-w-sm flex-1 items-center gap-2 rounded-xl border bg-slate-50 px-3 py-2 text-sm text-slate-400 sm:flex"><span>⌕</span><span class="truncate">Search products, customers, invoices...</span></div>
        <div class="ml-auto flex items-center gap-2">
            <form method="POST" action="{{ route('interface.locale.update') }}">@csrf @method('PUT')<input type="hidden" name="locale" value="{{ app()->getLocale() === 'en' ? 'sw' : 'en' }}"><button type="submit" class="flex h-9 min-w-9 items-center justify-center rounded-xl border px-2 text-[11px] font-black text-slate-600 hover:bg-slate-50" title="{{ app()->getLocale() === 'en' ? 'Badili kwenda Kiswahili' : 'Switch to English' }}">{{ strtoupper(app()->getLocale()) }}</button></form>
            <button type="button" @click="$dispatch('theme-toggle')" class="flex h-9 w-9 items-center justify-center rounded-xl border text-slate-600 hover:bg-slate-50" :title="dark ? 'Use light mode' : 'Use dark mode'" aria-label="Toggle light and dark mode"><span x-show="!dark">◐</span><span x-show="dark" x-cloak>☀</span></button>
            @if($quickCreate->isNotEmpty())<div class="relative hidden sm:block" @click.outside="quickCreateOpen=false"><button type="button" @click="quickCreateOpen=!quickCreateOpen" :aria-expanded="quickCreateOpen" class="flex items-center gap-1 rounded-xl bg-blue-700 px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">+ {{ __('ui.quick_create') }} <span class="text-[10px]">⌄</span></button><div x-show="quickCreateOpen" x-cloak x-transition.origin.top.right class="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border bg-white py-1 shadow-xl">@foreach($quickCreate as [$label,$url,$shortcut])<a href="{{ $url }}" wire:navigate @click="quickCreateOpen=false" class="flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-blue-50 hover:text-blue-800"><span>{{ $label }}</span><kbd class="rounded border bg-slate-50 px-1.5 py-0.5 font-mono text-[10px] text-slate-400">{{ $shortcut }}</kbd></a>@endforeach</div></div>@endif
            @if($business && $can(\App\Enums\PermissionName::NotificationsView))<a href="{{ route('owner.notifications.index') }}" class="relative rounded-xl border p-2.5 text-slate-600 hover:bg-slate-50" aria-label="Notifications">♢@if($unreadNotifications)<span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500"></span>@endif</a>@endif
            <a href="{{ route('profile') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-xs font-black text-white">{{ str($user->name)->substr(0,1)->upper() }}</a>
        </div>
    </header>
</div>
