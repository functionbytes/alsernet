<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Notification\Http\Requests\BulkActionRequest;
use Modules\Notification\Http\Requests\BulkDestroyRequest;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = $request->get('filter', 'all'); // all, unread, read

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(paginationNumber());

        return view('notification::managers.notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
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

        // Redirigir a la URL de acción si existe (solo URLs internas)
        $actionUrl = $notification->data['action_url'] ?? null;

        if ($actionUrl && str_starts_with($actionUrl, config('app.url'))) {
            return redirect($actionUrl)->with('success', 'Notificación marcada como leída');
        }

        return redirect()->route('notifications.index')->with('success', 'Notificación marcada como leída');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
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
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
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

    /**
     * Execute a bulk action on selected notifications.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        $user = $request->user();
        $query = $user->notifications()->whereIn('id', $request->validated('ids'));

        if ($request->validated('action') === 'mark_read') {
            $count = $query->clone()->whereNull('read_at')->count();
            $query->whereNull('read_at')->get()->each->markAsRead();
        } else {
            $count = $query->count();
            $query->delete();
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'unread_count' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Delete multiple notifications by ID.
     */
    public function bulkDestroy(BulkDestroyRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->notifications()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotificationsCount(),
        ]);
    }

    /**
     * Delete all notifications for the authenticated user.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
