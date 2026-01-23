<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    /**
     * Display activity logs
     */
    public function logs(Request $request)
    {
        $pageTitle = 'Registro de cambios';
        $breadcrumb = 'Historial / Registro de cambios';

        $query = Activity::query()
            ->latest('created_at');

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%")
                ->orWhereJsonContains('properties->old', $search)
                ->orWhereJsonContains('properties->attributes', $search);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'));
        }

        // Filter by model
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        $activities = $query->paginate(50);

        return view('activity::logs.index', compact(
            'pageTitle',
            'breadcrumb',
            'activities'
        ));
    }

    /**
     * Display audit information
     */
    public function audit(Request $request)
    {
        $pageTitle = 'Auditoría';
        $breadcrumb = 'Historial / Auditoría';

        $query = Activity::query()
            ->latest('created_at');

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%");
        }

        // Filter by event
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $activities = $query->paginate(50);

        return view('activity::audit.index', compact(
            'pageTitle',
            'breadcrumb',
            'activities'
        ));
    }
}
