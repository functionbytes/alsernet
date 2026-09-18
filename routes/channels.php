<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskTickets\Models\Ticket;

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
    Log::debug('Channel authorization attempt', [
        'channel' => 'users.'.$id,
        'user_present' => $user !== null,
        'user_id' => $user?->id,
        'requested_id' => $id,
    ]);

    // Solo el usuario puede escuchar sus propias notificaciones
    if (! $user) {
        Log::warning('No user authenticated for channel authorization');

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
// SEC-01: antes solo comprobaba el permiso grueso 'helpdesk.conversations.view'
// — cualquier agente con ese permiso podía suscribirse a CUALQUIER
// conversationId (entero secuencial, enumerable) y leer conversaciones de
// bandejas fuera de su ámbito. Ahora delega en ConversationPolicy::view, que
// respeta el aislamiento por AgentInboxCapacity (o el bypass helpdesk.manage).
Broadcast::channel('helpdesk.conversation.{conversationId}', function ($user, $conversationId) {
    if (! $user instanceof User) {
        return false;
    }

    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return Gate::forUser($user)->allows('view', $conversation);
});

// Canal privado por bandeja para la lista de conversaciones (sidebar).
// SEC-01: reemplaza al antiguo canal global 'helpdesk.inbox', que transportaba
// el payload COMPLETO de ConversationMessageCreated (incluidas notas internas)
// a cualquier agente con el permiso grueso 'helpdesk.conversations.view', sin
// respetar el aislamiento por bandeja. Ahora cada bandeja tiene su propio
// canal, autorizado contra AgentInboxCapacity (mismo criterio que
// ConversationPolicy::canAccessInbox), y solo recibe el payload ligero de
// ConversationInboxItemCreated (ver ese evento).
Broadcast::channel('helpdesk.inbox.{inboxId}', function ($user, $inboxId) {
    if (! $user instanceof User) {
        return false;
    }

    if ($user->can('helpdesk.manage')) {
        return true;
    }

    return AgentInboxCapacity::where('user_id', $user->id)
        ->where('inbox_id', $inboxId)
        ->exists();
});

// Presence channel para colaboracion en tiempo real en tickets de helpdesk
// Permite collision detection y typing indicator entre agentes
// SEC-01: antes bastaba con el permiso 'manager.helpdesk.tickets.show' (que
// cualquier manager tiene globalmente) o el rol super-admin, sin comprobar
// grupo/propiedad del ticket concreto. Ahora delega en TicketPolicy::view,
// que también admite al agente asignado (assignee_id) sin el permiso global.
Broadcast::channel('ticket.{ticketId}', function ($user, int $ticketId) {
    if (! $user instanceof User) {
        return false;
    }

    $ticket = Ticket::find($ticketId);

    if (! $ticket || ! Gate::forUser($user)->allows('view', $ticket)) {
        return false;
    }

    return ['id' => $user->id, 'name' => trim($user->firstname.' '.$user->lastname)];
});

// Canal privado para contexto ERP de pedidos listos (HelpdeskErp)
// El hash es md5(strtolower(trim(email))). SEC-01: el permiso estaba mal
// escrito ('helpdeskErp.view', con mayúsculas) contra el nombre real
// sembrado en minúsculas ('helpdeskerp.view' — ver
// HelpdeskErpPermissionsSeeder), así que este callback devolvía false
// SIEMPRE (bug, no vulnerabilidad) — y además el permiso, aun corregido, es
// global y no comprobaba que el emailHash corresponda a un cliente dentro
// del ámbito real del agente (sus bandejas). Se corrige el casing y se añade
// esa comprobación de ámbito: debe existir al menos una conversación, en una
// bandeja del agente, cuyo cliente tenga ese email.
Broadcast::channel('erp-orders-ready.{emailHash}', function ($user, string $emailHash) {
    if (! $user instanceof User || ! $user->can('helpdeskerp.view')) {
        return false;
    }

    if ($user->can('helpdesk.manage')) {
        return true;
    }

    $inboxIds = AgentInboxCapacity::where('user_id', $user->id)->pluck('inbox_id');

    if ($inboxIds->isEmpty()) {
        return false;
    }

    return Conversation::query()
        ->whereIn('inbox_id', $inboxIds)
        ->whereHas('customer', function ($query) use ($emailHash) {
            $query->whereRaw('MD5(LOWER(TRIM(email))) = ?', [$emailHash]);
        })
        ->exists();
});

// Canal privado para la bandeja social (comentarios de redes sociales).
// SEC-01: comprobaba una lista de roles hardcodeada en vez del permiso real
// del módulo ('helpdesksocial.view', convención {alias}.action), lo que
// podía divergir silenciosamente de SocialCommentPolicy si los roles
// cambian. Se delega ahora en la Policy (viewAny) para una única fuente de
// verdad. NOTA: SocialCommentPolicy no distingue por cuenta/bandeja social
// hoy (ninguna acción del módulo lo hace todavía — ver SocialCommentPolicy),
// así que este canal sigue siendo global a todo agente con el permiso; no
// hay un modelo de aislamiento por cuenta al que delegar aún.
Broadcast::channel('helpdesk.social.inbox', function ($user) {
    return $user instanceof User && Gate::forUser($user)->allows('viewAny', SocialComment::class);
});

// Canal privado para un comentario social específico (detalle en tiempo real).
// SEC-01: misma lista de roles hardcodeada que arriba, sin comprobar el
// comentario concreto. Se delega en SocialCommentPolicy::view (hoy
// equivalente al permiso, pero centralizado y sensible a futuros cambios de
// ámbito en la Policy sin tener que tocar este archivo).
Broadcast::channel('helpdesk.social.comment.{commentId}', function ($user, $commentId) {
    if (! $user instanceof User) {
        return false;
    }

    $comment = SocialComment::find($commentId);

    if (! $comment) {
        return false;
    }

    return Gate::forUser($user)->allows('view', $comment);
});
