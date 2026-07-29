<?php

namespace Modules\Prestashop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\Setting;
use Modules\Prestashop\Entities\ProductBlockade;

class PrestashopSettingsController extends Controller
{
    /**
     * Mostrar dashboard de configuración PrestaShop
     */
    public function index()
    {
        $settings = Setting::getPrestashopSettings();
        $stats = Setting::getPrestashopStats();

        return view('prestashop::settings.index', compact('settings', 'stats'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        $settings = Setting::getPrestashopSettings();

        return view('prestashop::settings.edit', compact('settings'));
    }

    /**
     * Actualizar configuración
     */
    public function update(Request $request)
    {
        $rules = Setting::getPrestashopRules();
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Setting::setPrestashopSettings($request->all());

        return redirect()->route('settings.prestashop.index')
            ->with('success', 'Configuración de PrestaShop actualizada correctamente');
    }

    /**
     * Verificar conexión a la base de datos PrestaShop
     */
    public function checkConnection()
    {
        try {
            $settings = Setting::getPrestashopSettings();

            config([
                'database.connections.prestashop_check' => [
                    'driver'    => 'mysql',
                    'host'      => $settings['prestashop_db_host'],
                    'port'      => (int) $settings['prestashop_db_port'],
                    'database'  => $settings['prestashop_db_database'],
                    'username'  => $settings['prestashop_db_username'],
                    'password'  => $settings['prestashop_db_password'],
                    'charset'   => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix'    => '',
                    'strict'    => false,
                ],
            ]);

            DB::connection('prestashop_check')->getPdo();
            DB::purge('prestashop_check');

            Setting::updatePrestashopSyncStatus('online');

            return response()->json([
                'success' => true,
                'status' => 'online',
                'message' => 'Conexión a PrestaShop establecida correctamente',
            ]);

        } catch (\Exception $e) {
            Setting::updatePrestashopSyncStatus('offline');
            Log::error('Error verificando conexión PrestaShop: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'offline',
                'message' => 'No se pudo conectar a la base de datos de PrestaShop: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar servicio
     */
    public function toggleActive(Request $request)
    {
        $isEnabled = $request->input('enabled');

        if ($isEnabled === null) {
            $current = Setting::get('prestashop_enabled', 'no');
            $isEnabled = $current === 'yes' ? 'no' : 'yes';
        } else {
            $isEnabled = $isEnabled ? 'yes' : 'no';
        }

        Setting::set('prestashop_enabled', $isEnabled);

        return response()->json([
            'success' => true,
            'enabled' => $isEnabled === 'yes',
            'message' => $isEnabled === 'yes' ? 'PrestaShop habilitado' : 'PrestaShop deshabilitado',
        ]);
    }

    /**
     * Resetear estadísticas
     */
    public function resetStats()
    {
        Setting::resetPrestashopStats();

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas reseteadas correctamente',
        ]);
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function getStats()
    {
        $stats = Setting::getPrestashopStats();

        if (! $stats) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada',
            ], 404);
        }

        $lastCheck = $stats['last_sync_check'] ?? null;
        $lastCheckDate = $lastCheck ? \Carbon\Carbon::parse($lastCheck) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'total_syncs' => $stats['total_syncs'] ?? 0,
                'failed_syncs' => $stats['failed_syncs'] ?? 0,
                'success_rate' => $stats['success_rate'] ?? 100,
                'last_check' => $lastCheckDate?->diffForHumans() ?? null,
                'last_status' => $stats['last_sync_status'] ?? null,
                'enabled' => $stats['enabled'] ?? false,
            ],
        ]);
    }

    /**
     * Test de sincronización
     */
    public function testSync()
    {
        try {
            $settings = Setting::getPrestashopSettings();

            config([
                'database.connections.prestashop_check' => [
                    'driver'    => 'mysql',
                    'host'      => $settings['prestashop_db_host'],
                    'port'      => (int) $settings['prestashop_db_port'],
                    'database'  => $settings['prestashop_db_database'],
                    'username'  => $settings['prestashop_db_username'],
                    'password'  => $settings['prestashop_db_password'],
                    'charset'   => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix'    => '',
                    'strict'    => false,
                ],
            ]);

            DB::purge('prestashop_check');

            $pendingOrders = DB::connection('prestashop_check')
                ->table('ps_orders')
                ->where('valid', 1)
                ->count();

            DB::purge('prestashop_check');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización funcionando correctamente',
                'pending_orders' => $pendingOrders,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync product blockades from external MySQL database
     */
    public function syncProductBlockades(Request $request)
    {
        try {
            $fresh = $request->input('fresh', false);

            // Execute artisan command
            $exitCode = \Artisan::call('migrate:product-blockades', [
                '--fresh' => $fresh,
            ]);

            $output = \Artisan::output();

            // Save last sync info
            Setting::set('product_blockades_last_sync', now());
            Setting::set('product_blockades_sync_count', (int) Setting::get('product_blockades_sync_count', 0) + 1);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? 'Sincronización de bloqueos completada exitosamente'
                    : 'Sincronización completada con errores',
                'output' => $output,
                'last_sync' => now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing product blockades: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar bloqueos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product blockades sync status
     */
    public function getBlockadesSyncStatus()
    {
        $lastSync = Setting::get('product_blockades_last_sync');
        $syncCount = Setting::get('product_blockades_sync_count', 0);
        $totalBlockades = ProductBlockade::count();

        return response()->json([
            'success' => true,
            'last_sync' => $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Nunca',
            'sync_count' => (int) $syncCount,
            'total_blockades' => $totalBlockades,
        ]);
    }

    /**
     * Create a new product blockade manually
     */
    public function createBlockade(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'source_id' => 'required|integer',
            'product_id' => 'nullable|integer|required_without:product_attribute_id',
            'product_attribute_id' => 'nullable|integer|required_without:product_id',
            'blockade_type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if already exists
            $exists = ProductBlockade::hasBlockade(
                $request->product_id,
                $request->product_attribute_id,
                $request->blockade_type
            );

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este bloqueo ya existe para este producto',
                ], 409);
            }

            $blockade = ProductBlockade::create([
                'source_id' => $request->source_id,
                'product_id' => $request->product_id,
                'product_attribute_id' => $request->product_attribute_id,
                'blockade_type' => $request->blockade_type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bloqueo creado exitosamente',
                'blockade' => $blockade,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating product blockade: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el bloqueo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save product blockade labels configuration
     */
    public function saveBlockadeLabels(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'labels' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Etiquetas inválidas',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            Setting::set('product_blockade_labels', $request->labels);

            return response()->json([
                'success' => true,
                'message' => 'Etiquetas guardadas exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving blockade labels: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las etiquetas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product blockade
     */
    public function deleteBlockade(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_blockades,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ID inválido',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $blockade = ProductBlockade::find($request->id);
            $blockade->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bloqueo eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting product blockade: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el bloqueo: '.$e->getMessage(),
            ], 500);
        }
    }
}
