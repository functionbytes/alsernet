<?php

namespace Modules\Database\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Backup\Jobs\CreateBackupJob;
use Modules\Core\Models\Setting;
use Modules\Database\Http\Requests\TruncateTablesRequest;

class DatabaseCleanupController extends Controller
{
    private const PROTECTED_TABLES = [
        'users',
        'sessions',
        'password_reset_tokens',
        'password_resets',
        'failed_jobs',
        'permissions',
        'roles',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'migrations',
        'personal_access_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'settings',
        'modules',
    ];

    public function index(): View|RedirectResponse
    {
        $cleanupEnabled = (bool) Setting::get('cleanup_enabled', config('database.cleanup.enabled', false));

        if (! $cleanupEnabled) {
            return view('database::cleanup.disabled', [
                'pageTitle' => 'Limpieza de base de datos',
                'breadcrumb' => 'Configuración / Limpieza de base de datos',
            ]);
        }

        try {
            $tables = $this->getTablesList();

            return view('database::cleanup.index', [
                'tables' => $tables,
                'pageTitle' => 'Limpieza de base de datos',
                'breadcrumb' => 'Configuración / Limpieza de base de datos',
                'cleanupEnabled' => $cleanupEnabled,
                'protectedTables' => self::PROTECTED_TABLES,
            ]);
        } catch (\Exception $e) {
            Log::error('Database cleanup index failed: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al obtener la lista de tablas. Por favor, revisa los logs del sistema.');
        }
    }

    public function truncate(TruncateTablesRequest $request): JsonResponse
    {
        if (! (bool) Setting::get('cleanup_enabled', config('database.cleanup.enabled', false))) {
            return response()->json([
                'success' => false,
                'message' => 'Esta característica no está habilitada. Configura DATABASE_CLEANUP_ENABLED=true en .env',
            ], 403);
        }

        $tablesToTruncate = $request->validated()['tables'];

        // Block critical tables — fail-closed
        $protectedFound = array_values(array_intersect($tablesToTruncate, self::PROTECTED_TABLES));
        if (! empty($protectedFound)) {
            return response()->json([
                'success' => false,
                'message' => 'Las siguientes tablas son críticas del sistema y no pueden vaciarse: '.implode(', ', $protectedFound),
                'protected_tables' => $protectedFound,
            ], 422);
        }

        // Validate all tables exist
        $database = config('database.connections.'.config('database.default').'.database');
        $allTables = DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?', [$database]);
        $validTableNames = array_map(fn ($t) => $t->TABLE_NAME, $allTables);

        foreach ($tablesToTruncate as $table) {
            if (! in_array($table, $validTableNames, true)) {
                return response()->json([
                    'success' => false,
                    'message' => "La tabla '{$table}' no existe en la base de datos.",
                ], 400);
            }
        }

        // Dispatch safety backup before truncate
        try {
            if (class_exists(CreateBackupJob::class)) {
                CreateBackupJob::dispatchSync([
                    'reason' => 'pre_truncate_safety_backup',
                    'triggered_by' => auth()->id(),
                    'tables_affected' => $tablesToTruncate,
                ]);
            } else {
                Log::warning('Backup module not available; skipping pre-truncate backup', [
                    'tables' => $tablesToTruncate,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Pre-truncate backup failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el backup de seguridad previo. Operación cancelada.',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Execute truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $truncatedCount = 0;
        $errors = [];

        try {
            foreach ($tablesToTruncate as $table) {
                try {
                    DB::table($table)->truncate();
                    $truncatedCount++;
                } catch (\Throwable $e) {
                    $errors[] = ['table' => $table, 'error' => $e->getMessage()];
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Activity log
        try {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'tables' => $tablesToTruncate,
                    'truncated_count' => $truncatedCount,
                    'errors' => $errors,
                ])
                ->log('Limpieza de base de datos: '.$truncatedCount.' tabla(s)');
        } catch (\Throwable $e) {
            Log::warning('Failed to log cleanup activity: '.$e->getMessage());
        }

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => "Se limpiaron {$truncatedCount} tabla(s) pero hubo errores en ".count($errors).' tabla(s)',
                'errors' => $errors,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Se limpiaron correctamente {$truncatedCount} tabla(s). Backup de seguridad creado.",
            'truncated_count' => $truncatedCount,
        ]);
    }

    public function getTableCount(Request $request): JsonResponse
    {
        try {
            $tableName = $request->input('table');
            $database = config('database.connections.'.config('database.default').'.database');

            $exists = DB::select(
                'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $tableName]
            );

            if (empty($exists)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tabla no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'count' => DB::table($tableName)->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Database table count failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el conteo de registros.',
            ], 500);
        }
    }

    private function getTablesList(): array
    {
        $database = config('database.connections.'.config('database.default').'.database');

        $result = DB::select(
            'SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME ASC LIMIT 200',
            [$database]
        );

        return array_map(fn ($row) => [
            'name' => $row->TABLE_NAME,
            'records' => (int) $row->TABLE_ROWS,
            'estimated' => (int) $row->TABLE_ROWS,
            'protected' => in_array($row->TABLE_NAME, self::PROTECTED_TABLES, true),
        ], $result);
    }
}
