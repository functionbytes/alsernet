<?php

return [
    'name' => 'HelpdeskChatFlow',

    /*
    |--------------------------------------------------------------------------
    | AI response node
    |--------------------------------------------------------------------------
    | Defaults for the `ai_response` node (RAG over the help center + LLM).
    */
    'ai' => [
        'model' => env('CHATFLOW_AI_MODEL', 'gpt-4o-mini'),
        'temperature' => (float) env('CHATFLOW_AI_TEMPERATURE', 0.3),
        'max_tokens' => (int) env('CHATFLOW_AI_MAX_TOKENS', 500),
        'kb_results' => (int) env('CHATFLOW_AI_KB_RESULTS', 4),
        'min_similarity' => (float) env('CHATFLOW_AI_MIN_SIMILARITY', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP request node
    |--------------------------------------------------------------------------
    | Safety bounds for the generic `http_request` (webhook) node.
    */
    'http' => [
        'timeout' => (int) env('CHATFLOW_HTTP_TIMEOUT', 10),
        'max_response_chars' => (int) env('CHATFLOW_HTTP_MAX_RESPONSE', 20000),
        'block_private_hosts' => (bool) env('CHATFLOW_HTTP_BLOCK_PRIVATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice note transcription
    |--------------------------------------------------------------------------
    | Safety bound for downloading inbound voice notes before Whisper transcription.
    */
    'voice' => [
        'max_bytes' => (int) env('CHATFLOW_VOICE_MAX_BYTES', 26214400), // 25 MB
    ],
];
