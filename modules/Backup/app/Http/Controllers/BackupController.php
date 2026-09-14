<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $this->middleware('can:Backup.backups.index')->only('index', 'create', 'store', 'getStatus', 'setup', 'guide', 'prerequisites', 'schedulerConfigureInstructions', 'supervisorStatus', 'supervisorInstallInstructions', 'supervisorApplyInstructions', 'supervisorRestartInstructions');
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
     * Return copy-paste crontab instructions for the Laravel scheduler.
     * No execution — the admin runs this manually in their terminal.
     */
    public function schedulerConfigureInstructions(): JsonResponse
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return response()->json([
                'success' => false,
                'message' => 'La configuración de cron no está disponible en Windows. Usa el Programador de tareas.',
            ], 422);
        }

        $phpBinary = $this->phpBinaryCli();
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/scheduler.log');
        $cronLine = "* * * * * {$phpBinary} {$artisan} schedule:run >> {$logFile} 2>&1";

        return response()->json([
            'success' => true,
            'instructions' => 'Ejecuta `crontab -e` en tu terminal y agrega esta línea al final:',
            'cron_line' => $cronLine,
            'verify_command' => 'crontab -l | grep schedule:run',
        ]);
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
     * Return copy-paste installation instructions for Supervisor.
     * No execution — the admin runs these commands manually in their terminal.
     */
    public function supervisorInstallInstructions(): JsonResponse
    {
        $packageManager = $this->detectPackageManager();

        $commands = match ($packageManager) {
            'brew' => [
                'brew install supervisor',
                'brew services start supervisor',
            ],
            'apt' => [
                'sudo apt-get update',
                'sudo apt-get install -y supervisor',
                'sudo systemctl enable supervisor',
                'sudo systemctl start supervisor',
            ],
            'yum' => [
                'sudo yum install -y supervisor',
                'sudo systemctl enable supervisord',
                'sudo systemctl start supervisord',
            ],
            default => [
                '# Sistema operativo no detectado automáticamente.',
                '# Consulta: http://supervisord.org/installing.html',
            ],
        };

        return response()->json([
            'success' => true,
            'os' => PHP_OS_FAMILY,
            'package_manager' => $packageManager,
            'instructions' => 'Ejecuta estos comandos en tu terminal con privilegios de administrador:',
            'commands' => $commands,
            'docs_url' => 'http://supervisord.org/installing.html',
        ]);
    }

    private function detectPackageManager(): string
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            return (is_executable('/opt/homebrew/bin/brew') || is_executable('/usr/local/bin/brew'))
                ? 'brew'
                : 'manual';
        }

        if (is_executable('/usr/bin/apt-get')) {
            return 'apt';
        }

        if (is_executable('/usr/bin/yum') || is_executable('/usr/bin/dnf')) {
            return 'yum';
        }

        return 'manual';
    }

    /**
     * Generate supervisor config file in storage and return copy-paste instructions.
     * No execution — the admin copies the file and runs supervisorctl manually.
     */
    public function supervisorApplyInstructions(): JsonResponse
    {
        $projectPath = base_path();
        $phpBinary = $this->phpBinaryCli();
        $program = self::SUPERVISOR_PROGRAM;
        $userLine = PHP_OS_FAMILY !== 'Darwin' ? "\nuser=www-data" : '';
        $cmd = "\"{$phpBinary}\" \"{$projectPath}/artisan\" queue:work --sleep=3 --tries=3 --max-time=3600";
        $logFile = "{$projectPath}/storage/logs/worker.log";

        $configContent = implode("\n", array_map('ltrim', explode("\n", <<<INI
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

        $tempPath = storage_path("app/{$program}.conf");
        file_put_contents($tempPath, $configContent);

        $confDir = $this->supervisorConfDir();
        $ext = PHP_OS_FAMILY === 'Darwin' ? 'ini' : 'conf';
        $targetFile = "{$confDir}/{$program}.{$ext}";

        return response()->json([
            'success' => true,
            'config_path' => $tempPath,
            'config_content' => $configContent,
            'instructions' => 'Configuración generada. Aplica con estos comandos en tu terminal:',
            'commands' => [
                "sudo cp {$tempPath} {$targetFile}",
                'sudo supervisorctl reread',
                'sudo supervisorctl update',
                "sudo supervisorctl start {$program}:*",
            ],
            'target_file' => $targetFile,
        ]);
    }

    /**
     * Return copy-paste commands to restart the supervisor worker.
     * No execution — the admin runs these commands manually in their terminal.
     */
    public function supervisorRestartInstructions(): JsonResponse
    {
        $program = self::SUPERVISOR_PROGRAM;

        return response()->json([
            'success' => true,
            'instructions' => 'Para reiniciar el worker, ejecuta en tu terminal:',
            'commands' => [
                "sudo supervisorctl restart {$program}:*",
            ],
            'verify_command' => "sudo supervisorctl status {$program}:*",
        ]);
    }
}
