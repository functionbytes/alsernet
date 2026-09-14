<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Backup\Models\BackupSchedule;

class BackupScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Backup.schedules.index')->only('index', 'create', 'edit', 'getScheduleDetails');
        $this->middleware('can:Backup.schedules.create')->only('store');
        $this->middleware('can:Backup.schedules.update')->only('update', 'toggle');
        $this->middleware('can:Backup.schedules.delete')->only('destroy');
    }

    /**
     * Display backup schedules list
     */
    public function index(Request $request)
    {
        $query = BackupSchedule::query();

        // Search by name
        if ($request->has('search') && $request->get('search')) {
            $query->where('name', 'like', '%'.$request->get('search').'%');
        }

        // Filter by status
        if ($request->has('status') && $request->get('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('enabled', true);
            } elseif ($status === 'inactive') {
                $query->where('enabled', false);
            }
        }

        $schedules = $query->paginate(50)->withQueryString();
        $searchKey = $request->get('search');

        $statsRow = BackupSchedule::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN enabled = 0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN last_run_at IS NULL THEN 1 ELSE 0 END) as never_run
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'inactive' => (int) $statsRow->inactive,
            'never_run' => (int) $statsRow->never_run,
        ];

        return view('backup::schedules.index', compact('schedules', 'searchKey', 'stats'));
    }

    /**
     * Show form to create new schedule
     */
    public function create()
    {
        $pageTitle = 'Crear Backup Programado';
        $breadcrumb = 'Configuración / Backups Programados / Crear';

        $frequencies = ['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'custom' => 'Personalizado'];
        $backupOptions = [
            'app_code' => 'Código de la Aplicación',
            'config' => 'Configuración',
            'routes' => 'Rutas',
            'resources' => 'Recursos',
            'migrations' => 'Migraciones',
            'storage' => 'Almacenamiento',
            'database' => 'Base de Datos',
        ];

        return view('backup::schedules.create', compact(
            'pageTitle',
            'breadcrumb',
            'frequencies',
            'backupOptions'
        ));
    }

    /**
     * Store new backup schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'scheduled_time' => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_month' => 'nullable|array',
            'custom_interval_hours' => 'nullable|integer|min:1',
            'backup_types' => 'required|array|min:1',
        ]);

        // Validate frequency-specific requirements
        if ($validated['frequency'] === 'weekly' && (empty($validated['days_of_week']) || ! is_array($validated['days_of_week']))) {
            return back()->with('error', 'Debe seleccionar al menos un día de la semana para backups semanales');
        }

        if ($validated['frequency'] === 'monthly' && (empty($validated['days_of_month']) || ! is_array($validated['days_of_month']))) {
            return back()->with('error', 'Debe seleccionar al menos un día del mes para backups mensuales');
        }

        if ($validated['frequency'] === 'custom' && empty($validated['custom_interval_hours'])) {
            return back()->with('error', 'Debe especificar el intervalo en horas para backups personalizados');
        }

        $schedule = BackupSchedule::create([
            'name' => $validated['name'],
            'enabled' => $validated['enabled'],
            'frequency' => $validated['frequency'],
            'scheduled_time' => $validated['scheduled_time'].':00',
            'days_of_week' => $validated['days_of_week'] ?? null,
            'days_of_month' => $validated['days_of_month'] ?? null,
            'custom_interval_hours' => $validated['custom_interval_hours'] ?? null,
            'backup_types' => $validated['backup_types'],
        ]);

        // Calculate and set the next run time
        $schedule->update([
            'next_run_at' => $schedule->calculateNextRun(),
        ]);

        return redirect()->route('settings.backup.schedules.index')
            ->with('success', 'Schedule de backup creado exitosamente');
    }

    /**
     * Show form to edit schedule
     */
    public function edit(BackupSchedule $schedule): View
    {
        $pageTitle = 'Editar Backup Programado';
        $breadcrumb = 'Configuración / Backups Programados / Editar';

        $frequencies = ['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'custom' => 'Personalizado'];
        $backupOptions = [
            'app_code' => 'Código de la Aplicación',
            'config' => 'Configuración',
            'routes' => 'Rutas',
            'resources' => 'Recursos',
            'migrations' => 'Migraciones',
            'storage' => 'Almacenamiento',
            'database' => 'Base de Datos',
        ];

        return view('backup::schedules.edit', compact(
            'schedule',
            'pageTitle',
            'breadcrumb',
            'frequencies',
            'backupOptions'
        ));
    }

    /**
     * Update backup schedule
     */
    public function update(Request $request, BackupSchedule $schedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'scheduled_time' => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_month' => 'nullable|array',
            'custom_interval_hours' => 'nullable|integer|min:1',
            'backup_types' => 'required|array|min:1',
        ]);

        // First update with all the new values
        $schedule->update([
            'name' => $validated['name'],
            'enabled' => $validated['enabled'],
            'frequency' => $validated['frequency'],
            'scheduled_time' => $validated['scheduled_time'].':00',
            'days_of_week' => $validated['days_of_week'] ?? null,
            'days_of_month' => $validated['days_of_month'] ?? null,
            'custom_interval_hours' => $validated['custom_interval_hours'] ?? null,
            'backup_types' => $validated['backup_types'],
        ]);

        // Reload the model to get the updated values, then calculate next_run_at
        $schedule->refresh();
        $schedule->update([
            'next_run_at' => $schedule->calculateNextRun(),
        ]);

        return redirect()->route('settings.backup.schedules.index')
            ->with('success', 'Schedule de backup actualizado exitosamente');
    }

    /**
     * Delete backup schedule
     */
    public function destroy(Request $request, BackupSchedule $schedule)
    {
        $schedule->delete();

        $isJsonRequest = $request->expectsJson() || $request->header('Accept') === 'application/json';

        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'message' => 'Schedule eliminado exitosamente',
            ]);
        }

        return redirect()->route('settings.backup.schedules.index')
            ->with('success', 'Schedule eliminado exitosamente');
    }

    /**
     * Toggle schedule enabled/disabled
     */
    public function toggle(Request $request, BackupSchedule $schedule)
    {
        $schedule->update(['enabled' => ! $schedule->enabled]);

        $isJsonRequest = $request->expectsJson() || $request->header('Accept') === 'application/json';

        if ($isJsonRequest) {
            return response()->json([
                'success' => true,
                'enabled' => $schedule->enabled,
                'message' => $schedule->enabled ? 'Schedule activado' : 'Schedule desactivado',
            ]);
        }

        return back()->with('success', $schedule->enabled ? 'Schedule activado' : 'Schedule desactivado');
    }

    /**
     * Bulk action on schedules
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids', []);

        match ($request->action) {
            'activate' => BackupSchedule::whereIn('id', $ids)->update(['enabled' => true]),
            'deactivate' => BackupSchedule::whereIn('id', $ids)->update(['enabled' => false]),
            'delete' => BackupSchedule::whereIn('id', $ids)->delete(),
        };

        $count = count($ids);

        $messages = [
            'activate' => "{$count} programación(es) activada(s) correctamente.",
            'deactivate' => "{$count} programación(es) desactivada(s) correctamente.",
            'delete' => "{$count} programación(es) eliminada(s) correctamente.",
        ];

        return response()->json(['message' => $messages[$request->action]]);
    }

    /**
     * Get schedule details via AJAX
     */
    public function getScheduleDetails(BackupSchedule $schedule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'schedule' => [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'enabled' => $schedule->enabled,
                'frequency' => $schedule->frequency,
                'scheduled_time' => $schedule->scheduled_time,
                'backup_types' => $schedule->backup_types,
                'last_run_at' => $schedule->last_run_at?->format('Y-m-d H:i:s'),
                'next_run_at' => $schedule->next_run_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
