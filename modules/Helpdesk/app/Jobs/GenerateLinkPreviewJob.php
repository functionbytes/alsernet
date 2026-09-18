<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\LinkPreviewService;

/**
 * Genera la vista previa OpenGraph de un enlace FUERA del hilo HTTP: la descarga
 * (hasta 6s / 2MB) bloqueaba el request del agente al enviar un mensaje con URL.
 *
 * Al terminar re-emite MessageReceived (widget del cliente) Y ConversationMessageCreated
 * (hilo del agente en el panel manager) — ambos lados deduplican/reemplazan por id
 * de item (conversations.js: "If bubble already exists... replace it", mismo
 * patrón ya usado para la descarga de adjuntos), así que actualizan el mensaje
 * en vez de duplicarlo (patrón fast-path: el mensaje aparece ya, el preview
 * llega después). Antes solo se re-notificaba al widget; el panel del agente
 * dependía de un fetch síncrono duplicado en el controller para ver el
 * preview en la primera pintura — ver ConversationMessagesController::store().
 */
class GenerateLinkPreviewJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 20;

    public int $backoff = 10;

    public function __construct(
        private readonly int $itemId,
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(LinkPreviewService $linkPreview): void
    {
        $item = ConversationItem::find($this->itemId);

        if (! $item || $item->is_internal || blank($item->body)) {
            return;
        }

        $existing = $item->metadata ?? [];
        if (isset($existing['link_preview'])) {
            return;
        }

        $preview = $linkPreview->previewFromBody($item->body);
        if ($preview === null) {
            return;
        }

        $item->metadata = array_merge($existing, ['link_preview' => $preview]);
        $item->saveQuietly();

        if ($item->conversation) {
            $fresh = $item->fresh();
            broadcast(new MessageReceived($item->conversation, $fresh));
            broadcast(new ConversationMessageCreated($fresh, false));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('GenerateLinkPreviewJob failed', [
            'item_id' => $this->itemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
