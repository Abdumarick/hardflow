<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\CreateBusinessAction;
use App\Actions\DisableBusinessAction;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Business::class);

        return view('super-admin.businesses.index', [
            'businesses' => Business::query()->withCount(['branches', 'memberships'])->latest()->paginate(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Business::class);

        return view('super-admin.businesses.create');
    }

    public function store(Request $request, CreateBusinessAction $createBusiness): RedirectResponse
    {
        $this->authorize('create', Business::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('businesses', 'code')],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_code' => ['required', 'alpha_dash', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tin' => ['nullable', 'string', 'max:50'],
            'vrn' => ['nullable', 'string', 'max:50'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_phone' => ['required', 'string', 'max:30'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['owner_phone'] = PhoneNumber::normalize($data['owner_phone']);
        if ($data['owner_phone'] === null) {
            return back()->withInput()->withErrors(['owner_phone' => 'Enter a valid Tanzanian phone number after the 255 prefix.']);
        }
        if (filled($data['phone'] ?? null)) {
            $data['phone'] = PhoneNumber::normalize($data['phone']);
            if ($data['phone'] === null) {
                return back()->withInput()->withErrors(['phone' => 'Enter a valid Tanzanian phone number after the 255 prefix.']);
            }
        }
        if (User::query()->where('phone', $data['owner_phone'])->exists()) {
            return back()->withInput()->withErrors(['owner_phone' => 'This phone number is already in use.']);
        }

        $owner = User::query()->create([
            'name' => $data['owner_name'],
            'email' => $data['owner_email'] ?? null,
            'phone' => $data['owner_phone'],
            'password' => $data['owner_password'],
        ]);

        try {
            $business = $createBusiness->execute($request->user(), $owner, $data);
        } catch (\Throwable $exception) {
            $owner->delete();

            throw $exception;
        }

        return redirect()->route('super-admin.businesses.show', $business)
            ->with('status', 'Business created successfully.');
    }

    public function show(Business $business): View
    {
        $this->authorize('view', $business);

        return view('super-admin.businesses.show', [
            'business' => $business->load(['branches', 'memberships.user', 'roles']),
        ]);
    }

    public function disable(
        Request $request,
        Business $business,
        DisableBusinessAction $disableBusiness,
    ): RedirectResponse {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $disableBusiness->execute($request->user(), $business, $data['reason']);

        return redirect()->route('super-admin.businesses.index')
            ->with('status', 'Business disabled successfully.');
    }
}
