<?php

namespace Modules\Erp\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
            'oracle_password' => 'required|string|max:255',
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

        // Actualizar .env
        $this->updateOracleEnv($request);

        return redirect()->route('settings.erp.database.index')
            ->with('success', 'Configuración de Oracle Database actualizada correctamente');
    }

    /**
     * Actualizar configuración Oracle en .env file
     */
    private function updateOracleEnv(Request $request): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        $envVars = [
            'oracle_host' => 'ORACLE_HOST',
            'oracle_port' => 'ORACLE_PORT',
            'oracle_database' => 'ORACLE_DATABASE',
            'oracle_service_name' => 'ORACLE_SERVICE_NAME',
            'oracle_username' => 'ORACLE_USERNAME',
            'oracle_password' => 'ORACLE_PASSWORD',
            'oracle_schema' => 'ORACLE_SCHEMA',
            'oracle_charset' => 'ORACLE_CHARSET',
        ];

        foreach ($envVars as $formField => $envVar) {
            if ($request->filled($formField)) {
                $value = $request->input($formField);
                $value = str_replace('"', '\\"', $value);

                if (strpos($envContent, $envVar.'=') !== false) {
                    $envContent = preg_replace(
                        "/^{$envVar}=.*/m",
                        "{$envVar}=\"{$value}\"",
                        $envContent
                    );
                } else {
                    $envContent .= "\n{$envVar}=\"{$value}\"";
                }
            }
        }

        file_put_contents($envPath, $envContent);
        Artisan::call('config:clear');
    }

    /**
     * Verificar conexión con Oracle Database
     */
    public function checkConnection()
    {
        try {
            $settings = Setting::getErpSettings();

            // Intentar conectar a Oracle
            // Fallback a la conexión 'oracle' ya definida en config/database.php
            // (evita leer env() fuera de un archivo de config).
            $host = $settings['oracle_host'] ?? config('database.connections.oracle.host');
            $port = (int) ($settings['oracle_port'] ?? config('database.connections.oracle.port'));
            $serviceName = $settings['oracle_service_name'] ?? config('database.connections.oracle.service_name');
            $username = $settings['oracle_username'] ?? config('database.connections.oracle.username');
            $password = $settings['oracle_password'] ?? config('database.connections.oracle.password');
            $charset = $settings['oracle_charset'] ?? config('database.connections.oracle.charset');
            $database = $settings['oracle_database'] ?? config('database.connections.oracle.database');

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
            Setting::set('oracle_last_check', now()->toIso8601String());
            Setting::set('oracle_last_status', 'online');

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

            Setting::set('oracle_last_status', 'offline');

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
