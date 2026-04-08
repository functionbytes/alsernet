<?php

namespace Modules\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('analytics.dashboard.view');

        $pageTitle = 'Analytics Dashboard';
        $breadcrumb = 'Analytics / Dashboard';

        try {
            $propertyId = setting('google_analytics_property_id');
            $credentials = setting('google_analytics_credentials');

            if (empty($propertyId) || empty($credentials)) {
                return redirect()->route('settings.analytics.index')
                    ->with('error', 'Analytics no está configurado. Por favor, configura primero tu Google Analytics.');
            }

            $range = $request->input('range', 'last_7_days');

            return view('analytics::dashboard.index', compact('pageTitle', 'breadcrumb', 'range'));
        } catch (\RuntimeException $e) {
            Log::error('Analytics dashboard runtime error', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('settings.analytics.index')
                ->with('error', 'Ha ocurrido un error en la configuración de Analytics. Por favor verifica tu configuración.');
        } catch (\Exception $e) {
            Log::error('Analytics dashboard error', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('settings.analytics.index')
                ->with('error', 'Error al cargar datos de Analytics. Por favor, verifica tu configuración.');
        }
    }
}
