<?php

return [

    'sidebar' => [
        'section' => 'Helpdesk · Translations',
        'item' => 'Translations (DeepL)',
    ],

    'errors' => [
        'service_unavailable' => 'Translation service is unavailable.',
        'daily_quota_exceeded' => 'You have reached the daily translated-character limit. Please try again tomorrow.',
        'item_empty' => 'Item has no text to translate.',
        'no_api_key' => 'No API key configured.',
        'deepl_status' => 'DeepL responded with code :status.',
        'deepl_connect' => 'Error contacting DeepL: :message',
        'cache_clear_failed' => 'Could not clear the cache.',
    ],

    'success' => [
        'settings_saved' => 'Translation settings updated successfully.',
        'deepl_ok' => 'Connection OK.',
        'cache_cleared' => 'Cache cleared: :count translations removed.',
    ],

    'settings' => [
        'page_title' => 'Translation settings',
        'card_title' => 'Translations (HelpdeskTranslate)',
        'card_description' => 'Configure the translation provider, DeepL API key and default target language.',
        'cache_section' => 'Local translation cache',
    ],

    'panel' => [
        'title' => 'Translate message',
        'mode_label' => 'Translation mode',
        'mode_incoming' => 'Translate incoming',
        'mode_outgoing' => 'Translate outgoing',
        'mode_both' => 'Bidirectional',
        'mode_incoming_desc' => 'Translate customer messages',
        'mode_outgoing_desc' => 'Translate agent messages',
        'mode_both_desc' => 'Bidirectional translation',
        'lang_from' => 'Source language',
        'lang_to' => 'Target language',
        'btn_disable' => 'Disable',
        'btn_enable' => 'Enable translation',
    ],

    'composer' => [
        'tab' => 'Translate',
    ],

    'thread' => [
        'btn_translate' => 'Translate',
        'btn_translate_title' => 'Translate this message',
        'js_translated' => 'Message translated',
        'js_error' => 'Could not translate.',
        'js_from' => 'Translated from :lang',
    ],

    'languages' => [
        'auto' => 'Auto-detect',
        'es' => 'Spanish',
        'en' => 'English',
        'fr' => 'French',
        'de' => 'German',
        'pt' => 'Portuguese',
        'it' => 'Italian',
    ],

    'validation' => [
        'text_required' => 'The text to translate is required.',
        'text_max' => 'The text cannot exceed 2000 characters.',
        'from_in' => 'The source language is not valid.',
        'to_required' => 'The target language is required.',
        'to_regex' => 'The target language is not valid (use an ISO code like `es` or `en-US`).',
        'target_required' => 'The target language is required.',
        'target_regex' => 'The target language is not valid (use an ISO code like `es` or `en-US`).',
        'provider_required' => 'The translation provider is required.',
        'provider_in' => 'The selected provider is not valid.',
        'default_target_required' => 'The default target language is required.',
        'default_target_in' => 'The selected target language is not supported.',
        'deepl_key_max' => 'The DeepL API key cannot exceed 255 characters.',
        'deepl_url_in' => 'The DeepL URL must be one of the official endpoints (Free or Pro).',
        'libretranslate_endpoint_url' => 'The LibreTranslate endpoint must be a valid http or https URL.',
        'libretranslate_endpoint_unsafe' => 'The LibreTranslate endpoint cannot point to an internal or private network address.',
        'detect_text_required' => 'The text is required.',
        'detect_text_max' => 'The text cannot exceed 2000 characters.',
        'usage_from_date' => 'The "from" date is not valid.',
        'usage_to_date' => 'The "to" date is not valid.',
        'usage_to_after_from' => 'The "to" date must be on or after the "from" date.',
    ],

    'attributes' => [
        'text' => 'text',
        'from' => 'source language',
        'to' => 'target language',
        'target' => 'target language',
        'provider' => 'provider',
        'default_target' => 'default target language',
        'auto_translate_incoming' => 'auto-translate incoming',
        'auto_translate_outgoing' => 'auto-translate outgoing',
        'deepl_key' => 'DeepL API key',
        'deepl_url' => 'DeepL URL',
        'libretranslate_endpoint' => 'LibreTranslate endpoint',
        'usage_from' => 'from date',
        'usage_to' => 'to date',
    ],

];
