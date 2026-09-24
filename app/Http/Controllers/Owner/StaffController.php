<?php

namespace App\Http\Controllers\Owner;

use App\Actions\CreateStaffAction;
use App\Actions\DisableStaffAction;
use App\Actions\EnableStaffAction;
use App\Actions\ResetStaffPasswordAction;
use App\Actions\UpdateStaffAccessAction;
use App\Actions\UpdateStaffDetailsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SewmrSmsService;
use App\Support\PhoneNumber;
use App\Support\TemporaryCredentials;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): View
    {
        $business = $tenantContext->business();
        $this->authorize('viewStaff', $business);

        return view('owner.staff.index', [
            'business' => $business,
            'memberships' => $business->memberships()->with(['user.roles', 'user.branches'])->paginate(),
            'branches' => $business->branches()->where('is_active', true)->get(),
            'roles' => $business->roles()->where('is_active', true)->get(),
        ]);
    }

    public function store(
        Request $request,
        TenantContext $tenantContext,
        CreateStaffAction $createStaff,
        SewmrSmsService $sms,
    ): RedirectResponse {
        $business = $tenantContext->business();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer'],
        ]);

        $data['phone'] = PhoneNumber::normalize($data['phone']);
        if (! $data['phone']) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Tanzanian mobile number.']);
        }
        if (User::query()->where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered.']);
        }

        $temporaryPassword = TemporaryCredentials::password($data['name']);
        $data['username'] = TemporaryCredentials::username($data['name']);
        $data['password'] = $temporaryPassword;
        $staff = $createStaff->execute($request->user(), $business, $data);
        $sms->sendStaffCredentials($staff, $business, $temporaryPassword);

        return back()->with('status', 'Staff member created successfully.');
    }

    public function disable(
        Request $request,
        User $staff,
        TenantContext $tenantContext,
        DisableStaffAction $disableStaff,
    ): RedirectResponse {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $disableStaff->execute($request->user(), $tenantContext->business(), $staff, $data['reason']);

        return back()->with('status', 'Staff membership disabled successfully.');
    }

    public function enable(
        Request $request,
        User $staff,
        TenantContext $tenantContext,
        EnableStaffAction $enableStaff,
    ): RedirectResponse {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $enableStaff->execute($request->user(), $tenantContext->business(), $staff, $data['reason']);

        return back()->with('status', __('staff.enabled_successfully'));
    }

    public function resetPassword(Request $request, User $staff, TenantContext $tenantContext, ResetStaffPasswordAction $action, SewmrSmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if (blank($staff->phone)) {
            throw ValidationException::withMessages([
                'staff' => 'This staff member needs a mobile number before their password can be reset by SMS.',
            ]);
        }

        $temporaryPassword = TemporaryCredentials::password($staff->name);
        $action->execute($request->user(), $tenantContext->businessOrFail(), $staff, $temporaryPassword, $data['reason']);
        $sms->sendPasswordReset($staff, $temporaryPassword);

        return back()->with('status', __('staff.password_reset_successfully'));
    }

    public function updateAccess(Request $request, User $staff, TenantContext $tenantContext, UpdateStaffAccessAction $action): RedirectResponse
    {
        $data = $request->validate([
            'branch_ids' => ['required', 'array', 'min:1'], 'branch_ids.*' => ['integer'],
            'role_ids' => ['required', 'array', 'min:1'], 'role_ids.*' => ['integer'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $action->execute($request->user(), $tenantContext->businessOrFail(), $staff, $data['branch_ids'], $data['role_ids'], $data['reason']);

        return back()->with('status', __('staff.access_updated_successfully'));
    }

    public function updateDetails(Request $request, User $staff, TenantContext $tenantContext, UpdateStaffDetailsAction $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9]+$/', Rule::unique('users', 'username')->ignore($staff->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'phone' => ['required', 'string', 'max:30'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $data['phone'] = PhoneNumber::normalize($data['phone']);
        if (! $data['phone']) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Tanzanian mobile number.']);
        }
        if (User::query()->where('phone', $data['phone'])->where('id', '!=', $staff->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number is already registered.']);
        }

        $action->execute($request->user(), $tenantContext->businessOrFail(), $staff, $request->only(['name', 'username', 'email']) + ['phone' => $data['phone']], $data['reason']);

        return back()->with('status', __('staff.details_updated_successfully'));
    }
}
