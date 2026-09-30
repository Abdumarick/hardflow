<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->is_active && $request->user()->is_super_admin, 403);

        $query = AuditLog::query()
            ->with(['business', 'branch', 'user'])
            ->whereDoesntHave('user', fn ($user) => $user->where('is_super_admin', true))
            ->latest();

        if ($request->filled('business')) {
            $query->where('business_id', $request->integer('business'));
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action').'%');
        }

        return view('super-admin.audit.index', [
            'businesses' => Business::query()->orderBy('name')->get(['id', 'name', 'code']),
            'logs' => $query->paginate(50)->withQueryString(),
        ]);
    }
}
