<?php

namespace App\Http\Controllers\Owner;

use App\Actions\CreateRoleAction;
use App\Actions\SyncRolePermissionsAction;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): View
    {
        $business = $tenantContext->business();
        $this->authorize('manageRoles', $business);

        return view('owner.roles.index', [
            'business' => $business,
            'roles' => $business->roles()->with('permissions')->get(),
            'permissions' => Permission::query()->orderBy('module')->orderBy('name')->get(),
        ]);
    }

    public function store(
        Request $request,
        TenantContext $tenantContext,
        CreateRoleAction $createRole,
    ): RedirectResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer'],
        ]);

        $createRole->execute(
            $request->user(),
            $tenantContext->business(),
            $data['name'],
            $data['permission_ids'] ?? [],
            $data['description'] ?? null,
        );

        return back()->with('status', 'Role created successfully.');
    }

    public function updatePermissions(
        Request $request,
        Role $role,
        TenantContext $tenantContext,
        SyncRolePermissionsAction $syncRolePermissions,
    ): RedirectResponse {
        $data = $request->validate([
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer'],
        ]);

        $syncRolePermissions->execute(
            $request->user(),
            $tenantContext->business(),
            $role,
            $data['permission_ids'] ?? [],
        );

        return back()->with('status', 'Role permissions updated successfully.');
    }
}
