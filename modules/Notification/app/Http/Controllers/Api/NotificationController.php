<?php

namespace Modules\Notification\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Http\Requests\Api\RegisterPushTokenRequest;
use Modules\Notification\Http\Requests\Api\UpdatePreferencesRequest;
use Modules\Notification\Http\Resources\NotificationResource;
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

        $notifications = $query->take($limit)->get();

        return response()->json([
            'notifications' => NotificationResource::collection($notifications),
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
    public function updatePreferences(UpdatePreferencesRequest $request): JsonResponse
    {
        $user = $request->user();

        foreach ($request->validated('preferences') as $pref) {
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
    public function registerPushToken(RegisterPushTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $pushToken = NotificationPushToken::register(
            $user->id,
            $validated['token'],
            [
                'device_type' => $validated['device_type'] ?? null,
                'device_id' => $validated['device_id'] ?? null,
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
        $weekStart = now()->startOfWeek()->toDateTimeString();
        $weekEnd = now()->endOfWeek()->toDateTimeString();
        $today = today()->toDateString();

        $row = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->selectRaw(
                'COUNT(*) as total,
                 SUM(read_at IS NULL) as unread,
                 SUM(read_at IS NOT NULL) as `read`,
                 SUM(DATE(created_at) = ?) as today,
                 SUM(created_at BETWEEN ? AND ?) as this_week',
                [$today, $weekStart, $weekEnd]
            )
            ->first();

        return response()->json([
            'total' => (int) $row->total,
            'unread' => (int) $row->unread,
            'read' => (int) $row->read,
            'today' => (int) $row->today,
            'this_week' => (int) $row->this_week,
        ]);
    }
}
