<?php

return [
    'name' => 'HelpdeskAgents',

    /*
    |--------------------------------------------------------------------------
    | AI Runtime Master Switch
    |--------------------------------------------------------------------------
    | Gates whether the AI agent runtime is wired into the Helpdesk conversation
    | flow (StartAiAgentSessionJob dispatched on inbound customer messages).
    | MUST default to FALSE: the infrastructure is wired but AI never runs in
    | production unless explicitly enabled per deploy ("do no harm").
    */
    'enabled' => env('HELPDESKAGENTS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Local LLM allowed hosts (SSRF allowlist)
    |--------------------------------------------------------------------------
    | Hosts (hostname o IP, separados por coma en el env) a los que puede
    | apuntar la base_url del proveedor "local" (Ollama). Vacío = cualquier
    | host privado/loopback (comportamiento histórico); link-local/metadata
    | queda bloqueado siempre. Defínelo en producción para impedir que un
    | admin escanee la red interna vía el test de conexión.
    */
    'local_llm_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HELPDESKAGENTS_LOCAL_LLM_ALLOWED_HOSTS', ''))
    ))),

    'llm_rate_limits' => [
        'per_user_per_minute' => 10,
        'per_session_per_5min' => 30,
        'per_user_per_day' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | History Window
    |--------------------------------------------------------------------------
    | Ventana deslizante de historial enviada al LLM por turno: se toman los N
    | mensajes MÁS RECIENTES de la sesión (no los primeros), para que las
    | conversaciones largas no pierdan el contexto reciente.
    */
    'history_window' => 100,

    'prompt_injection_patterns' => [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/system\s+prompt/i',
        '/reveal\s+your\s+(system\s+)?prompt/i',
        '/you\s+are\s+(now\s+)?a/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Providers
    |--------------------------------------------------------------------------
    | Keyed by provider slug. Each entry defines the available models array
    | that the AgentSettingsController and views iterate over.
    */
    'providers' => [
        'openai' => [
            'label' => 'OpenAI',
            'models' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
        ],
        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'models' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
                'claude-3-opus-20240229' => 'Claude 3 Opus',
            ],
        ],
        'gemini' => [
            'label' => 'Google Gemini',
            'models' => [
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
        ],
        'local' => [
            'label' => 'Local Model',
            'models' => [
                'llama3' => 'Llama 3',
                'mistral' => 'Mistral',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddings
    |--------------------------------------------------------------------------
    */
    'embeddings' => [
        'api_key' => env('HELPDESKAGENTS_EMBEDDING_API_KEY', null),
        'model' => env('HELPDESKAGENTS_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'timeout' => 30,
        'top_k' => 5,
        'min_similarity' => 0.75,
        // Techo de docs puntuados por búsqueda de similaridad (los más
        // recientes primero). El coseno se calcula en PHP — sin este límite
        // el coste crece linealmente con el corpus del agente.
        'max_candidates' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Execution
    |--------------------------------------------------------------------------
    | allow_api is FALSE by default (SSRF risk). Enable explicitly per deploy.
    | allowed_hosts: HTTPS allowlist for API tools. Empty = all blocked.
    | allowed_tables: table allowlist for database tools. Empty = all blocked
    | (fail-closed) — even with allow_database on, a SELECT can only read the
    | tables listed here, never `users`/`password_reset_tokens`/etc.
    */
    'tools' => [
        'allow_api' => env('HELPDESKAGENTS_TOOLS_ALLOW_API', false),
        'api_timeout' => 15,
        'allow_database' => env('HELPDESKAGENTS_TOOLS_ALLOW_DATABASE', false),
        'allow_function' => env('HELPDESKAGENTS_TOOLS_ALLOW_FUNCTION', false),
        'allowed_functions' => [],
        'allowed_tables' => [],
        'allowed_hosts' => [],
    ],
];
