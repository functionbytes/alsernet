<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $filter = $request->get('filter', 'all'); // all, unread, read

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20);

        return view('notification::managers.notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Notification marked as read',
                'unread_count' => $user->unreadNotificationsCount(),
            ]);
        }

        // Redirigir a la URL de acción si existe
        $actionUrl = $notification->data['action_url'] ?? route('notifications.index');

        return redirect($actionUrl)->with('success', 'Notificación marcada como leída');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->markAllNotificationsAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
            ]);
        }

        return redirect()->route('notifications.index')->with('success', 'Todas las notificaciones marcadas como leídas');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Notification deleted',
                'unread_count' => $user->unreadNotificationsCount(),
            ]);
        }

        return redirect()->route('notifications.index')->with('success', 'Notificación eliminada');
    }
}
