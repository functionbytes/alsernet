<?php

return [
    'name' => 'HelpdeskTranslate',

    'provider' => env('HELPDESK_TRANSLATE_PROVIDER', 'deepl'),

    'default_target' => env('HELPDESK_TRANSLATE_DEFAULT_TARGET', 'es'),

    /*
     * When true, the TranslateIncomingMessage listener auto-translates every
     * incoming visitor message into the agent's preferred language. When
     * false, agents must trigger translation manually from the chat thread.
     */
    'auto_translate_incoming' => env('HELPDESK_TRANSLATE_AUTO', false),

    /*
     * When true, the TranslateOutgoingMessage listener auto-translates every
     * outgoing agent message into the customer's detected language. When
     * false, outgoing translation is disabled regardless of customer language.
     */
    'auto_translate_outgoing' => env('HELPDESK_TRANSLATE_AUTO_OUTGOING', false),

    'deepl' => [
        'key' => env('DEEPL_API_KEY', ''),
        'url' => env('DEEPL_API_URL', 'https://api-free.deepl.com'),
    ],

    /*
     * Allowlist of DeepL base URLs accepted by the settings form. The DeepL
     * API key is sent as an `Authorization` header to this base URL, so an
     * arbitrary host would allow credential exfiltration / SSRF. Only the two
     * official DeepL endpoints (Free and Pro) are permitted.
     */
    'deepl_allowed_urls' => [
        'https://api-free.deepl.com',
        'https://api.deepl.com',
    ],

    /*
     * LibreTranslate is the secondary provider used by the auto-translate
     * listener (TranslateIncomingMessage). Leaving the endpoint empty puts
     * the service in mock mode (returns "[xx] text" without an HTTP call).
     */
    'libretranslate' => [
        'endpoint' => env('LIBRETRANSLATE_ENDPOINT', ''),
        'api_key' => env('LIBRETRANSLATE_API_KEY', ''),
    ],

    /*
     * Languages shown in the translation panel and accepted as `target` /
     * `default_target` across the UI. Keep ISO 639-1 codes (lowercase).
     */
    'supported_languages' => ['es', 'en', 'fr', 'de', 'pt', 'it'],

    /*
     * Languages accepted as `from` when the caller knows the source language
     * upfront. Mirrors DeepL's accepted source codes; LibreTranslate accepts
     * the same subset so this list is safe for both providers.
     */
    'source_languages' => [
        'ar', 'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi',
        'fr', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv', 'nb', 'nl',
        'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh',
    ],
];
