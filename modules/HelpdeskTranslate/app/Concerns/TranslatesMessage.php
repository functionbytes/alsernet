<?php

namespace Modules\HelpdeskTranslate\Concerns;

use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\Setting;

/**
 * Shared helpers for the auto-translation listeners.
 *
 * Centralises the comparison/setting lookups and the guard clauses that are
 * otherwise duplicated across TranslateIncomingMessage and
 * TranslateOutgoingMessage.
 *
 * Nota sobre el evento: ambos listeners escuchan ConversationMessageCreated,
 * el evento que TODOS los canales disparan al crear un conversation item
 * (WhatsApp/redes via InboundMessageIngestor, widget de live chat, respuestas
 * de agente) — no existe un evento MessageSent de salida separado en
 * Modules\Helpdesk\Events. La dirección se distingue por `$item->user_id`
 * (null = cliente entrante, con valor = agente saliente).
 */
trait TranslatesMessage
{
    /**
     * Per-class memoization of the schema check (one entry per listener so a
     * trait-level cache can't leak the incoming columns' result to the
     * outgoing listener or vice versa).
     *
     * @var array<class-string, bool>
     */
    private static array $columnsExistCache = [];

    /**
     * Common guard clauses both listeners run before doing any work:
     * feature enabled, per-direction toggle on, translation columns migrated,
     * and the event actually carrying a message + conversation.
     *
     * @param  array<int, string>  $requiredColumns  columns on helpdesk_conversation_items
     */
    protected function passesCommonGuards(ConversationMessageCreated $event, string $toggleKey, array $requiredColumns): bool
    {
        if (! helpdesk_translate_enabled()) {
            return false;
        }

        if (! $this->settingValue($toggleKey)) {
            return false;
        }

        if (! $this->columnsExist($requiredColumns)) {
            return false;
        }

        return ($event->item ?? null) !== null && $event->item->conversation !== null;
    }

    /**
     * Trimmed message body, or null when it is too short to be worth
     * translating (same `< 3 chars` heuristic both listeners used).
     */
    protected function translatableBody(object $item): ?string
    {
        $body = trim((string) ($item->body ?? ''));

        return ($body === '' || mb_strlen($body) < 3) ? null : $body;
    }

    /**
     * Whether the listener's translation columns exist on
     * helpdesk_conversation_items. Memoized per listener class per process.
     *
     * @param  array<int, string>  $columns
     */
    protected function columnsExist(array $columns): bool
    {
        if (! array_key_exists(static::class, self::$columnsExistCache)) {
            $schema = Schema::connection('helpdesk');

            $exists = true;
            foreach ($columns as $column) {
                if (! $schema->hasColumn('helpdesk_conversation_items', $column)) {
                    $exists = false;
                    break;
                }
            }

            self::$columnsExistCache[static::class] = $exists;
        }

        return self::$columnsExistCache[static::class];
    }

    /**
     * Per-job memoization of Setting::get() results.
     *
     * Per-INSTANCE (sin static): los listeners se instancian una vez por job,
     * así que una propiedad de instancia ya evita SELECTs repetidos dentro del
     * mismo job. Un static aquí se filtraba entre jobs en `queue:work` daemon
     * (el proceso no se recicla por job) y entre tests: un toggle memoizado
     * como false hacía que cambios posteriores del setting nunca se releyeran.
     *
     * @var array<string, mixed>
     */
    private array $settingCache = [];

    /**
     * Compare two locale strings using their first two characters, so values
     * like "en", "EN", "en-US" or "english" still match correctly.
     */
    protected function localesMatch(string $a, string $b): bool
    {
        return strtolower(substr($a, 0, 2)) === strtolower(substr($b, 0, 2));
    }

    /**
     * Resolve a Setting value falling back to config when the stored value
     * is missing OR an explicit empty string (operators may "clear" a setting
     * by saving '' — without this helper the `??` operator skips the fallback).
     *
     * Results are memoized for the duration of the job to avoid repeated
     * SELECT queries for settings that almost never change mid-job.
     */
    protected function settingValue(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->settingCache)) {
            $this->settingCache[$key] = Setting::get($key);
        }

        $value = $this->settingCache[$key];

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Locale the agent is configured to read in (admin panel setting wins,
     * module config is the fallback, then 'es' if nothing is set).
     */
    protected function agentLocale(): string
    {
        return (string) $this->settingValue(
            'helpdesktranslate.default_target',
            config('helpdesktranslate.default_target', 'es')
        );
    }
}
