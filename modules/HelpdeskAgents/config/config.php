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
            'supports_tools' => true,
            // OJO: esta lista se escribio con los modelos vigentes en 2024 y
            // OpenAI retira identificadores con el tiempo. Antes de fijar uno
            // nuevo aqui, confirmalo contra `GET https://api.openai.com/v1/models`
            // con la API key real — nunca de memoria.
            'models' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ],
        ],
        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'supports_tools' => true,
            'models' => [
                'claude-opus-5' => 'Claude Opus 5',
                'claude-sonnet-5' => 'Claude Sonnet 5',
                'claude-haiku-4-5' => 'Claude Haiku 4.5',
            ],
        ],
        'gemini' => [
            'label' => 'Google Gemini',
            // Sin tool-calling: AgentLlmService::chatWithTools() degrada a una
            // completion normal (sin herramientas) cuando el agente por
            // defecto es Gemini. Ver la nota en ese servicio.
            'supports_tools' => false,
            'models' => [
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
        ],
        'local' => [
            'label' => 'Local Model',
            'supports_tools' => false,
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
    |   HelpdeskTranslate's CachedTranslator — el mismo punto de entrada que usa
    |   el resto del helpdesk, con el proveedor configurado (DeepL por defecto),
    |   su fallback, su circuit breaker, su cupo y su cache.
    | - context_*: cost caps for the text sent to the LLM.
    */
    'ticket_ai' => [
        'summaries_enabled' => env('HELPDESKAGENTS_TICKET_SUMMARIES', true),
        'summary_cooldown_minutes' => 10,
        'auto_classification' => env('HELPDESKAGENTS_TICKET_AUTO_CLASSIFICATION', false),
        'classification_min_confidence' => 0.75,
        'language_detection' => env('HELPDESKAGENTS_TICKET_LANGUAGE_DETECTION', true),
        // ULTIMO recurso de deteccion de idioma, no el segundo: solo entra
        // cuando NINGUN proveedor de traduccion respondio (circuito abierto,
        // cupo agotado, ninguno configurado). Pedirle a un LLM que nombre un
        // idioma que DeepL ya devuelve gratis en su respuesta de traduccion
        // seria gasto tirado. Existe porque sin el, con los dos proveedores
        // caidos, DetectTicketLanguageJob es un no-op silencioso y
        // AssignmentService::preferLanguageSpeakers() se queda sin datos.
        'language_detection_llm_fallback' => env('HELPDESKAGENTS_TICKET_LANGUAGE_LLM_FALLBACK', true),
        /*
        | Extraccion de los campos personalizados de la categoria
        | (helpdesk_ticket_category_fields) desde el texto del ticket.
        |
        | A diferencia del sentimiento, que viaja gratis en el turno de
        | clasificacion, esto es una llamada propia: OFF por defecto. Solo se
        | gasta cuando la categoria define campos y siguen vacios, y nunca pisa
        | un valor escrito por una persona.
        */
        'field_extraction' => env('HELPDESKAGENTS_TICKET_FIELD_EXTRACTION', false),

        'context_max_items' => 10,
        'context_max_bytes' => 8192,
        'context_max_item_chars' => 1500,

        /*
        | Sugerencia de respuesta (TicketReplySuggestionService).
        |
        | SIEMPRE borrador: el texto generado se inserta en el composer para
        | que el agente lo revise, nunca se envia al cliente automaticamente.
        | No hay flag de auto-envio y no debe anadirse uno sin una decision
        | explicita de producto.
        |
        | - enabled: expone el boton "Sugerir respuesta" en la ficha.
        | - use_tools: permite al modelo consultar ERP/PrestaShop/contacto via
        |   las tools MCP. Con false, redacta solo con el hilo y las plantillas.
        | - max_templates: cuantas plantillas candidatas entran al prompt (el
        |   catalogo entero dispararia el coste por sugerencia).
        | - cache_minutes: 0 desactiva la cache. Se cachea por ticket+updated_at,
        |   asi que un mensaje nuevo invalida la sugerencia anterior.
        */
        'reply_suggestions' => [
            'enabled' => env('HELPDESKAGENTS_TICKET_REPLY_SUGGESTIONS', true),
            'use_tools' => env('HELPDESKAGENTS_TICKET_REPLY_TOOLS', true),
            'max_templates' => 8,
            'max_tokens' => 900,
            'cache_minutes' => 5,
        ],

        /*
        | Enrutado asistido (AiRoutingService + accion `ai_route` del
        | AutomationEngine). Reutiliza la categoria sugerida por la IA y el
        | idioma detectado para elegir agente con AssignmentService.
        | Por debajo del umbral el ticket se queda SIN asignar, en vez de
        | asignarse mal.
        */
        'routing' => [
            'enabled' => env('HELPDESKAGENTS_TICKET_AI_ROUTING', false),
            'min_confidence' => 0.75,
            'strategy' => env('HELPDESKAGENTS_TICKET_AI_ROUTING_STRATEGY', 'workload'), // workload|round_robin|skills
        ],
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
        // Por defecto YA NO es 0/ilimitado: el presupuesto diario es el unico
        // freno de gasto que existe, y dejarlo abierto convierte un bucle mal
        // cerrado en una factura. Estos numeros son un techo de seguridad
        // conservador, no una estimacion de uso — subelos por env cuando
        // `php artisan helpdesk:ai-usage` muestre el consumo real.
        'daily_max_calls' => (int) env('HELPDESKAGENTS_AI_DAILY_MAX_CALLS', 2000),
        'daily_max_tokens' => (int) env('HELPDESKAGENTS_AI_DAILY_MAX_TOKENS', 3000000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Similitud entre tickets (duplicados + incidencias masivas)
    |--------------------------------------------------------------------------
    | Indexa el texto de cada ticket como vector (helpdesk_ticket_embeddings)
    | para responder dos preguntas: "esto ya lo tenemos abierto" y "esto le
    | esta pasando a mucha gente a la vez".
    |
    | OFF por defecto: cada ticket nuevo cuesta una llamada al proveedor de
    | embeddings, asi que es la unica funcion de IA del modulo con un coste
    | proporcional al volumen de entrada. Necesita ademas
    | embeddings.api_key configurada.
    |
    | Umbrales: min_similarity es para el aviso de duplicado (mas exigente,
    | porque un falso positivo manda al agente a mirar un ticket ajeno);
    | incident_similarity agrupa la incidencia masiva. Ambos sobre coseno de
    | text-embedding-3-small, donde textos del mismo problema rondan 0.85-0.95.
    */
    'ticket_similarity' => [
        'enabled' => env('HELPDESKAGENTS_TICKET_SIMILARITY', false),

        // Ventana general de busqueda y techo de candidatos puntuados (el
        // coseno se calcula en PHP: sin techo, el coste crece con el histórico).
        'window_days' => (int) env('HELPDESKAGENTS_TICKET_SIMILARITY_WINDOW_DAYS', 30),
        'max_candidates' => (int) env('HELPDESKAGENTS_TICKET_SIMILARITY_MAX_CANDIDATES', 1500),
        'min_similarity' => (float) env('HELPDESKAGENTS_TICKET_SIMILARITY_MIN', 0.86),

        // Duplicados: ventana mas corta. Un ticket parecido de hace un mes es
        // un problema recurrente, no un duplicado.
        'duplicate_window_days' => (int) env('HELPDESKAGENTS_TICKET_DUPLICATE_WINDOW_DAYS', 14),

        // Busqueda semantica de tickets: ventana larga (el valor de buscar por
        // significado es encontrar el caso de hace ocho meses) y umbral mas
        // laxo que el de duplicados — aqui un resultado relacionado sigue
        // siendo util, mientras que un falso duplicado manda al agente a mirar
        // un ticket que no es.
        'search_window_days' => (int) env('HELPDESKAGENTS_TICKET_SEARCH_WINDOW_DAYS', 365),
        'search_min_similarity' => (float) env('HELPDESKAGENTS_TICKET_SEARCH_MIN', 0.72),

        // Incidencia masiva: cuantos tickets parecidos en cuanto tiempo dejan
        // de ser casualidad.
        'incident_window_minutes' => (int) env('HELPDESKAGENTS_TICKET_INCIDENT_WINDOW', 60),
        'incident_min_size' => (int) env('HELPDESKAGENTS_TICKET_INCIDENT_MIN_SIZE', 5),
        'incident_similarity' => (float) env('HELPDESKAGENTS_TICKET_INCIDENT_SIMILARITY', 0.88),
        'incident_max_tickets' => (int) env('HELPDESKAGENTS_TICKET_INCIDENT_MAX_TICKETS', 300),
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

    /*
    |--------------------------------------------------------------------------
    | Herramientas MCP (Modules\HelpdeskAgents\Mcp\Tools\*)
    |--------------------------------------------------------------------------
    | Distintas de la seccion `tools` de arriba: aquellas son tools que un
    | administrador CONFIGURA desde el panel (una URL, un SELECT, una funcion)
    | y por eso ToolExecutionService las rodea de guardas SSRF y allowlists.
    | Estas son CODIGO VERSIONADO: clases Laravel\Mcp\Server\Tool que envuelven
    | servicios que ya existen (ErpContextService, PrestashopContextService,
    | ContactAggregatorService...). No pasan por ToolExecutionService y no
    | necesitan `tools.allow_api`.
    |
    | Reglas que el codigo hace cumplir, no solo documentacion:
    |  - SOLO LECTURA. Ninguna tool escribe en el ERP ni en PrestaShop.
    |  - El ambito de cliente lo fija el ticket, nunca un id que elija el
    |    modelo (ver McpToolContext).
    |  - Todo resultado se recorta antes de entrar al contexto del LLM.
    |
    | server_enabled expone ademas el servidor MCP HTTP para clientes externos
    | (Claude Desktop / Claude Code del equipo). Va autenticado y NO debe
    | publicarse fuera de la red interna: sirve datos de clientes reales.
    */
    'mcp' => [
        'server_enabled' => env('HELPDESKAGENTS_MCP_SERVER', true),
        'server_route' => env('HELPDESKAGENTS_MCP_ROUTE', 'mcp/helpdesk'),

        // Techo de iteraciones del bucle de tool-calling. Sin el, un modelo
        // confundido puede encadenar llamadas al ERP indefinidamente.
        'max_tool_iterations' => (int) env('HELPDESKAGENTS_MCP_MAX_ITERATIONS', 4),

        // Bytes maximos por resultado de herramienta que entran al contexto.
        'max_result_bytes' => 6000,

        // Timeout (segundos) de una llamada con herramientas: el bucle hace
        // varias peticiones al proveedor, asi que necesita mas margen que una
        // completion suelta.
        'timeout' => 45,
    ],
];
