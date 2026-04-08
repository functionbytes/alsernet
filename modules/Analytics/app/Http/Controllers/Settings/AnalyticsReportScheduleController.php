<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Analytics\Events\ScheduleCreated;
use Modules\Analytics\Http\Requests\Settings\StoreScheduleRequest;
use Modules\Analytics\Models\AnalyticsReportSchedule;
use Modules\Analytics\Services\AnalyticsReportService;

class AnalyticsReportScheduleController extends Controller
{
    public function __construct(private readonly AnalyticsReportService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('analytics.reports.view');

        $search = $request->get('search');

        $query = AnalyticsReportSchedule::query()->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $schedules = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => AnalyticsReportSchedule::count(),
            'active' => AnalyticsReportSchedule::where('is_active', true)->count(),
            'inactive' => AnalyticsReportSchedule::where('is_active', false)->count(),
            'pending' => AnalyticsReportSchedule::where('is_active', true)
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', now()->addDays(7))
                ->count(),
        ];

        return view('analytics::settings.schedules.index', compact('schedules', 'stats', 'search'));
    }

    public function create(): View
    {
        $this->authorize('analytics.reports.schedule');

        return view('analytics::settings.schedules.create');
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $this->authorize('analytics.reports.schedule');

        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['next_run_at'] = $this->service->calculateNextRun($data['frequency']);

        $schedule = AnalyticsReportSchedule::create($data);

        event(new ScheduleCreated($schedule));

        return redirect()->route('settings.analytics.schedules.index')
            ->with('success', __('analytics::analytics.success.schedule_created'));
    }

    public function edit(AnalyticsReportSchedule $schedule): View
    {
        $this->authorize('analytics.reports.schedule');

        return view('analytics::settings.schedules.edit', compact('schedule'));
    }

    public function update(StoreScheduleRequest $request, AnalyticsReportSchedule $schedule): RedirectResponse
    {
        $this->authorize('analytics.reports.schedule');

        $data = $request->validated();
        $data['next_run_at'] = $this->service->calculateNextRun($data['frequency']);

        $schedule->update($data);

        return redirect()->route('settings.analytics.schedules.index')
            ->with('success', 'Reporte actualizado correctamente.');
    }

    public function destroy(AnalyticsReportSchedule $schedule): RedirectResponse
    {
        $this->authorize('analytics.reports.schedule');

        $schedule->delete();

        return redirect()->route('settings.analytics.schedules.index')
            ->with('success', __('analytics::analytics.success.schedule_deleted'));
    }

    public function toggle(AnalyticsReportSchedule $schedule): JsonResponse
    {
        $this->authorize('analytics.reports.schedule');

        $schedule->update(['is_active' => ! $schedule->is_active]);

        return response()->json(['is_active' => $schedule->is_active]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('analytics.reports.schedule');

        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:analytics_report_schedules,id',
        ]);

        $query = AnalyticsReportSchedule::whereIn('id', $request->ids);
        $count = $query->count();

        match ($request->action) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => $query->delete(),
        };

        return response()->json(['success' => true, 'count' => $count]);
    }
}
