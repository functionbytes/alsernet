<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use App\Models\PushNotificationToken;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Mostrar notificaciones del usuario
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        if ($request->filled('type')) {
            $query->where('data->type', $request->type);
        }

        if ($request->filled('read')) {
            if ($request->read === 'unread') {
                $query->whereNull('read_at');
            } else {
                $query->whereNotNull('read_at');
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        // Obtener estadísticas
        $stats = [
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'today' => $user->notifications()->whereDate('created_at', today())->count(),
        ];

        return view('notifications.index', compact('notifications', 'stats'));
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(Request $request, $id = null)
    {
        $user = Auth::user();

        if ($id) {
            // Marcar una notificación específica
            $notification = $user->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json(['success' => true]);
        } else {
            // Marcar todas las notificaciones como leídas
            $user->unreadNotifications->markAsRead();

            return redirect()->back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
        }
    }

    /**
     * Eliminar notificación
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Obtener notificaciones no leídas (para AJAX)
     */
    public function getUnread()
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notificación',
                    'message' => $notification->data['message'] ?? '',
                    'type' => $notification->data['type'] ?? 'general',
                    'created_at' => $notification->created_at->diffForHumans(),
                    'action_url' => $notification->data['action_url'] ?? null,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Registrar token de notificación push
     */
    public function registerPushToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'required|in:ios,android,web',
            'device_id' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Verificar si el token ya existe
        $existingToken = PushNotificationToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if ($existingToken) {
            $existingToken->update([
                'active' => true,
                'last_used_at' => now(),
            ]);
        } else {
            PushNotificationToken::create([
                'user_id' => $user->id,
                'token' => $request->token,
                'device_type' => $request->device_type,
                'device_id' => $request->device_id,
                'active' => true,
                'last_used_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Desregistrar token de notificación push
     */
    public function unregisterPushToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();

        PushNotificationToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->update(['active' => false]);

        return response()->json(['success' => true]);
    }
}
