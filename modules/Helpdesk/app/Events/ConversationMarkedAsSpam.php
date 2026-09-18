<?php

namespace Modules\Helpdesk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Helpdesk\Models\Conversation;

/**
 * Disparado desde ConversationsController::markSpam(). Sin ShouldBroadcast a
 * proposito — es una señal interna para que módulos satélite reaccionen
 * (p. ej. HelpdeskTickets añade el remitente a su lista negra de tickets),
 * no una actualización en vivo del inbox (eso ya lo cubre
 * broadcastInboxChanged('spam') en el propio controller).
 */
class ConversationMarkedAsSpam
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ?int $markedByUserId = null,
    ) {}
}
