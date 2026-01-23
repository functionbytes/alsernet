<?php

namespace Modules\Notification\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Models\NotificationPreference;
use Modules\Notification\Models\NotificationPushToken;

class NotificationController extends Controller
{
    /**
     * Listar notificaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 50);
        $onlyUnread = $request->boolean('unread', false);

        $query = $user->notifications();

        if ($onlyUnread) {
            $query->whereNull('read_at');
        }

        $notifications = $query->take($limit)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $data['title'] ?? 'Notificación',
                    'message' => $data['message'] ?? '',
                    'icon' => $data['icon'] ?? 'fas fa-bell',
                    'color' => $data['color'] ?? 'primary',
                    'action_url' => $data['action_url'] ?? null,
                    'action_text' => $data['action_text'] ?? 'Ver',
                    'priority' => $data['priority'] ?? 'normal',
                    'is_read' => $notification->read_at !== null,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->markAllNotificationsAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
            'unread_count' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Obtener preferencias de notificaciones
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $user->notificationPreferences()->get()->groupBy('channel');

        return response()->json([
            'preferences' => $preferences,
        ]);
    }

    /**
     * Actualizar preferencias de notificaciones
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.channel' => 'required|in:in_app,push,email,sms',
            'preferences.*.type' => 'required|string',
            'preferences.*.enabled' => 'required|boolean',
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::toggle(
                $user->id,
                $pref['channel'],
                $pref['type'],
                $pref['enabled']
            );
        }

        return response()->json([
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Registrar un token de push notification
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string',
            'browser' => 'nullable|string',
            'platform' => 'nullable|string',
        ]);

        $pushToken = NotificationPushToken::register(
            $user->id,
            $validated['token'],
            [
                'device_type' => $validated['device_type'] ?? null,
                'browser' => $validated['browser'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'ip_address' => $request->ip(),
            ]
        );

        return response()->json([
            'message' => 'Push token registered successfully',
            'token_id' => $pushToken->id,
        ]);
    }

    /**
     * Desactivar un token de push notification
     */
    public function deactivatePushToken(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $token = $user->pushTokens()
            ->where('id', $id)
            ->firstOrFail();

        $token->deactivate();

        return response()->json([
            'message' => 'Push token deactivated successfully',
        ]);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'total' => $user->notifications()->count(),
            'unread' => $user->unreadNotificationsCount(),
            'read' => $user->notifications()->whereNotNull('read_at')->count(),
            'today' => $user->notifications()->whereDate('created_at', today())->count(),
            'this_week' => $user->notifications()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ]);
    }
}
