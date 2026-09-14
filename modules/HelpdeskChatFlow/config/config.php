<?php

return [
    'name' => 'HelpdeskChatFlow',

    /*
    |--------------------------------------------------------------------------
    | Retención de sesiones
    |--------------------------------------------------------------------------
    | Días que se conservan las sesiones ya terminadas (completed/failed/
    | transferred/abandoned) antes de purgarlas junto a sus executions
    | (chatflow:prune-sessions, diario). 0 o menos desactiva la limpieza.
    */
    'session_retention_days' => (int) env('HELPDESKCHATFLOW_SESSION_RETENTION_DAYS', 90),

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

    /*
    |--------------------------------------------------------------------------
    | Flow import
    |--------------------------------------------------------------------------
    | Cota de nodos al importar un flow (además del límite de bytes del Form
    | Request) para no degradar el validador ni el editor React.
    */
    'import' => [
        'max_nodes' => (int) env('CHATFLOW_IMPORT_MAX_NODES', 500),
    ],

    /*
     * Human labels for document-request keys, shown to the customer in the
     * document-collection node. Centralised here so they can be edited without
     * touching the engine (fallback is the raw key).
     */
    'document_labels' => [
        'dni_frontal' => 'DNI/NIE frontal',
        'dni_trasera' => 'DNI/NIE trasera',
        'pasaporte' => 'Pasaporte',
        'contrato' => 'Contrato firmado',
        'factura' => 'Factura de compra',
        'foto_producto' => 'Foto del producto',
        'proforma' => 'Factura proforma',
        'iban' => 'Certificado bancario / IBAN',
        'selfie' => 'Selfie con documento',
        'recibo' => 'Recibo',
    ],
];
