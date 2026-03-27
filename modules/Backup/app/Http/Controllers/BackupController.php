<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Backup\Events\BackupDeleted;
use Modules\Backup\Events\BackupDownloaded;
use Modules\Backup\Jobs\CreateBackupJob;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Backup.backups.index')->only('index', 'create', 'store', 'getStatus');
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
                ->with('error', 'Error al crear el backup: '.$e->getMessage());
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
            return redirect()->route('settings.backups.index')
                ->with('error', 'Error al descargar el backup: '.$e->getMessage());
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

            if ($isJsonRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el backup: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('settings.backups.index')
                ->with('error', 'Error al eliminar el backup: '.$e->getMessage());
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

        return $backups;
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
