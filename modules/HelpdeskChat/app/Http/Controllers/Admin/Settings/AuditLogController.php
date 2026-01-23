<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::forAccount(auth()->user()->account_id)
            ->with(['user', 'auditable'])
            ->latest();

        // Filter by event
        if ($request->filled('event')) {
            $query->event($request->event);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        $logs = $query->paginate(50);
        $events = AuditLog::forAccount(auth()->user()->account_id)
            ->distinct()
            ->pluck('event');

        $users = User::where('account_id', auth()->user()->account_id)
            ->orderBy('name')
            ->get();

        return view('helpdeskchat::admin.settings.audits.index', compact('logs', 'events', 'users'));
    }
}
