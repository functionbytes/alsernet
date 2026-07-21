<?php

namespace Modules\HelpdeskTranslate\Concerns;

use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Setting;

/**
 * Shared helpers for the auto-translation listeners.
 *
 * Centralises the comparison/setting lookups and the guard clauses that are
 * otherwise duplicated across TranslateIncomingMessage and
 * TranslateOutgoingMessage.
 *
 * Nota sobre el evento: ambos listeners escuchan MessageReceived porque en
 * este proyecto ese evento es en la práctica "conversation item creado"
 * (broadcast `message.received` hacia el widget) y transporta tanto mensajes
 * del cliente como respuestas del agente — no existe un evento MessageSent
 * de salida en Modules\Helpdesk\Events. La dirección se distingue por
 * `$item->user_id` (null = cliente entrante, con valor = agente saliente).
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
    protected function passesCommonGuards(MessageReceived $event, string $toggleKey, array $requiredColumns): bool
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

        return ($event->message ?? null) !== null && ($event->conversation ?? null) !== null;
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
     * Static so both listeners reuse the same lookup within the same process
     * slot. Reset between jobs by Horizon's forked worker model.
     *
     * @var array<string, mixed>
     */
    private static array $settingCache = [];

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
        if (! array_key_exists($key, self::$settingCache)) {
            self::$settingCache[$key] = Setting::get($key);
        }

        $value = self::$settingCache[$key];

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
