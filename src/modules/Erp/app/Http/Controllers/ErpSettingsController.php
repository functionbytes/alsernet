<?php

namespace Modules\Erp\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\Setting;
use Modules\Erp\Services\ErpService;

class ErpSettingsController extends Controller
{
    protected ErpService $erpService;

    public function __construct(ErpService $erpService)
    {
        $this->erpService = $erpService;
    }

    /**
     * Mostrar dashboard general del ERP
     */
    public function index()
    {
        return view('erp::settings.erp.index');
    }

    /**
     * Mostrar formulario de edición de API del ERP
     */
    public function edit()
    {
        $settings = Setting::getErpSettings();
        $stats = $this->erpService->getStats();

        return view('erp::settings.api.edit', compact('settings', 'stats'));
    }

    /**
     * Actualizar configuración de API del ERP
     */
    public function update(Request $request)
    {
        $rules = [
            'erp_api_url' => 'required|url',
            'erp_sync_url' => 'required|url',
            'erp_xmlrpc_url' => 'nullable|url',
            'erp_sms_url' => 'nullable|url',
            'erp_connect_timeout' => 'nullable|numeric|min:1|max:300',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $settingsData = [
            'erp_api_url' => $request->input('erp_api_url'),
            'erp_sync_url' => $request->input('erp_sync_url'),
            'erp_xmlrpc_url' => $request->input('erp_xmlrpc_url'),
            'erp_sms_url' => $request->input('erp_sms_url'),
            'erp_connect_timeout' => $request->input('erp_connect_timeout', 30),
        ];

        Setting::setErpSettings($settingsData);
        $this->erpService->clearCache();

        return redirect()->route('settings.erp.api.edit')
            ->with('success', 'Configuración de API del ERP actualizada correctamente');
    }

    /**
     * Mostrar formulario de configuración de seguridad de la API ERP.
     */
    public function editApiSecurity()
    {
        $settings = Setting::getErpSettings();

        return view('erp::settings.api-security.edit', [
            'enabled' => ($settings['erp_api_auth_enabled'] ?? 'no') === 'yes',
            'guard' => $settings['erp_api_auth_guard'] ?? 'sanctum',
            'throttle' => $settings['erp_api_throttle'] ?? '60,1',
            'publicTokenThrottle' => $settings['erp_public_token_throttle'] ?? '60,1',
        ]);
    }

    /**
     * Guardar configuración de seguridad de la API ERP.
     */
    public function updateApiSecurity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'erp_api_auth_enabled' => 'required|in:yes,no',
            'erp_api_auth_guard' => 'required|in:sanctum,erp_token,both',
            'erp_api_throttle' => ['required', 'regex:/^\d+,\d+$/'],
            'erp_public_token_throttle' => ['required', 'regex:/^\d+,\d+$/'],
        ], [
            'erp_api_throttle.regex' => 'El formato debe ser "peticiones,minutos" (ej. 60,1)',
            'erp_public_token_throttle.regex' => 'El formato debe ser "peticiones,minutos" (ej. 60,1)',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Setting::setErpSettings([
            'erp_api_auth_enabled' => $request->input('erp_api_auth_enabled'),
            'erp_api_auth_guard' => $request->input('erp_api_auth_guard'),
            'erp_api_throttle' => $request->input('erp_api_throttle'),
            'erp_public_token_throttle' => $request->input('erp_public_token_throttle'),
        ]);

        return redirect()->route('settings.erp.api-security.edit')
            ->with('success', 'Configuración de seguridad de API actualizada. Recuerda que los cambios de throttle requieren `route:cache`.');
    }

    /**
     * Verificar servicios ERP externos
     */
    public function checkServices()
    {
        try {
            $settings = Setting::getErpSettings();
            $services = [];
            $allOnline = true;

            // Verificar cada servicio
            $urls = [
                'API REST' => $settings['erp_api_url'] ?? null,
                'Sincronización' => $settings['erp_sync_url'] ?? null,
                'XML-RPC' => $settings['erp_xmlrpc_url'] ?? null,
                'SMS' => $settings['erp_sms_url'] ?? null,
            ];

            foreach ($urls as $name => $url) {
                if (! $url) {
                    $services[$name] = false;
                    $allOnline = false;

                    continue;
                }

                try {
                    $timeout = (int) ($settings['erp_connect_timeout'] ?? 10);
                    $response = Http::timeout($timeout)->head($url);
                    $services[$name] = $response->successful();
                    if (! $response->successful()) {
                        $allOnline = false;
                    }
                } catch (\Exception $e) {
                    $services[$name] = false;
                    $allOnline = false;
                }
            }

            return response()->json([
                'success' => $allOnline,
                'message' => $allOnline ? 'Todos los servicios están disponibles' : 'Algunos servicios no están disponibles',
                'services' => $services,
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando servicios ERP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar servicios: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar/Desactivar servicio ERP
     */
    public function toggleActive(Request $request)
    {
        $isActive = $request->input('active');

        if ($isActive === null) {
            $current = Setting::get('erp_is_active', 'no');
            $isActive = $current === 'yes' ? 'no' : 'yes';
        } else {
            $isActive = $isActive ? 'yes' : 'no';
        }

        Setting::set('erp_is_active', $isActive);

        return response()->json([
            'success' => true,
            'is_active' => $isActive === 'yes',
            'message' => $isActive === 'yes' ? 'Servicio ERP activado' : 'Servicio ERP desactivado',
        ]);
    }

    /**
     * Limpiar cache del ERP
     */
    public function clearCache()
    {
        try {
            $this->erpService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache del ERP limpiado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resetear estadísticas
     */
    public function resetStats()
    {
        Setting::resetErpStats();

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
        $stats = Setting::getErpStats();

        if (! $stats) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada',
            ], 404);
        }

        $lastCheck = $stats['last_connection_check'] ?? null;
        $lastCheckDate = $lastCheck ? Carbon::parse($lastCheck) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'total_requests' => $stats['total_requests'] ?? 0,
                'failed_requests' => $stats['failed_requests'] ?? 0,
                'success_rate' => $stats['success_rate'] ?? 100,
                'last_check' => $lastCheckDate?->diffForHumans() ?? null,
                'last_status' => $stats['last_connection_status'] ?? null,
                'is_active' => $stats['is_active'] ?? false,
            ],
        ]);
    }

    /**
     * Test de sincronización
     */
    public function testSync()
    {
        try {
            $result = $this->erpService->getCambiosPendientes(10, 0);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización funcionando correctamente',
                    'pending_changes' => $result['count'] ?? 0,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar al servicio de sincronización',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización: '.$e->getMessage(),
            ], 500);
        }
    }
}
