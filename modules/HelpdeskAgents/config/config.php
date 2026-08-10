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
    |
    | Bajado de 100 a 20 (revisión de rendimiento, ago-2026): con 100, cada
    | turno reenvía el historial COMPLETO de sesiones largas — el coste/
    | latencia de la llamada al proveedor crece linealmente con la duración
    | de la sesión (turno 50 podía costar ~10× más tokens de entrada que el
    | turno 5). 20 mensajes (~10 intercambios) es un contexto conversacional
    | razonable para la mayoría de flujos de soporte sin ese crecimiento sin
    | límite; ajustable por instalación si un flujo concreto necesita más.
    */
    'history_window' => 20,

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
    | Ticket AI enrichment (summaries / classification / language routing)
    |--------------------------------------------------------------------------
    | Background AI features hooked into HelpdeskTickets events. All of them
    | run in queued jobs and fail silently — a broken/unconfigured LLM never
    | affects ticket flows.
    |
    | - summaries_enabled: AI summary as internal note on assign/escalate.
    |   Effectively inert until a default AiAgent with API key is configured.
    | - auto_classification: MUST default to FALSE ("do no harm") — the LLM
    |   suggests category (closed list from DB) + priority and only applies
    |   them above classification_min_confidence, logging it in the history.
    | - language_detection: stamps helpdesk_tickets.detected_language using
    |   HelpdeskTranslate's detector; a no-op if LibreTranslate is not set up.
    | - context_*: cost caps for the text sent to the LLM.
    */
    'ticket_ai' => [
        'summaries_enabled' => env('HELPDESKAGENTS_TICKET_SUMMARIES', true),
        'summary_cooldown_minutes' => 10,
        'auto_classification' => env('HELPDESKAGENTS_TICKET_AUTO_CLASSIFICATION', false),
        'classification_min_confidence' => 0.75,
        'language_detection' => env('HELPDESKAGENTS_TICKET_LANGUAGE_DETECTION', true),
        'context_max_items' => 10,
        'context_max_bytes' => 8192,
        'context_max_item_chars' => 1500,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI usage ledger + daily spend cap (observabilidad de coste)
    |--------------------------------------------------------------------------
    | Cada llamada LLM (AgentLlmService: summary/classification; flujo
    | conversacional: chatflow) se registra en helpdesk_ai_usage — proveedor,
    | modelo, tokens in/out, duración y éxito/fallo. Consulta con
    | `php artisan helpdesk:ai-usage`.
    |
    | daily_max_calls / daily_max_tokens: presupuesto diario para las
    | completions en background de AgentLlmService. 0 = sin límite. Al
    | superarlo el servicio devuelve null con log (fail-silent, coherente con
    | su filosofía): las features de IA se degradan, los tickets nunca.
    */
    'ai_usage' => [
        'enabled' => env('HELPDESKAGENTS_AI_USAGE_TRACKING', true),
        'daily_max_calls' => (int) env('HELPDESKAGENTS_AI_DAILY_MAX_CALLS', 0),
        'daily_max_tokens' => (int) env('HELPDESKAGENTS_AI_DAILY_MAX_TOKENS', 0),
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
