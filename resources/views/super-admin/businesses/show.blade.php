<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">{{ $business->code }}</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $business->name }}</h1>
            </div>
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $business->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                {{ $business->is_active ? 'Active' : 'Disabled' }}
            </span>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Branches</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach ($business->branches as $branch)
                    <div class="flex items-center justify-between py-3">
                        <div><p class="font-medium text-slate-900">{{ $branch->name }}</p><p class="text-sm text-slate-500">{{ $branch->code }}</p></div>
                        @if ($branch->is_main)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Main</span>@endif
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-slate-900">Summary</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Members</dt><dd class="font-semibold text-slate-900">{{ $business->memberships->count() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Roles</dt><dd class="font-semibold text-slate-900">{{ $business->roles->count() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Currency</dt><dd class="font-semibold text-slate-900">{{ $business->currency }}</dd></div>
                </dl>
                @if($business->branches->where('is_active', true)->isNotEmpty())<form method="POST" action="{{ route('tenant.select', $business) }}" class="mt-5">@csrf<input type="hidden" name="branch" value="{{ $business->branches->where('is_active', true)->first()->public_id }}"><input type="hidden" name="destination" value="staff"><button class="w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white">Manage business users</button></form>@endif
            </div>
            @if ($business->is_active)
                <form method="POST" action="{{ route('super-admin.businesses.disable', $business) }}" class="rounded-xl border border-red-200 bg-red-50 p-6">
                    @csrf
                    <h2 class="font-semibold text-red-900">Disable business</h2>
                    <p class="mt-1 text-sm text-red-700">Normal protected operations will stop, but records remain intact.</p>
                    <textarea name="reason" required minlength="5" rows="2" placeholder="Reason" class="mt-4 w-full rounded-lg border-red-300 text-sm"></textarea>
                    <button class="mt-3 w-full rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Disable business</button>
                </form>
            @endif
        </aside>
    </div>
</x-app-layout>
