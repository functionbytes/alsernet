<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    private const HELPDESK_LOG_NAMES = [
        'helpdesk',
        'helpdesk-conversations',
        'helpdesk-tickets',
        'helpdesk-webhooks',
        'default',
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['index']);
    }

    public function index(Request $request): View
    {
        if (! class_exists('\Spatie\Activitylog\Models\Activity')) {
            return view('helpdesk::settings.audits.index', [
                'activities' => null,
                'users' => collect(),
                'logNames' => [],
                'unavailable' => true,
            ]);
        }

        $query = Activity::query()
            ->with('causer')
            ->whereIn('log_name', self::HELPDESK_LOG_NAMES);

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id)
                ->where('causer_type', 'App\\Models\\User');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        $activities = $query->latest()->paginate(30);

        $users = User::query()
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->orderBy('firstname')
            ->get();

        $logNames = Activity::query()
            ->whereIn('log_name', self::HELPDESK_LOG_NAMES)
            ->distinct()
            ->pluck('log_name')
            ->sort()
            ->values();

        return view('helpdesk::settings.audits.index', compact('activities', 'users', 'logNames'));
    }
}
