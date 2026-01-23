<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DeviceController extends Controller
{
    /**
     * Display authorized devices
     */
    public function index(): View
    {
        // TODO: Implement device tracking
        $devices = [];

        return view('auth::settings.devices.index', [
            'devices' => $devices,
        ]);
    }

    /**
     * Revoke a device
     */
    public function destroy(string $deviceId)
    {
        // TODO: Implement device revocation

        return redirect()->route('settings.auth.devices.index')
            ->with('success', 'Dispositivo revocado correctamente');
    }
}
