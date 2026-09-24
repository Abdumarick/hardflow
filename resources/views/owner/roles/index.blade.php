<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">{{ $business->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('staff.roles_title') }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))<div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('owner.roles.store') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold text-slate-900">{{ __('staff.create_role') }}</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <input name="name" required placeholder="{{ __('staff.role_name') }}" class="rounded-lg border-slate-300">
                <input name="description" placeholder="{{ __('staff.description_optional') }}" class="rounded-lg border-slate-300">
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($permissions as $permission)
                    <label class="flex gap-2 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" class="rounded border-slate-300 text-blue-700">
                        <span><strong class="block text-slate-900">{{ $permission->name }}</strong><span class="text-xs text-slate-500">{{ $permission->slug }}</span></span>
                    </label>
                @endforeach
            </div>
            <button class="mt-5 rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white">{{ __('staff.create_role_button') }}</button>
        </form>

        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($roles as $role)
                <form method="POST" action="{{ route('owner.roles.permissions.update', $role) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    @method('PUT')
                    <div class="flex items-start justify-between">
                        <div><h2 class="font-semibold text-slate-900">{{ $role->name }}</h2><p class="text-sm text-slate-500">{{ $role->description ?: __('staff.no_description') }}</p></div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $role->is_system ? __('staff.default') : __('staff.custom') }}</span>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            <label class="flex gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission)) @disabled($role->is_system && $role->slug === 'owner') class="rounded border-slate-300 text-blue-700">
                                {{ $permission->name }}
                            </label>
                        @endforeach
                    </div>
                    @unless ($role->is_system && $role->slug === 'owner')
                        <button class="mt-5 rounded-lg border border-blue-300 px-4 py-2 text-sm font-semibold text-blue-700">{{ __('staff.save_permissions') }}</button>
                    @endunless
                </form>
            @endforeach
        </div>
    </div>
</x-app-layout>
