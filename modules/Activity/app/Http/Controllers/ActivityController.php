<?php

namespace Modules\Activity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Activity\Http\Resources\ActivityResource;
use Modules\Activity\Services\ActivityLogService;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    /**
     * Display activity logs
     */
    public function logs(Request $request): View
    {
        $this->authorize('Activity.logs.index');
        $pageTitle = 'Registro de cambios';
        $breadcrumb = 'Historial / Registro de cambios';

        $query = Activity::query()
            ->with('causer')
            ->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%{$search}%")
                ->orWhereJsonContains('properties->old', $search)
                ->orWhereJsonContains('properties->attributes', $search);
        }

        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        $activities = $query->paginate(paginationNumber());
        $stats = $this->eventStats();

        return view('activity::settings.logs.index', compact('pageTitle', 'breadcrumb', 'activities', 'stats'));
    }

    /**
     * Display audit information
     */
    public function audit(Request $request): View
    {
        $this->authorize('Activity.audit.index');
        $pageTitle = 'Auditoría';
        $breadcrumb = 'Historial / Auditoría';

        $query = Activity::query()
            ->with('causer')
            ->latest('created_at');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        $activities = $query->paginate(paginationNumber());
        $stats = $this->eventStats();
        $logNames = Activity::query()->distinct()->pluck('log_name')->filter()->sort()->values();

        return view('activity::settings.audit.index', compact('pageTitle', 'breadcrumb', 'activities', 'stats', 'logNames'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('Activity.logs.export');

        $query = Activity::query()
            ->with('causer')
            ->when($request->filled('user_id'), fn ($q) => $q->where('causer_id', $request->user_id))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->filled('search'), fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->latest();

        $filename = 'actividad_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['ID', 'Evento', 'Descripción', 'Tipo', 'ID Objeto', 'Usuario', 'IP', 'Fecha']);

            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->event ?? '',
                        $log->description,
                        class_basename($log->subject_type ?? ''),
                        $log->subject_id ?? '',
                        $log->causer?->name ?? $log->causer?->email ?? 'Sistema',
                        $log->properties['ip'] ?? '',
                        $log->created_at->format('d/m/Y H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{total: int, created: int, updated: int, deleted: int} */
    private function eventStats(): array
    {
        return Cache::remember('activity:event_stats', now()->addMinutes(5), function () {
            $row = Activity::query()
                ->selectRaw("COUNT(*) as total, SUM(event='created') as created, SUM(event='updated') as updated, SUM(event='deleted') as deleted")
                ->first();

            return [
                'total' => (int) $row->total,
                'created' => (int) $row->created,
                'updated' => (int) $row->updated,
                'deleted' => (int) $row->deleted,
            ];
        });
    }

    public function stats(): JsonResponse
    {
        $this->authorize('Activity.logs.index');

        $data = app(ActivityLogService::class)->countByEvent();

        return response()->json($data);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('Activity.logs.delete');

        $validated = $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = Activity::whereIn('id', $validated['ids'])->delete();
        Cache::forget('activity:event_stats');

        return response()->json([
            'message' => $count.' registro(s) eliminados.',
            'count' => $count,
        ]);
    }

    public function show(int $id): View
    {
        $this->authorize('Activity.logs.index');

        $activity = Activity::with('causer')->findOrFail($id);

        $pageTitle = 'Detalle del registro';
        $breadcrumb = 'Historial / Registro de cambios / Detalle';

        return view('activity::settings.logs.show', compact('pageTitle', 'breadcrumb', 'activity'));
    }

    public function auditData(Request $request): JsonResponse
    {
        $this->authorize('Activity.audit.index');
        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->input('search').'%');
        }
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $activities = $query->paginate(paginationNumber());

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities->getCollection()),
            'pagination' => [
                'total' => $activities->total(),
                'perPage' => $activities->perPage(),
                'currentPage' => $activities->currentPage(),
                'lastPage' => $activities->lastPage(),
            ],
        ]);
    }
}
