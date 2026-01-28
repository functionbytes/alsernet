<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Mailing\Http\Controllers\Controller;
use Modules\Mailing\Models\CronjobSetting;

class CronjobSettingController extends Controller
{
    /**
     * Display the cronjob settings page with all scheduled jobs.
     */
    public function index(): View
    {
        Gate::authorize('mailing.settings.cronjobs');

        $cronjobs = CronjobSetting::orderBy('name')->paginate(15);

        return view('mailing::settings.cronjob-settings.index', [
            'cronjobs' => $cronjobs,
        ]);
    }

    /**
     * Store a new cronjob setting.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:mailing_cronjob_settings',
                'description' => 'nullable|string|max:1000',
                'job_class' => 'required|string|max:255',
                'schedule_expression' => 'required|string|max:255',
                'enabled' => 'boolean',
                'run_in_maintenance_mode' => 'boolean',
                'without_overlapping' => 'boolean',
                'on_single_server' => 'boolean',
                'ping_before_url' => 'nullable|url',
                'ping_after_url' => 'nullable|url',
                'ping_on_failure_url' => 'nullable|url',
                'max_execution_time' => 'nullable|integer|min:1',
                'retry_on_failure' => 'boolean',
                'retry_times' => 'nullable|integer|min:1|max:10',
                'retry_delay_seconds' => 'nullable|integer|min:1|max:3600',
                'timeout_seconds' => 'nullable|integer|min:5|max:86400',
                'notify_on_failure' => 'boolean',
                'notification_email' => 'nullable|email|max:255',
                'log_output' => 'boolean',
                'custom_options' => 'nullable|json',
            ]);

            // Convert checkbox values
            $validated['enabled'] = $request->has('enabled');
            $validated['run_in_maintenance_mode'] = $request->has('run_in_maintenance_mode');
            $validated['without_overlapping'] = $request->has('without_overlapping');
            $validated['on_single_server'] = $request->has('on_single_server');
            $validated['retry_on_failure'] = $request->has('retry_on_failure');
            $validated['notify_on_failure'] = $request->has('notify_on_failure');
            $validated['log_output'] = $request->has('log_output');

            CronjobSetting::create($validated);

            return redirect()
                ->route('settings.mailing.settings.cronjobs.index')
                ->with('success', 'Trabajo cron creado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al crear: '.$e->getMessage());
        }
    }

    /**
     * Update a cronjob setting.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $cronjob = CronjobSetting::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:mailing_cronjob_settings,name,'.$id,
                'description' => 'nullable|string|max:1000',
                'job_class' => 'required|string|max:255',
                'schedule_expression' => 'required|string|max:255',
                'enabled' => 'boolean',
                'run_in_maintenance_mode' => 'boolean',
                'without_overlapping' => 'boolean',
                'on_single_server' => 'boolean',
                'ping_before_url' => 'nullable|url',
                'ping_after_url' => 'nullable|url',
                'ping_on_failure_url' => 'nullable|url',
                'max_execution_time' => 'nullable|integer|min:1',
                'retry_on_failure' => 'boolean',
                'retry_times' => 'nullable|integer|min:1|max:10',
                'retry_delay_seconds' => 'nullable|integer|min:1|max:3600',
                'timeout_seconds' => 'nullable|integer|min:5|max:86400',
                'notify_on_failure' => 'boolean',
                'notification_email' => 'nullable|email|max:255',
                'log_output' => 'boolean',
                'custom_options' => 'nullable|json',
            ]);

            // Convert checkbox values
            $validated['enabled'] = $request->has('enabled');
            $validated['run_in_maintenance_mode'] = $request->has('run_in_maintenance_mode');
            $validated['without_overlapping'] = $request->has('without_overlapping');
            $validated['on_single_server'] = $request->has('on_single_server');
            $validated['retry_on_failure'] = $request->has('retry_on_failure');
            $validated['notify_on_failure'] = $request->has('notify_on_failure');
            $validated['log_output'] = $request->has('log_output');

            $cronjob->update($validated);

            return redirect()
                ->route('settings.mailing.settings.cronjobs.index')
                ->with('success', 'Trabajo cron actualizado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    /**
     * Execute a cronjob immediately.
     */
    public function runNow(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $cronjob = CronjobSetting::findOrFail($id);

            if (! $cronjob->enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede ejecutar un trabajo cron deshabilitado.',
                ], 422);
            }

            // Execute the job class
            if (class_exists($cronjob->job_class)) {
                $job = new $cronjob->job_class;

                if (method_exists($job, 'handle')) {
                    $job->handle();
                } else {
                    throw new \Exception('El trabajo no tiene un método handle.');
                }

                // Record last execution time
                $cronjob->update([
                    'last_executed_at' => now(),
                    'last_execution_status' => 'success',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Trabajo cron ejecutado correctamente.',
                ]);
            } else {
                throw new \Exception("Clase de trabajo no encontrada: {$cronjob->job_class}");
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a cronjob setting.
     */
    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $cronjob = CronjobSetting::findOrFail($id);
            $cronjob->delete();

            return redirect()
                ->route('settings.mailing.settings.cronjobs.index')
                ->with('success', 'Trabajo cron eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al eliminar: '.$e->getMessage());
        }
    }

    /**
     * Toggle the status of a cronjob.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $cronjob = CronjobSetting::findOrFail($id);

            $newStatus = ! $cronjob->enabled;
            $cronjob->update(['enabled' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Estado del trabajo cron actualizado correctamente.',
                'enabled' => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get execution history for a cronjob.
     */
    public function executionHistory(int $id): JsonResponse
    {
        Gate::authorize('mailing.settings.cronjobs');

        try {
            $cronjob = CronjobSetting::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'last_executed_at' => $cronjob->last_executed_at?->toIso8601String(),
                    'last_execution_status' => $cronjob->last_execution_status,
                    'total_executions' => $cronjob->total_executions ?? 0,
                    'successful_executions' => $cronjob->successful_executions ?? 0,
                    'failed_executions' => $cronjob->failed_executions ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 422);
        }
    }
}
