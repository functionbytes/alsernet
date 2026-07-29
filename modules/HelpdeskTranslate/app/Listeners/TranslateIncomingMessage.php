<?php

namespace Modules\HelpdeskTranslate\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Events\CustomerLanguageDetected;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskTranslate\Concerns\TranslatesMessage;
use Modules\HelpdeskTranslate\Events\ItemTranslated;
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
        if (($event->item ?? null) !== null) {
            $this->translateItem($event->item);
        }
    }

    /**
     * Translate an incoming item in place. Called synchronously by the
     * channel processors (WhatsApp/Facebook/Instagram/simulator) BEFORE they
     * broadcast ConversationMessageCreated, so the bubble paints already
     * translated on first render instead of a few seconds later. handle()
     * above still exists as a queued fallback for any creation path that
     * doesn't call this directly (email, etc).
     */
    public function translateItem(ConversationItem $item): void
    {
        if (filled($item->translated_body)) {
            return; // Already translated (e.g. the sync call already ran).
        }

        if (! $this->passesCommonGuards($item, 'helpdesktranslate.auto_translate_incoming', ['translated_body', 'source_locale'])) {
            return;
        }

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

        $translated = $this->translator->translate($body, $agentLocale, $sourceLocale, feature: 'auto_incoming');

        if ($translated && $translated !== $body) {
            $item->forceFill([
                'translated_body' => $translated,
                'source_locale' => $sourceLocale,
            ])->saveQuietly();

            broadcast(new ItemTranslated($item->id, $item->conversation_id, 'translated_body', $translated));
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

        // The customers table seeds 'es' as default — treat that as "unknown"
        // until we actually detect a real language from the first message.
        if ($stored && $stored !== 'es') {
            return $stored;
        }

        // 'es' is ambiguous: could be the seeded default (never checked) or a
        // customer genuinely confirmed to write in Spanish. Since a confirmed
        // 'es' would never re-persist (detected === stored, the write-guard
        // below skips it), without this cache flag every single message from
        // a Spanish-speaking customer re-hits the DeepL detection endpoint
        // forever, burning quota for a result that's always discarded anyway.
        $confirmedEsKey = $customer ? "helpdesk:translate:es-confirmed:{$customer->id}" : null;
        if ($stored === 'es' && $confirmedEsKey && Cache::has($confirmedEsKey)) {
            return 'es';
        }

        $detected = $this->translator->detectLanguage($body, feature: 'auto_incoming');
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

        if ($detected === 'es' && $confirmedEsKey) {
            Cache::forever($confirmedEsKey, true);
        }

        return $detected;
    }
}
