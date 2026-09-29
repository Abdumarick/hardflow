<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.2em] text-blue-700">{{ auth()->user()->is_super_admin ? 'Platform administration' : 'Account settings' }}</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950">{{ __('Profile') }}</h1>
                <p class="mt-1 text-sm text-slate-500">Manage your personal details and sign-in security.</p>
            </div>
            @if(auth()->user()->is_super_admin)
                <a href="{{ route('super-admin.businesses.index') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Manage businesses</a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @if(auth()->user()->is_super_admin)
            <section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <div><p class="text-sm font-black text-blue-950">Platform administrator account</p><p class="mt-1 text-sm text-blue-700">Your account can create and manage business workspaces across HardFlow.</p></div>
                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-blue-700 shadow-sm">Super admin</span>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <livewire:profile.update-profile-information-form />
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <livewire:profile.update-password-form />
            </section>

            @if(auth()->user()->is_super_admin)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7 lg:col-span-2">
                    <livewire:profile.manage-sessions-form />
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
