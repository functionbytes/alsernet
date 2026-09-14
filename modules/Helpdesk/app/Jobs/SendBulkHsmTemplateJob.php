<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\HsmConversationService;

/**
 * Envía un LOTE de plantillas HSM de WhatsApp a varios contactos (envío
 * masivo desde panel/contacts). A diferencia de SendBroadcastChunkJob no hay
 * una entidad de campaña que trackear (BroadcastRecipient) — es un envío
 * ad-hoc a una selección puntual de contactos, no una campaña programada.
 * Cada contacto recibe su propia conversación/item, y un fallo individual no
 * aborta el resto del lote (mismo criterio de try/catch por destinatario).
 */
class SendBulkHsmTemplateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 30;

    /**
     * @param  array<int, int>  $customerIds
     * @param  array<int, string>  $variables
     */
    public function __construct(
        private readonly array $customerIds,
        private readonly string $templateName,
        private readonly array $variables,
        private readonly ?string $language,
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(HsmConversationService $hsmConversations): void
    {
        $customers = Customer::query()->whereIn('id', $this->customerIds)->get();

        foreach ($customers as $customer) {
            try {
                $conversation = $hsmConversations->findOrCreateWhatsAppConversation($customer);
                $hsmConversations->sendToConversation($conversation, $this->templateName, $this->variables, $this->language);
            } catch (\Throwable $e) {
                Log::error('SendBulkHsmTemplateJob: recipient delivery failed', [
                    'customer_id' => $customer->id,
                    'template_name' => $this->templateName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkHsmTemplateJob failed', [
            'customer_ids' => $this->customerIds,
            'template_name' => $this->templateName,
            'error' => $exception->getMessage(),
        ]);
    }
}
