<?php

namespace Modules\Erp\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\Setting;

class OracleDatabaseController extends Controller
{
    /**
     * Dashboard de configuración de Base de Datos Oracle
     */
    public function index()
    {
        $settings = Setting::getErpSettings();

        // Obtener estado de la conexión
        $lastCheck = $settings['oracle_last_check'] ?? null;
        $lastStatus = $settings['oracle_last_status'] ?? 'unknown';
        $lastCheckDate = $lastCheck ? Carbon::parse($lastCheck) : null;

        return view('erp::settings.database.index', compact('settings', 'lastStatus', 'lastCheckDate'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        $settings = Setting::getErpSettings();

        return view('erp::settings.database.edit', compact('settings'));
    }

    /**
     * Actualizar configuración de Oracle
     */
    public function update(Request $request)
    {
        $rules = [
            'oracle_host' => 'required|string|max:255',
            'oracle_port' => 'required|numeric|min:1|max:65535',
            'oracle_database' => 'required|string|max:255',
            'oracle_service_name' => 'required|string|max:255',
            'oracle_username' => 'required|string|max:255',
            // 29-sep-2026: el formulario ya no rellena la contraseña; vacía = sin cambios.
            'oracle_password' => 'nullable|string|max:255',
            'oracle_schema' => 'required|string|max:255',
            'oracle_charset' => 'required|string|max:50',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Guardar en settings
        $settingsData = [
            'oracle_host' => $request->input('oracle_host'),
            'oracle_port' => $request->input('oracle_port'),
            'oracle_database' => $request->input('oracle_database'),
            'oracle_service_name' => $request->input('oracle_service_name'),
            'oracle_username' => $request->input('oracle_username'),
            'oracle_password' => $request->input('oracle_password'),
            'oracle_schema' => $request->input('oracle_schema'),
            'oracle_charset' => $request->input('oracle_charset'),
            'oracle_enable_cache' => $request->has('oracle_enable_cache') ? true : false,
        ];

        Setting::setErpSettings($settingsData);

        // 28-sep-2026: ya NO se reescribe el .env (guardaba la contraseña de
        // Oracle en texto plano y obligaba a dejarlo escribible por el usuario
        // web — un .env 777 lo pudo modificar cualquiera). La conexión real sale
        // de la tabla settings (ErpServiceProvider::applyDynamicOracleConfig),
        // que se aplica de inmediato con la invalidación de caché de abajo; el
        // .env solo sirve de valor inicial de reserva.

        // Forzar que la nueva configuración se aplique de inmediato:
        // 1) invalidar el bundle de settings ERP cacheado (settings_erp_bundle, TTL 10 min)
        // 2) olvidar las claves oracle_* cacheadas individualmente
        // 3) cerrar la conexión Oracle activa para que la próxima reconecte con el nuevo host/schema
        Setting::clearErpSettingsCache();
        foreach (array_keys($settingsData) as $cacheKey) {
            Cache::forget("setting_{$cacheKey}");
        }
        try {
            DB::purge('oracle');
        } catch (\Throwable $e) {
            Log::warning('No se pudo purgar la conexión Oracle tras guardar config', ['error' => $e->getMessage()]);
        }

        return redirect()->route('settings.erp.database.index')
            ->with('success', 'Configuración de Oracle Database actualizada correctamente');
    }

    /**
     * Verificar conexión con Oracle Database
     */
    public function checkConnection()
    {
        try {
            $settings = Setting::getErpSettings();

            // Intentar conectar a Oracle
            $host = $settings['oracle_host'] ?? env('ORACLE_HOST');
            $port = (int) ($settings['oracle_port'] ?? env('ORACLE_PORT', 1521));
            $serviceName = $settings['oracle_service_name'] ?? env('ORACLE_SERVICE_NAME');
            $username = $settings['oracle_username'] ?? env('ORACLE_USERNAME');
            $password = $settings['oracle_password'] ?? env('ORACLE_PASSWORD');
            $charset = $settings['oracle_charset'] ?? env('ORACLE_CHARSET', 'AL32UTF8');
            $database = $settings['oracle_database'] ?? env('ORACLE_DATABASE');

            // Check if OCI8 extension is available
            if (! extension_loaded('oci8')) {
                throw new \Exception('La extensión OCI8 no está disponible');
            }

            Log::info('Intentando conexión Oracle con OCI8', [
                'host' => $host,
                'port' => $port,
                'service_name' => $serviceName,
                'username' => $username,
            ]);

            // Construir connection string en formato simple
            $connString = "{$host}:{$port}/{$serviceName}";

            // Intentar conexión
            $startTime = microtime(true);
            $conn = @oci_connect($username, $password, $connString, $charset);
            $elapsed = round(microtime(true) - $startTime, 2);

            if (! $conn) {
                $error = oci_error();
                throw new \Exception(
                    'Error de conexión OCI8: '.(isset($error['message']) ? $error['message'] : 'Unknown error')
                );
            }

            // Ejecutar una consulta simple para validar la conexión
            $sql = "SELECT TO_CHAR(SYSDATE, 'DD-MON-YY HH24:MI:SS') AS fecha FROM DUAL";
            $stmt = oci_parse($conn, $sql);

            if (! $stmt) {
                $error = oci_error($conn);
                oci_close($conn);
                throw new \Exception('Error al parsear SQL: '.$error['message']);
            }

            if (! oci_execute($stmt)) {
                $error = oci_error($stmt);
                oci_close($conn);
                throw new \Exception('Error al ejecutar SQL: '.$error['message']);
            }

            // Obtener resultado
            $row = oci_fetch_array($stmt, OCI_ASSOC);
            $serverDate = $row ? $row['FECHA'] : null;

            oci_free_statement($stmt);
            oci_close($conn);

            // Log de éxito
            Log::info('Conexión con Oracle Database verificada exitosamente', [
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'elapsed_time' => $elapsed,
                'server_date' => $serverDate,
            ]);

            // Actualizar estado en settings
            Setting::updateOrCreate(
                ['key' => 'oracle_last_check'],
                ['value' => now()->toIso8601String()]
            );
            Setting::updateOrCreate(
                ['key' => 'oracle_last_status'],
                ['value' => 'online']
            );

            return response()->json([
                'success' => true,
                'status' => 'online',
                'message' => "Conexión con Oracle Database establecida correctamente. Fecha servidor: {$serverDate}",
                'host' => "{$host}:{$port}",
                'database' => $database,
                'service_name' => $serviceName,
                'server_date' => $serverDate,
                'elapsed_time' => "{$elapsed}s",
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando conexión Oracle: '.$e->getMessage());

            Setting::updateOrCreate(
                ['key' => 'oracle_last_status'],
                ['value' => 'offline']
            );

            return response()->json([
                'success' => false,
                'status' => 'offline',
                'message' => 'Error al conectar a Oracle: '.$e->getMessage(),
                'troubleshooting' => [
                    'Verifica que OCI8 esté disponible',
                    'Verifica que el host y puerto sean correctos',
                    'Asegúrate de que el servidor Oracle está en línea',
                    'Verifica que el usuario y contraseña sean correctos',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 200);
        }
    }
}
