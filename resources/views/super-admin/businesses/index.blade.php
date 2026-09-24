<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Platform administration</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">Businesses</h1>
            </div>
            <a href="{{ route('super-admin.businesses.create') }}" class="rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Create business
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Business</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Code</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Branches</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Users</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($businesses as $business)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <a href="{{ route('super-admin.businesses.show', $business) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $business->name }}</a>
                                    <p class="text-sm text-slate-500">{{ $business->email ?: 'No email provided' }}</p>
                                </td>
                                <td class="px-5 py-4 font-mono text-sm text-slate-700">{{ $business->code }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ $business->branches_count }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ $business->memberships_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $business->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $business->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">No businesses have been created.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $businesses->links() }}</div>
    </div>
</x-app-layout>
