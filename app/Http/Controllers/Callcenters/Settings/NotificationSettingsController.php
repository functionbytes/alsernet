<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationSettingsController extends Controller
{
    /**
     * Mostrar configuraciones de notificaciones
     */
    public function index()
    {
        $user = Auth::user();

        $channels = ['email', 'sms', 'push', 'database'];
        $notificationTypes = [
            'welcome' => 'Bienvenida',
            'order_status' => 'Estado de pedidos',
            'promotion' => 'Promociones',
            'system' => 'Sistema',
            'security' => 'Seguridad',
        ];

        $settings = $user->notificationSettings()
            ->get()
            ->groupBy('notification_type')
            ->toArray();

        return view('notifications.settings', compact('channels', 'notificationTypes', 'settings'));
    }

    /**
     * Actualizar configuraciones de notificaciones
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'array',
            'settings.*.enabled' => 'boolean',
        ]);

        foreach ($request->settings as $notificationType => $channels) {
            foreach ($channels as $channel => $config) {
                NotificationSetting::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'channel' => $channel,
                        'notification_type' => $notificationType,
                    ],
                    [
                        'enabled' => $config['enabled'] ?? false,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Configuraciones de notificaciones actualizadas.');
    }

    /**
     * Restablecer configuraciones por defecto
     */
    public function reset()
    {
        $user = Auth::user();

        $user->notificationSettings()->delete();

        return redirect()->back()->with('success', 'Configuraciones restablecidas a valores por defecto.');
    }

    /**
     * Enviar notificación de prueba
     */
    public function test(Request $request)
    {
        $request->validate([
            'channel' => 'required|in:email,sms,push,database',
        ]);

        $user = Auth::user();

        $testNotification = new \App\Notifications\TestNotification($request->channel);

        try {
            $user->notify($testNotification);
            return response()->json(['success' => true, 'message' => 'Notificación de prueba enviada.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error enviando notificación: ' . $e->getMessage()]);
        }
    }
}
