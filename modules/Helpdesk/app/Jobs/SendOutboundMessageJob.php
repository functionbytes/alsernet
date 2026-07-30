<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Support\ChannelMetrics;

/**
 * Send an agent reply (text + attachments) to the customer's external channel
 * (WhatsApp / Facebook / Instagram) off the HTTP request thread.
 *
 * The Graph/WhatsApp APIs use 15s timeouts with retries; running them inline
 * exhausts PHP-FPM workers under load. Text and attachments are pre-resolved by
 * the caller (absolute URLs, stripped body) so this job only performs the I/O
 * and correlates the returned external message id back onto the item.
 */
class SendOutboundMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    /**
     * @param  array<int, array{type?: string, url: string, name?: ?string}>  $attachments
     * @param  string  $correlation  'external_id' (item column) or 'metadata' (outbound_message_id)
     */
    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
        private readonly ?string $body,
        private readonly array $attachments = [],
        private readonly string $correlation = 'external_id',
    ) {
        $this->onQueue('helpdesk');
    }

    /**
     * Rate-limit de envíos a Meta para no exceder los límites de la Cloud API bajo
     * ráfaga (respuestas masivas / broadcasts). Al topar el límite el job se
     * reencola; retryUntil() da una ventana amplia para que acabe enviándose.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('helpdesk-meta-outbound')];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function handle(OutboundMessageService $outbound): void
    {
        $conversation = Conversation::find($this->conversationId);
        $item = ConversationItem::find($this->itemId);

        if (! $conversation || ! $item || ! $outbound->supports($conversation)) {
            return;
        }

        // Idempotencia ante reintentos del job: si el texto ya se envió en un
        // intento previo (external_id persistido) NO lo reenviamos, para no
        // duplicar el mensaje en el chat del cliente.
        $externalId = $this->correlation === 'external_id' ? $item->external_id : null;

        if (filled($this->body) && blank($externalId)) {
            $externalId = $outbound->sendReply($conversation, $this->body);

            // Persistir de inmediato: si un paso posterior falla y el job
            // reintenta, no se reenvía el texto.
            if (filled($externalId) && $this->correlation === 'external_id') {
                $item->update(['external_id' => $externalId]);
            }
        }

        foreach ($this->attachments as $attachment) {
            $url = (string) ($attachment['url'] ?? '');

            if (blank($url)) {
                continue;
            }

            $attExternalId = $outbound->sendAttachment(
                $conversation,
                (string) ($attachment['type'] ?? 'file'),
                $url,
                null,
                $attachment['name'] ?? null,
            );

            $externalId = $externalId ?: $attExternalId;
        }

        if (! $externalId) {
            $this->markSendFailed($item);

            return;
        }

        // Envío exitoso: limpiar cualquier marca previa de "no entregado".
        $meta = is_array($item->metadata) ? $item->metadata : [];
        unset($meta['send_failed'], $meta['send_failed_at']);

        if ($this->correlation === 'metadata') {
            $item->update(['metadata' => array_merge($meta, [
                'outbound_message_id' => $externalId,
                'sent_via' => $conversation->channel,
            ])]);

            return;
        }

        $item->external_id = $externalId;
        $item->metadata = $meta;
        $item->save();
    }

    /**
     * Marca el ítem como "no entregado" para que la UI muestre el estado de fallo
     * y el agente pueda reintentar. No se lanza excepción (evita el reintento
     * automático del job y el riesgo de duplicar el mensaje): reintentar es una
     * acción explícita del agente.
     */
    private function markSendFailed(ConversationItem $item): void
    {
        ChannelMetrics::increment('send_failed', $item->conversation?->channel ?? 'unknown');

        $item->update(['metadata' => array_merge($item->metadata ?? [], [
            'send_failed' => true,
            'send_failed_at' => now()->toIso8601String(),
        ])]);

        try {
            broadcast(new ConversationMessageCreated($item, false));
        } catch (\Throwable) {
            // Best-effort: el indicador se muestra igualmente al abrir/recargar el hilo.
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendOutboundMessageJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'error' => $exception->getMessage(),
        ]);
    }
}
