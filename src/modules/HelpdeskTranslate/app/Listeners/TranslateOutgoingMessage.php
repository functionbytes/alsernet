<?php

namespace Modules\HelpdeskTranslate\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\HelpdeskTranslate\Concerns\TranslatesMessage;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Throwable;

/**
 * Auto-translate outgoing agent messages to the customer's detected language.
 *
 * When an agent writes in their configured language and the customer's
 * language differs, the translated version is stored in `outgoing_translated_body`
 * so the widget can display the translation instead of the original.
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

    public function handle(MessageReceived $event): void
    {
        if (! $this->settingValue('helpdesktranslate.auto_translate_outgoing')) {
            return;
        }

        if (! $this->columnsExist()) {
            return;
        }

        $item = $event->message ?? null;
        $conversation = $event->conversation ?? null;

        if (! $item || ! $conversation) {
            return;
        }

        // Only translate agent (outbound) messages, not customer messages or internal notes.
        if ($item->user_id === null || $item->is_internal) {
            return;
        }

        $body = trim((string) ($item->body ?? ''));
        if ($body === '' || mb_strlen($body) < 3) {
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

        $translated = $this->translator->translate($body, $customerLang, $agentLocale);

        if ($translated && $translated !== $body) {
            $item->forceFill([
                'outgoing_translated_body' => $translated,
                'outgoing_target_locale' => $customerLang,
            ])->saveQuietly();
        }
    }

    public function failed(MessageReceived $event, Throwable $exception): void
    {
        Log::warning('TranslateOutgoingMessage failed', [
            'item_id' => $event->message?->id,
            'conversation_id' => $event->conversation?->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private static ?bool $columnsExist = null;

    private function columnsExist(): bool
    {
        if (self::$columnsExist === null) {
            self::$columnsExist = Schema::connection('helpdesk')->hasColumn('helpdesk_conversation_items', 'outgoing_translated_body')
                && Schema::connection('helpdesk')->hasColumn('helpdesk_conversation_items', 'outgoing_target_locale');
        }

        return self::$columnsExist;
    }
}
