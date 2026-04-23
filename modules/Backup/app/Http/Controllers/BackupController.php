<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Backup\Events\BackupDeleted;
use Modules\Backup\Events\BackupDownloaded;
use Modules\Backup\Jobs\CreateBackupJob;
use Modules\Backup\Models\BackupSchedule;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BackupController extends Controller
{
    private const SUPERVISOR_PROGRAM = 'backups-worker';

    /**
     * Detect the supervisorctl binary path for the current OS.
     * sudo strips PATH so we need the absolute path.
     */
    private function supervisorctlBin(): string
    {
        $candidates = PHP_OS_FAMILY === 'Darwin'
            ? ['/opt/homebrew/bin/supervisorctl', '/usr/local/bin/supervisorctl', '/usr/bin/supervisorctl']
            : ['/usr/bin/supervisorctl', '/usr/local/bin/supervisorctl'];

        foreach ($candidates as $bin) {
            if (is_executable($bin)) {
                return $bin;
            }
        }

        return $candidates[0];
    }

    /**
     * Detect the supervisor conf.d directory for the current OS,
     * returning the first existing one or the OS-default if none exist yet.
     */
    private function supervisorConfDir(): string
    {
        $candidates = PHP_OS_FAMILY === 'Darwin'
            ? ['/opt/homebrew/etc/supervisor.d', '/usr/local/etc/supervisor.d', '/etc/supervisor/conf.d']
            : ['/etc/supervisor/conf.d', '/etc/supervisord.d', '/usr/local/etc/supervisor.d'];

        foreach ($candidates as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return $candidates[0]; // fallback: first candidate for this OS
    }

    /**
     * Find the PHP CLI binary suitable for running artisan commands.
     * PHP_BINARY in a web/FPM context points to php-fpm, not the CLI php.
     */
    private function phpBinaryCli(): string
    {
        $binary = PHP_BINARY;
        $base = basename($binary);
        $dir = dirname($binary);

        // FPM binary is not a CLI runner (patterns: php-fpm, php84-fpm, php8.4-fpm)
        if (str_contains($base, 'fpm')) {
            // php84-fpm → php84,  php-fpm → php
            $cliBase = preg_replace('/-?fpm$/', '', $base);
            foreach ([$cliBase, 'php'] as $candidate) {
                $path = $dir.'/'.$candidate;
                if ($candidate !== '' && is_executable($path)) {
                    return $path;
                }
            }
            // Try sibling bin/ directory (e.g. sbin/php-fpm → bin/php)
            $siblingBin = realpath($dir.'/../bin/php');
            if ($siblingBin && is_executable($siblingBin)) {
                return $siblingBin;
            }
        }

        // PATH-based lookup (works well in Herd/Homebrew environments)
        $which = trim((string) shell_exec('command -v php 2>/dev/null'));
        if ($which !== '' && is_executable($which)) {
            return $which;
        }

        foreach (['/opt/homebrew/bin/php', '/usr/local/bin/php', '/usr/bin/php'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return $binary;
    }

    private function supervisorConfigPath(): string
    {
        // Homebrew supervisord.conf includes *.ini; Linux uses *.conf
        $ext = PHP_OS_FAMILY === 'Darwin' ? 'ini' : 'conf';

        return $this->supervisorConfDir()."/backups-worker.{$ext}";
    }

    public function __construct()
    {
        $this->middleware('can:Backup.backups.index')->only('index', 'create', 'store', 'getStatus', 'setup', 'guide', 'prerequisites', 'schedulerConfigure', 'supervisorStatus', 'supervisorInstall', 'supervisorApply', 'supervisorRestart');
        $this->middleware('can:Backup.backups.download')->only('download');
        $this->middleware('can:Backup.backups.delete')->only('destroy');
    }

    /**
     * Display the backup management page
     */
    public function index()
    {
        $backups = $this->getBackupFiles();
        $pageTitle = 'Administrador de Backups';
        $breadcrumb = 'Configuración / Backups';

        return view('backup::backups.index', compact('backups', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Show backup creation form
     */
    public function create()
    {
        $pageTitle = 'Crear Nuevo Backup';
        $breadcrumb = 'Configuración / Backups / Crear';

        return view('backup::backups.create', compact('pageTitle', 'breadcrumb'));
    }

    /**
     * Create a new backup with selected options
     */
    public function store(Request $request)
    {
        try {
            $backupTypes = $request->input('backup_types', []);

            if (empty($backupTypes)) {
                return redirect()->back()
                    ->with('error', 'Debes seleccionar al menos una opción de backup');
            }

            // Build include paths based on selection - START FROM EMPTY
            $includePaths = [];
            $databases = [];

            $typeMap = [
                'app_code' => base_path('app'),
                'config' => base_path('config'),
                'routes' => base_path('routes'),
                'resources' => base_path('resources'),
                'migrations' => base_path('database/migrations'),
                'storage' => base_path('storage/app'),
            ];

            // Only add paths that were explicitly selected
            foreach ($backupTypes as $type) {
                if ($type === 'database') {
                    continue; // Database is handled separately
                }
                if (isset($typeMap[$type])) {
                    $includePaths[] = $typeMap[$type];
                }
            }

            // Create a clean backup config with ONLY what was selected
            $backupConfig = [
                'files' => [
                    'include' => $includePaths,
                    'exclude' => config('backup.backup.source.files.exclude', []),
                    'follow_links' => false,
                    'relative_path' => null,
                ],
                'databases' => [],
            ];

            if (in_array('database', $backupTypes)) {
                $backupConfig['databases'] = [config('database.default', 'mysql')];
            }

            // Dispatch the backup job to queue (runs asynchronously)
            // Credentials are read from config at job runtime — never serialized here
            CreateBackupJob::dispatch($backupTypes, $backupConfig);

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['types' => $backupTypes])
                ->log('Backup initiated');

            // Format types for display
            $displayTypes = array_map(function ($type) {
                $labels = [
                    'app_code' => 'Código',
                    'config' => 'Configuración',
                    'routes' => 'Rutas',
                    'resources' => 'Recursos',
                    'migrations' => 'Migraciones',
                    'storage' => 'Almacenamiento',
                    'database' => 'Base de Datos',
                ];

                return $labels[$type] ?? $type;
            }, $backupTypes);

            return redirect()->route('settings.backups.index')
                ->with('success', 'Backup en progreso... Se está creando el backup incluyendo: '.implode(', ', $displayTypes).'. Esto puede tardar varios minutos.');
        } catch (\Throwable $e) {
            \Log::error('Backup creation failed: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al crear el backup. Por favor, revisa los logs del sistema.');
        }
    }

    /**
     * Download a backup file
     */
    public function download($filename)
    {
        try {
            $filename = basename($filename);

            // Try multiple possible backup locations
            $possiblePaths = [
                'backups/'.$filename,
                config('app.name').'/'.$filename,
            ];

            $backupPath = null;
            foreach ($possiblePaths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    $backupPath = $path;
                    break;
                }
            }

            if (! $backupPath) {
                return redirect()->route('settings.backups.index')
                    ->with('error', 'El archivo de backup no existe');
            }

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['filename' => $filename])
                ->log('Backup downloaded');

            event(new BackupDownloaded($filename, auth()->user()));

            return Storage::disk('local')->download($backupPath, $filename);
        } catch (\Exception $e) {
            \Log::error('Backup download failed: '.$e->getMessage());

            return redirect()->route('settings.backups.index')
                ->with('error', 'Error al descargar el backup. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Delete a backup file
     */
    public function destroy(Request $request, $filename)
    {
        try {
            $filename = basename($filename);

            // Check if this is a JSON request (by Accept header or explicit flag)
            $isJsonRequest = $request->expectsJson() ||
                           $request->header('Accept') === 'application/json' ||
                           $request->header('Content-Type') === 'application/json';

            // Try multiple possible backup locations
            $possiblePaths = [
                'backups/'.$filename,
                config('app.name').'/'.$filename,
            ];

            $backupPath = null;
            foreach ($possiblePaths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    $backupPath = $path;
                    break;
                }
            }

            if (! $backupPath) {
                if ($isJsonRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El archivo de backup no existe',
                    ], 404);
                }

                return redirect()->route('settings.backups.index')
                    ->with('error', 'El archivo de backup no existe');
            }

            Storage::disk('local')->delete($backupPath);

            event(new BackupDeleted($filename, auth()->user()));

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['filename' => $filename])
                ->log('Backup deleted');

            if ($isJsonRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup eliminado exitosamente',
                ]);
            }

            return redirect()->route('settings.backups.index')
                ->with('success', 'Backup eliminado exitosamente');
        } catch (\Exception $e) {
            $isJsonRequest = $request->expectsJson() ||
                           $request->header('Accept') === 'application/json' ||
                           $request->header('Content-Type') === 'application/json';

            \Log::error('Backup deletion failed: '.$e->getMessage());

            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el backup. Por favor, inténtalo de nuevo.',
                ], 500);
            }

            return redirect()->route('settings.backups.index')
                ->with('error', 'Error al eliminar el backup. Por favor, inténtalo de nuevo.');
        }
    }

    /**
     * Get list of backup files
     */
    private function getBackupFiles()
    {
        $backups = [];

        // Try multiple possible backup locations
        $possiblePaths = [
            storage_path('app/backups'),
            storage_path('app/'.config('app.name')),
        ];

        foreach ($possiblePaths as $backupPath) {
            if (! is_dir($backupPath)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($backupPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isFile() && preg_match('/\.zip$|\.tar\.gz$/', $file->getFilename())) {
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => backup_format_bytes($file->getSize()),
                        'size_raw' => $file->getSize(),
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                        'timestamp' => $file->getMTime(),
                    ];
                }
            }
        }

        // Sort by date (newest first)
        usort($backups, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($backups, 0, 500);
    }

    /**
     * Display the backup system guide/documentation page
     */
    public function guide(): View
    {
        $this->authorize('Backup.backups.index');

        $os = PHP_OS_FAMILY;
        $projectPath = base_path();
        $phpBinary = $this->phpBinaryCli();
        $schedulerActive = $this->detectSchedulerActive();
        $supervisorConfigPath = $this->supervisorConfigPath();
        $supervisorctlBin = $this->supervisorctlBin();
        $supervisorInstalled = is_executable($supervisorctlBin);
        $pageTitle = 'Guía de configuración';
        $breadcrumb = 'Configuración / Backups / Guía';

        return view('backup::backups.guide', compact(
            'os', 'projectPath', 'phpBinary', 'schedulerActive',
            'supervisorConfigPath', 'supervisorctlBin', 'supervisorInstalled',
            'pageTitle', 'breadcrumb'
        ));
    }

    /**
     * Display the backup system setup wizard
     */
    public function setup(): View
    {
        $this->authorize('Backup.backups.index');

        $os = PHP_OS_FAMILY;
        $projectPath = base_path();
        $phpBinary = $this->phpBinaryCli();
        $schedulerActive = $this->detectSchedulerActive();
        $supervisorConfigPath = $this->supervisorConfigPath();
        $supervisorctlBin = $this->supervisorctlBin();
        $supervisorInstalled = is_executable($supervisorctlBin);
        $detectedTab = match ($os) {
            'Darwin' => 'mac',
            'Windows' => 'windows',
            default => 'linux',
        };
        $pageTitle = 'Asistente de configuración';
        $breadcrumb = 'Configuración / Backups / Asistente';

        return view('backup::backups.setup', compact(
            'os', 'detectedTab', 'projectPath', 'phpBinary', 'schedulerActive',
            'supervisorConfigPath', 'supervisorctlBin', 'supervisorInstalled',
            'pageTitle', 'breadcrumb'
        ));
    }

    /**
     * Return system prerequisites status for the setup wizard
     */
    public function prerequisites(): JsonResponse
    {
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.1.0', '>=');
        $schedulerActive = $this->detectSchedulerActive();
        $projectPath = base_path();
        $artisanExists = file_exists($projectPath.'/artisan');
        $queueConnection = config('queue.default');

        return response()->json([
            'php_version' => $phpVersion,
            'php_binary' => PHP_BINARY,
            'php_ok' => $phpOk,
            'scheduler_active' => $schedulerActive,
            'project_path' => $projectPath,
            'artisan_exists' => $artisanExists,
            'queue_connection' => $queueConnection,
        ]);
    }

    /**
     * Detect whether the Laravel scheduler has run recently
     */
    /**
     * Add a crontab entry for the Laravel scheduler
     */
    public function schedulerConfigure(): JsonResponse
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return response()->json([
                'success' => false,
                'message' => 'La configuración automática de cron no está disponible en Windows.',
            ], 422);
        }

        $projectPath = base_path();
        $phpBinary = PHP_BINARY;
        $cronLine = "* * * * * cd {$projectPath} && {$phpBinary} artisan schedule:run >> /dev/null 2>&1";

        // Check if already configured
        $existing = (string) shell_exec('crontab -l 2>/dev/null');
        if (str_contains($existing, 'artisan schedule:run')) {
            return response()->json([
                'success' => true,
                'message' => 'El scheduler ya está configurado en el crontab.',
            ]);
        }

        // Append the new line safely
        $newCrontab = trim($existing)."\n".$cronLine."\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'cron_');
        file_put_contents($tmpFile, $newCrontab);

        $output = shell_exec("crontab {$tmpFile} 2>&1");
        @unlink($tmpFile);

        // Verify it was added
        $verify = (string) shell_exec('crontab -l 2>/dev/null');
        if (str_contains($verify, 'artisan schedule:run')) {
            return response()->json([
                'success' => true,
                'message' => 'Scheduler configurado correctamente en el crontab.',
                'cron' => $cronLine,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo escribir en el crontab.',
            'output' => trim((string) $output),
        ], 500);
    }

    private function detectSchedulerActive(): bool
    {
        // 1. Crontab entry for this project
        try {
            $crontab = (string) shell_exec('crontab -l 2>/dev/null');
            if ($crontab !== '' && str_contains($crontab, 'artisan schedule:run')) {
                return true;
            }
        } catch (\Throwable) {
        }

        // 2. Herd scheduler service (Mac)
        if (PHP_OS_FAMILY === 'Darwin') {
            try {
                $herd = (string) shell_exec('launchctl list 2>/dev/null | grep -i "herd.*scheduler\|scheduler.*herd" 2>/dev/null');
                if (trim($herd) !== '') {
                    return true;
                }
            } catch (\Throwable) {
            }
        }

        // 3. A backup schedule ran in the last 2 minutes (scheduler ran recently)
        return BackupSchedule::whereNotNull('last_run_at')
            ->where('last_run_at', '>=', now()->subMinutes(2))
            ->exists();
    }

    /**
     * Get backup status via AJAX
     */
    public function getStatus()
    {
        try {
            $backups = $this->getBackupFiles();
            $totalSize = array_sum(array_column($backups, 'size_raw'));

            return response()->json([
                'success' => true,
                'count' => count($backups),
                'total_size' => backup_format_bytes($totalSize),
                'latest' => ! empty($backups) ? [
                    'name' => $backups[0]['name'],
                    'date' => $backups[0]['date'],
                    'size' => $backups[0]['size'],
                ] : null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Backup status check failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de los backups.',
            ], 500);
        }
    }

    /**
     * Check supervisor worker status via AJAX
     */
    public function supervisorStatus(): JsonResponse
    {
        $configPath = $this->supervisorConfigPath();
        $configExists = file_exists($configPath);
        $supervisorAvailable = false;
        $processRunning = false;
        $processStatus = null;

        try {
            $output = shell_exec($this->supervisorctlBin().' status '.self::SUPERVISOR_PROGRAM.': 2>&1');
            if ($output !== null) {
                $supervisorAvailable = true;
                $processStatus = trim($output);
                $processRunning = str_contains($output, 'RUNNING');
            }
        } catch (\Throwable) {
        }

        return response()->json([
            'config_exists' => $configExists,
            'config_path' => $configPath,
            'supervisor_available' => $supervisorAvailable,
            'process_running' => $processRunning,
            'process_status' => $processStatus,
        ]);
    }

    /**
     * Install Supervisor on the system
     */
    public function supervisorInstall(Request $request): JsonResponse
    {
        $sudoPassword = $request->input('sudo_password');

        if (PHP_OS_FAMILY === 'Darwin') {
            // Find brew — no sudo needed
            $brew = '/opt/homebrew/bin/brew';
            if (! is_executable($brew)) {
                $brew = '/usr/local/bin/brew';
            }

            if (! is_executable($brew)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró Homebrew. Instálalo desde https://brew.sh y luego vuelve aquí.',
                ], 422);
            }

            $result = $this->shellExec("{$brew} install supervisor 2>&1 && {$brew} services start supervisor 2>&1");

            return response()->json([
                'success' => $result['success'],
                'output' => $result['output'],
                'message' => $result['success'] ? 'Supervisor instalado correctamente.' : 'Error durante la instalación.',
                'reload' => $result['success'],
            ]);
        }

        // Linux — requires sudo
        if (empty($sudoPassword)) {
            return response()->json([
                'success' => false,
                'requires_sudo' => true,
                'message' => 'Se necesita contraseña sudo para instalar Supervisor.',
            ], 403);
        }

        // Detect package manager
        if (is_executable('/usr/bin/apt-get')) {
            $result = $this->sudoExec(['/usr/bin/apt-get', 'update', '-qq'], $sudoPassword);
            if ($result['success']) {
                $result = $this->sudoExec(['/usr/bin/apt-get', 'install', '-y', 'supervisor'], $sudoPassword);
            }
            if ($result['success']) {
                $this->sudoExec(['/bin/systemctl', 'enable', 'supervisor'], $sudoPassword);
                $this->sudoExec(['/bin/systemctl', 'start', 'supervisor'], $sudoPassword);
            }
        } elseif (is_executable('/usr/bin/yum')) {
            $result = $this->sudoExec(['/usr/bin/yum', 'install', '-y', 'supervisor'], $sudoPassword);
            if ($result['success']) {
                $this->sudoExec(['/bin/systemctl', 'enable', 'supervisord'], $sudoPassword);
                $this->sudoExec(['/bin/systemctl', 'start', 'supervisord'], $sudoPassword);
            }
        } elseif (is_executable('/usr/bin/dnf')) {
            $result = $this->sudoExec(['/usr/bin/dnf', 'install', '-y', 'supervisor'], $sudoPassword);
            if ($result['success']) {
                $this->sudoExec(['/bin/systemctl', 'enable', 'supervisord'], $sudoPassword);
                $this->sudoExec(['/bin/systemctl', 'start', 'supervisord'], $sudoPassword);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se reconoció el gestor de paquetes. Instala Supervisor manualmente.',
            ], 422);
        }

        $installed = is_executable($this->supervisorctlBin());

        return response()->json([
            'success' => $installed,
            'output' => $result['output'] ?? '',
            'message' => $installed ? 'Supervisor instalado correctamente.' : 'La instalación finalizó pero no se detectó supervisorctl. Revisa el log.',
            'reload' => $installed,
        ]);
    }

    /**
     * Write supervisor config and activate worker
     */
    public function supervisorApply(Request $request): JsonResponse
    {
        $projectPath = base_path();
        $phpBinary = $this->phpBinaryCli();
        $program = self::SUPERVISOR_PROGRAM;
        $sudoPassword = $request->input('sudo_password');

        $configPath = $this->supervisorConfigPath();
        $confDir = dirname($configPath);

        // On macOS the www-data user doesn't exist; run as current process user
        $userLine = PHP_OS_FAMILY !== 'Darwin' ? "\nuser=www-data" : '';

        // Quote binary and path to handle spaces (supervisord uses Python shlex)
        $cmd = "\"{$phpBinary}\" \"{$projectPath}/artisan\" queue:work --sleep=3 --tries=3 --max-time=3600";
        $logFile = "{$projectPath}/storage/logs/worker.log";

        $config = implode("\n", array_map('ltrim', explode("\n", <<<INI
            [program:{$program}]
            process_name=%(program_name)s_%(process_num)02d
            command={$cmd}
            autostart=true
            autorestart=true
            stopasgroup=true
            killasgroup=true{$userLine}
            numprocs=1
            redirect_stderr=true
            stdout_logfile={$logFile}
            stopwaitsecs=3600
            INI)));

        // Try direct write
        $written = @file_put_contents($configPath, $config);

        if ($written === false) {
            if (empty($sudoPassword)) {
                return response()->json([
                    'success' => false,
                    'requires_sudo' => true,
                    'message' => 'El proceso web no tiene permisos. Introduce tu contraseña sudo para continuar.',
                ], 403);
            }

            // Ensure the conf.d directory exists (sudo mkdir -p)
            if (! is_dir($confDir)) {
                $mkdir = $this->sudoExec(['/bin/mkdir', '-p', $confDir], $sudoPassword);
                if (! $mkdir['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo crear el directorio de configuración.',
                        'output' => $mkdir['output'],
                    ], 403);
                }
            }

            // Write via temp file + sudo mv (avoids stdin conflict with sudo -S)
            $tmpFile = tempnam(sys_get_temp_dir(), 'bkp_worker_');
            file_put_contents($tmpFile, $config);

            $mv = $this->sudoExec(['/bin/mv', $tmpFile, $configPath], $sudoPassword);

            if (! $mv['success']) {
                @unlink($tmpFile);

                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña incorrecta o sin permisos de sudo.',
                    'output' => $mv['output'],
                ], 403);
            }
        }

        // Reload supervisor configuration
        $lines = [];
        $ctl = $this->supervisorctlBin();

        foreach (['reread', 'update'] as $sub) {
            $result = empty($sudoPassword)
                ? $this->shellExec("{$ctl} {$sub}")
                : $this->sudoExec([$ctl, $sub], $sudoPassword);
            if (trim($result['output']) !== '') {
                $lines[] = $result['output'];
            }
        }

        // Attempt to start the worker
        $startResult = empty($sudoPassword)
            ? $this->shellExec("{$ctl} start {$program}:*")
            : $this->sudoExec([$ctl, 'start', "{$program}:*"], $sudoPassword);
        $lines[] = $startResult['output'];

        // macOS Homebrew fallback: supervisorctl reread/update silently fails when
        // supervisord is not running as a daemon. Restart via brew so supervisord
        // re-reads conf.d on startup; autostart=true will launch the worker.
        if (! $startResult['success'] && PHP_OS_FAMILY === 'Darwin') {
            $brew = is_executable('/opt/homebrew/bin/brew') ? '/opt/homebrew/bin/brew' : '/usr/local/bin/brew';
            if (is_executable($brew)) {
                $brewResult = $this->shellExec("{$brew} services restart supervisor 2>&1");
                $lines[] = 'brew restart: '.$brewResult['output'];
                sleep(4); // wait for supervisord to fully start and auto-launch workers
            }
        }

        // Verify final state — "already started" also counts as success
        $statusResult = $this->shellExec("{$ctl} status {$program}: 2>&1");
        $isRunning = str_contains($statusResult['output'], 'RUNNING');
        $alreadyUp = str_contains(strtolower($startResult['output']), 'already started');
        $success = $isRunning || $alreadyUp;

        $output = implode("\n", array_filter(array_map('trim', $lines)));

        Log::info('Supervisor worker applied', ['output' => $output, 'running' => $isRunning]);

        return response()->json([
            'success' => $success,
            'output' => trim($output),
            'message' => $success
                ? 'Worker configurado y activo.'
                : 'La configuración fue escrita pero el worker no pudo iniciarse. Revisa la salida.',
        ]);
    }

    /**
     * Restart supervisor worker
     */
    public function supervisorRestart(Request $request): JsonResponse
    {
        $program = self::SUPERVISOR_PROGRAM;
        $sudoPassword = $request->input('sudo_password');

        $result = empty($sudoPassword)
            ? $this->shellExec($this->supervisorctlBin()." restart {$program}:*")
            : $this->sudoExec([$this->supervisorctlBin(), 'restart', "{$program}:*"], $sudoPassword);

        if (! $result['success'] && str_contains(strtolower($result['output']), 'permission') && empty($sudoPassword)) {
            return response()->json([
                'success' => false,
                'requires_sudo' => true,
                'message' => 'Se requieren permisos de sudo para reiniciar el worker.',
            ], 403);
        }

        return response()->json([
            'success' => $result['success'],
            'output' => $result['output'],
            'message' => $result['success'] ? 'Worker reiniciado correctamente.' : 'Error al reiniciar el worker.',
        ]);
    }

    /**
     * Run a shell command and return success + output
     *
     * @return array{success: bool, output: string}
     */
    private function shellExec(string $command): array
    {
        $output = shell_exec("{$command} 2>&1");
        $success = $output !== null && ! str_contains(strtolower((string) $output), 'error');

        return ['success' => $success, 'output' => trim((string) $output)];
    }

    /**
     * Run a command with sudo, piping the password via stdin
     *
     * @param  string[]  $command
     * @return array{success: bool, output: string}
     */
    private function sudoExec(array $command, string $password): array
    {
        $process = proc_open(
            array_merge(['sudo', '-S'], $command),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (! is_resource($process)) {
            return ['success' => false, 'output' => 'No se pudo iniciar el proceso.'];
        }

        fwrite($pipes[0], $password."\n");
        fclose($pipes[0]);

        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);

        return [
            'success' => $code === 0,
            'output' => trim(implode("\n", array_filter([$out, $err]))),
        ];
    }
}
