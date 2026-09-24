<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Business;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $businessId = $request->session()->get('tenant.business_id');
        $branchId = $request->session()->get('tenant.branch_id');

        if ($businessId === null) {
            [$businessId, $branchId] = $this->selectDefaultWorkspace($request);
        }

        $business = Business::query()->findOrFail($businessId);
        $branch = $branchId === null ? $this->firstAccessibleBranch($request, $business) : Branch::query()->findOrFail($branchId);

        if ($branch !== null) {
            $request->session()->put('tenant.branch_id', $branch->id);
        }

        $this->tenantContext->setForUser($request->user(), $business, $branch);

        return $next($request);
    }

    /** @return array{0:int,1:int|null} */
    private function selectDefaultWorkspace(Request $request): array
    {
        $user = $request->user();
        $business = $user->is_super_admin
            ? Business::query()->where('is_active', true)->orderBy('name')->first()
            : $user->businesses()
                ->wherePivot('is_active', true)
                ->where('businesses.is_active', true)
                ->orderBy('businesses.name')
                ->first();

        abort_if($business === null, Response::HTTP_FORBIDDEN, 'No active business is assigned to this account.');

        $branch = $this->firstAccessibleBranch($request, $business);
        $request->session()->put('tenant.business_id', $business->id);
        $request->session()->put('tenant.branch_id', $branch?->id);

        return [$business->id, $branch?->id];
    }

    private function firstAccessibleBranch(Request $request, Business $business): ?Branch
    {
        $branches = $business->branches()->where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get();

        return $branches->first(fn (Branch $branch): bool => $request->user()->is_super_admin || $request->user()->hasActiveBranchAccess($branch));
    }
}
