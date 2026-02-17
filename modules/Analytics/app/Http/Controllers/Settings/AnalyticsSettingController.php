<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Analytics\Forms\AnalyticsSettingForm;
use Modules\Analytics\Http\Requests\Settings\AnalyticsSettingRequest;

class AnalyticsSettingController extends Controller
{
    /**
     * Display analytics settings page.
     */
    public function index(): View
    {
        $pageTitle = 'Configuración de Google Analytics';
        $breadcrumb = 'Configuración / Analytics';

        // Get current settings
        $settings = [
            'google_analytics_enable' => setting('google_analytics_enable', false),
            'google_analytics_property_id' => setting('google_analytics_property_id', ''),
            'google_analytics_credentials' => setting('google_analytics_credentials', ''),
            'analytics_cache_lifetime' => setting('analytics_cache_lifetime', 60),
            'analytics_dashboard_widgets' => setting('analytics_dashboard_widgets', ['general', 'top_pages', 'top_browsers', 'top_referrers']),
        ];

        // Get form configuration
        $formFields = AnalyticsSettingForm::getFields();
        $widgetTypes = AnalyticsSettingForm::getWidgetTypes();
        $dateRanges = AnalyticsSettingForm::getDateRanges();

        return view('analytics::settings.index', compact(
            'pageTitle',
            'breadcrumb',
            'settings',
            'formFields',
            'widgetTypes',
            'dateRanges'
        ));
    }

    /**
     * Update analytics settings.
     */
    public function update(AnalyticsSettingRequest $request)
    {
        try {
            $validated = $request->validated();

            // Prepare settings data
            $settingsToUpdate = [
                'google_analytics_enable' => $validated['google_analytics_enable'] ?? false,
                'google_analytics_property_id' => $validated['google_analytics_property_id'] ?? '',
                'google_analytics_credentials' => $validated['google_analytics_credentials'] ?? '',
                'analytics_cache_lifetime' => $validated['analytics_cache_lifetime'] ?? 60,
                'analytics_dashboard_widgets' => $validated['analytics_dashboard_widgets'] ?? [],
            ];

            // Save each setting
            foreach ($settingsToUpdate as $key => $value) {
                setting([$key => $value]);
            }

            // Clear analytics cache when settings are updated
            \Cache::tags(['analytics'])->flush();

            return response()->json([
                'status' => true,
                'message' => 'Configuración de Analytics actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating analytics settings: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate Google Analytics credentials.
     */
    public function validateCredentials(Request $request)
    {
        $propertyId = $request->input('property_id');
        $credentials = $request->input('credentials');

        if (empty($propertyId) || empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Property ID y credenciales son requeridos',
            ], 422);
        }

        try {
            // Validate JSON format
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido: '.json_last_error_msg());
            }

            // Check required fields
            $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
            foreach ($requiredFields as $field) {
                if (! isset($decoded[$field])) {
                    throw new \Exception("Falta el campo requerido: {$field}");
                }
            }

            // Validate type
            if ($decoded['type'] !== 'service_account') {
                throw new \Exception('El tipo de cuenta debe ser "service_account"');
            }

            // Try to initialize Analytics client
            try {
                $analytics = new \Modules\Analytics\Analytics($propertyId, $credentials);
                $client = $analytics->getClient();

                return response()->json([
                    'status' => true,
                    'message' => 'Credenciales validadas correctamente',
                ]);
            } catch (\Exception $e) {
                throw new \Exception('Error al conectar con Google Analytics: '.$e->getMessage());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al validar credenciales: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Test Analytics connection.
     */
    public function testConnection(Request $request)
    {
        try {
            $propertyId = setting('google_analytics_property_id');
            $credentials = setting('google_analytics_credentials');

            if (empty($propertyId) || empty($credentials)) {
                throw new \Exception('Google Analytics no está configurado');
            }

            $analytics = new \Modules\Analytics\Analytics($propertyId, $credentials);
            $period = \Modules\Analytics\Period::days(1);

            // Try to fetch some basic data
            $data = $analytics
                ->dateRange($period)
                ->metrics(['sessions'])
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Conexión exitosa con Google Analytics',
                'data' => [
                    'sessions' => $data->getTotals()['sessions'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al conectar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear analytics cache.
     */
    public function clearCache()
    {
        try {
            \Cache::tags(['analytics'])->flush();

            return response()->json([
                'status' => true,
                'message' => 'Caché de Analytics limpiado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al limpiar caché: '.$e->getMessage(),
            ], 500);
        }
    }
}
