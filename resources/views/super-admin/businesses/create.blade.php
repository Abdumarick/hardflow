<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Platform administration</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Create business</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('super-admin.businesses.store') }}" class="space-y-6">
            @csrf
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Business details</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach (['name' => 'Business name', 'code' => 'Business code', 'phone' => 'Phone', 'email' => 'Email', 'tin' => 'TIN', 'vrn' => 'VRN'] as $field => $label)
                        <label class="block text-sm font-medium text-slate-700">{{ $label }}
                            <input name="{{ $field }}" value="{{ old($field) }}" @required(in_array($field, ['name', 'code'])) class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                            @error($field)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                    <label class="block text-sm font-medium text-slate-700 sm:col-span-2">Address
                        <textarea name="address" rows="2" class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">{{ old('address') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Main branch</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Branch name
                        <input name="branch_name" value="{{ old('branch_name', 'Main Branch') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Branch code
                        <input name="branch_code" value="{{ old('branch_code', 'MAIN') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Shop owner</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Owner name
                        <input name="owner_name" value="{{ old('owner_name') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Owner email
                        <input type="email" name="owner_email" value="{{ old('owner_email') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Temporary password
                        <input type="password" name="owner_password" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Confirm password
                        <input type="password" name="owner_password_confirmation" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                    </label>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('super-admin.businesses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-lg bg-blue-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create business</button>
            </div>
        </form>
    </div>
</x-app-layout>
