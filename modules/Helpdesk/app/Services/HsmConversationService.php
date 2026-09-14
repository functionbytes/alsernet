<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Jobs\SendHsmTemplateJob;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;

/**
 * Envío de plantillas HSM de WhatsApp, compartido entre el composer del
 * inbox (conversación ya abierta) y las acciones de envío desde Contactos
 * (individual y masivo), que primero necesitan resolver/crear la conversación.
 */
class HsmConversationService
{
    /**
     * Busca una conversación de WhatsApp abierta del cliente, o crea una
     * nueva — mismo patrón de creación que ConversationsController::store()
     * (inbox_id cacheado por canal, external_sender_id resuelto igual que
     * resolveExternalSenderId(), status por defecto).
     */
    public function findOrCreateWhatsAppConversation(Customer $customer): Conversation
    {
        $existing = $customer->conversations()
            ->where('channel', 'whatsapp')
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'subject' => 'Plantilla WhatsApp',
            'priority' => 'normal',
            'channel' => 'whatsapp',
            'external_sender_id' => $customer->whatsapp_phone ?: $customer->phone,
            // Sin esto la conversacion nace con inbox_id NULL: ConversationPolicy
            // y AgentInboxCapacity la vuelven invisible para agentes restringidos
            // por bandeja. Mismo cache de 30 min por canal que usa store().
            'inbox_id' => Cache::remember(
                'helpdesk:inbox_id:whatsapp',
                now()->addMinutes(30),
                fn () => Inbox::query()->where('channel_type', 'whatsapp')->value('id'),
            ),
        ]);
        $conversation->status_id = ConversationStatus::getDefault()?->id;
        $conversation->save();

        return $conversation;
    }

    /**
     * Envía una plantilla HSM a una conversación ya resuelta: crea el item
     * de forma optimista (UI inmediata) y encola el envío real a Meta —
     * mismo comportamiento que tenía ConversationsController::sendHsm().
     *
     * @param  array<int, string>  $variables
     */
    public function sendToConversation(
        Conversation $conversation,
        string $templateName,
        array $variables = [],
        ?string $language = null,
    ): ConversationItem {
        $conversation->loadMissing('customer');

        // Resuelto una sola vez: el idioma real de la plantilla (ej. 'en_US' para
        // hello_world) es obligatorio para Meta — mandar 'es' fijo cuando la
        // plantilla está en otro idioma también dispara el error 132001.
        $template = WhatsAppTemplate::query()->where('external_id', $templateName)->first();
        $languageCode = $template?->language ?: ($language ?? 'es');
        $body = $this->renderHsmBody($template, $templateName, $variables);

        // El item se crea de inmediato (UI optimista, mismo patrón que las
        // respuestas de texto normales vía ConversationMessageService) y el
        // envío real a Meta se encola: la Cloud API usa timeouts de 15s con
        // reintentos (hasta ~45s en el peor caso), que bloquearían un worker
        // PHP-FPM si se hicieran aquí. SendHsmTemplateJob completa
        // wa_message_id/mocked o marca el item como no entregado.
        $item = $conversation->items()->create([
            'user_id' => auth()->id(),
            'type' => 'message',
            'body' => $body,
            'is_internal' => false,
            'metadata' => [
                'hsm_template' => $templateName,
                'hsm_variables' => $variables,
            ],
        ]);

        SendHsmTemplateJob::dispatch(
            $conversation->id,
            $item->id,
            $templateName,
            $variables,
            $languageCode,
            $template?->category,
        );

        return $item;
    }

    /**
     * Resuelve el cuerpo real enviado al cliente: el body_template de la
     * plantilla (buscada por su nombre técnico) con los {{n}} sustituidos por
     * las variables, para que el hilo muestre el mensaje que efectivamente
     * llegó por WhatsApp en vez de un placeholder genérico.
     *
     * @param  array<int, string>  $variables
     */
    private function renderHsmBody(?WhatsAppTemplate $template, string $templateName, array $variables): string
    {
        if (! $template || blank($template->body_template)) {
            return "[Plantilla HSM: {$templateName}]";
        }

        $body = $template->body_template;
        foreach (array_values($variables) as $i => $value) {
            $body = str_replace('{{'.($i + 1).'}}', (string) $value, $body);
        }

        return $body;
    }
}
