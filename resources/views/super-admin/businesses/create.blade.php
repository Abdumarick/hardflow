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
                <p class="mt-1 text-sm text-slate-500"><span class="font-semibold text-red-600">Required</span> fields must be completed. <span class="font-semibold text-slate-500">Optional</span> fields can be added later.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach (['name' => 'Business name', 'code' => 'Business code', 'phone' => 'Phone', 'email' => 'Email', 'tin' => 'TIN', 'vrn' => 'VRN'] as $field => $label)
                        @php($isRequired = in_array($field, ['name', 'code']))
                        <label class="block text-sm font-medium text-slate-700">
                            <span class="flex items-center justify-between gap-2">
                                <span>{{ $label }}</span>
                                <span class="text-xs font-semibold {{ $isRequired ? 'text-red-600' : 'text-slate-400' }}">{{ $isRequired ? 'Required' : 'Optional' }}</span>
                            </span>
                            @if($field === 'phone')
                                <div class="mt-1.5 flex rounded-lg shadow-sm"><span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">+255</span><input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" autocomplete="tel-national" pattern="[0-9]{9}" maxlength="9" placeholder="712 345 678" class="min-w-0 flex-1 rounded-r-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700"></div>
                                <span class="mt-1 block text-xs text-slate-500">Enter the remaining 9 digits if you want the business phone shown on invoices.</span>
                            @else
                                <input type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}" value="{{ old($field) }}" @required($isRequired) class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                            @endif
                            @error($field)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                    <label class="block text-sm font-medium text-slate-700 sm:col-span-2">
                        <span class="flex items-center justify-between gap-2"><span>Address</span><span class="text-xs font-semibold text-slate-400">Optional</span></span>
                        <textarea name="address" rows="2" class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">{{ old('address') }}</textarea>
                        @error('address')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Main branch</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Branch name</span><span class="text-xs font-semibold text-red-600">Required</span></span>
                        <input name="branch_name" value="{{ old('branch_name', 'Main Branch') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                        @error('branch_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Branch code</span><span class="text-xs font-semibold text-red-600">Required</span></span>
                        <input name="branch_code" value="{{ old('branch_code', 'MAIN') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                        @error('branch_code')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Shop owner</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Owner name</span><span class="text-xs font-semibold text-red-600">Required</span></span>
                        <input name="owner_name" value="{{ old('owner_name') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                        @error('owner_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Owner phone number</span><span class="text-xs font-semibold text-red-600">Required</span></span>
                        <div class="mt-1.5 flex rounded-lg shadow-sm"><span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">+255</span><input type="tel" name="owner_phone" value="{{ old('owner_phone') }}" required inputmode="numeric" autocomplete="tel-national" pattern="[0-9]{9}" maxlength="9" placeholder="712 345 678" class="min-w-0 flex-1 rounded-r-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700"></div>
                        <span class="mt-1 block text-xs text-slate-500">Enter the remaining 9 digits. This number is used for SMS and can be used to log in.</span>
                        @error('owner_phone')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Owner email</span><span class="text-xs font-semibold text-slate-400">Optional</span></span>
                        <input type="email" name="owner_email" value="{{ old('owner_email') }}" autocomplete="email" class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                        <span class="mt-1 block text-xs text-slate-500">Email can also be used to log in if provided.</span>
                        @error('owner_email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Temporary password</span><span class="text-xs font-semibold text-red-600">Required</span></span>
                        <input type="password" name="owner_password" required class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-blue-700 focus:ring-blue-700">
                        <span class="mt-1 block text-xs text-slate-500">Use at least 8 characters.</span>
                        @error('owner_password')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700"><span class="flex items-center justify-between gap-2"><span>Confirm password</span><span class="text-xs font-semibold text-red-600">Required</span></span>
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
