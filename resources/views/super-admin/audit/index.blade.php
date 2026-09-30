<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.2em] text-blue-700">Platform administration</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950">All audit logs</h1>
                <p class="mt-1 text-sm text-slate-500">Review activity across every business workspace.</p>
            </div>
            <a href="{{ route('super-admin.businesses.index') }}" class="rounded-xl border bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Manage businesses</a>
        </div>
    </x-slot>

    <div class="space-y-5 py-6">
        <form class="flex flex-wrap gap-3 rounded-2xl border bg-white p-4 shadow-sm">
            <select name="business" class="rounded-xl border-slate-200 text-sm">
                <option value="">All businesses</option>
                @foreach($businesses as $business)
                    <option value="{{ $business->id }}" @selected(request('business') == $business->id)>{{ $business->name }} ({{ $business->code }})</option>
                @endforeach
            </select>
            <input name="action" value="{{ request('action') }}" placeholder="Filter by action" class="min-w-52 rounded-xl border-slate-200 text-sm">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Filter</button>
        </form>

        <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">When</th><th class="px-5 py-3">Business</th><th class="px-5 py-3">Who did it</th><th class="px-5 py-3">What was done</th><th class="px-5 py-3">Branch</th><th class="px-5 py-3">IP / device</th><th class="px-5 py-3">Reason / record</th></tr></thead>
                    <tbody class="divide-y">@forelse($logs as $log)<tr class="hover:bg-slate-50"><td class="whitespace-nowrap px-5 py-4"><p>{{ $log->created_at->format('d M Y') }}</p><p class="text-xs text-slate-500">{{ $log->created_at->format('H:i:s') }}</p></td><td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $log->business?->name ?? 'Platform' }}</p><p class="text-xs text-slate-500">{{ $log->business?->code }}</p></td><td class="px-5 py-4">{{ $log->user?->name ?? 'System' }}</td><td class="px-5 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-800">{{ str($log->action)->replace('.', ' ')->headline() }}</span></td><td class="px-5 py-4 text-slate-600">{{ $log->branch?->name ?? 'Business-wide' }}</td><td class="px-5 py-4"><p class="font-mono text-xs text-slate-700">{{ $log->ip_address ?: 'Not recorded' }}</p><p class="mt-1 max-w-52 truncate text-xs text-slate-500" title="{{ $log->user_agent }}">{{ $log->user_agent ?: 'Device not recorded' }}</p></td><td class="px-5 py-4"><p class="max-w-sm text-slate-600">{{ $log->reason ?: '—' }}</p><p class="mt-1 text-xs text-slate-400">{{ class_basename($log->subject_type ?? '') }} #{{ $log->subject_id }}</p></td></tr>@empty<tr><td colspan="7" class="p-14 text-center text-slate-500">No audit records match the current filters.</td></tr>@endforelse</tbody>
                </table>
            </div>
            <div class="border-t p-4">{{ $logs->links() }}</div>
        </section>
    </div>
</x-app-layout>
