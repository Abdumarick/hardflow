<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">{{ $business->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ __('staff.title') }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8" x-data="{ managing: null }">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('staff.add_member') }}</h2>
            <form method="POST" action="{{ route('owner.staff.store') }}" class="mt-5 space-y-4">
                @csrf
                @foreach (['name' => __('staff.full_name'), 'email' => __('staff.email')] as $field => $label)
                    <label class="block text-sm font-medium text-slate-700">{{ $label }}
                        <input name="{{ $field }}" value="{{ old($field) }}" required class="mt-1.5 w-full rounded-lg border-slate-300">
                    </label>
                @endforeach
                <label class="block text-sm font-medium text-slate-700">{{ __('staff.phone') }}
                    <div class="mt-1.5 flex rounded-lg border border-slate-300 bg-white focus-within:border-blue-600 focus-within:ring-1 focus-within:ring-blue-600"><span class="inline-flex items-center border-r border-slate-300 bg-slate-50 px-3 text-sm text-slate-600">+255</span><input name="phone" value="{{ old('phone') }}" required inputmode="numeric" autocomplete="tel" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm focus:ring-0" placeholder="712345678"></div>
                </label>
                <fieldset><legend class="text-sm font-medium text-slate-700">{{ __('staff.branches') }}</legend>
                    <div class="mt-2 space-y-2">@foreach ($branches as $branch)
                        <label class="flex gap-2 text-sm text-slate-700"><input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="rounded border-slate-300 text-blue-700"> {{ $branch->name }}</label>
                    @endforeach</div>
                </fieldset>
                <fieldset><legend class="text-sm font-medium text-slate-700">{{ __('staff.role') }}</legend>
                    <div class="mt-2 space-y-2">@foreach ($roles as $role)
                        <label class="flex gap-2 text-sm text-slate-700"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="rounded border-slate-300 text-blue-700"> {{ $role->name }}</label>
                    @endforeach</div>
                </fieldset>
                @if ($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
                <button class="w-full rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white">{{ __('staff.create_member') }}</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">{{ __('staff.business_users') }}</h2>
                <a href="{{ route('owner.roles.index') }}" class="text-sm font-semibold text-blue-700">{{ __('staff.manage_roles') }}</a>
            </div>
            @if (session('status'))<div class="m-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
            <div class="divide-y divide-slate-100">
                @foreach ($memberships as $membership)
                    <article class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $membership->user->name }}</p>
                                <p class="text-sm text-slate-500">{{ $membership->user->email }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($membership->user->roles->where('pivot.business_id', $business->id) as $role)
                                        <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $membership->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $membership->is_active ? __('staff.active') : __('staff.disabled') }}</span>@can('updateStaff', $business)@php($targetIsOwner = $membership->user->roles->where('pivot.business_id', $business->id)->contains('slug', 'owner'))@if(!$membership->user->is(auth()->user()) && (auth()->user()->is_super_admin || !$targetIsOwner))<button type="button" @click="managing = managing === {{ $membership->user_id }} ? null : {{ $membership->user_id }}" class="rounded-lg border px-3 py-1.5 text-xs font-bold text-blue-700">{{ __('staff.manage') }}</button>@endif @endcan</div>
                        </div>
                        @can('updateStaff', $business)
                            <div x-show="managing === {{ $membership->user_id }}" x-cloak class="mt-4 grid gap-4 rounded-xl border border-blue-100 bg-blue-50/40 p-4 lg:grid-cols-3">
                                <form method="POST" action="{{ route('owner.staff.details.update', $membership->user) }}" class="space-y-3">@csrf @method('PUT')
                                    <h3 class="text-sm font-bold">{{ __('staff.employee_details') }}</h3><p class="text-xs text-slate-500">{{ __('staff.employee_details_help') }}</p>
                                    <input name="name" required maxlength="255" value="{{ $membership->user->name }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.full_name') }}">
                                    <input name="username" required minlength="3" maxlength="30" value="{{ $membership->user->username }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.username') }}">
                                    <input name="email" required type="email" maxlength="255" value="{{ $membership->user->email }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.email') }}">
                                    <input name="phone" required inputmode="numeric" maxlength="30" value="{{ $membership->user->phone }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="255712345678">
                                    <input name="reason" required minlength="5" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.details_change_reason') }}"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white">{{ __('staff.save_details') }}</button>
                                </form>
                                <form method="POST" action="{{ route('owner.staff.access.update', $membership->user) }}" class="space-y-3">@csrf @method('PUT')
                                    <h3 class="text-sm font-bold">{{ __('staff.roles_permissions_branches') }}</h3>
                                    <p class="text-xs text-slate-500">{{ __('staff.roles_permissions_help') }}</p>
                                    <fieldset><legend class="text-xs font-bold text-slate-600">{{ __('staff.branches') }}</legend><div class="mt-2 flex flex-wrap gap-3">@foreach($branches as $branch)<label class="text-xs"><input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" @checked($membership->user->branches->where('pivot.business_id',$business->id)->where('pivot.is_active',true)->contains('id',$branch->id)) class="rounded border-slate-300 text-blue-700"> {{ $branch->name }}</label>@endforeach</div></fieldset>
                                    <fieldset><legend class="text-xs font-bold text-slate-600">{{ __('staff.role') }}</legend><div class="mt-2 flex flex-wrap gap-3">@foreach($roles as $role)@if(auth()->user()->is_super_admin || $role->slug !== 'owner')<label class="text-xs"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked($membership->user->roles->where('pivot.business_id',$business->id)->contains('id',$role->id)) class="rounded border-slate-300 text-blue-700"> {{ $role->name }}</label>@endif @endforeach</div></fieldset>
                                    <input name="reason" required minlength="5" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.access_change_reason') }}"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white">{{ __('staff.save_access') }}</button>
                                </form>
                                <form method="POST" action="{{ route('owner.staff.password.reset', $membership->user) }}" class="space-y-3">@csrf @method('PUT')
                                    <h3 class="text-sm font-bold">{{ __('staff.reset_password') }}</h3><p class="text-xs text-slate-500">{{ __('staff.reset_password_help') }}</p>
                                    <input name="reason" required minlength="5" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('staff.password_reset_reason') }}"><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">{{ __('staff.reset_password') }}</button>
                                </form>
                            </div>
                        @endcan
                        @if ($membership->is_active && ! $membership->user->is(auth()->user()))
                            <form method="POST" action="{{ route('owner.staff.disable', $membership->user) }}" class="mt-4 flex gap-2">
                                @csrf
                                <input name="reason" required minlength="5" placeholder="{{ __('staff.disable_reason') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
                                <button class="rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-700">{{ __('staff.disable') }}</button>
                            </form>
                        @elseif (! $membership->is_active)
                            <form method="POST" action="{{ route('owner.staff.enable', $membership->user) }}" class="mt-4 flex gap-2">
                                @csrf
                                <input name="reason" required minlength="5" placeholder="{{ __('staff.enable_reason') }}" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
                                <button class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">{{ __('staff.enable') }}</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-200 p-4">{{ $memberships->links() }}</div>
        </section>
    </div>
</x-app-layout>
