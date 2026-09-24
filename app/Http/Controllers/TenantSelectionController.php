<?php

namespace App\Http\Controllers;

use App\Actions\SwitchTenantContextAction;
use App\Models\Branch;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSelectionController extends Controller
{
    public function __invoke(
        Request $request,
        Business $business,
        SwitchTenantContextAction $switchTenantContext,
    ): RedirectResponse {
        $data = $request->validate(['branch' => ['nullable', 'uuid'], 'destination' => ['nullable', 'in:dashboard,staff']]);
        $branch = isset($data['branch'])
            ? Branch::query()->where('public_id', $data['branch'])->firstOrFail()
            : null;

        $switchTenantContext->execute($request->user(), $business, $branch);

        return redirect()->route(($data['destination'] ?? 'dashboard') === 'staff' ? 'owner.staff.index' : 'dashboard');
    }
}
