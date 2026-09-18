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

    /*
     * Circuit breaker (por proveedor). El path manual síncrono encadena DeepL
     * (timeout 15s) + fallback LibreTranslate (10s) → hasta ~25s por request
     * cuando ambos están caídos. Tras N fallos consecutivos de un proveedor se
     * corta ese proveedor durante `circuit_open_seconds` para que el fallback
     * dispare al instante (o se devuelva null sin colgar si ambos están caídos).
     */
    'circuit_failure_threshold' => env('HELPDESK_TRANSLATE_CIRCUIT_FAILURE_THRESHOLD', 3),
    'circuit_open_seconds' => env('HELPDESK_TRANSLATE_CIRCUIT_OPEN_SECONDS', 60),

    /*
     * Retención (días) de la caché de traducciones. La tabla guarda el texto
     * original y traducido, que puede contener PII del cliente; al estar
     * deduplicada por hash (sin customer_id) no puede purgarse por cliente en un
     * borrado GDPR hard. En su lugar se limita la retención: las entradas sin uso
     * en este nº de días se podan a diario (helpdesktranslate:prune-cache),
     * acotando la vida de la PII sin perder las frases recurrentes (cada acierto
     * refresca last_used_at, así que lo común sobrevive y lo único/PII caduca).
     */
    'cache_retention_days' => (int) env('HELPDESK_TRANSLATE_CACHE_RETENTION_DAYS', 90),

    /*
    | Cupo de caracteres traducidos por usuario y día. El throttle por minuto
    | no acota el volumen total contra el provider (coste). 0 = sin límite.
    */
    'daily_char_limit' => (int) env('HELPDESK_TRANSLATE_DAILY_CHAR_LIMIT', 200000),

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
