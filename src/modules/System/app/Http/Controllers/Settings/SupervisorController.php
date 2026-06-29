<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Backup\Models\SupervisorBackup;
use Modules\System\Services\SupervisorService;

class SupervisorController extends Controller
{
    /**
     * Artisan commands that may be executed via the run-command endpoint.
     * All other commands are rejected with 422.
     */
    private const ALLOWED_COMMANDS = [
        'cache:clear',
        'config:cache',
        'config:clear',
        'route:cache',
        'route:clear',
        'view:cache',
        'view:clear',
        'optimize',
        'optimize:clear',
        'queue:restart',
        'schedule:run',
        'schedule:list',
        'horizon:terminate',
        'horizon:pause',
        'horizon:continue',
    ];

    public function __construct(
        private readonly SupervisorService $supervisor
    ) {}

    /**
     * Display the Supervisor dashboard with all processes.
     */
    public function index(): View
    {
        try {
            $supervisorStatus = $this->supervisor->getStatus();
            $processes = $supervisorStatus['processes'] ?? [];
            $alsarnetProcesses = $this->supervisor->getAlsernetProcesses();

            $pageTitle = 'Panel de Control - Supervisor';
            $breadcrumb = 'Configuración / Sistema / Supervisor';

            return view('system::supervisor.index', compact(
                'processes',
                'alsarnetProcesses',
                'pageTitle',
                'breadcrumb'
            ));
        } catch (\Exception $e) {
            Log::error('Supervisor connection failed', ['error' => $e->getMessage()]);

            return view('system::supervisor.index', [
                'error' => 'Error al conectar con Supervisor.',
                'processes' => [],
                'alsarnetProcesses' => [],
                'pageTitle' => 'Panel de Control - Supervisor',
                'breadcrumb' => 'Configuración / Sistema / Supervisor',
            ]);
        }
    }

    /**
     * Show detailed view for a specific process.
     */
    public function show(Request $request, string $processName): View|RedirectResponse
    {
        try {
            $this->supervisor->validateProcessName($processName);
            $processStatus = $this->supervisor->getProcessStatus($processName);

            if (isset($processStatus['error'])) {
                return redirect()->route('settings.system.supervisor.index')
                    ->with('error', 'Proceso no encontrado: '.$processStatus['error']);
            }

            $logs = $this->supervisor->getProcessLogs($processName, 100);
            $pid = $this->supervisor->getPid($processName);
            $uptime = $this->supervisor->getUptime($processName);

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
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Nombre de proceso no válido.');
        } catch (\Exception $e) {
            Log::error('Supervisor process details failed', ['error' => $e->getMessage(), 'process' => $processName]);

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al cargar detalles del proceso.');
        }
    }

    /**
     * Start a process via AJAX.
     */
    public function start(Request $request, string $processName): JsonResponse|RedirectResponse
    {
        try {
            $request->validate(['processName' => 'regex:/^[a-zA-Z0-9_\-:.]+$/']);
            $this->supervisor->validateProcessName($processName);
            $result = $this->supervisor->startProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso iniciado correctamente' : 'Error al iniciar el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso iniciado correctamente');
        } catch (\InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Nombre de proceso no válido.'], 422)
                : redirect()->route('settings.system.supervisor.index')->with('error', 'Nombre de proceso no válido.');
        } catch (\Exception $e) {
            Log::error('Supervisor process start failed', ['error' => $e->getMessage(), 'process' => $processName]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al iniciar el proceso.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al iniciar el proceso.');
        }
    }

    /**
     * Stop a process via AJAX.
     */
    public function stop(Request $request, string $processName): JsonResponse|RedirectResponse
    {
        try {
            $request->validate(['processName' => 'regex:/^[a-zA-Z0-9_\-:.]+$/']);
            $this->supervisor->validateProcessName($processName);
            $result = $this->supervisor->stopProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso detenido correctamente' : 'Error al detener el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso detenido correctamente');
        } catch (\InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Nombre de proceso no válido.'], 422)
                : redirect()->route('settings.system.supervisor.index')->with('error', 'Nombre de proceso no válido.');
        } catch (\Exception $e) {
            Log::error('Supervisor process stop failed', ['error' => $e->getMessage(), 'process' => $processName]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al detener el proceso.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al detener el proceso.');
        }
    }

    /**
     * Restart a process via AJAX.
     */
    public function restart(Request $request, string $processName): JsonResponse|RedirectResponse
    {
        try {
            $request->validate(['processName' => 'regex:/^[a-zA-Z0-9_\-:.]+$/']);
            $this->supervisor->validateProcessName($processName);
            $result = $this->supervisor->restartProcess($processName);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Proceso reiniciado correctamente' : 'Error al reiniciar el proceso',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Proceso reiniciado correctamente');
        } catch (\InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Nombre de proceso no válido.'], 422)
                : redirect()->route('settings.system.supervisor.index')->with('error', 'Nombre de proceso no válido.');
        } catch (\Exception $e) {
            Log::error('Supervisor process restart failed', ['error' => $e->getMessage(), 'process' => $processName]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al reiniciar el proceso.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al reiniciar el proceso.');
        }
    }

    /**
     * Reload Supervisor configuration via AJAX.
     */
    public function reload(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->supervisor->reload();

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
            Log::error('Supervisor reload failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al recargar la configuración.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al recargar la configuración.');
        }
    }

    /**
     * Get process logs via AJAX.
     */
    public function getLogs(Request $request, string $processName): JsonResponse
    {
        try {
            $this->supervisor->validateProcessName($processName);
            $lines = min((int) $request->input('lines', 50), 500);
            $logs = $this->supervisor->getProcessLogs($processName, $lines);

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
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => 'Nombre de proceso no válido.'], 422);
        } catch (\Exception $e) {
            Log::error('Supervisor get logs failed', ['error' => $e->getMessage(), 'process' => $processName]);

            return response()->json(['success' => false, 'message' => 'Error al obtener logs.'], 500);
        }
    }

    /**
     * Get status of all processes via AJAX (for real-time updates).
     */
    public function getStatusAjax(Request $request): JsonResponse
    {
        try {
            $supervisorStatus = $this->supervisor->getStatus();

            if (isset($supervisorStatus['error'])) {
                Log::warning('Supervisor Service Error: '.$supervisorStatus['error']);

                return response()->json([
                    'success' => false,
                    'message' => $supervisorStatus['error'],
                ], 503);
            }

            return response()->json([
                'success' => true,
                'processes' => $supervisorStatus['processes'] ?? [],
                'alsarnetProcesses' => $this->supervisor->getAlsernetProcesses(),
            ]);
        } catch (\Exception $e) {
            Log::error('Supervisor get status failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener estado.'], 503);
        }
    }

    /**
     * Get all scheduled jobs (Laravel Scheduler).
     */
    public function getScheduledJobs(): JsonResponse
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
            Log::error('Supervisor get scheduled jobs failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener scheduled jobs.'], 500);
        }
    }

    /**
     * Run scheduler manually.
     */
    public function runScheduler(Request $request): JsonResponse
    {
        try {
            Artisan::call('schedule:run');

            return response()->json([
                'success' => true,
                'message' => 'Scheduler ejecutado correctamente',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            Log::error('Supervisor run scheduler failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al ejecutar scheduler.'], 500);
        }
    }

    /**
     * Run an Artisan command from the explicit allowlist.
     * Any command not in ALLOWED_COMMANDS is rejected with 422.
     */
    public function runCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_COMMANDS)],
        ]);

        try {
            Artisan::call($validated['command']);

            return response()->json([
                'success' => true,
                'message' => 'Comando ejecutado correctamente',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            Log::error('Supervisor run command failed', ['error' => $e->getMessage(), 'command' => $validated['command']]);

            return response()->json(['success' => false, 'message' => 'Error al ejecutar comando.'], 503);
        }
    }

    /**
     * List available Artisan commands.
     */
    public function listCommands(): JsonResponse
    {
        try {
            Artisan::call('list', ['--format' => 'json']);
            $data = json_decode(Artisan::output(), true);
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
     * Restart Supervisor service.
     */
    public function restartSupervisor(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->supervisor->restartSupervisor();

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
            Log::error('Supervisor restart failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al reiniciar supervisor.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al reiniciar supervisor.');
        }
    }

    /**
     * Restart all Supervisor processes.
     */
    public function restartAll(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->supervisor->restartAllProcesses();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $result['success'] ?? false,
                    'message' => $result['success'] ?? false ? 'Todos los procesos reiniciados correctamente' : 'Error al reiniciar los procesos',
                    'output' => $result['output'] ?? '',
                ]);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Todos los procesos reiniciados correctamente');
        } catch (\Exception $e) {
            Log::error('Supervisor restart all failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al reiniciar los procesos.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al reiniciar los procesos.');
        }
    }

    /**
     * List all backups.
     */
    public function listBackups(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $backups = $this->supervisor->getBackups($request->input('environment'));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'total_backups' => $backups->count(),
                    'backups' => $backups->map(fn ($backup) => [
                        'id' => $backup->id,
                        'name' => $backup->name,
                        'description' => $backup->description,
                        'environment' => $backup->environment,
                        'backup_size' => $backup->formatted_size,
                        'backed_up_at' => $backup->backed_up_at?->format('Y-m-d H:i:s'),
                        'relative_time' => $backup->relative_time,
                        'restored_at' => $backup->restored_at?->format('Y-m-d H:i:s'),
                        'is_auto' => $backup->is_auto,
                    ])->all(),
                ]);
            }

            return redirect()->route('settings.system.supervisor.index');
        } catch (\Exception $e) {
            Log::error('Supervisor list backups failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener backups.'], 500);
        }
    }

    /**
     * Create a backup.
     */
    public function createBackup(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'environment' => 'required|in:dev,prod,staging',
        ]);

        try {
            $result = $this->supervisor->createBackup(
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
            Log::error('Supervisor create backup failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al crear backup.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al crear backup.');
        }
    }

    /**
     * Restore a backup.
     */
    public function restoreBackup(Request $request, int|string $backupId): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->supervisor->restoreBackup($backupId, auth()->id());

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Configuración restaurada exitosamente');
        } catch (\Exception $e) {
            Log::error('Supervisor restore backup failed', ['error' => $e->getMessage(), 'backup_id' => $backupId]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al restaurar backup.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al restaurar backup.');
        }
    }

    /**
     * Delete a backup.
     */
    public function deleteBackup(Request $request, int|string $backupId): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->supervisor->deleteBackup($backupId);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Backup eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Supervisor delete backup failed', ['error' => $e->getMessage(), 'backup_id' => $backupId]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al eliminar backup.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al eliminar backup.');
        }
    }

    /**
     * Download a backup.
     */
    public function downloadBackup(int|string $backupId): JsonResponse
    {
        try {
            $backup = SupervisorBackup::findOrFail($backupId);
            $filename = 'supervisor-backup-'.$backup->environment.'-'.$backup->backed_up_at->format('Y-m-d-His').'.json';

            return response()->json($backup->toArray())
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            Log::error('Supervisor download backup failed', ['error' => $e->getMessage(), 'backup_id' => $backupId]);

            return response()->json(['success' => false, 'message' => 'Error al descargar backup.'], 500);
        }
    }

    /**
     * List configuration files from the supervisor conf.d directory.
     */
    public function listConfigFiles(): JsonResponse
    {
        try {
            $files = $this->supervisor->listConfDirFiles();

            return response()->json(['success' => true, 'files' => $files]);
        } catch (\Exception $e) {
            Log::error('Supervisor list config files failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al obtener archivos de configuración.'], 500);
        }
    }

    /**
     * Get a specific configuration file.
     */
    public function getConfigFile(Request $request): JsonResponse
    {
        $filePath = $request->input('file');

        if (! $filePath) {
            return response()->json(['success' => false, 'message' => 'Ruta del archivo requerida'], 400);
        }

        try {
            return response()->json($this->supervisor->getConfigFile($filePath));
        } catch (\Exception $e) {
            Log::error('Supervisor get config file failed', ['error' => $e->getMessage(), 'file' => $filePath]);

            return response()->json(['success' => false, 'message' => 'Error al obtener archivo.'], 500);
        }
    }

    /**
     * Update a configuration file.
     */
    public function updateConfigFile(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|string',
            'content' => 'required|string',
        ]);

        try {
            $result = $this->supervisor->updateConfigFile($validated['file'], $validated['content']);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('success', 'Archivo actualizado exitosamente');
        } catch (\Exception $e) {
            Log::error('Supervisor update config file failed', ['error' => $e->getMessage(), 'file' => $validated['file']]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar archivo.'], 500);
            }

            return redirect()->route('settings.system.supervisor.index')
                ->with('error', 'Error al actualizar archivo.');
        }
    }
}
