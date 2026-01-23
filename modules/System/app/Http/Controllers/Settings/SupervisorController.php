<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Models\Setting;
use Modules\System\Services\SupervisorService;

class SupervisorController extends Controller
{
    /**
     * Display the Supervisor dashboard with all processes
     */
    public function index()
    {
        try {
            $supervisorStatus = SupervisorService::getStatus();
            $processes = $supervisorStatus['processes'] ?? [];

            // Filter only Alsernet processes
            $alsarnetProcesses = SupervisorService::getAlsernetProcesses();

            $pageTitle = 'Panel de Control - Supervisor';
            $breadcrumb = 'Configuración / Sistema / Supervisor';

            return view('system::supervisor.index', compact(
                'processes',
                'alsarnetProcesses',
                'pageTitle',
                'breadcrumb'
            ));
        } catch (\Exception $e) {
            return view('system::supervisor.index', [
                'error' => 'Error al conectar con Supervisor: '.$e->getMessage(),
                'processes' => [],
                'alsarnetProcesses' => [],
                'pageTitle' => 'Panel de Control - Supervisor',
                'breadcrumb' => 'Configuración / Sistema / Supervisor',
            ]);
        }
    }

    /**
     * Show detailed view for a specific process
     */
    public function show($processName)
    {
        try {
            $processStatus = SupervisorService::getProcessStatus($processName);

            if (isset($processStatus['error'])) {
                return redirect()->route('settings.system.supervisor.index')
                    ->with('error', 'Proceso no encontrado: '.$processStatus['error']);
            }

            $logs = SupervisorService::getProcessLogs($processName, 100);
            $pid = SupervisorService::getPid($processName);
            $uptime = SupervisorService::getUptime($processName);

            $pageTitle = 'Detalles del Proceso - '.$processName;
            $breadcrumb = 'Configuración / Sistema / Supervisor / '.$processName;

            return view('system::supervisor.show', compact(
                'processName',
                'processStatus',
                'logs',
                'pid',
                'uptime',
                'pageTitle',
                'breadcrumb'
            ));
        } catch (\Exception $e) {
            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al cargar detalles del proceso: '.$e->getMessage());
        }
    }

    /**
     * Start a process via AJAX
     */
    public function start(Request $request, $processName)
    {
        try {
            $result = SupervisorService::startProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso iniciado correctamente' : 'Error al iniciar el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso iniciado correctamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al iniciar el proceso: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al iniciar el proceso: '.$e->getMessage());
        }
    }

    /**
     * Stop a process via AJAX
     */
    public function stop(Request $request, $processName)
    {
        try {
            $result = SupervisorService::stopProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso detenido correctamente' : 'Error al detener el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso detenido correctamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al detener el proceso: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al detener el proceso: '.$e->getMessage());
        }
    }

    /**
     * Restart a process via AJAX
     */
    public function restart(Request $request, $processName)
    {
        try {
            $result = SupervisorService::restartProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso reiniciado correctamente' : 'Error al reiniciar el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso reiniciado correctamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al reiniciar el proceso: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al reiniciar el proceso: '.$e->getMessage());
        }
    }

    /**
     * Reload Supervisor configuration via AJAX
     */
    public function reload(Request $request)
    {
        try {
            $result = SupervisorService::reload();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Configuración recargada correctamente' : 'Error al recargar la configuración',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Configuración recargada correctamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al recargar la configuración: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al recargar la configuración: '.$e->getMessage());
        }
    }

    /**
     * Get process logs via AJAX
     */
    public function getLogs(Request $request, $processName)
    {
        try {
            $lines = $request->input('lines', 50);
            $logs = SupervisorService::getProcessLogs($processName, $lines);

            if (isset($logs['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener logs: '.$logs['error'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'logs' => $logs['logs'] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get status of all processes via AJAX (for real-time updates)
     */
    public function getStatusAjax(Request $request)
    {
        try {
            $supervisorStatus = SupervisorService::getStatus();

            // Check if there's an error from the service
            if (isset($supervisorStatus['error'])) {
                \Log::warning('Supervisor Service Error: '.$supervisorStatus['error']);

                return response()->json([
                    'success' => false,
                    'message' => $supervisorStatus['error'],
                ], 200); // Return 200 so JavaScript success block handles it
            }

            $processes = $supervisorStatus['processes'] ?? [];

            // Filter only Alsernet processes
            $alsarnetProcesses = SupervisorService::getAlsernetProcesses();

            return response()->json([
                'success' => true,
                'processes' => $processes,
                'alsarnetProcesses' => $alsarnetProcesses,
            ]);
        } catch (\Exception $e) {
            \Log::error('SupervisorController::getStatusAjax - '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado: '.$e->getMessage(),
            ], 200); // Return 200 so JavaScript success block handles it
        }
    }

    /**
     * Get all scheduled jobs (Laravel Scheduler)
     */
    public function getScheduledJobs()
    {
        try {
            $schedule = app(Schedule::class);
            $jobs = [];

            foreach ($schedule->events() as $event) {
                $jobs[] = [
                    'command' => $event->command ?? $event->description,
                    'expression' => $event->expression,
                    'description' => $event->description ?? 'No description',
                    'next_run' => $event->nextRunDate()->format('Y-m-d H:i:s'),
                ];
            }

            return response()->json([
                'success' => true,
                'total_jobs' => count($jobs),
                'jobs' => $jobs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener scheduled jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run scheduler manually
     */
    public function runScheduler(Request $request)
    {
        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Scheduler ejecutado correctamente',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar scheduler: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run arbitrary Artisan command
     */
    public function runCommand(Request $request)
    {
        $command = $request->input('command');

        if (empty($command)) {
            return response()->json([
                'success' => false,
                'message' => 'El comando es requerido',
            ], 400);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Comando ejecutado correctamente',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar comando: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available Artisan commands
     */
    public function listCommands()
    {
        try {
            Artisan::call('list', ['--format' => 'json']);
            $output = Artisan::output();
            $data = json_decode($output, true);
            $commands = [];

            if (isset($data['commands'])) {
                foreach ($data['commands'] as $command) {
                    $commands[] = [
                        'name' => $command['name'],
                        'description' => $command['description'] ?? '',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_commands' => count($commands),
                'commands' => $commands,
            ]);
        } catch (\Exception $e) {
            // Fallback with common commands
            $commands = [
                ['name' => 'cache:clear', 'description' => 'Limpiar la caché de la aplicación'],
                ['name' => 'config:cache', 'description' => 'Crear un archivo de caché de configuración'],
                ['name' => 'route:cache', 'description' => 'Crear un archivo de caché de rutas'],
                ['name' => 'view:cache', 'description' => 'Compilar todas las plantillas Blade'],
                ['name' => 'queue:work', 'description' => 'Iniciar el procesamiento de jobs en la cola'],
                ['name' => 'schedule:list', 'description' => 'Listar tareas programadas'],
                ['name' => 'schedule:run', 'description' => 'Ejecutar las tareas programadas'],
            ];

            return response()->json([
                'success' => true,
                'total_commands' => count($commands),
                'commands' => $commands,
            ]);
        }
    }

    /**
     * Restart Supervisor service
     */
    public function restartSupervisor(Request $request)
    {
        try {
            $result = SupervisorService::restartSupervisor();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Supervisor reiniciado correctamente' : 'Error al reiniciar supervisor',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Supervisor reiniciado correctamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al reiniciar supervisor: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al reiniciar supervisor: '.$e->getMessage());
        }
    }

    /**
     * List all backups
     */
    public function listBackups(Request $request)
    {
        try {
            $environment = $request->input('environment');
            $backups = SupervisorService::getBackups($environment);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'total_backups' => $backups->count(),
                    'backups' => $backups->map(function ($backup) {
                        return [
                            'id' => $backup->id,
                            'name' => $backup->name,
                            'description' => $backup->description,
                            'environment' => $backup->environment,
                            'backup_size' => $backup->formatted_size,
                            'backed_up_at' => $backup->backed_up_at?->format('Y-m-d H:i:s'),
                            'relative_time' => $backup->relative_time,
                            'restored_at' => $backup->restored_at?->format('Y-m-d H:i:s'),
                            'is_auto' => $backup->is_auto,
                        ];
                    })->all(),
                ]);
            }

            return redirect()->route('settings.system.supervisor.index');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener backups: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a backup
     */
    public function createBackup(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'environment' => 'required|in:dev,prod,staging',
        ]);

        try {
            $result = SupervisorService::createBackup(
                $validated['name'],
                $validated['description'] ?? null,
                $validated['environment']
            );

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Backup creado exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear backup: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al crear backup: '.$e->getMessage());
        }
    }

    /**
     * Restore a backup
     */
    public function restoreBackup(Request $request, $backupId)
    {
        try {
            $result = SupervisorService::restoreBackup($backupId, auth()->id());

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Configuración restaurada exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al restaurar backup: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al restaurar backup: '.$e->getMessage());
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(Request $request, $backupId)
    {
        try {
            $result = SupervisorService::deleteBackup($backupId);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Backup eliminado exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar backup: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al eliminar backup: '.$e->getMessage());
        }
    }

    /**
     * Download a backup
     */
    public function downloadBackup($backupId)
    {
        try {
            $backup = Setting\Backup\SupervisorBackup::findOrFail($backupId);

            $filename = 'supervisor-backup-'.$backup->environment.'-'.$backup->backed_up_at->format('Y-m-d-His').'.json';

            return response()->json($backup->toArray())
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar backup: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * List configuration files
     */
    public function listConfigFiles()
    {
        try {
            $configFiles = SupervisorService::getBackups(null, 1);

            if ($configFiles->count() > 0) {
                $files = array_keys($configFiles[0]->config_files ?? []);
            } else {
                $files = [];
            }

            return response()->json([
                'success' => true,
                'files' => $files,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivos de configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific configuration file
     */
    public function getConfigFile(Request $request)
    {
        $filePath = $request->input('file');

        if (! $filePath) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta del archivo requerida',
            ], 400);
        }

        try {
            $result = SupervisorService::getConfigFile($filePath);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener archivo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a configuration file
     */
    public function updateConfigFile(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|string',
            'content' => 'required|string',
        ]);

        try {
            $result = SupervisorService::updateConfigFile($validated['file'], $validated['content']);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Archivo actualizado exitosamente');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar archivo: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al actualizar archivo: '.$e->getMessage());
        }
    }
}
