<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

/**
 * Lado Helpdesk del punto de extensión EntityPanelRegistry de
 * HelpdeskEmailActivity — cuando el detalle de un email referencia una
 * Conversation (entity_type === Conversation::class), muestra un resumen
 * mínimo: canal, estado y el cliente asociado (Conversation::customer() es
 * una relación BelongsTo directa, barata de cargar con eager loading).
 */
class ConversationEmailLogPanelRenderer implements EmailLogEntityPanelRenderer
{
    public function supports(string $entityType): bool
    {
        return $entityType === Conversation::class;
    }

    public function render(EmailLog $emailLog): ?string
    {
        $conversation = Conversation::with(['customer', 'status'])->find($emailLog->entity_id);

        if (! $conversation) {
            return null;
        }

        return View::make('helpdesk::partials.email-log-panel-conversation', [
            'conversation' => $conversation,
        ])->render();
    }
}
