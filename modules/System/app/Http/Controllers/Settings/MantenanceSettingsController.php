<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class MantenanceSettingsController extends Controller
{
    public function index(): View
    {
        $secret = Str::random(20);

        $maintenanceMode = Setting::get('maintenance_mode', 'false');
        $maintenanceTitle = Setting::get('maintenance_title', 'Volvemos pronto');
        $maintenanceMessage = Setting::get('maintenance_message', 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.');
        $maintenanceModeValue = Setting::get('maintenance_mode_value');

        return view('system::maintenance.index', compact(
            'secret',
            'maintenanceMode',
            'maintenanceTitle',
            'maintenanceMessage',
            'maintenanceModeValue',
        ));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'maintenance_title' => 'nullable|string|max:255',
            'maintenance_message' => 'nullable|string|max:1000',
            'maintenance_mode' => 'nullable|string|in:true,false',
            'maintenance_mode_value' => 'nullable|string|max:255',
        ]);

        $data = [
            'maintenance_title' => $validated['maintenance_title'] ?? 'Volvemos pronto',
            'maintenance_message' => $validated['maintenance_message'] ?? 'Estamos realizando mejoras en el sistema. Por favor, vuelve en unos minutos.',
        ];

        if ($validated['maintenance_mode'] === 'true') {
            $data['maintenance_mode'] = 'true';
            $data['maintenance_mode_value'] = $validated['maintenance_mode_value'];
            updateSettings($data);
            Artisan::call('down', ['--secret' => $validated['maintenance_mode_value']]);
        } else {
            $data['maintenance_mode'] = 'false';
            $data['maintenance_mode_value'] = null;
            updateSettings($data);
            Artisan::call('up');
        }

        return response()->json([
            'success' => true,
            'message' => 'Se actualizó correctamente el modo mantenimiento',
        ]);
    }
}
