<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnalyticsSettingsController extends Controller
{
    public function index()
    {
        $pageTitle = 'Configuración de Google Analytics';
        $breadcrumb = 'Configuración / Analytics';

        // Obtener configuración actual
        $settings = [
            'google_analytics_enable' => setting('google_analytics_enable', false),
            'google_analytics_property_id' => setting('google_analytics_property_id', ''),
            'google_analytics_credentials' => setting('google_analytics_credentials', ''),
        ];

        return view('analytics::settings.index', compact(
            'pageTitle',
            'breadcrumb',
            'settings'
        ));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_analytics_enable' => 'nullable|boolean',
            'google_analytics_property_id' => 'nullable|string|max:50',
            'google_analytics_credentials' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = [
                'google_analytics_enable' => (bool) $request->google_analytics_enable,
                'google_analytics_property_id' => $request->google_analytics_property_id ?? '',
                'google_analytics_credentials' => $request->google_analytics_credentials ?? '',
            ];

            // Guardar configuración
            foreach ($data as $key => $value) {
                setting([$key => $value]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Configuración de Analytics actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar configuración: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate credentials
     */
    public function validateCredentials(Request $request)
    {
        $propertyId = $request->input('property_id');
        $credentials = $request->input('credentials');

        if (empty($propertyId) || empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Property ID y credenciales son requeridos',
            ]);
        }

        try {
            // Validar que las credenciales sean un JSON válido
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido');
            }

            return response()->json([
                'status' => true,
                'message' => 'Credenciales válidas',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al validar credenciales: ' . $e->getMessage(),
            ]);
        }
    }
}
