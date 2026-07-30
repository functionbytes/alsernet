<?php

namespace Modules\HelpdeskTranslate\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\HelpdeskTranslate\Concerns\TranslatesMessage;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Throwable;

/**
 * Auto-translate outgoing agent messages to the customer's detected language.
 *
 * When an agent writes in their configured language and the customer's
 * language differs, the translated version is stored in `outgoing_translated_body`
 * so the widget can display the translation instead of the original.
 *
 * Escucha ConversationMessageCreated: es el "conversation item creado"
 * genérico del Helpdesk (no existe un evento outbound dedicado) y transporta
 * también respuestas de agente; ver nota en TranslatesMessage.
 */
class TranslateOutgoingMessage implements ShouldQueue
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
        if (! $this->passesCommonGuards($event, 'helpdesktranslate.auto_translate_outgoing', ['outgoing_translated_body', 'outgoing_target_locale'])) {
            return;
        }

        $item = $event->item;
        $conversation = $item->conversation;

        // Only translate agent (outbound) messages, not customer messages or internal notes.
        if ($item->user_id === null || $item->is_internal) {
            return;
        }

        $body = $this->translatableBody($item);
        if ($body === null) {
            return;
        }

        $customer = $conversation->customer;
        $customerLang = $customer?->language ? strtolower($customer->language) : null;

        if (! $customerLang) {
            return;
        }

        $agentLocale = $this->agentLocale();

        // If the customer reads in the same language the agent writes in, no
        // translation is needed. This also covers the case where the customer
        // language was seeded as the agent's default (e.g. both 'es').
        if ($this->localesMatch($customerLang, $agentLocale)) {
            return;
        }

        $translated = $this->translator->translate($body, $customerLang, $agentLocale, feature: 'auto_outgoing');

        if ($translated && $translated !== $body) {
            $item->forceFill([
                'outgoing_translated_body' => $translated,
                'outgoing_target_locale' => $customerLang,
            ])->saveQuietly();
        }
    }

    public function failed(ConversationMessageCreated $event, Throwable $exception): void
    {
        Log::warning('TranslateOutgoingMessage failed', [
            'item_id' => $event->item?->id,
            'conversation_id' => $event->item?->conversation_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
