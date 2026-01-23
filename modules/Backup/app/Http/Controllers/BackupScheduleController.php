<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Backup\Models\BackupSchedule;

class BackupScheduleController extends Controller
{
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

        $schedules = $query->get();
        $pageTitle = 'Backups Programados';
        $breadcrumb = 'Configuración / Backups Programados';
        $searchKey = $request->get('search');

        return view('backup::schedules.index', compact('schedules', 'pageTitle', 'breadcrumb', 'searchKey'));
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
    public function edit($id)
    {
        $schedule = BackupSchedule::findOrFail($id);
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
    public function update(Request $request, $id)
    {
        $schedule = BackupSchedule::findOrFail($id);

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
    public function destroy(Request $request, $id)
    {
        $schedule = BackupSchedule::findOrFail($id);
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
    public function toggle(Request $request, $id)
    {
        $schedule = BackupSchedule::findOrFail($id);
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
     * Get schedule details via AJAX
     */
    public function getScheduleDetails($id)
    {
        $schedule = BackupSchedule::findOrFail($id);

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
