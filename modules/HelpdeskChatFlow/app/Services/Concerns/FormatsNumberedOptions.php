<?php

namespace Modules\HelpdeskChatFlow\Services\Concerns;

/**
 * Standardizes interactive options as a numbered list ("1. X / 2. Y") so the
 * same flow works on every channel — WhatsApp, Messenger, Instagram, email and
 * web — without depending on each channel's native button limits. The customer
 * can reply with the number or the exact option text.
 */
trait FormatsNumberedOptions
{
    /**
     * @param  array<int, string>  $options
     */
    protected function numberedList(array $options): string
    {
        $options = array_values($options);

        return implode("\n", array_map(
            fn ($i, $opt) => ($i + 1).'. '.$opt,
            array_keys($options),
            $options,
        ));
    }

    /**
     * Build the full prompt: header + numbered options + reply hint.
     *
     * @param  array<int, string>  $options
     */
    protected function numberedPrompt(string $header, array $options, string $hint = 'Responde con el número de la opción.'): string
    {
        $header = trim($header);
        $list = $this->numberedList($options);

        return trim($header."\n\n".$list."\n\n".$hint);
    }

    /**
     * Resolve a user reply (a number, or the exact option text) to the option
     * text. Returns null when nothing matches.
     *
     * @param  array<int, string>  $options
     */
    protected function resolveNumberedChoice(string $input, array $options): ?string
    {
        $options = array_values($options);
        $input = trim($input);

        if (is_numeric($input)) {
            $index = (int) $input - 1;

            return $options[$index] ?? null;
        }

        foreach ($options as $option) {
            if (mb_strtolower(trim((string) $option)) === mb_strtolower($input)) {
                return $option;
            }
        }

        return null;
    }
}
