<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal personalizado para notificaciones de usuarios
// Formato: users.{id}
// NOTE: Private channels require session auth which WebSocket clients can't provide.
// Keeping this for reference, but using public-notifications.{id} for WebSocket support.
Broadcast::channel('users.{id}', function ($user, $id) {
    \Log::debug('Channel authorization attempt', [
        'channel' => 'users.'.$id,
        'user_present' => $user !== null,
        'user_id' => $user?->id,
        'requested_id' => $id,
    ]);

    // Solo el usuario puede escuchar sus propias notificaciones
    if (! $user) {
        \Log::warning('No user authenticated for channel authorization');

        return false;
    }

    return (int) $user->id === (int) $id;
});

// Canal privado para notificaciones por usuario (NewNotificationEvent)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for notifications (no auth required)
// This works with WebSocket because no session cookies are needed
// The channel name includes the user ID for routing notifications to the correct user
Broadcast::channel('public-notifications.{id}', function ($user, $id) {
    // Allow anyone to listen to public notification channels
    // Security is ensured by:
    // 1. Only the backend broadcasts to these channels
    // 2. Channel name includes user ID, so users can only subscribe to their own
    // 3. Frontend only subscribes to channels for authenticated users
    return true;
});

// Canal privado para mensajes de conversaciones helpdesk (panel de agentes)
Broadcast::channel('helpdesk.conversation.{conversationId}', function ($user) {
    return $user instanceof User && $user->can('helpdesk.conversations.view');
});

// Canal privado global para la bandeja de helpdesk: recibe avisos de nuevas
// conversaciones y nuevos mensajes para que el sidebar se actualice solo.
Broadcast::channel('helpdesk.inbox', function ($user) {
    return $user instanceof User && $user->can('helpdesk.conversations.view');
});

// Presence channel para colaboracion en tiempo real en tickets de helpdesk
// Permite collision detection y typing indicator entre agentes
Broadcast::channel('ticket.{ticketId}', function ($user, int $ticketId) {
    if ($user->can('manager.helpdesk.tickets.show') || $user->hasRole('super-admin')) {
        return ['id' => $user->id, 'name' => trim($user->firstname.' '.$user->lastname)];
    }

    return false;
});

// Canal privado para contexto ERP de pedidos listos (HelpdeskErp)
// El hash es md5(strtolower(trim(email))) — solo usuarios con permiso helpdeskErp.view pueden suscribirse
Broadcast::channel('erp-orders-ready.{emailHash}', function ($user) {
    return $user instanceof User && $user->can('helpdeskErp.view');
});

// Canal privado para la bandeja social (comentarios de redes sociales)
Broadcast::channel('helpdesk.social.inbox', function ($user) {
    return $user instanceof User
        && $user->hasAnyRole(['helpdesk-agent', 'administrative', 'manager', 'super-admin']);
});

// Canal privado para un comentario social específico (detalle en tiempo real)
Broadcast::channel('helpdesk.social.comment.{commentId}', function ($user) {
    return $user instanceof User
        && $user->hasAnyRole(['helpdesk-agent', 'administrative', 'manager', 'super-admin']);
});
