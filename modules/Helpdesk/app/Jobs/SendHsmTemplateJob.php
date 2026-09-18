<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Exceptions\WhatsAppHsmException;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\WhatsAppUsage;
use Modules\Helpdesk\Services\WhatsAppHsmService;
use Modules\Helpdesk\Support\ChannelMetrics;

/**
 * Send an approved WhatsApp template (HSM) off the HTTP request thread.
 *
 * Same reasoning as SendOutboundMessageJob: the Cloud API uses 15s timeouts
 * with retries (up to ~45s worst case), which would exhaust PHP-FPM workers
 * if run inline. The item is created synchronously by the caller (optimistic
 * UI, same pattern as regular replies) — this job only resolves wa_message_id
 * or marks the item as not delivered.
 */
class SendHsmTemplateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    /**
     * @param  array<int, string>  $variables
     */
    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
        private readonly string $templateName,
        private readonly array $variables,
        private readonly string $languageCode,
        private readonly ?string $category,
    ) {
        $this->onQueue('helpdesk');
    }

    /**
     * Mismo limitador que los envios de texto/adjuntos normales — ambos pegan
     * a la misma Cloud API de Meta y comparten el mismo techo de rate limit.
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

    public function handle(WhatsAppHsmService $hsm): void
    {
        $conversation = Conversation::find($this->conversationId);
        $item = ConversationItem::find($this->itemId);

        if (! $conversation || ! $item) {
            return;
        }

        try {
            $result = $hsm->send(
                conversation: $conversation,
                templateName: $this->templateName,
                variables: $this->variables,
                languageCode: $this->languageCode,
            );
        } catch (WhatsAppHsmException $e) {
            Log::warning('HSM send failed', array_merge(['message' => $e->getMessage()], $e->context()));
            $this->logUsage(false);
            $this->markSendFailed($item);

            return;
        }

        $isMocked = $result['mocked'] ?? false;

        $item->update(['metadata' => array_merge($item->metadata ?? [], [
            'wa_message_id' => $result['id'] ?? null,
            'mocked' => $isMocked,
        ])]);

        // Modo mock (WhatsApp sin configurar) no llega de verdad a Meta — no
        // debe contar como gasto real en el reporte de consumo.
        if (! $isMocked) {
            $this->logUsage(true);
        }

        try {
            broadcast(new ConversationMessageCreated($item, false));
        } catch (\Throwable) {
            // Best-effort: el indicador se muestra igualmente al abrir/recargar el hilo.
        }
    }

    /**
     * Marca el ítem como "no entregado" para que la UI muestre el fallo y el
     * agente pueda reintentar — mismo mecanismo que SendOutboundMessageJob.
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
            // Best-effort.
        }
    }

    /**
     * Registra un envío de plantilla HSM en el ledger de gasto de WhatsApp
     * (reporte en Settings). Un fallo al escribir el ledger no debe tumbar el
     * envío real ya confirmado/rechazado por Meta.
     */
    private function logUsage(bool $success): void
    {
        try {
            WhatsAppUsage::query()->create([
                'conversation_id' => $this->conversationId,
                'template_name' => $this->templateName,
                'category' => $this->category,
                'message_type' => 'template',
                'success' => $success,
            ]);
        } catch (\Throwable) {
            // Observabilidad, no debe tumbar el envío real.
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendHsmTemplateJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'template' => $this->templateName,
            'error' => $exception->getMessage(),
        ]);
    }
}
