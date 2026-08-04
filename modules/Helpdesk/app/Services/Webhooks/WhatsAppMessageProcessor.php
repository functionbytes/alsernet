<?php

namespace Modules\Helpdesk\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;

class WhatsAppMessageProcessor
{
    public function __construct(
        private readonly InboundMessageIngestor $ingestor,
    ) {}

    /**
     * Process a parsed WhatsApp message event and persist it as a ConversationItem.
     *
     * Delega la conversación/dedup/ítem/broadcast/ConversationCreated a
     * InboundMessageIngestor — el mismo pipeline que usa ProcessSocialWebhookJob
     * para los webhooks reales. Antes este processor duplicaba esa lógica y por
     * eso los simuladores (público y del manager) y el comando de consola nunca
     * disparaban ConversationCreated: ninguna automatización enganchada a ese
     * evento (auto-asignación, respuesta fuera de horario, workflows) corría al
     * probar por ahí, aunque sí funcionara con webhooks reales.
     *
     * @param  array<string, mixed>  $event  Parsed event from WhatsAppBusinessService::parseWebhookPayload()
     */
    public function process(array $event): ?ConversationItem
    {
        if (($event['type'] ?? '') !== 'message') {
            return null;
        }

        $phone = $event['phone'];
        $name = $event['name'] ?? $phone;
        $body = $event['body'] ?? $this->fallbackBody($event);

        $customer = Customer::firstOrCreate(
            ['whatsapp_phone' => $phone],
            ['name' => $name, 'phone' => $phone, 'language' => 'es'],
        );

        $item = $this->ingestor->ingest('whatsapp', $phone, $customer, [
            'body' => $body,
            'external_id' => $event['message_id'],
            'metadata' => array_filter([
                'media_id' => $event['media_id'] ?? null,
                'mime_type' => $event['mime_type'] ?? null,
                'filename' => $event['filename'] ?? null,
                'caption' => $event['caption'] ?? null,
                'message_type' => $event['message_type'] ?? null,
                'platform' => 'whatsapp',
                'raw' => $event,
            ]),
        ]);

        if ($item === null) {
            return null;
        }

        // last_customer_message_at alimenta Conversation::isWhatsAppWindowOpen()
        // (ventana de 24h de WhatsApp para texto libre vs. plantilla HSM); el
        // ingestor ya la actualiza, aquí solo falta last_message_at para que la
        // vista previa de la bandeja refleje este mensaje.
        DB::connection('helpdesk')->table('helpdesk_conversations')
            ->where('id', $item->conversation_id)
            ->update(['last_message_at' => now()]);

        $conversation = $item->conversation;
        if ($conversation->assignee_id) {
            event(new InboxItemChanged($conversation->id, $conversation->assignee_id, 'message_added'));
        }

        return $item;
    }

    private function fallbackBody(array $event): string
    {
        $type = $event['message_type'] ?? 'unknown';
        $caption = $event['caption'] ?? null;

        return match ($type) {
            'image' => $caption ? "[imagen]: {$caption}" : '[imagen]',
            'document' => '[documento'.($event['filename'] ? ": {$event['filename']}" : '').']',
            'audio' => '[audio]',
            'video' => $caption ? "[video]: {$caption}" : '[video]',
            'sticker' => '[sticker]',
            default => "[{$type}]",
        };
    }
}
