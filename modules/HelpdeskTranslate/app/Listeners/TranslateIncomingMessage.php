<?php

namespace Modules\HelpdeskTranslate\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\CustomerLanguageDetected;
use Modules\HelpdeskTranslate\Concerns\TranslatesMessage;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Throwable;

/**
 * Auto-translate incoming customer messages whenever the detected language
 * differs from the agent locale.
 *
 * Reads the global `helpdesktranslate.auto_translate_incoming` setting (admin
 * panel toggle) so the feature can be turned off without code changes. When
 * the customer's language is unknown we run language detection on the first
 * message and stamp `customer.language` so subsequent calls skip detection.
 */
class TranslateIncomingMessage implements ShouldQueue
{
    use InteractsWithQueue;
    use TranslatesMessage;

    public string $queue = 'helpdesk-events';

    public int $tries = 2;

    public int $backoff = 5;

    public function __construct(
        private readonly CachedTranslator $translator,
    ) {}

    public function handle(ConversationMessageCreated $event): void
    {
        if (! $this->passesCommonGuards($event, 'helpdesktranslate.auto_translate_incoming', ['translated_body', 'source_locale'])) {
            return;
        }

        $item = $event->item;
        $conversation = $item->conversation;

        if ($item->user_id !== null) {
            return; // Outbound (agent) messages skip auto-translation.
        }

        $body = $this->translatableBody($item);
        if ($body === null) {
            return;
        }

        $agentLocale = $this->agentLocale();
        $sourceLocale = $this->resolveCustomerLanguage($conversation, $body);

        if (! $sourceLocale || $this->localesMatch($sourceLocale, $agentLocale)) {
            return;
        }

        // El cupo diario (compartido con el resto de traducciones
        // automáticas) se comprueba y descuenta DENTRO de translate(), solo
        // en el cache-miss real contra el proveedor — así un mensaje
        // repetido que ya está en caché no cuenta contra el cupo.
        $translated = $this->translator->translate($body, $agentLocale, $sourceLocale, feature: 'auto_incoming');

        // El proveedor devolvió el texto SIN cambios pese a pedir source !=
        // target — señal fuerte de que $sourceLocale estaba mal detectado
        // (no de que el mensaje ya estuviera en el idioma del agente: ese
        // caso ya se filtra arriba con localesMatch()). Se vuelve a detectar
        // ignorando el valor guardado para no dejar al cliente atascado con
        // un idioma erróneo en cada mensaje futuro.
        if ($translated === $body) {
            $this->redetectCustomerLanguage($conversation, $body, $sourceLocale);

            return;
        }

        if ($translated) {
            $item->forceFill([
                'translated_body' => $translated,
                'source_locale' => $sourceLocale,
            ])->saveQuietly();
        }
    }

    public function failed(ConversationMessageCreated $event, Throwable $exception): void
    {
        Log::warning('TranslateIncomingMessage failed', [
            'item_id' => $event->item?->id,
            'conversation_id' => $event->item?->conversation_id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveCustomerLanguage($conversation, string $body): ?string
    {
        $customer = $conversation->customer;
        $stored = $customer?->language ? strtolower($customer->language) : null;

        // Una detección YA confirmada (language_detected_at no nulo) se
        // respeta tal cual, incluso cuando el valor es 'es' — antes no se
        // distinguía "es" sembrado por defecto de "es" confirmado por
        // detectViaProvider(), así que un cliente hispanohablante volvía a
        // pagar una detección completa en CADA mensaje entrante para siempre
        // (detectar 'es' nunca cambiaba el valor guardado, así que nunca se
        // marcaba como "ya resuelto").
        if ($stored && $customer->language_detected_at !== null) {
            return $stored;
        }

        // The customers table seeds 'es' as default — treat that as "unknown"
        // until we actually detect a real language from the first message.
        if ($stored && $stored !== 'es') {
            return $stored;
        }

        $detected = $this->translator->detectLanguage($body, feature: 'auto_incoming');
        if (! $detected) {
            return $stored; // fallback to whatever we had (likely 'es')
        }

        if ($customer) {
            $this->updateCustomerLanguage($conversation, $customer, $detected, $stored);
        }

        return $detected;
    }

    /**
     * Cuando translate() devuelve el texto sin cambios, se vuelve a detectar
     * el idioma IGNORANDO el valor guardado (a diferencia de
     * resolveCustomerLanguage(), que no vuelve a preguntar si ya hay un
     * valor confirmado) — precisamente porque ese valor confirmado es
     * sospechoso de estar mal. Si la redetección da algo distinto, se
     * corrige el cliente y se reutiliza el mismo broadcast
     * CustomerLanguageDetected que dispara una detección normal, para que el
     * panel pueda ofrecer la corrección manual con el mecanismo ya existente.
     */
    private function redetectCustomerLanguage($conversation, string $body, ?string $staleLocale): void
    {
        $customer = $conversation->customer;
        if (! $customer) {
            return;
        }

        $redetected = $this->translator->detectLanguage($body, feature: 'auto_incoming');
        if (! $redetected || $redetected === $staleLocale) {
            return;
        }

        $this->updateCustomerLanguage($conversation, $customer, $redetected, $staleLocale);
    }

    private function updateCustomerLanguage($conversation, $customer, string $detected, ?string $previous): void
    {
        $customer->forceFill([
            'language' => $detected,
            'language_detected_at' => now(),
        ])->saveQuietly();

        if ($detected === $previous) {
            return;
        }

        try {
            broadcast(new CustomerLanguageDetected($conversation, $detected))->toOthers();
        } catch (Throwable) {
            // Broadcasting may fail when the queue connection is offline;
            // a missed widget update is acceptable, the language stays
            // persisted on the customer row.
        }
    }
}
