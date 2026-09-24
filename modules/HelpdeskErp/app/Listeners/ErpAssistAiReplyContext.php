<?php

namespace Modules\HelpdeskErp\Listeners;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskErp\Services\ErpAssist\ErpAssistAiContext;

/**
 * Aporta el resumen de Gestión (ERP) al prompt de las sugerencias de IA.
 *
 * Escucha el evento de cadena SuggestReplyService::CONTEXT_EVENT
 * ('helpdesk.ai.reply-context', payload [Conversation, ?User]) y devuelve un
 * fragmento de texto, o null. Solo lee caché: nunca bloquea la sugerencia.
 * Se registra desde config/ext/assist.php ('listeners').
 */
class ErpAssistAiReplyContext
{
    public function __construct(
        private readonly ErpAssistAiContext $context,
    ) {}

    public function handle(mixed $conversation = null, mixed $agent = null): ?string
    {
        if (! $conversation instanceof Conversation) {
            return null;
        }

        try {
            return $this->context->forConversation($conversation, $agent ?? auth()->user());
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
