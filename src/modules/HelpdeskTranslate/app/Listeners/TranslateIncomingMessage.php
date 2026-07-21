<?php

namespace Modules\HelpdeskTranslate\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\CustomerLanguageDetected;
use Modules\Helpdesk\Events\MessageReceived;
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

    public function handle(MessageReceived $event): void
    {
        if (! $this->passesCommonGuards($event, 'helpdesktranslate.auto_translate_incoming', ['translated_body', 'source_locale'])) {
            return;
        }

        $item = $event->message;
        $conversation = $event->conversation;

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

        $translated = $this->translator->translate($body, $agentLocale, $sourceLocale);

        if ($translated && $translated !== $body) {
            $item->forceFill([
                'translated_body' => $translated,
                'source_locale' => $sourceLocale,
            ])->saveQuietly();
        }
    }

    public function failed(MessageReceived $event, Throwable $exception): void
    {
        Log::warning('TranslateIncomingMessage failed', [
            'item_id' => $event->message?->id,
            'conversation_id' => $event->conversation?->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveCustomerLanguage($conversation, string $body): ?string
    {
        $customer = $conversation->customer;
        $stored = $customer?->language ? strtolower($customer->language) : null;

        // The customers table seeds 'es' as default — treat that as "unknown"
        // until we actually detect a real language from the first message.
        if ($stored && $stored !== 'es') {
            return $stored;
        }

        $detected = $this->translator->detectLanguage($body);
        if (! $detected) {
            return $stored; // fallback to whatever we had (likely 'es')
        }

        if ($customer && $detected !== $stored) {
            $customer->forceFill(['language' => $detected])->saveQuietly();

            try {
                broadcast(new CustomerLanguageDetected(
                    $conversation,
                    $detected
                ))->toOthers();
            } catch (Throwable) {
                // Broadcasting may fail when the queue connection is offline;
                // a missed widget update is acceptable, the language stays
                // persisted on the customer row.
            }
        }

        return $detected;
    }
}
