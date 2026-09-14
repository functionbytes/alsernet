<?php

namespace Modules\Helpdesk\Observers;

use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Jobs\GenerateLinkPreviewJob;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * Al crear un ConversationItem del agente con URL, encola la generación del
 * preview OpenGraph (antes se hacía SÍNCRONO en el observer, bloqueando el
 * request hasta 6s). Corre en todo item nuevo sin importar qué controller/job lo
 * persistió.
 *
 * Se salta items de visitantes y notas internas (intencional: solo el agente
 * recibe unfurls en el widget, como WhatsApp/Instagram).
 */
class ConversationItemLinkPreviewObserver
{
    /**
     * Canales con un consumidor real de MessageReceived (el widget de chat en
     * vivo y su simulador). Para el resto (whatsapp/facebook/instagram/email)
     * este broadcast no tiene oyentes — dispararlo solo gasta una query extra
     * + una llamada a Reverb en cada mensaje sin ningún beneficio.
     */
    private const WIDGET_CHANNELS = ['web', 'widget'];

    public function created(ConversationItem $item): void
    {
        if ($item->is_internal) {
            return;
        }

        // Solo enriquecemos con preview los mensajes del agente que contienen una
        // URL; el fetch OG (HTTP) se hace en GenerateLinkPreviewJob, fuera del
        // request. El check de URL es barato y evita despachar un job por mensaje.
        $isAgent = ! empty($item->user_id);

        if ($isAgent && is_string($item->body) && trim($item->body) !== '') {
            $existing = $item->metadata ?? [];

            if (! isset($existing['link_preview']) && preg_match('#https?://#i', $item->body)) {
                GenerateLinkPreviewJob::dispatch($item->id);
            }
        }

        // Broadcast inmediato del evento del widget (single source of truth). El
        // preview llega después vía el job, que re-emite MessageReceived; el
        // widget deduplica por id y actualiza el mensaje en su sitio.
        $conversation = $item->conversation;

        if ($conversation && in_array($conversation->channel, self::WIDGET_CHANNELS, true)) {
            broadcast(new MessageReceived($conversation, $item));
        }
    }
}
