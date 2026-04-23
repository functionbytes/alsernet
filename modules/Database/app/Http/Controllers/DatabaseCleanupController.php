<?php

namespace Modules\Database\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class DatabaseCleanupController extends Controller
{
    /**
     * Display database cleanup form with table list
     */
    public function index(): View|RedirectResponse
    {
        // Check if cleanup is enabled
        $cleanupEnabled = (bool) Setting::get('cleanup_enabled', config('database.cleanup.enabled', false));

        if (! $cleanupEnabled) {
            return view('database::cleanup.disabled', [
                'pageTitle' => 'Limpieza de base de datos',
                'breadcrumb' => 'Configuración / Limpieza de base de datos',
            ]);
        }

        try {
            $tables = $this->getTablesList();
            $pageTitle = 'Limpieza de base de datos';
            $breadcrumb = 'Configuración / Limpieza de base de datos';

            return view('database::cleanup.index', compact(
                'tables',
                'pageTitle',
                'breadcrumb',
                'cleanupEnabled'
            ));
        } catch (\Exception $e) {
            Log::error('Database cleanup index failed: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al obtener la lista de tablas. Por favor, revisa los logs del sistema.');
        }
    }

    /**
     * Get list of all tables with record count
     */
    private function getTablesList(): array
    {
        $database = config('database.connections.'.config('database.default').'.database');
        $tables = [];

        $result = DB::select('SELECT TABLE_NAME, TABLE_ROWS
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_SCHEMA = ?
                            ORDER BY TABLE_NAME ASC
                            LIMIT 200', [$database]);

        foreach ($result as $row) {
            $tables[] = [
                'name' => $row->TABLE_NAME,
                'records' => (int) $row->TABLE_ROWS,
                'estimated' => (int) $row->TABLE_ROWS,
            ];
        }

        return $tables;
    }

    /**
     * Truncate selected tables
     */
    public function truncate(Request $request): JsonResponse
    {
        // Check if cleanup is enabled
        if (! (bool) Setting::get('cleanup_enabled', config('database.cleanup.enabled', false))) {
            return response()->json([
                'success' => false,
                'message' => 'Esta característica no está habilitada. Configura DATABASE_CLEANUP_ENABLED=true en el archivo .env',
            ], 403);
        }

        try {
            $request->validate([
                'tables' => 'required|array|min:1|max:50',
                'tables.*' => 'required|string',
            ]);

            $tablesToTruncate = $request->input('tables');
            $database = config('database.connections.'.config('database.default').'.database');

            $allTables = DB::select('SELECT TABLE_NAME
                                    FROM INFORMATION_SCHEMA.TABLES
                                    WHERE TABLE_SCHEMA = ?', [$database]);

            $validTableNames = array_map(fn ($t) => $t->TABLE_NAME, $allTables);

            foreach ($tablesToTruncate as $table) {
                if (! in_array($table, $validTableNames)) {
                    return response()->json([
                        'success' => false,
                        'message' => "La tabla '$table' no existe o no es válida",
                    ], 400);
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                $truncatedCount = 0;
                $errors = [];

                foreach ($tablesToTruncate as $table) {
                    try {
                        DB::table($table)->truncate();
                        $truncatedCount++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'table' => $table,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            if (! empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => "Se limpiaron $truncatedCount tabla(s) pero hubo errores en ".count($errors).' tabla(s)',
                    'errors' => $errors,
                ], 400);
            }

            // Log the cleanup action
            try {
                activity()
                    ->causedBy(auth()->user())
                    ->log("Limpieza de base de datos: Se vaciaron $truncatedCount tabla(s)");
            } catch (\Exception $activityException) {
                Log::warning('Failed to log cleanup activity: '.$activityException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Se limpiaron correctamente $truncatedCount tabla(s)",
                'truncated_count' => $truncatedCount,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Database truncate failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar la base de datos. Por favor, revisa los logs del sistema.',
            ], 500);
        }
    }

    /**
     * Get table record count via AJAX
     */
    public function getTableCount(Request $request): JsonResponse
    {
        try {
            $tableName = $request->input('table');
            $database = config('database.connections.'.config('database.default').'.database');

            // Validate table exists
            $exists = DB::select('SELECT TABLE_NAME
                                FROM INFORMATION_SCHEMA.TABLES
                                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $tableName]);

            if (empty($exists)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tabla no encontrada',
                ], 404);
            }

            $count = DB::table($tableName)->count();

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('Database table count failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el conteo de registros.',
            ], 500);
        }
    }
}
