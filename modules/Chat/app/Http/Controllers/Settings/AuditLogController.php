<?php

namespace Modules\Chat\Http\Controllers\Settings;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->with(['user', 'auditable'])
            ->latest();

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $logs = $query->paginate(50);

        // Get statistics
        $baseStats = AuditLog::query();
        $createdCount = (clone $baseStats)->where('event', 'created')->count();
        $updatedCount = (clone $baseStats)->where('event', 'updated')->count();
        $deletedCount = (clone $baseStats)->whereIn('event', ['deleted', 'force_deleted'])->count();

        // Get distinct events for filter dropdown
        $events = AuditLog::query()
            ->distinct()
            ->pluck('event')
            ->filter()
            ->sort()
            ->values();

        // Get all users for filter dropdown
        $users = User::query()
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->get();

        return view('Chat::settings.audits.index', compact('logs', 'events', 'users', 'createdCount', 'updatedCount', 'deletedCount'));
    }
}
