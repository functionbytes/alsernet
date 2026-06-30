# Auditoría Helpdesk — 13/06/2026

> Generado por workflow `helpdesk-audit` (59 agentes). **Nota:** la fase de verificación adversarial y la síntesis se cortaron por rate-limit (429); los hallazgos provienen de los agentes auditores que leyeron código real, pero los critical/high están **pendientes de verificación adversarial** (re-ejecutar para confirmar). Trátalos como alta-confianza-no-verificados.

## Resumen

**Total: 171 hallazgos** — 🔴 14 critical · 🟠 31 high · 🟡 48 medium · ⚪ 78 low


| Módulo | 🔴 | 🟠 | 🟡 | ⚪ | Tests |
|---|--:|--:|--:|--:|---|
| HelpdeskAgents | 4 | 4 | 6 | 10 | Existen tests Unit (modelos, servicios, PromptSanitizer, Age |
| HelpdeskTickets | 4 | 7 | 7 | 5 | Cobertura amplia en superficie (55 archivos de test: Feature |
| HelpdeskCampaigns | 2 | 6 | 5 | 7 | Existen 5 archivos de test. Sin embargo, CampaignsFeatureTes |
| HelpdeskPrestashop | 2 | 1 | 3 | 4 | Buena cobertura de servicio/HTTP: caching, circuit breaker,  |
| HelpdeskLivechat | 1 | 0 | 3 | 9 | Cobertura buena para un módulo de este tamaño: ~23 ficheros  |
| HelpdeskSocial | 1 | 5 | 6 | 4 | Cobertura amplia: 28 archivos de test (Feature Api/Web/Jobs/ |
| Helpdesk | 0 | 3 | 5 | 6 | Débil para el tamaño del módulo: 21 archivos de test (15 Fea |
| HelpdeskEmailLog | 0 | 1 | 2 | 10 | Buena cobertura funcional: 5 ficheros Feature cubren control |
| HelpdeskErp | 0 | 0 | 3 | 9 | Excelente. 10 archivos Feature cubren contexto, caché (TTL f |
| HelpdeskHelpcenter | 0 | 3 | 6 | 6 | Buena para flujos manager/público/votos/traducciones (4 arch |
| HelpdeskTranslate | 0 | 1 | 2 | 8 | Excelente para un módulo de este tamaño: 10 archivos de test |

---

## Hallazgos por módulo


### Helpdesk

_Módulo Helpdesk muy grande y maduro (~150 controllers/services, 80+ Form Requests, 12 policies, 11 jobs, ~115 migraciones). La arquitectura es sólida: webhooks verifican firma HMAC en el FormRequest, policies registradas correctamente, Resources camelCase/ISO8601 limpios, integraciones (Customer360, WhatsApp/FB/IG) con circuit breaker y degradación elegante. Los problemas principales son: un listener de evento roto por import faltante, un N+1 severo en el listado de inbox (~200 queries/carga), env() como default de config() con la clave 'helpdesk.ai' inexistente (rompe IA en producción cacheada), y violaciones sistémicas de convención (validación inline en 30+ controllers, jobs sin tries/timeout/backoff)._


#### 🟠 [HIGH/bug] Listener de ConversationUnsnoozed nunca se ejecuta (import faltante en EventServiceProvider)
- **Archivo:** `app/Providers/EventServiceProvider.php:88`
- **Esfuerzo:** trivial
- **Problema:** En el array $listen se mapea 'ConversationUnsnoozed::class' => [LogActivityOnConversationUnsnoozed::class], pero la clase Event NO está importada (solo está el Listener en la línea 28). Como el provider vive en namespace Modules\Helpdesk\Providers, 'ConversationUnsnoozed::class' resuelve a la cadena 'Modules\Helpdesk\Providers\ConversationUnsnoozed' (clase inexistente), no al evento real 'Modules\Helpdesk\Events\ConversationUnsnoozed' que se despacha en UnsnoozeConversationJob:44. Resultado: cuando una conversación se des-pospone, el listener LogActivityOnConversationUnsnoozed nunca se dispara y no se registra la actividad. ::class no lanza error en tiempo de parseo, así que el bug es silencioso.
- **Fix:** Añadir 'use Modules\Helpdesk\Events\ConversationUnsnoozed;' a los imports del provider. Idealmente añadir un test que despache UnsnoozeConversationJob y asserte que el activity log se crea.

#### 🟠 [HIGH/performance] N+1 severo al renderizar el listado del inbox (~200 queries por carga de 50 conversaciones)
- **Archivo:** `app/Http/Controllers/Managers/ConversationsController.php:282`
- **Esfuerzo:** medium
- **Problema:** index() y listJson() paginan 50 conversaciones con with(['customer','status','assignee']) pero luego mapean cada una con toInboxArray() (Conversation.php:436). Ese método llama por conversación a: getLatestMessage() (1 query, Conversation.php:476), unreadCountForInbox() (2 queries: un DB::table read + un items()->count(), Conversation.php:551 y 567) y accede a la relación inbox (no eager-loaded → 1 query, línea 455). Son ~4 queries extra × 50 = ~200 queries por carga de inbox, en cada navegación AJAX (listJson).
- **Fix:** Eager-load 'inbox' en el with() de index/listJson. Precargar el último mensaje con un with(['items' => fn($q)=>$q->latest()->limit(1)]) o una subconsulta, y withCount de no-leídos. Sustituir unreadCountForInbox()->items()->count() por un conteo precalculado. Considerar denormalizar last_message_preview en la tabla helpdesk_conversations.

#### 🟠 [HIGH/config] env() usado como default de config() con clave 'helpdesk.ai' inexistente — rompe IA con config cacheada
- **Archivo:** `app/Services/AI/AiClient.php:160`
- **Esfuerzo:** trivial
- **Problema:** AiClient::isEnabled() hace 'config("helpdesk.ai.enabled", env("HELPDESK_AI_ENABLED", true))' y AiAgentService::isEnabled() (línea 99) hace lo mismo con 'helpdesk.ai.agent_enabled' / env('HELPDESK_AI_AGENT_ENABLED'). PROBLEMA DOBLE: (1) config/helpdesk.php NO define ninguna clave 'ai', así que config() siempre cae al default; (2) ese default es una llamada a env(), que devuelve null cuando la config está cacheada en producción (config:cache). En producción la IA queda deshabilitada de forma silenciosa (chat devuelve null, el agente autónomo escala todo a humano).
- **Fix:** Añadir un bloque 'ai' => ['enabled' => env('HELPDESK_AI_ENABLED', true), 'agent_enabled' => env('HELPDESK_AI_AGENT_ENABLED', false)] en config/helpdesk.php y cambiar el código a config('helpdesk.ai.enabled') / config('helpdesk.ai.agent_enabled') sin el segundo argumento env().

#### 🟡 [MEDIUM/quality] Validación inline (request->validate) en 30+ controllers en vez de Form Requests
- **Archivo:** `app/Http/Controllers/SurveyController.php:42`
- **Esfuerzo:** large
- **Problema:** Violación sistémica de la convención del proyecto (form-requests.md: nunca validación inline). Hay 30+ ocurrencias de $request->validate()/Validator::make en controllers: SurveyController:42 (existe SubmitSurveyRequest sin usar), WebhooksController:93 (existe StoreWebhookRequest), SurveysController:95 (existe StoreSurveyRequest), BannersController:75 (existe StoreBannerRequest), RoutingRulesController:61/80 (existe StoreRoutingRuleRequest), AutomationRulesController:64/93, NotificationSettingsController:56, ConversationViewsController, AiController:44, PortalDashboardController:47/85, GdprController:52, StatusPageController (4×), TwoFactorController (3×), etc. Muchos tienen ya el Form Request creado pero no enlazado.
- **Fix:** Migrar la validación inline a los Form Requests existentes (o crearlos) inyectándolos en la firma del método. Empezar por los que ya tienen FormRequest creado: SurveyController->SubmitSurveyRequest, WebhooksController->StoreWebhookRequest/UpdateWebhookRequest, SurveysController->Store/UpdateSurveyRequest, BannersController->Store/UpdateBannerRequest, RoutingRulesController->Store/UpdateRoutingRuleRequest.

#### 🟡 [MEDIUM/quality] Mayoría de Jobs sin $tries/$timeout/$backoff explícitos (incl. jobs HTTP)
- **Archivo:** `app/Jobs/DispatchWebhookJob.php`
- **Esfuerzo:** small
- **Problema:** jobs.md exige $tries, $timeout y $backoff explícitos. Solo ProcessSocialWebhookJob y DownloadConversationAttachmentsJob los definen. Carecen de ellos: DispatchWebhookJob (hace HTTP saliente — sin timeout puede colgar el worker), SendBroadcastJob, ProcessEmailInboundJob, ProcessDripStepJob, TranscribeAudioJob (HTTP a OpenAI), SendScheduledMessageJob, CreateActivityMessageJob, UnsnoozeConversationJob. Sin $timeout un fetch lento puede bloquear el worker; sin $backoff los reintentos son inmediatos.
- **Fix:** Añadir public int $tries=3; public int $timeout=60 (o más para HTTP); public int $backoff=10; a todos los jobs. Prioridad en los que hacen HTTP externo: DispatchWebhookJob, TranscribeAudioJob, ProcessEmailInboundJob.

#### 🟡 [MEDIUM/quality] Jobs sin método failed() — errores permanentes sin trazabilidad
- **Archivo:** `app/Jobs/SendHelpdeskEmailJob.php`
- **Esfuerzo:** small
- **Problema:** jobs.md exige failed() en todos los jobs. SendHelpdeskEmailJob (no tiene failed, ni tries/timeout/backoff, ni onQueue; además traga toda excepción dentro de handle con try/catch por destinatario, por lo que nunca llega a fallar/reintentar) y DownloadConversationAttachmentsJob (tiene tries/timeout/backoff pero no failed()). Cuando el job se agota sin failed() no queda registro estructurado del fallo.
- **Fix:** Añadir failed(\Throwable $e) con Log::error y contexto (ids) en SendHelpdeskEmailJob y DownloadConversationAttachmentsJob. En SendHelpdeskEmailJob añadir onQueue('emails') + tries/timeout/backoff y dejar que la excepción se propague para reintentos.

#### 🟡 [MEDIUM/security] IMAP con validate_cert=false — desactiva validación de certificado TLS (MITM)
- **Archivo:** `app/Services/Email/ImapPullService.php:48`
- **Esfuerzo:** trivial
- **Problema:** ImapPullService::pullAccount() configura el cliente IMAP con 'validate_cert' => false de forma fija. Esto desactiva la verificación del certificado del servidor IMAP, exponiendo las credenciales del buzón y los correos a ataques man-in-the-middle. Las credenciales (imap_password) se transmiten en cada pull.
- **Fix:** Cambiar a 'validate_cert' => true por defecto, o leerlo de config/EmailAccount (p.ej. $account->imap_validate_cert ?? true) y documentar que false solo es para entornos de prueba.

#### 🟡 [MEDIUM/security] Threading de email por [#NNN] confía en From spoofeable para inyectar como agente
- **Archivo:** `app/Services/Email/ImapPullService.php:97`
- **Esfuerzo:** medium
- **Problema:** processMessage() detecta respuestas-por-email de agente buscando un User por el campo From del correo (spoofeable) y un patrón [#NNN] en el asunto. Si coincide, inserta un ConversationItem con user_id del agente (addAgentReply) en CUALQUIER conversación cuyo id sea NNN. Un atacante que falsifique el From de un agente y ponga [#NNN] en el asunto puede publicar mensajes que aparecen como enviados por ese agente en cualquier conversación. resolveConversation() también permite saltar entre conversaciones arbitrarias vía el id en el asunto.
- **Fix:** Verificar SPF/DKIM/DMARC del correo entrante (a nivel MTA o vía headers Authentication-Results) antes de aceptar reply-by-email de agentes. Usar un token firmado/opaco por conversación en el asunto en vez del id numérico crudo, y validar que el agente está realmente asignado/participante de la conversación.

#### ⚪ [LOW/bug] Cast 'is_blocked' en Customer apunta a columna inexistente
- **Archivo:** `app/Models/Customer.php:60`
- **Esfuerzo:** trivial
- **Problema:** casts() declara 'is_blocked' => 'boolean' pero no existe columna is_blocked en la migración create_helpdesk_customers_table (el bloqueo se modela con banned_at; isBanned() usa banned_at). El cast es código muerto/engañoso: cualquier código que lea $customer->is_blocked obtendrá null casteado a false, no el estado real de baneo.
- **Fix:** Eliminar el cast 'is_blocked' o reemplazarlo por un accessor is_blocked basado en banned_at (Attribute::get(fn()=>$this->banned_at!==null)).

#### ⚪ [LOW/quality] Código muerto: WhatsAppBusinessService::verifySignature y métodos download no usados en ProcessSocialWebhookJob
- **Archivo:** `app/Services/WhatsAppBusinessService.php:197`
- **Esfuerzo:** small
- **Problema:** verifySignature() en el servicio nunca se invoca: la verificación de firma real ocurre en WhatsAppWebhookRequest::authorize() (correcto). Además ProcessSocialWebhookJob contiene métodos privados sin uso tras mover las descargas al job en background: resolveWhatsAppMediaUrl() (línea 519), downloadMedia() (653), labelForType() (632) y extensionFromMime() (703) están duplicados con los de DownloadConversationAttachmentsJob. Aumenta superficie de mantenimiento y confusión.
- **Fix:** Eliminar WhatsAppBusinessService::verifySignature() (o documentar que la verificación vive en el FormRequest). Borrar de ProcessSocialWebhookJob los métodos privados ya no llamados.

#### ⚪ [LOW/quality] html_body se escribe pero el render usa un accessor body_html distinto
- **Archivo:** `app/Services/ConversationMessageService.php:56`
- **Esfuerzo:** small
- **Problema:** store() guarda la columna 'html_body' => nl2br(e($body)). Pero la vista thread.blade.php:239 renderiza $item->body_html, que es un ACCESSOR (getBodyHtmlAttribute, ConversationItem.php:368) que recalcula desde body en cada lectura (escapando con e() + linkify + chips de mención). La columna html_body almacenada nunca se usa para mostrar, lo que la deja desincronizada y como almacenamiento muerto. (Nota positiva: el render es seguro frente a XSS porque body_html escapa el body primero.)
- **Fix:** Decidir una sola fuente: o usar la columna html_body persistida (y entonces dejar de escribir el accessor), o eliminar la escritura de html_body y confiar solo en el accessor body_html. Documentar cuál es la canónica.

#### ⚪ [LOW/quality] ConversationsController de 2448 líneas — controller gordo con lógica de negocio
- **Archivo:** `app/Http/Controllers/Managers/ConversationsController.php:1`
- **Esfuerzo:** large
- **Problema:** El controller mezcla 40+ acciones (inbox, mensajes, adjuntos con Intervention/Image, email saliente, HSM/WhatsApp, merge, macros, IA, scheduled messages, drafts) en 2448 líneas, violando controllers.md (controllers delgados, lógica en services). Hay queries crudas DB::connection('helpdesk') y DB::table('sessions') embebidas (líneas 188, 1408-1472) en vez de Model::query() o un service. Esto dificulta tests y mantenimiento (parte de la baja cobertura).
- **Fix:** Extraer la lógica a services dedicados (procesamiento de adjuntos/imágenes, envío de email, HSM, métricas del statusbar). Mover las subconsultas de agentes online/meta a un AgentPresenceService o repositorio. Reducir el controller a orquestación delgada.

#### ⚪ [LOW/integration] SendGrid inbound usa comparación de secreto estático en vez de la firma ECDSA real
- **Archivo:** `app/Http/Controllers/EmailInboundController.php:64`
- **Esfuerzo:** medium
- **Problema:** verifySendgrid() compara hash_equals($secret, $headerSignature): es un secreto compartido estático, no el esquema de firma ECDSA (X-Twilio-Email-Event-Webhook-Signature/Timestamp) que SendGrid usa realmente para Inbound Parse/Event Webhook. Si no hay secreto configurado devuelve true (acepta cualquier payload). Mailgun/Postmark están bien implementados (HMAC). El provider 'generic' devuelve true (documentado como solo dev).
- **Fix:** Implementar la verificación de firma de SendGrid con clave pública ECDSA (verify_signature sobre timestamp+payload) o documentar explícitamente que SendGrid inbound requiere otra capa de autenticación (IP allowlist / basic auth en la URL del webhook). Evitar el return true cuando no hay secreto.

#### ⚪ [LOW/bug] Race condition en findOrCreateConversation / Customer::firstOrCreate bajo webhooks concurrentes
- **Archivo:** `app/Jobs/ProcessSocialWebhookJob.php:416`
- **Esfuerzo:** small
- **Problema:** findOrCreateConversation() hace SELECT abierto + Conversation::create() de forma no atómica; Customer::firstOrCreate por phone/psid/ig_id tampoco está protegido por unique index garantizado. Dos webhooks del mismo remitente procesados en paralelo (cola con varios workers) pueden crear conversaciones/clientes duplicados. Meta normalmente serializa por remitente, lo que reduce el riesgo, pero no lo elimina si la cola escala.
- **Fix:** Asegurar índices UNIQUE en helpdesk_customers (whatsapp_phone, facebook_psid, instagram_id) y capturar la colisión, o envolver la creación en lock (Cache::lock por external_sender_id). Verificar que las migraciones de social_channel_indexes incluyen UNIQUE, no solo INDEX.

### HelpdeskAgents

_Módulo de agentes IA para helpdesk (flujos conversacionales, base de conocimiento con embeddings, herramientas, turnos/guardias de agentes). El código de servicios (FlowEngine, rate limiting, circuit breaker, sanitización de prompts) está bien construido, pero hay un grave desfase entre la capa de datos (migraciones de refactor mayo-2026) y los modelos/controladores: faltan el scope default(), columnas en fillable, y TODA la configuración del módulo (providers, embeddings, tools) está sin definir. Resultado: la página de settings del agente, el listado/creación de flujos y getDefaultAgent() están rotos en runtime, y las API tools tienen SSRF abierto por defecto._


#### 🔴 [CRITICAL/bug] getDefaultAgent() llama a un scope default() inexistente → BadMethodCallException
- **Archivo:** `app/Concerns/InteractsWithDefaultAiAgent.php:19-23 (modelo app/Models/AiAgent.php)`
- **Esfuerzo:** trivial
- **Problema:** El trait hace AiAgent::query()->default()->first(), pero el modelo AiAgent NO define scopeDefault (solo scopeActive, scopeByProvider, scopeByModel). Verificado con tinker: method_exists(...,'scopeDefault') === false. Este trait lo usan AgentSettingsController (index/update/statistics), AiToolsController, AiKnowledgeController y AiAgentObserver. Cada llamada lanza BadMethodCallException → todas las pantallas de settings/tools/knowledge revientan en producción.
- **Fix:** Añadir a AiAgent: public function scopeDefault($query) { return $query->where('is_default', true); } y agregar 'is_default' al $fillable + cast boolean en casts(). La columna is_default existe en el esquema vivo (verificado).

#### 🔴 [CRITICAL/config] Configuración del módulo (providers, embeddings, tools) NO definida → settings rota y SSRF abierto
- **Archivo:** `config/config.php`
- **Esfuerzo:** small
- **Problema:** config/config.php solo define name, llm_rate_limits y prompt_injection_patterns. Verificado con tinker: config('helpdeskagents.providers'), config('helpdeskagents.embeddings') y config('helpdeskagents.tools') === null. Consecuencias: (1) AgentSettingsController::index pasa $providers=null a la vista; settings-tab.blade.php hace @foreach($providers as ...) y $providers[$agent->provider]['models'] → TypeError, la página de settings del agente no carga. (2) getModels() siempre devuelve []. (3) EmbeddingService/KnowledgeRetrieval funcionan solo por fallback a services.openai.key. (4) ToolExecutionService::executeApiTool usa config('helpdeskagents.tools.allow_api', true) → por defecto TRUE: las API tools quedan habilitadas sin allowlist.
- **Fix:** Definir en config/config.php las claves 'providers' (con sub-array models por proveedor que la blade espera), 'embeddings' (api_key, model, timeout, top_k, min_similarity) y 'tools' (allow_api con default seguro FALSE, api_timeout, allow_database, allow_function, allowed_functions, y un allowlist de hosts para URLs). Añadir tests que afirmen config no-nula.

#### 🔴 [CRITICAL/bug] Modelo AiAgent desincronizado con el refactor de columnas (parameters/system_prompt/is_default)
- **Archivo:** `app/Models/AiAgent.php:18-42`
- **Esfuerzo:** medium
- **Problema:** La migración 2026_05_21_000100 renombró backups→parameters y personality→system_prompt y añadió is_default. Pero el modelo sigue con $fillable=['...,'personality','backups',...]' sin 'parameters','system_prompt','is_default', y casts() castea 'backups' (no 'parameters'). AgentSettingsController::update escribe $agent->parameters (asignación directa, no mass-assign, así que se guarda) pero callAiProvider()/getModelConfig()/getApiKey() leen $agent->backups (datos viejos/duplicados). Además el factory state default() y DefaultAiAgentSeeder fijan is_default/active que no están en fillable → se descartan silenciosamente. Esquema vivo confirma: existen ambas columnas backups y parameters (datos divergentes).
- **Fix:** Actualizar $fillable y casts() a 'parameters','system_prompt','is_default'; cambiar callAiProvider/getModelConfig/getApiKey para leer $this->parameters; eliminar referencias a backups. Migrar/eliminar la columna backups obsoleta. Quitar 'active' del seeder (columna no usada por el modelo).

#### 🔴 [CRITICAL/bug] AiAgentFlowsController usa trigger_type pero el modelo/DB usan trigger → crear/editar/filtrar flujos roto
- **Archivo:** `app/Http/Controllers/Managers/AiAgentFlowsController.php:35,62,100-113,149-155`
- **Esfuerzo:** small
- **Problema:** Tras la migración 000600 (trigger_type→trigger) el modelo AiAgentFlow tiene $fillable=['trigger'] y la blade postea name="trigger". Pero el controller valida 'trigger_type' (required) y crea/actualiza con 'trigger_type'. Como el form nunca envía 'trigger_type', la validación required SIEMPRE falla; aunque pasara, 'trigger_type' no está en fillable y se descarta. Además index() filtra ->where('trigger_type', ...) y 'filters'=>only(['trigger_type']). En el esquema vivo de este entorno la columna sigue siendo trigger_type (la 000600 no se aplicó), así que el modelo (trigger) tampoco persiste. En cualquier caso el trigger del flujo queda NULL. Las Form Requests StoreAiAgentFlowRequest/UpdateAiAgentFlowRequest (que validan 'trigger') existen pero NO se usan.
- **Fix:** Unificar a 'trigger' en todo el controller (validación, create, update, filtros) o usar las Form Requests StoreAiAgentFlowRequest/UpdateAiAgentFlowRequest ya escritas. Confirmar que la migración 000600 está aplicada en todos los entornos y alinear modelo/esquema. Añadir test de creación de flujo que verifique el valor de trigger persistido.

#### 🟠 [HIGH/security] SSRF en ToolExecutionService::executeApiTool — URL controlada sin allowlist ni bloqueo de IPs internas
- **Archivo:** `app/Services/ToolExecutionService.php:55-89`
- **Esfuerzo:** medium
- **Problema:** La URL de la tool (tool->implementation) y los placeholders {key} se rellenan con argumentos y se hace Http->get/post sin validar host/scheme ni bloquear redes internas (169.254.169.254, localhost, 10.x, etc.). Combinado con allow_api default=true (config no definida), cualquiera que pueda crear tools (helpdesk.aiagents.create) puede provocar peticiones server-side a servicios internos/metadata cloud. Los argumentos vienen potencialmente de la salida del LLM.
- **Fix:** Cambiar el default de allow_api a false; validar el scheme (solo https), resolver y bloquear IPs privadas/loopback/link-local, y exigir un allowlist de hosts por configuración. Validar/escapar placeholders.

#### 🟠 [HIGH/security] executeFunctionTool pasa argumentos del LLM directo a app()->call()
- **Archivo:** `app/Services/ToolExecutionService.php:116-130`
- **Esfuerzo:** medium
- **Problema:** Aunque hay allowlist de nombres de función (allowed_functions, default []), app()->call($functionName, $arguments) inyecta $arguments (potencialmente derivados de la salida del modelo/usuario) como parámetros nombrados a la función/método invocable. Si una función permitida acepta parámetros sensibles, se pueden sobreescribir. Además allow_function lee config no definida (default false, OK por ahora) pero allowed_functions no está documentado/definido.
- **Fix:** Definir un contrato estricto para funciones tool (p.ej. interface que reciba un único array validado) en lugar de binding por nombre de parámetro; validar el esquema de arguments contra tool->parameters antes de invocar.

#### 🟠 [HIGH/bug] AiAgentFlowsController::index redirige a ruta inexistente manager.helpdesk.ai.settings
- **Archivo:** `app/Http/Controllers/Managers/AiAgentFlowsController.php:29`
- **Esfuerzo:** trivial
- **Problema:** Cuando no hay agente configurado, index() hace redirect()->route('manager.helpdesk.ai.settings'). Verificado con Route::has: esa ruta NO existe (la real es 'helpdesk.ai.settings'). Lanza RouteNotFoundException justo en el caso que pretende manejar (sistema sin agente).
- **Fix:** Cambiar a route('helpdesk.ai.settings'). El método create() (línea 80) ya usa el nombre correcto sin prefijo manager.

#### 🟠 [HIGH/bug] AgentShiftResource referencia agent_id/relación agent inexistentes
- **Archivo:** `app/Http/Resources/AgentShiftResource.php:17-21`
- **Esfuerzo:** trivial
- **Problema:** El recurso expone 'agentId'=>$this->agent_id y whenLoaded('agent'). Pero AgentShift tiene columna user_id y relación user() (no agent ni agent_id). agentId siempre será null y la relación 'agent' nunca carga. ScheduleController eager-loadea 'user'. Los recursos AgentShiftResource/OncallRotationResource además no se usan en ningún controller (búsqueda confirmó cero usos), código muerto.
- **Fix:** Corregir a user_id/whenLoaded('user') o eliminar los Resources si no se usan. Si se planea una API, exponerlos vía controller API real con auth:sanctum.

#### 🟡 [MEDIUM/quality] AiAgentFlowsController: validación inline en vez de Form Requests (existen pero no se usan)
- **Archivo:** `app/Http/Controllers/Managers/AiAgentFlowsController.php:100-104,149-153,169-172,256-264,284-288`
- **Esfuerzo:** small
- **Problema:** Todos los métodos (store, update, updateStructure, storeNode, updateNode) usan $request->validate() inline con sintaxis pipe ('required|string|max:255'), violando rules/controllers.md y rules/form-requests.md. Ya existen Form Requests StoreAiAgentFlowRequest, UpdateAiAgentFlowRequest, StoreAiAgentFlowNodeRequest, UpdateAiAgentFlowNodeRequest, UpdateAiAgentFlowStructureRequest sin usar. Este es el único controller del módulo que no usa Form Requests (los demás sí).
- **Fix:** Inyectar las Form Requests existentes en cada método. Asegurar que sus rules usan 'trigger' (no trigger_type) y sintaxis array. Eliminar la validación inline.

#### 🟡 [MEDIUM/security] Inconsistencia de permiso entre middleware de ruta y authorize() de Form Requests (Schedule)
- **Archivo:** `app/Http/Requests/StoreShiftRequest.php:11 (y StoreVacationRequest, StoreOncallRequest); app/Http/Controllers/Managers/Settings/ScheduleController.php:20-21`
- **Esfuerzo:** trivial
- **Problema:** El ScheduleController protege store* con middleware 'can:helpdesk.schedule.update', pero StoreShiftRequest/StoreVacationRequest/StoreOncallRequest::authorize() exigen 'helpdesk.settings.update'. Un usuario con helpdesk.schedule.update (el permiso que el módulo seedea/declara en NavService) pasa el middleware pero recibe 403 del Form Request por exigir un permiso distinto. Convención del proyecto: authorize() debe usar el mismo {alias}.action que la ruta.
- **Fix:** Cambiar authorize() de los 3 requests a 'helpdesk.schedule.update' para alinear con el middleware y la convención.

#### 🟡 [MEDIUM/bug] executeActionNode asume $session->conversation no nulo y relación tickets()
- **Archivo:** `app/Services/AiAgentFlowEngine.php:277-303`
- **Esfuerzo:** small
- **Problema:** $ticket = $session->conversation->tickets()->latest()->first(): si la sesión no tiene conversación cargada/existente, $session->conversation puede ser null → 'Attempt to read property on null' (capturado por el try/catch genérico, pero degrada toda acción a 'Action could not be completed'). Depende de que Conversation tenga relación tickets() (módulo HelpdeskTickets) — acoplamiento cross-módulo sin verificación de disponibilidad del módulo.
- **Fix:** Validar $session->conversation antes de usarlo y comprobar que el módulo HelpdeskTickets esté habilitado / method_exists(tickets) antes de invocar acciones de ticket.

#### 🟡 [MEDIUM/performance] executePromptNode carga hasta 100 mensajes del historial en cada turno (coste/latencia LLM)
- **Archivo:** `app/Services/AiAgentFlowEngine.php:179-198`
- **Esfuerzo:** medium
- **Problema:** Cada processMessage reconstruye el contexto leyendo limit(100) mensajes y los envía íntegros al proveedor. Con conversaciones largas esto crece los tokens (coste $) y puede exceder max_tokens del modelo. No hay truncado por tokens ni resumen. Además el orderBy('id') sobre messages no garantiza índice (revisar índice session_id en helpdesk_ai_agent_session_messages).
- **Fix:** Limitar el historial por ventana de tokens o por nº configurable más pequeño, o resumir mensajes antiguos. Asegurar índice en (session_id, id).

#### 🟡 [MEDIUM/security] AiAgentTool::store/update no valida implementation según tipo (SQL/URL/función)
- **Archivo:** `app/Http/Requests/StoreAiAgentToolRequest.php:14-26; app/Services/ToolExecutionService.php:95-110`
- **Esfuerzo:** medium
- **Problema:** implementation es 'nullable|string' sin validación por tipo. Para type=database se ejecuta DB::select($sql, $arguments) tras comprobar solo que empieza por SELECT (un SELECT puede igualmente filtrar datos sensibles de cualquier tabla, o usar subqueries pesadas). Para type=api la URL no se valida (ver SSRF). No hay restricción de a qué tablas puede consultar.
- **Fix:** Validar implementation condicionalmente al tipo (URL válida https para api, patrón/whitelist de tablas para database). Para database, considerar una conexión read-only con permisos limitados y/o lista blanca de tablas.

#### 🟡 [MEDIUM/bug] Migración add_default_type usa ->change() sin redeclarar atributos (Laravel 12 los descarta)
- **Archivo:** `database/migrations/2026_05_21_000500_add_default_type_to_helpdesk_ai_agents.php:18`
- **Esfuerzo:** trivial
- **Problema:** $table->string('type')->default('chat')->change() — en Laravel 12 ->change() descarta atributos no especificados. Si la columna type era nullable, tras esta migración deja de serlo (no se redeclaró nullable). Viola rules/migrations.md (incluir TODOS los atributos en ->change()). El down() también pierde el default.
- **Fix:** Redeclarar todos los atributos previos de la columna type (nullable, length, etc.) en el ->change(). Verificar el estado original de la columna.

#### ⚪ [LOW/quality] AgentVacation creado con status validado pero StoreVacationRequest exige status; AgentShift/Oncall fuerzan is_active sin Form Request
- **Archivo:** `app/Http/Controllers/Managers/Settings/ScheduleController.php:42-44,84-85`
- **Esfuerzo:** small
- **Problema:** storeShift y storeOncall fijan is_active=true e inyectan timezone en el controller en lugar de manejarlo como default en migración/Form Request. Menor, pero mezcla lógica de defaults en el controller. AgentShift no usa SoftDeletes ni HasFactory; AgentVacation/OncallRotation tampoco tienen factory (los Schedule no tienen tests de happy-path de store).
- **Fix:** Mover defaults (is_active, timezone) a la migración o a prepareForValidation() del Form Request. Crear factories para AgentShift/AgentVacation/OncallRotation y tests de store.

#### ⚪ [LOW/config] Config duplicada y namespace inconsistente: llm_rate_limits/prompt_injection en helpdesk.* y helpdeskagents.*
- **Archivo:** `config/config.php:6-17 vs modules/Helpdesk/config/helpdesk.php:164-170`
- **Esfuerzo:** small
- **Problema:** FlowEngine lee config('helpdesk.llm_rate_limits.*') (definido en módulo Helpdesk core). PromptSanitizer lee config('helpdeskagents.prompt_injection_patterns'). El config/config.php del módulo duplica llm_rate_limits y prompt_injection_patterns bajo el namespace helpdeskagents, pero el de rate_limits queda muerto (nadie lo lee). Riesgo de drift: cambiar uno no afecta al otro.
- **Fix:** Elegir un solo namespace. Recomendado: que el módulo lea siempre helpdeskagents.* y eliminar la duplicación en helpdesk.php, o documentar que las llaves viven en core. Quitar la entrada muerta.

#### ⚪ [LOW/quality] ToolExecutionService usa DB::select y DB::connection()->table()->insert() en vez de modelos
- **Archivo:** `app/Services/ToolExecutionService.php:107,142-151`
- **Esfuerzo:** trivial
- **Problema:** logExecution inserta vía DB::connection('helpdesk')->table('helpdesk_ai_tool_executions')->insert() en lugar de usar el modelo AiToolExecution existente (que tiene casts). Inserta arguments/result con json_encode manual y no setea updated_at. Viola rules/models.md (preferir Model::query()). El DB::select del database tool es aceptable por ser SQL raw read-only intencional.
- **Fix:** Usar AiToolExecution::create([...]) con casts array para arguments/result; mantener el try/catch.

#### ⚪ [LOW/quality] Cache key inconsistente entre AiAgentFlowsController y el trait InteractsWithDefaultAiAgent
- **Archivo:** `app/Http/Controllers/Managers/AiAgentFlowsController.php:21-24,73-77`
- **Esfuerzo:** small
- **Problema:** FlowsController cachea el agente bajo 'helpdesk:ai-agent:first' con AiAgent::oldest()->first(), mientras el resto del módulo usa el trait con clave 'helpdesk:ai-agent:default' y lógica default()->first() ?? orderBy(id). Dos fuentes de verdad y dos claves de caché para 'el agente'; tras guardar settings, el observer solo invalida 'helpdesk:ai-agent:default', dejando 'helpdesk:ai-agent:first' obsoleta. Además FlowsController no usa el trait pese a existir.
- **Fix:** Usar el trait InteractsWithDefaultAiAgent en FlowsController y eliminar la caché ad-hoc 'helpdesk:ai-agent:first'.

#### ⚪ [LOW/bug] Mass assignment de requires_approval/is_active mezclando $request->has() y boolean()
- **Archivo:** `app/Http/Controllers/Managers/AiToolsController.php:44-45 vs 57-58; AiKnowledgeController.php:44 vs 56; AiTagsController.php:30 vs 42`
- **Esfuerzo:** trivial
- **Problema:** En store() se usa $request->has('is_active') (true si la clave existe aunque valga '0'/'false'), mientras update() usa $request->boolean('is_active') (interpreta el valor). Comportamiento inconsistente entre crear y editar: un checkbox enviado como is_active=0 marcaría activo en store pero inactivo en update. Las Form Requests ya validan estos campos como boolean nullable.
- **Fix:** Usar $request->boolean(...) de forma consistente en store y update (o el patrón documentado en form-requests.md). Idealmente normalizar en prepareForValidation() del Form Request.

#### ⚪ [LOW/quality] AiAgentKnowledgeBase::generateEmbedding (modelo) confunde su rol con el Job; modelo por defecto obsoleto
- **Archivo:** `app/Models/AiAgentKnowledgeBase.php:135-143; app/Http/Controllers/Managers/AiKnowledgeController.php:80-87`
- **Esfuerzo:** small
- **Problema:** El método de modelo generateEmbedding() solo pone embedding=null y embedding_model='text-embedding-ada-002' (modelo deprecado y distinto al config text-embedding-3-small); no genera nada. El controller knowledge.generateEmbedding lo llama y responde 'Embedding generado correctamente', mensaje engañoso: el embedding real lo genera GenerateEmbeddingJob vía el observer al cambiar content (que se dispara igualmente). El endpoint manual no encola el job.
- **Fix:** Hacer que el endpoint despache GenerateEmbeddingJob::dispatch($knowledge) y eliminar/renombrar el método de modelo, o que dispare el job. Quitar el default 'text-embedding-ada-002'.

#### ⚪ [LOW/ux] Inline style en tags-tab (color de tag) — viola regla no style inline
- **Archivo:** `resources/views/managers/ai-agent/partials/tags-tab.blade.php:83`
- **Esfuerzo:** trivial
- **Problema:** <span ... style="background-color:{{ $tag->color ?? '#90bb13' }};"> usa estilo inline, violando blade-views.md (NEVER use style=""). El resto de vistas del módulo están limpias (FA6, jQuery, dropdown pattern correcto en flows index, sin Tabler/Livewire).
- **Fix:** Usar una CSS var/clase o data-attribute + JS para aplicar el color, o un set acotado de clases de color. Es el único caso del módulo.

#### ⚪ [LOW/bug] settings-tab.blade lee $agent->settings['api_key'] (atributo inexistente)
- **Archivo:** `resources/views/managers/ai-agent/partials/settings-tab.blade.php:107`
- **Esfuerzo:** trivial
- **Problema:** old('api_key', $agent->settings['api_key'] ?? '') — el modelo AiAgent no tiene atributo 'settings' (la API key está en api_key_encrypted, que además no debe prerellenarse en un input). Siempre cae al '' por el null-coalescing, pero referencia una propiedad inexistente y es confuso.
- **Fix:** Quitar la referencia a $agent->settings['api_key']; dejar value vacío (no exponer la clave encriptada en el formulario).

#### ⚪ [LOW/integration] StartAiAgentSessionJob usa el trait Queueable legacy y no aparece despachado desde ningún sitio
- **Archivo:** `app/Jobs/StartAiAgentSessionJob.php:5-18`
- **Esfuerzo:** small
- **Problema:** Usa Illuminate\Bus\Queueable + InteractsWithQueue + SerializesModels (estilo antiguo) en lugar del Illuminate\Foundation\Queue\Queueable unificado que pide jobs.md (GenerateEmbeddingJob sí lo usa). Además grep no encontró ningún dispatch de StartAiAgentSessionJob ni llamadas a AiAgentFlowEngine::startSession/processMessage fuera del propio módulo/tests: la integración que arranca sesiones IA desde el flujo de conversación/widget no está cableada (o vive fuera del repo auditado). El job tampoco define onConnection y pasa ?Customer nullable con SerializesModels (correcto, pero verificar serialización de null).
- **Fix:** Confirmar quién despacha StartAiAgentSessionJob (listener de mensaje entrante). Si falta el wiring, documentarlo/implementarlo. Unificar al trait Queueable de Foundation.

#### ⚪ [LOW/integration] EmbeddingService no aplica retry/circuit breaker como el FlowEngine
- **Archivo:** `app/Services/EmbeddingService.php:25-74`
- **Esfuerzo:** small
- **Problema:** embed()/embedBatch() hacen Http->timeout()->post() sin retry ni CircuitBreaker, a diferencia de los proveedores LLM en FlowEngine (que sí usan retry(2,500) + CircuitBreaker). Un fallo transitorio de OpenAI hace fallar el GenerateEmbeddingJob (que reintenta 3x por job, aceptable) pero también findRelevant cae a fulltext en caliente. Inconsistencia de robustez entre las dos integraciones OpenAI del módulo.
- **Fix:** Añadir ->retry(2,500,throw:false) y, si se quiere, CircuitBreaker('llm:openai-embeddings') para uniformar el manejo de errores HTTP.

### HelpdeskCampaigns

_Módulo de campañas de marketing para chat/helpdesk con tracking de impresiones, A/B testing, frequency capping, targeting, webhooks y workflow de aprobación. La arquitectura está bien pensada (jobs async, eventos, listeners en cola, índices compuestos) pero el módulo está roto en su estado actual: el CampaignsController carece de 7 métodos que las rutas y los tests referencian, el modelo Campaign no tiene la relación variants() que VariantSelector invoca, y varios campos enterprise no son fillable ni se persisten. El conjunto de tests Feature principal fallaría por completo._


#### 🔴 [CRITICAL/bug] CampaignsController carece de 7 métodos referenciados por rutas (500 garantizado)
- **Archivo:** `app/Http/Controllers/Managers/CampaignsController.php`
- **Esfuerzo:** large
- **Problema:** routes/managers.php registra rutas a CampaignsController@statisticsTimeline, @activity, @exportStatistics, @bulkAction, @submitForApproval, @approve y @reject (líneas 22-31), pero ninguno de esos métodos existe en el controller (solo llega hasta templates()). Cualquier petición a esas rutas lanza un error de método no encontrado. La barra de acciones masivas del index.blade.php hace POST a helpdesk.campaigns.bulk-action, que reventaría siempre. El workflow de aprobación y los endpoints de estadísticas/exportación están totalmente inoperativos.
- **Fix:** Implementar los métodos faltantes en el controller: bulkAction (acción+ids con autorización 'manage'), submitForApproval, approve, reject (con sanitización de la razón en metadata['rejection_reason']), statistics.timeline (GROUP BY DATE(viewed_at)), activity y exportStatistics (CSV). Delegar la lógica a un servicio. Alternativamente, si la funcionalidad fue revertida, eliminar las rutas y los tests asociados. Tras el cambio: php artisan route:clear.

#### 🔴 [CRITICAL/bug] VariantSelector llama a $campaign->variants() pero la relación no existe en el modelo Campaign
- **Archivo:** `app/Services/VariantSelector.php:18`
- **Esfuerzo:** trivial
- **Problema:** VariantSelector::pick() ejecuta $campaign->variants()->orderBy('id')->get(), pero el modelo Campaign (app/Models/Campaign.php) solo define impressions() y templates(); no hay método variants(). En cuanto una campaña activa con variantes recibe una impresión (ImpressionTrackingController::recordView -> $variants->pick()), Laravel lanza BadMethodCallException 'Call to undefined method ... variants()' y el endpoint público de tracking devuelve 500. El test test_variant_selector_returns_null_when_no_variants enmascara esto con un try/catch que hace markTestSkipped.
- **Fix:** Añadir a Campaign: public function variants(): HasMany { return $this->hasMany(CampaignVariant::class); } y eager-loadear donde aplique. Añadir un test real que cree variantes y verifique la selección ponderada determinista.

#### 🟠 [HIGH/bug] Campos enterprise no están en $fillable: se descartan silenciosamente al crear/actualizar
- **Archivo:** `app/Models/Campaign.php:39`
- **Esfuerzo:** small
- **Problema:** $fillable solo contiene name, description, type, status, content, appearance, conditions, metadata, published_at, ends_at. Faltan max_impressions_per_user, cooldown_minutes, goal_type, goal_value, approval_required, approved_at, approved_by_user_id e impressions_count/clicks_count — columnas que SÍ existen en la BD (migración 2026_05_06_095859). Los Form Requests Managers/StoreCampaignRequest y Managers/UpdateCampaignRequest validan esos campos, pero Campaign::create($request->validated()) los ignora por mass-assignment protection. Resultado: frequency capping, goal-based auto-end y approval workflow nunca se persisten desde la UI. FrequencyCapService::shouldShow lee $campaign->max_impressions_per_user (siempre null) y EndExpiredCampaignsJob filtra por goal_type/goal_value (siempre null).
- **Fix:** Añadir esas columnas a $fillable y a casts() (approval_required => boolean, approved_at => datetime, goal_value/cooldown_minutes/max_impressions_per_user => integer). impressions_count/clicks_count deben quedar fuera de fillable (se gestionan vía increment), pero sí necesitan cast integer.

#### 🟠 [HIGH/bug] El controller Managers usa los Form Requests raíz (sin campos enterprise) en lugar de los de Managers/
- **Archivo:** `app/Http/Controllers/Managers/CampaignsController.php:8`
- **Esfuerzo:** trivial
- **Problema:** El controller importa Modules\HelpdeskCampaigns\Http\Requests\StoreCampaignRequest y UpdateCampaignRequest (las versiones raíz), no las de Http\Requests\Managers\. Las raíz NO validan max_impressions_per_user, cooldown_minutes, goal_type, goal_value ni approval_required, por lo que aunque se arregle el $fillable esos campos seguirían sin llegar. Hay duplicación: existen dos pares de Form Requests casi idénticos (raíz vs Managers). Además la versión raíz permite 'status' en las reglas, lo que combinado con status fillable habilita fijar status='active' directamente al crear (ver finding de seguridad).
- **Fix:** Cambiar los imports a Modules\HelpdeskCampaigns\Http\Requests\Managers\StoreCampaignRequest y UpdateCampaignRequest. Eliminar los Form Requests raíz duplicados (app/Http/Requests/StoreCampaignRequest.php y UpdateCampaignRequest.php) para evitar confusión.

#### 🟠 [HIGH/bug] Doble conteo de impressions_count al registrar un clic
- **Archivo:** `app/Listeners/UpdateCampaignImpressionCounters.php:37`
- **Esfuerzo:** trivial
- **Problema:** El listener hace $campaign->increment('impressions_count') SIEMPRE que se dispara CampaignImpressionRecorded, y además increment('clicks_count') si wasClick. Pero el evento se dispara dos veces por el ciclo de vida de una impresión: (1) en RecordImpressionJob al crear la fila (wasClick=false) y (2) en ImpressionTrackingController::recordClick (wasClick=true). En el clic se vuelve a incrementar impressions_count, inflando el total. El CTR mostrado y el goal-based auto-end por impresiones quedan distorsionados.
- **Fix:** Incrementar impressions_count solo cuando ! $event->wasClick (es decir, en la creación de la impresión), e incrementar únicamente clicks_count cuando wasClick. Añadir un test que registre vista+clic y verifique impressions_count=1, clicks_count=1.

#### 🟠 [HIGH/bug] Policy sin método view() correcto para autorización a nivel de clase en index()
- **Archivo:** `app/Http/Controllers/Managers/CampaignsController.php:20`
- **Esfuerzo:** trivial
- **Problema:** index() llama $this->authorize('view', Campaign::class). En Laravel, pasar el nombre de la clase a una ability cuyo método de policy declara una instancia (view(User, Campaign)) NO mapea a viewAny; intenta invocar view() con el modelo como null. CampaignPolicy::view(User $user, Campaign $campaign) tiene el parámetro tipado no-nullable, por lo que la resolución del argumento del modelo puede fallar o, en el mejor caso, la convención correcta es usar 'viewAny'. El API controller sí usa correctamente authorize('viewAny', Campaign::class). Inconsistencia que puede romper el listado.
- **Fix:** En index() usar $this->authorize('viewAny', Campaign::class). Mantener 'view' solo para instancias ($campaign). Igualar la convención con el ApiController.

#### 🟠 [HIGH/security] status es fillable y validado en el request raíz: permite fijar 'active' al crear (bypass del workflow)
- **Archivo:** `app/Models/Campaign.php:43`
- **Esfuerzo:** small
- **Problema:** 'status' está en $fillable y el StoreCampaignRequest raíz (el que usa el controller) lo valida con in:draft,scheduled,active,ended,paused. Por tanto un usuario con permiso create puede enviar status=active y publicar una campaña directamente, saltándose publish()/aprobación y los eventos de lifecycle (CampaignPublished nunca se dispara, no se notifica ni se registra). El test test_store_cannot_set_status_directly espera que el status quede en 'draft', por lo que además ese test FALLA con la configuración actual.
- **Fix:** Quitar 'status' de las reglas de los Store/Update Requests (gestionar transiciones solo vía publish/pause/resume/end). Opcionalmente quitar 'status' de $fillable y exponerlo solo mediante los métodos de transición. Esto alinea el comportamiento con el test existente.

#### 🟠 [HIGH/tests] Suite Feature principal no ejecutable: depende de métodos/comportamiento no implementado
- **Archivo:** `tests/Feature/CampaignsFeatureTest.php:275`
- **Esfuerzo:** medium
- **Problema:** CampaignsFeatureTest cubre approval workflow (submit/approve/reject con rejection_reason sanitizado), bulk actions, statistics.timeline, export CSV — todo contra métodos de controller que no existen. Estos ~15 tests erroran (no fallan limpiamente) con MethodNotAllowed/500. Además los tests crean campañas con goal_type/impressions_count vía factory create(), que al no ser fillable se descartan, invalidando test_end_expired_campaigns_job_ends_goal_based_campaigns. La suite da una falsa sensación de cobertura ya verde porque probablemente no se ejecuta en CI (requiere schema helpdesk).
- **Fix:** Tras implementar los métodos del controller y arreglar el fillable, ejecutar la suite completa y corregir. Hacer goal_type/impressions_count fillable (o setearlos con forceFill en el factory) para que los tests de goal-based end sean válidos.

#### 🟡 [MEDIUM/security] Endpoint público de tracking sin validación de campaign_id existente ni de varios campos enviados
- **Archivo:** `app/Http/Requests/Public/RecordImpressionRequest.php:18`
- **Esfuerzo:** small
- **Problema:** RecordImpressionRequest::authorize() devuelve true (correcto por ser público), pero las reglas no validan 'country', 'language' ni 'page_url' contra formato esperado, y customer_session_id se valida como integer mientras dedupKey()/FrequencyCapService lo tratan como string hasheado y VariantSelector lo usa como identidad. customer_id e customer_session_id no llevan exists: por lo que un atacante puede inyectar IDs arbitrarios en helpdesk_campaign_impressions (FK set null mitiga parcialmente). page_url se persiste hasta 2000 chars sin sanitizar y luego se muestra en estadísticas. No hay verificación de que el campaign_id pertenezca a una campaña visible.
- **Fix:** Añadir reglas: country ['nullable','string','max:8'], language ['nullable','string','max:8']. Validar customer_id/customer_session_id con exists si aplica, o documentar que se confía en FK set null. Asegurar escape de page_url en las vistas de estadísticas. El rate limit throttle:120,1 es razonable pero conviene capear por IP+campaign.

#### 🟡 [MEDIUM/config] Config del módulo no define las claves que el código consume (webhooks, retention)
- **Archivo:** `config/config.php:3`
- **Esfuerzo:** trivial
- **Problema:** config/config.php solo contiene ['name' => 'HelpdeskCampaigns']. Sin embargo DispatchCampaignWebhooks lee config('helpdeskcampaigns.webhooks') y CleanupOldImpressionsJob lee config('helpdeskcampaigns.impressions_retention_days', 180). Las webhooks nunca pueden configurarse vía config (siempre []), y aunque el default de retención funciona, la clave no está documentada en el archivo. Drift entre código y config publicable.
- **Fix:** Añadir a config/config.php: 'impressions_retention_days' => env... no — usar valores con default y permitir override por env SOLO dentro del config: 'impressions_retention_days' => 180, 'webhooks' => []. Documentar el shape de cada webhook { url, secret?, events? }.

#### 🟡 [MEDIUM/performance] Accessor impressions_count puede ejecutar query (riesgo N+1 si se serializa sin withCount)
- **Archivo:** `app/Models/Campaign.php:113`
- **Esfuerzo:** small
- **Problema:** getImpressionsCountAttribute() devuelve la columna denormalizada si está presente, pero si no, hace $this->impressions()->count(). En la vista show.blade y en average_daily_impressions se invoca este accessor; en listados sin withCount('impressions') esto provoca un COUNT por fila (N+1). getConversionsCountAttribute() SIEMPRE hace una query agregada (no usa el contador denormalizado). El accessor ctr llama a ambos, duplicando queries. Como la BD ya tiene impressions_count/clicks_count denormalizados, no deberían recalcularse.
- **Fix:** Preferir las columnas denormalizadas impressions_count/clicks_count del modelo para los accessors. getConversionsCountAttribute() debería leer $this->clicks_count en lugar de la query SUM. Asegurar withCount o uso de columnas denormalizadas en todos los listados.

#### 🟡 [MEDIUM/performance] VariantSelector no cachea ni eager-loadea variantes: query por impresión en hot-path
- **Archivo:** `app/Services/VariantSelector.php:18`
- **Esfuerzo:** medium
- **Problema:** pick() ejecuta una query a helpdesk_campaign_variants en cada recordView (endpoint público de alto tráfico). El comentario del controller promete respuestas <50ms, pero esta query (más la de TargetingService::matchesSegments que puede hacer whereHas a Engagement) se ejecuta síncronamente antes de encolar el job. Bajo carga, esto añade latencia y carga de BD evitable.
- **Fix:** Cachear las variantes activas por campaña en Redis (TTL corto, invalidar al editar variantes), igual que se hace con FrequencyCapService. Mover, si es posible, la selección de variante al job, o memorizar el set de variantes en el objeto Campaign ya cargado.

#### 🟡 [MEDIUM/tests] Test test_campaign_controller_route_registered apunta a un nombre de ruta inexistente
- **Archivo:** `tests/Feature/CampaignsControllerTest.php:19`
- **Esfuerzo:** trivial
- **Problema:** El test filtra por la ruta con nombre 'manager.helpdesk-campaigns.index' y asserta isNotEmpty(). Las rutas reales se llaman 'helpdesk.campaigns.index' (routes/managers.php). El test por tanto falla siempre. Lo mismo ocurre con redirect a 'manager.helpdesk-campaigns.edit' que el controller store()/duplicate() usa (líneas 78 y 260) — ese nombre de ruta NO existe, por lo que store() y duplicate() lanzarían RouteNotFoundException al redirigir.
- **Fix:** Corregir el test a 'helpdesk.campaigns.index'. Crítico además: en CampaignsController store() (línea 78) y duplicate() (línea 260) cambiar route('manager.helpdesk-campaigns.edit', ...) por route('helpdesk.campaigns.edit', ...). Sin esto, crear o duplicar una campaña revienta tras guardar.

#### ⚪ [LOW/bug] CampaignVariant: impressions_count/clicks_count no son fillable y faltan en analytics
- **Archivo:** `app/Models/CampaignVariant.php:17`
- **Esfuerzo:** small
- **Problema:** $fillable de CampaignVariant es [campaign_id, label, weight, content, appearance]; no incluye impressions_count ni clicks_count (que sí existen en la migración 2026_05_06_095917 y se castean a integer). No hay ningún listener que incremente esos contadores por variante (UpdateCampaignImpressionCounters solo actualiza el campaign padre, no la variante de la impresión, pese a que la impresión guarda variant_id). El A/B testing por tanto no produce métricas por variante, y getCtrAttribute() siempre dará 0.
- **Fix:** En UpdateCampaignImpressionCounters, si $event->impression->variant_id existe, incrementar también CampaignVariant impressions_count/clicks_count. Añadir relación variant() en CampaignImpression. Los contadores no necesitan ser fillable (increment), pero validar que las migraciones los inicializan a 0.

#### ⚪ [LOW/bug] diffInMinutes/diffInDays sin abs ni floor: posibles valores negativos/flotantes
- **Archivo:** `app/Services/FrequencyCapService.php:54`
- **Esfuerzo:** trivial
- **Problema:** En Carbon 2/3 (Laravel 12 usa Carbon 3) now()->diffInMinutes($lastSeen) devuelve float y con signo según el orden de los argumentos. Aquí $lastSeen es pasado, así que el resultado puede ser negativo, y la comparación < $campaign->cooldown_minutes podría no comportarse como se espera en bordes. Mismo patrón en Campaign::getAverageDailyImpressionsAttribute (now()->diffInDays($this->published_at)) y CampaignImpression::getTimeToClickAttribute. El controller show() hace now()->diffInDays($campaign->published_at) para days_active, que puede dar negativo si published_at es futuro.
- **Fix:** Usar valores absolutos y casteo explícito: (int) abs($lastSeen->diffInMinutes(now())). Revisar todos los usos de diffIn* para fijar el orden y abs.

#### ⚪ [LOW/quality] Comparaciones de status con strings: frágiles si status pasara a enum cast
- **Archivo:** `app/Models/Campaign.php:158`
- **Esfuerzo:** medium
- **Problema:** Todo el módulo compara status con literales string ('active', 'paused', 'draft', etc.) en modelo, servicios, jobs, controller y blade. Hoy status es varchar sin cast, así que funciona, pero el proyecto advierte explícitamente que comparar un enum-cast con === string es un bug recurrente. Si en el futuro se añade un cast a enum (como sugiere el comentario 'status enum gains pending_approval' en la migración), todas estas comparaciones romperían silenciosamente.
- **Fix:** Introducir un enum CampaignStatus (string-backed) o al menos constantes de clase, y centralizar las transiciones. Evita literales dispersos y previene el bug enum-vs-string si se castea más adelante.

#### ⚪ [LOW/security] DispatchCampaignWebhooks permite esquema http y no re-resuelve IP tras parseo (SSRF parcial)
- **Archivo:** `app/Listeners/DispatchCampaignWebhooks.php:91`
- **Esfuerzo:** small
- **Problema:** isSafeUrl() bloquea localhost y rangos privados solo cuando el host ES literalmente una IP, pero un host DNS que resuelve a 127.0.0.1 o a una IP interna (rebinding) pasa el filtro. Además se permite scheme 'http'. La firma HMAC usa json_encode($payload) que puede diferir del cuerpo realmente enviado por Http::post si éste re-serializa, rompiendo la verificación en el receptor. El catch re-lanza la excepción (throw $e) lo cual está bien para reintentos, pero combinado con tries=3 puede martillear un endpoint caído.
- **Fix:** Resolver el host a IP (gethostbyname) y validar el rango antes de enviar; considerar forzar https. Para la firma, firmar exactamente el string enviado (->withBody(json_encode($payload),'application/json')). Es aceptable para un MVP interno, prioridad baja.

#### ⚪ [LOW/quality] CampaignResource usa whenCounted+when para clicksCount de forma inconsistente y genera URLs web
- **Archivo:** `app/Http/Resources/CampaignResource.php:26`
- **Esfuerzo:** small
- **Problema:** impressionsCount usa whenCounted('impressions') (requiere withCount), pero clicksCount usa $this->when(isset($this->clicks_count), ...) que detecta el alias del withCount('impressions as clicks_count'); si en otro endpoint se carga la columna denormalizada clicks_count el significado cambia. Las 'urls' apuntan a rutas web (helpdesk.campaigns.show/edit/statistics) desde una API REST: mezcla contextos (un cliente de API no necesariamente puede acceder a rutas web auth). No se exponen los contadores denormalizados directamente.
- **Fix:** Unificar: exponer 'impressionsCount' => $this->impressions_count y 'clicksCount' => $this->clicks_count desde las columnas denormalizadas (camelCase), o usar whenCounted consistentemente para ambos. Reconsiderar incluir URLs web en el Resource de API.

#### ⚪ [LOW/ux] Estilos inline en vistas (style="") violan la convención del proyecto
- **Archivo:** `resources/views/managers/campaigns/index.blade.php:90`
- **Esfuerzo:** small
- **Problema:** index.blade.php usa style="width: auto;" en selects (líneas 90, 98, 124); templates.blade.php usa style con cursor/border/min-height (líneas 36, 40, 55, 58); show.blade.php usa style en barras de progreso y contenedor de gráfico (líneas 234, 236, 294). El proyecto prohíbe explícitamente style="" inline (regla blade-views y memoria del proyecto). El ancho dinámico de la barra de progreso (width:{{ ctr }}%) es el único caso difícilmente evitable sin CSS var.
- **Fix:** Mover los estilos fijos a clases CSS en public/css/campaigns.css. Para el ancho dinámico de la barra, usar una CSS custom property con data-attribute o aria-valuenow + CSS. Reemplazar 'width:auto' por una clase utilitaria.

#### ⚪ [LOW/quality] Migración original de impresiones crea columna status no usada y duplica intención con clicked_at
- **Archivo:** `database/migrations/2025_12_29_020935_create_helpdesk_campaign_impressions_table.php:17`
- **Esfuerzo:** small
- **Problema:** La tabla helpdesk_campaign_impressions se creó con una columna status default 'shown' (e índice sobre status), pero el modelo CampaignImpression no la lista en fillable ni la usa en ninguna parte; el estado real (vista vs clic) se deriva de clicked_at. Columna e índice muertos. Hay además dos esquemas de timestamps: la migración original define timestamps() (created_at/updated_at) pero el modelo declara $timestamps=false y usa viewed_at; created_at queda como cast pero sin gestión.
- **Fix:** Eliminar la columna status y su índice si no se usa (migración de limpieza), o documentar su propósito. Verificar que created_at se sigue poblando o retirarlo del casts() del modelo.

### HelpdeskEmailLog

_Módulo de auditoría centralizada de emails bien estructurado: captura via eventos MessageSending/MessageSent, redacción de cuerpos sensibles, retención/purga programada, exportación CSV en streaming y preview en iframe sandbox. La calidad general es alta y respeta la mayoría de convenciones del proyecto (Form Requests con permisos Spatie, Policy registrada, casts enum, FA6, jQuery, sin inline styles). Los riesgos reales se concentran en el orden de "strip headers" tras la creación del log (posible fuga de cabeceras internas si falla el insert), la correlación ambigua de la fila "queued" y algo de código muerto._


#### 🟠 [HIGH/security] Las cabeceras internas X-* pueden filtrarse al destinatario si falla EmailLog::create()
- **Archivo:** `app/Listeners/LogEmailQueued.php:40-61`
- **Esfuerzo:** trivial
- **Problema:** En handle(), stripInternalHeaders($message) (línea 58) se ejecuta DESPUÉS de EmailLog::create() (línea 40), ambos dentro del mismo try. Si create() lanza (p.ej. from_address NULL en columna NOT NULL cuando $from y config('mail.from.address') son null; o un subject que supera 500 chars; o cualquier fallo de BD), el catch (Throwable) lo traga con un Log::warning y stripInternalHeaders NUNCA se ejecuta. Resultado: las cabeceras internas X-Mailable-Class, X-Email-Module, X-Entity-Type, X-Entity-Id llegan al destinatario real, exponiendo namespaces internos, IDs de entidad y la estructura de módulos. El propio docblock declara que 'never reach the recipient', invariante que aquí se rompe.
- **Fix:** Eliminar las cabeceras ANTES (o garantizado independientemente) de persistir el log. Mover stripInternalHeaders($message) al inicio del try (tras leer el contexto/headers con contextOf/ensureMessageId) y/o envolver solo la persistencia en su propio try, dejando el strip en un finally. Patrón sugerido: leer contexto -> stripInternalHeaders -> try { EmailLog::create(...) } catch { Log::warning }.

#### 🟡 [MEDIUM/bug] from_address puede insertar NULL en columna NOT NULL y romper el registro silenciosamente
- **Archivo:** `app/Listeners/LogEmailQueued.php:43`
- **Esfuerzo:** trivial
- **Problema:** 'from_address' => $from?->getAddress() ?: config('mail.from.address'). La migración define from_address como string(255) NOT NULL (2026_05_07_100000_create_email_logs_table.php:18). Si el mensaje no tiene From y config('mail.from.address') es null/'' (entornos sin MAIL_FROM_ADDRESS), se intenta insertar NULL -> SQLSTATE 23000, capturado por el catch del listener. El email se envía pero NO queda registrado, y además dispara el bug de fuga de cabeceras (#1). LogEmailSent.php:52 tiene el mismo patrón.
- **Fix:** Aplicar un fallback no-nulo: $from?->getAddress() ?: (config('mail.from.address') ?: 'unknown@localhost'), o hacer la columna nullable en una migración. Añadir test que envíe sin From ni config de from.

#### 🟡 [MEDIUM/bug] Correlación ambigua del fallback en LogEmailSent puede marcar como 'sent' la fila equivocada
- **Archivo:** `app/Listeners/LogEmailSent.php:72-94`
- **Esfuerzo:** small
- **Problema:** Cuando no hay match por Message-ID, findQueued() busca una fila 'queued' por subject + whereJsonContains(to_addresses, recipients[0]) creada en los últimos 5 min, con latest('id'). Si se envían dos correos idénticos (mismo asunto y primer destinatario) en esa ventana, o un broadcast con el mismo asunto, puede transicionar a 'sent' la fila incorrecta y dejar la otra colgada en 'queued' (que luego prune marcará como 'failed' aunque sí se envió). El camino por Message-ID es fiable, pero el fallback introduce esta condición de carrera/ambigüedad.
- **Fix:** Restringir más el fallback: comparar el conjunto completo de destinatarios (no solo el primero) y/o limitar a filas sin message_id ya asignado; considerar marcar solo si hay exactamente un candidato (count===1) y, si hay varios, no transicionar para evitar falsos positivos.

#### ⚪ [LOW/quality] El reenvío genera una fila de log huérfana sin contexto
- **Archivo:** `app/Jobs/ResendEmailLogJob.php:40-54`
- **Esfuerzo:** small
- **Problema:** ResendEmailLogJob usa Mail::html(...), que dispara MessageSending/MessageSent y crea una NUEVA EmailLog sin mailable_class, module, entity ni causer (corre en el worker, Auth::id() = null). Además no copia bcc_addresses ni adjuntos del original. Cada reenvío deja un registro 'sent' descontextualizado que ensucia stats y filtros por módulo.
- **Fix:** Adjuntar cabeceras de contexto al reenvío (X-Email-Module, X-Entity-Type/Id, X-Mailable-Class del log original) usando ->withSymfonyMessage() o headers, o marcar metadata.resent_from = uid original; alternativamente actualizar la fila existente en lugar de crear una nueva.

#### ⚪ [LOW/performance] Listeners de mail no son colas: trabajo síncrono en el request de cada envío
- **Archivo:** `app/Listeners/LogEmailSent.php:23`
- **Esfuerzo:** medium
- **Problema:** LogEmailQueued y LogEmailSent son listeners síncronos (no implementan ShouldQueue) registrados sobre MessageSending/MessageSent. Cada email añade al request: lectura de Setting::get (cacheado 10 min, OK), parseo del cuerpo, un INSERT (queued) y un UPDATE/SELECT (sent). MessageSent además ejecuta consultas (findQueued con MATCH/whereJsonContains). Para envíos en bucle (campañas/newsletter) esto multiplica I/O dentro del ciclo de envío. La convención de eventos del proyecto recomienda ShouldQueue para procesado pesado.
- **Fix:** Evaluar mover el procesado a un listener encolado (ShouldQueue, cola 'emails'/'default') o a un Job, manteniendo solo el strip de cabeceras síncrono (que debe ocurrir antes del envío). Como mínimo documentar el trade-off.

#### ⚪ [LOW/quality] Código muerto: markAsSent(), markAsFailed(), scopeForEntity(), Policy::deleteAny()
- **Archivo:** `app/Models/EmailLog.php:144-156`
- **Esfuerzo:** trivial
- **Problema:** markAsSent() y markAsFailed() no se usan en ningún sitio del módulo (LogEmailSent usa forceFill()->save() y el comando prune usa update() directo). scopeForEntity (línea 188) y EmailLogPolicy::deleteAny (línea 33) tampoco se invocan. Aumentan superficie sin valor; markAsFailed además duplica la lógica de truncado de error.
- **Fix:** Eliminar markAsSent/markAsFailed si no forman parte de una API pública documentada, o consumirlos desde los listeners/comando para unificar la transición de estado. Mantener scopeForEntity solo si está pensado como API para otros módulos (documentarlo).

#### ⚪ [LOW/quality] computeStats usa SUM(condición) específico de MySQL/MariaDB
- **Archivo:** `app/Http/Controllers/EmailLogController.php:164-170`
- **Esfuerzo:** small
- **Problema:** computeStats() usa selectRaw("SUM(status = 'sent')") y SUM(created_at >= ?) que dependen de la coerción booleana->entero de MySQL/MariaDB. Es correcto en este proyecto (MariaDB en prod y test), pero es SQL no portable y mezcla literales de estado en crudo en lugar de EmailStatus::Sent->value. Si algún día cambia el motor o se ejecutan tests en SQLite, fallaría.
- **Fix:** Sustituir por COUNT(CASE WHEN status = ? THEN 1 END) con bindings usando EmailStatus::Sent->value, o por conteos agrupados (groupBy('status')). Bajo riesgo dado el entorno actual.

#### ⚪ [LOW/performance] Búsqueda mezcla MATCH...AGAINST (fulltext) con LIKE en la misma cláusula OR
- **Archivo:** `app/Http/Controllers/EmailLogController.php:195-201`
- **Esfuerzo:** small
- **Problema:** El filtro de búsqueda hace orWhereRaw('MATCH(recipients_index) AGAINST (?)') Y a la vez orWhere('recipients_index','like',$like). El OR con un LIKE de comodín inicial ('%...%') obliga a full scan y anula la ventaja del índice fulltext que se creó expresamente (migración 2026_05_12_140000). Además MATCH...AGAINST en modo natural ignora tokens cortos (<innodb_ft_min_token_size, por defecto 3), por lo que búsquedas cortas dependen solo del LIKE.
- **Fix:** Decidir una estrategia: usar fulltext en BOOLEAN MODE para términos cortos/parciales, o quitar el MATCH y dejar solo LIKE si los datasets son moderados, o separar la búsqueda fulltext del LIKE. Documentar el comportamiento de tokens cortos.

#### ⚪ [LOW/bug] max_body_bytes truncado por bytes con substr() puede cortar multibyte/HTML a media etiqueta
- **Archivo:** `app/Listeners/Concerns/InspectsMailMessage.php:52-58`
- **Esfuerzo:** small
- **Problema:** bodyOf() trunca con substr($body, 0, $max) por bytes. Para body_html esto puede partir un carácter UTF-8 multibyte (mojibake en el preview) o cortar dentro de una etiqueta/atributo, dejando HTML inválido que el iframe renderiza de forma impredecible. El comentario de truncado se concatena después, sin cerrar etiquetas.
- **Fix:** Para body_text usar mb_substr; para body_html considerar truncar respetando límites de carácter (mb_strcut) y, si importa, cerrar el HTML. Riesgo bajo porque el límite por defecto es 512KB.

#### ⚪ [LOW/config] Doble registro de menú/sidebar sin guardas y acoplamiento a NavService
- **Archivo:** `app/Providers/HelpdeskEmailLogServiceProvider.php:124-149`
- **Esfuerzo:** trivial
- **Problema:** registerMenus() comprueba class_exists(NavService::class) pero llama a registerSidebar/addItemsToSection en boot() siempre que el módulo esté habilitado; correcto. Menor: el título de sección 'Email' y labels están hardcodeados en español en el provider en lugar de usar __('helpdeskemaillog::...'), divergiendo del resto del módulo que sí está i18n.
- **Fix:** Usar claves de traducción para 'title'/'label' del menú igual que en las vistas, para consistencia multi-idioma.

#### ⚪ [LOW/tests] Tests usan RefreshDatabase en lugar de DatabaseTransactions (preferencia del proyecto)
- **Archivo:** `tests/Feature/EmailLogControllerTest.php:17`
- **Esfuerzo:** small
- **Problema:** Los 5 ficheros de test usan RefreshDatabase. La memoria del proyecto indica preferir DatabaseTransactions para evitar conflictos de ordering de migraciones en testing (commit 53225445 hizo exactamente ese cambio en ConversationsControllerTest). Dado que 236 ficheros del repo aún usan RefreshDatabase, no es bloqueante, pero va contra la dirección reciente.
- **Fix:** Migrar a DatabaseTransactions sobre un esquema previamente migrado (system_test_pristine) para alinear con la convención reciente y acelerar la suite.

#### ⚪ [LOW/security] iframe srcdoc del preview: defensa correcta pero frágil ante cambios de sandbox
- **Archivo:** `resources/views/emails/preview.blade.php:33-36`
- **Esfuerzo:** trivial
- **Problema:** El cuerpo HTML del email se inyecta con srcdoc="{{ $log->body_html }}" en un iframe con sandbox="allow-popups allow-popups-to-escape-sandbox" (sin allow-scripts) y referrerpolicy=no-referrer. Hoy es seguro (Blade escapa las comillas para el atributo y sin allow-scripts el JS embebido no ejecuta). El riesgo es de mantenibilidad: si alguien añadiera 'allow-scripts' al sandbox, el contenido arbitrario del email (controlado potencialmente por terceros) ejecutaría JS en el panel admin. No hay test que fije esta invariante.
- **Fix:** Añadir un comentario de advertencia explícito de que NO debe agregarse allow-scripts/allow-same-origin, y un test que asserte que el atributo sandbox no contiene allow-scripts. Opcional: servir el preview desde una ruta de origen separado.

#### ⚪ [LOW/security] bulkDestroy no autoriza vía Policy ni filtra por ownership (solo Form Request)
- **Archivo:** `app/Http/Controllers/EmailLogController.php:106-113`
- **Esfuerzo:** trivial
- **Problema:** bulkDestroy delega la autorización al BulkDeleteEmailLogsRequest (authorize() -> can('helpdeskemaillog.manage')), correcto y consistente con el patrón de Form Request del proyecto. A diferencia de destroy() (que llama $this->authorize('delete')), aquí no hay $this->authorize() ni logActivity por-registro; se registra solo el conteo agregado. Es aceptable, pero la inconsistencia entre destroy (Policy) y bulkDestroy (Form Request) puede confundir y bulkDestroy borra por uid sin pasar por la Policy.
- **Fix:** Unificar el patrón: o añadir $this->authorize('deleteAny', EmailLog::class) en bulkDestroy para que la Policy sea la fuente única de verdad, o documentar que el control va por el Form Request. Bajo riesgo (mismo permiso 'manage').

### HelpdeskErp

_Módulo de integración ERP (bridge HTTP a manager Oracle) bien estructurado: servicios delgados, jobs con ShouldQueue/ShouldBeUnique, cache stale-while-revalidate, webhook con HMAC, y cobertura de tests muy alta (~2.4k LOC de tests sobre ~1.9k LOC de producción, 10 archivos Feature). Los problemas reales son menores en su mayoría: un broadcast en canal público que filtra customer_id, un meta.retry_after que nunca se propaga al frontend (bug de integración), un WarmErpCacheJob sin failed()/backoff, y varias inconsistencias de convención (uso de DB:: en lugar de Model::query(), permisos no cubiertos por seeder, falta de audit middleware en algunas rutas)._


#### 🟡 [MEDIUM/security] Broadcast en Channel público filtra customer_id (Oracle ID) sin autorización
- **Archivo:** `app/Events/ErpOrdersReady.php:23-26`
- **Esfuerzo:** small
- **Problema:** ErpOrdersReady implementa ShouldBroadcast sobre un Channel PÚBLICO ('erp-orders-ready.'+md5(email)), no un PrivateChannel. broadcastWith() incluye 'customer_id' (el IDCLIENTE de Oracle). Cualquier cliente WebSocket que conozca/adivine el hash md5 del email (md5 de un email es trivialmente derivable si se conoce el email) puede suscribirse y recibir el customer_id real de Oracle. La convención del proyecto (events-listeners.md) exige PrivateChannel para datos específicos de usuario. No hay entrada en routes/channels.php que autorice este canal precisamente porque es público.
- **Fix:** Convertir a PrivateChannel y registrar autorización en routes/channels.php verificando can('helpdeskErp.view'). Si el canal debe permanecer simple, eliminar customer_id del payload y dejar solo email_hash+timestamp (el frontend ya hace refetch al recibir el evento). El Resource ya expone meta.broadcastChannel como 'erp-orders-ready.{hash}' sin prefijo 'private-', habría que actualizarlo en consecuencia.

#### 🟡 [MEDIUM/bug] meta.retry_after nunca se propaga: el Resource lee una clave que el Service jamás escribe
- **Archivo:** `app/Http/Resources/CustomerContextResource.php:43`
- **Esfuerzo:** small
- **Problema:** buildMeta() hace $this->data['meta']['retry_after'], pero ErpContextService::fetchCustomerData() (líneas 260-279) construye el resultado SOLO con las claves customer/orders/invoices/orders_loading — nunca crea una clave 'meta'. El manager devuelve meta.retry_after (ver CONTEXT.md:100) y el servicio lo lee en $ordersMeta (línea 252) pero solo usa $ordersMeta['loading'] y descarta retry_after. Resultado: cuando ordersLoading=true, el frontend recibe meta.retryAfter=null y no sabe en cuántos segundos reintentar, anulando el patrón de polling diseñado.
- **Fix:** En fetchCustomerData() propagar el retry_after del manager al resultado: añadir 'meta' => ['retry_after' => $ordersMeta['retry_after'] ?? null] cuando ordersLoading. Verificar que ttlFor/stripMeta no rompan al existir esa clave. Añadir test que verifique meta.retryAfter no nulo cuando orders_loading=true.

#### 🟡 [MEDIUM/security] Permisos helpdeskErp.refresh y rutas search/timeline/warmCache no quedan cubiertos por el seeder ni concuerdan
- **Archivo:** `database/seeders/HelpdeskErpPermissionsSeeder.php:16-21`
- **Esfuerzo:** trivial
- **Problema:** El seeder crea 4 permisos (view, refresh, health.view, orders.detail.view). Pero: (1) refresh() en el controller exige tanto helpdeskErp.view COMO helpdeskErp.refresh (línea 106), y refresh sí está en el seeder — ok. (2) El endpoint warmCache exige helpdeskErp.refresh — ok. (3) search() y timeline() exigen helpdeskErp.view — ok. El problema real es que NINGÚN rol no-admin recibe estos permisos: solo se asignan a admin/super-admin/super-administrador. Los agentes de helpdesk (que son los usuarios naturales del contexto ERP en un ticket) no tienen forma de obtener helpdeskErp.view salvo asignación manual. Si la intención es que agentes vean el contexto, el seeder debe contemplar el rol agente.
- **Fix:** Confirmar con el equipo qué rol opera el inbox de helpdesk y otorgarle al menos helpdeskErp.view (y opcionalmente orders.detail.view) en el seeder. Documentar explícitamente que refresh/health/warmCache quedan reservados a admin.

#### ⚪ [LOW/quality] WarmErpCacheJob no implementa failed() ni define backoff
- **Archivo:** `app/Jobs/WarmErpCacheJob.php:9-33`
- **Esfuerzo:** trivial
- **Problema:** Viola .claude/rules/jobs.md: todo Job debe definir tries/timeout/backoff y método failed() para logging. WarmErpCacheJob solo tiene tries=1 y timeout=600, sin backoff ni failed(). Aunque cada email se procesa en try/catch interno (best-effort), un fallo fatal del job (p.ej. memoria/timeout) se perdería sin log. LinkCustomerToErpJob y RefreshErpContextJob sí cumplen el patrón completo, esto es una inconsistencia.
- **Fix:** Añadir public int $backoff = 30; y un método failed(\Throwable $e): void que loguee con Log::warning el número de emails y el error. Seguir el patrón de RefreshErpContextJob.

#### ⚪ [LOW/quality] Inconsistencia de audit.access: search, health, cache.warm y timeline carecen del middleware de auditoría
- **Archivo:** `routes/api.php:7-41`
- **Esfuerzo:** trivial
- **Problema:** Las rutas context, timeline, refresh y orders.detail llevan middleware audit.access:erp,*. Pero /customers/search (acceso a PII de clientes por email/teléfono/NIF), /health, /cache/warm NO lo llevan. timeline SÍ lo lleva. La búsqueda de clientes es precisamente la operación más sensible (enumera PII de Oracle) y no queda auditada, mientras que la vista de un contexto concreto sí. Inconsistencia que deja un hueco de trazabilidad.
- **Fix:** Añadir ->middleware('audit.access:erp,customer_search') a la ruta search y, si aplica, a cache.warm. Decidir conscientemente si health necesita auditoría (probablemente no).

#### ⚪ [LOW/quality] Uso de DB:: directo en lugar de Eloquent en timeline y warm-cache
- **Archivo:** `app/Services/CustomerTimelineService.php:223 y app/Console/Commands/WarmErpCacheCommand.php:62`
- **Esfuerzo:** small
- **Problema:** CLAUDE.md y rules/models.md prohíben DB:: para datos de modelo, prefiriendo Model::query(). CustomerTimelineService::collectHelpdeskEvents usa DB::table('helpdesk_conversations') y WarmErpCacheCommand::collectEmails usa DB::table(...) sobre varias tablas helpdesk. Es defendible como acceso cross-módulo defensivo (Schema::hasTable/hasColumn antes), ya que la tabla puede no existir y el módulo no quiere acoplarse a los modelos de Helpdesk. Pero la conversación dispone del modelo Conversation y Customer en el módulo Helpdesk; podría usarse Conversation::query() envuelto en try/catch para cumplir convención.
- **Fix:** Si se mantiene DB:: por desacoplamiento, documentarlo con un comentario PHPDoc justificando la excepción. Idealmente usar los modelos de Helpdesk (Conversation/Customer) ya que ambos viven en el mismo ecosistema y HelpdeskErp ya depende de Modules\Helpdesk\Models\Customer.

#### ⚪ [LOW/bug] searchCustomer hace fallback a results[0] aunque ningún email coincida exactamente
- **Archivo:** `app/Services/ErpContextService.php:347-368`
- **Esfuerzo:** small
- **Problema:** searchCustomer($email) recorre resultados buscando coincidencia exacta de email (case-insensitive); si no la encuentra, retorna results[0] (primer resultado arbitrario del manager). Para una búsqueda por email esto puede devolver un cliente DISTINTO al solicitado (el manager hace búsqueda fuzzy/parcial), atribuyendo pedidos/facturas de otro cliente al email consultado. ErpCustomerLinkerService::searchByEmail (líneas 80-91) SÍ es estricto (solo enlaza con match exacto), lo que evidencia que el fallback de searchCustomer es laxo de más para contexto de cliente.
- **Fix:** Eliminar el fallback 'return $results[0]' en searchCustomer cuando el tipo de búsqueda es por email: si no hay coincidencia exacta de email, devolver null (cliente no encontrado) en vez de un cliente arbitrario. Añadir test con manager devolviendo varios clientes sin email exacto que verifique customer.found=false.

#### ⚪ [LOW/performance] maybeScheduleRefresh y recordPulse se ejecutan también sobre entradas de error/miss cacheadas
- **Archivo:** `app/Services/ErpContextService.php:32-38, 322-333`
- **Esfuerzo:** trivial
- **Problema:** En cache hit se llama maybeScheduleRefresh() sin distinguir si lo cacheado es un error transitorio (TTL 5s) o un miss (TTL 60s). Para errores con TTL=5 y stale_grace=60, la condición age >= (ttl - staleGrace) => age >= -55 es SIEMPRE verdadera, por lo que cada lectura de un error cacheado dispara un RefreshErpContextJob. Aunque el job es ShouldBeUnique(uniqueFor=30), genera dispatch innecesarios y, combinado con TTL de 5s, puede causar refresh continuo bajo error sostenido del manager.
- **Fix:** No programar refresh para entradas con _error ni para miss (found=false): añadir guard al inicio de maybeScheduleRefresh que retorne si isset($cached['_error']) o si !($cached['customer']['found'] ?? false). El stale-while-revalidate solo tiene sentido para datos válidos.

#### ⚪ [LOW/config] Dependencia dura HelpdeskPrestashop en module.json contradice el manejo defensivo del código
- **Archivo:** `module.json:12`
- **Esfuerzo:** trivial
- **Problema:** module.json declara "requires": ["HelpdeskPrestashop"] (dependencia obligatoria), pero CustomerTimelineService::psEvents (líneas 76-96) trata HelpdeskPrestashop como OPCIONAL (Module::has + class_exists + try/catch). Hay contradicción: si PS es realmente requerido, el chequeo defensivo es código muerto; si es opcional (lo que sugiere el diseño), 'requires' bloqueará la activación de HelpdeskErp cuando PS esté deshabilitado, rompiendo el módulo ERP sin necesidad.
- **Fix:** Decidir la semántica real. Si la integración PS es opcional (recomendado, dado el código), quitar HelpdeskPrestashop de 'requires' y dejar solo Helpdesk como dependencia implícita. Verificar que el orden de boot no asuma PS.

#### ⚪ [LOW/performance] warmCache endpoint procesa hasta 50 emails síncronos en el dispatch sin throttle dedicado
- **Archivo:** `app/Http/Controllers/Api/ErpContextController.php:206-222`
- **Esfuerzo:** small
- **Problema:** warmCache acepta hasta 50 emails y despacha un único WarmErpCacheJob(timeout 600s) que recorre los 50 emails secuencialmente llamando getCustomerContext (cada uno = 1 search + pool de 4 llamadas HTTP al manager). 50 emails => hasta ~250 llamadas HTTP secuenciales en un job de 10 min, golpeando Oracle. El endpoint comparte el throttle global 60,1 del grupo; un atacante con permiso refresh podría encolar warming masivo. Además no hay deduplicación entre jobs de warming concurrentes.
- **Fix:** Trocear el WarmErpCacheJob en sub-jobs (uno por email o chunks de 5) con concurrencia controlada, o aplicar throttle más estricto a la ruta cache.warm (p.ej. throttle:5,1). El WarmErpCacheCommand ya hace array_chunk de 25 — alinear el endpoint con chunks pequeños.

#### ⚪ [LOW/quality] Autorización duplicada en refresh()/health()/orderDetail() en el controller en lugar de Form Request o policy
- **Archivo:** `app/Http/Controllers/Api/ErpContextController.php:98-118, 126-138, 146-162, 184-198, 206-222`
- **Esfuerzo:** medium
- **Problema:** show() y timeline() usan correctamente CustomerContextRequest::authorize(). Pero refresh(), health(), orderDetail(), search() y warmCache() hacen comprobaciones inline de permiso con if(!user->can(...)) return 403, en lugar de Form Requests dedicados (convención del proyecto: validación/authorize SIEMPRE en Form Request). refresh() además repite validación de email inline (filter_var) que debería estar en un Form Request. Esto engorda el controller y duplica la lógica de 403.
- **Fix:** Crear RefreshContextRequest (authorize helpdeskErp.refresh + helpdeskErp.view, rules email), OrderDetailRequest (orders.detail.view), SearchCustomersRequest (view + rules q/type), WarmCacheRequest (refresh + rules emails array max 50). Mover los chequeos de permiso a authorize() y la validación a rules(). Health puede quedar con guard simple o policy.

#### ⚪ [LOW/integration] Manejo de fallo parcial en Http::pool puede atribuir datos incompletos como 'cliente encontrado' y cachearlos 600s
- **Archivo:** `app/Services/ErpContextService.php:234-279, 299-317`
- **Esfuerzo:** small
- **Problema:** fetchCustomerData paraleliza 4 llamadas (summary/balance/orders/invoices). Si summary/balance fallan pero la búsqueda inicial encontró al cliente, found=true igualmente y se devuelven name/nif/balance como null. ttlFor cachea ese resultado 'found' parcial 600s (10 min) porque no hay _error ni orders_loading. Un fallo transitorio en balance/summary se congela 10 minutos con datos incompletos, sin posibilidad de reintento hasta expiración.
- **Fix:** Detectar fallo del summary (la respuesta canónica del cliente) y, si !$responses['summary']->successful(), tratar como error transitorio (TTL corto, ttlFor con _error) en vez de cachear 10 min un perfil mutilado. Loguear los códigos de estado de cada respuesta del pool para diagnóstico.

### HelpdeskHelpcenter

_Módulo de base de conocimiento bien estructurado: Form Requests con authorize() Spatie reales, Policies registradas, Resources camelCase/ISO8601, Job con ShouldQueue/tries/timeout/backoff/failed(), observers para sincronizar contadores y caché, y buena cobertura de tests Feature (votos, manager, público, traducciones). Los problemas más serios son funcionales: toda la infraestructura de embeddings/búsqueda semántica está muerta (config services.openai inexistente + EmbeddingsService::search() nunca se consume), y el widget cuenta artículos por una columna legacy (category_id) que el controller nunca rellena. Hay también un endpoint manager sin authorize() y varias deudas de convención (RefreshDatabase, validación inline en API widget, env() en config con cast)._


#### 🟠 [HIGH/integration] Infraestructura de embeddings completamente muerta: config services.openai no existe
- **Archivo:** `app/Services/EmbeddingsService.php:119-120`
- **Esfuerzo:** small
- **Problema:** callEmbeddingApi() lee config('services.openai.key') y config('services.openai.embedding_model'), pero no existe ninguna clave 'openai' en config/services.php ni en ningún config del repo (verificado con grep). El resultado es que config('services.openai.key') siempre devuelve '' aunque OPENAI_API_KEY esté en .env, así que generateForArticle() siempre registra el warning 'OPENAI_API_KEY not configured' y devuelve 0 chunks. El Job RegenerateArticleEmbeddingsJob, el listener EmbedArticleOnSave, el observer de traducciones y el comando programado semanal helpcenter:embeddings:reindex se ejecutan pero nunca producen embeddings.
- **Fix:** Añadir un bloque 'openai' => ['key' => env('OPENAI_API_KEY'), 'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small')] a config/services.php (o al config del módulo). Documentar la variable en .env.example. Si la feature no se va a usar, eliminar Job/Listener/Command/Service/migración de embeddings para reducir superficie.

#### 🟠 [HIGH/quality] EmbeddingsService::search() nunca se consume — búsqueda semántica no conectada
- **Archivo:** `app/Services/EmbeddingsService.php:201-240`
- **Esfuerzo:** medium
- **Problema:** Se construye toda la tubería de embeddings (tabla helpdesk_helpcenter_article_embeddings, generación de chunks, llamadas a OpenAI, ranking por similitud coseno) pero search() no se invoca desde ningún controller, servicio ni ruta (verificado con grep). La búsqueda pública (HelpCenterPublicController::search) y la del widget (HelpcenterWidgetService::searchArticles) usan LIKE/FULLTEXT, no embeddings. Es código + coste de OpenAI + complejidad sin valor entregado.
- **Fix:** O bien conectar search() en HelpCenterPublicController::search/HelpcenterWidgetService como ruta de búsqueda semántica con fallback a fulltext, o eliminar la tubería completa (Service::search, Job, Command, Listener, observer de traducciones, migración de embeddings).

#### 🟠 [HIGH/bug] Widget cuenta artículos por columna legacy category_id que el controller nunca rellena
- **Archivo:** `app/Services/HelpcenterWidgetService.php:16-36`
- **Esfuerzo:** medium
- **Problema:** getWidgetData() calcula los contadores por categoría con ->whereNotNull('category_id')->groupBy('category_id'). Pero storeArticle()/updateArticle() crean artículos sin category_id (NULL) y asocian la categoría/sección vía el pivote helpdesk_helpcenter_category_article. La migración 2026_05_01_700000 hizo category_id nullable precisamente porque 'el pivote maneja las asociaciones'. Resultado: todos los artículos creados por el panel tienen category_id=NULL y el contador 'count' del widget será 0 para esas categorías.
- **Fix:** Calcular los contadores vía el pivote: contar artículos publicados+activos por category_id de helpdesk_helpcenter_category_article (joineando sección→parent para agregar al nivel categoría raíz), igual que resolveCategorySection() resuelve la jerarquía. No depender de la columna legacy category_id.

#### 🟡 [MEDIUM/security] Endpoint manager searchArticles sin authorize()
- **Archivo:** `app/Http/Controllers/Managers/HelpCenterController.php:439-460`
- **Esfuerzo:** trivial
- **Problema:** searchArticles() (ruta manager.helpcenter.articles.search, registrada en loadAgentRoutes con solo ['web','auth','throttle']) es el único método del controller manager sin $this->authorize(). Cualquier usuario autenticado (p.ej. un agente sin permisos de helpcenter) puede listar títulos/extractos de artículos publicados. Bajo impacto (solo publicados) pero rompe la convención de autorizar siempre y es inconsistente con articlesIndex que sí llama authorize('viewAny').
- **Fix:** Añadir $this->authorize('viewAny', HelpCenterArticle::class) al inicio de searchArticles(), o si es intencionalmente abierto a agentes, documentar y usar un Gate específico.

#### 🟡 [MEDIUM/security] Endpoints del widget helpcenter sin throttle por-endpoint
- **Archivo:** `../HelpdeskLivechat/routes/widget.php:29-32`
- **Esfuerzo:** trivial
- **Problema:** Las rutas del widget /helpcenter, /helpcenter/search, /helpcenter/articles/{id} y .../feedback no tienen middleware throttle por-endpoint, a diferencia del resto de rutas del archivo (conversation, tickets, etc.). El comentario de cabecera dice que el throttle global se eliminó del grupo 'para que los límites no se multipliquen', dejando estas 4 rutas públicas sin rate limit. apiArticleFeedback además incrementa contadores en BD por cada llamada (ver hallazgo de feedback) — abusable para inflar/deflactar helpful_count sin coste.
- **Fix:** Envolver estas rutas en grupos throttle (p.ej. helpcenter/search y article a 60,1; feedback a 10,1 anti-spam) como el resto del archivo.

#### 🟡 [MEDIUM/bug] Feedback del widget incrementa contadores sin deduplicación ni límite
- **Archivo:** `app/Services/HelpcenterWidgetService.php:127-140`
- **Esfuerzo:** medium
- **Problema:** recordFeedback() hace $article->increment('helpful_count'|'unhelpful_count') sin ninguna comprobación de cookie/IP ni unicidad (a diferencia del ArticleVoteController que sí deduplica por cookie_id/ip_hash y usa una tabla con unique index). Combinado con la ausencia de throttle, cualquiera puede inflar helpful_count llamando repetidamente al endpoint. Además estos contadores (incrementados aquí) son distintos de los que recalcula HelpCenterArticleVoteObserver::syncCounts() desde la tabla de votos, así que ambas fuentes pueden divergir y pisarse.
- **Fix:** Unificar el feedback del widget con el sistema de votos (ArticleVoteController/HelpCenterArticleVote) que ya deduplica, en lugar de increment() directo. Si debe quedar separado, deduplicar por cookie/IP y añadir throttle.

#### 🟡 [MEDIUM/bug] Posible 500 por colisión de unique index en voto con IP compartida
- **Archivo:** `app/Http/Controllers/Api/ArticleVoteController.php:30-53`
- **Esfuerzo:** medium
- **Problema:** La migración 2026_05_13_000002 añadió unique(article_id, cookie_id) y unique(article_id, ip_hash). El controller busca un voto existente por (cookie_id OR ip_hash) y si no existe hace create(). Dos usuarios distintos tras la misma IP (NAT/oficina): (1) el segundo, sin cookie coincidente pero con ip_hash igual, encontrará el voto del primero por la cláusula OR y lo SOBREESCRIBIRÁ (un usuario altera el voto de otro); (2) en condición de carrera dos inserts concurrentes con el mismo (article, ip_hash) lanzarán QueryException no capturada → HTTP 500 en vez de respuesta controlada.
- **Fix:** Decidir la clave de identidad (cookie_id como principal) y no mezclar OR sobre ip_hash para la sobrescritura; envolver el create() en try/catch de UniqueConstraintViolationException devolviendo {already_voted:true} o usar updateOrCreate por (article_id, cookie_id). Revisar si el unique por ip_hash es deseable dado el NAT.

#### 🟡 [MEDIUM/tests] Tests usan RefreshDatabase en lugar de DatabaseTransactions
- **Archivo:** `tests/Feature/HelpCenterManagerTest.php:15`
- **Esfuerzo:** trivial
- **Problema:** Los 4 archivos de test usan trait RefreshDatabase. La memoria/convención del proyecto desaconseja explícitamente RefreshDatabase manual en testing (destruye el esquema por conflictos de ordering entre conexiones mariadb/helpdesk) y el commit reciente 53225445 sustituyó RefreshDatabase por DatabaseTransactions en ConversationsControllerTest por esta razón. Aquí ya está $connectionsToTransact definido, señal de que se pretendía transacciones.
- **Fix:** Reemplazar use RefreshDatabase por use DatabaseTransactions en los 4 tests (HelpCenterManagerTest, HelpCenterPublicTest, HelpCenterVoteTest, HelpCenterTranslationTest), manteniendo $connectionsToTransact = ['mariadb','helpdesk'].

#### 🟡 [MEDIUM/performance] Listado público ordena por views_count sin índice y filtra is_published sin índice
- **Archivo:** `app/Http/Controllers/HelpCenterPublicController.php:18-52`
- **Esfuerzo:** small
- **Problema:** index() hace where('is_published', true)->orderByDesc('views_count') con paginación. La tabla helpdesk_helpcenter_articles no tiene índice en is_published ni en views_count (las migraciones solo indexan category_id, order, active y el fulltext title/body). En tablas grandes esto fuerza full scan + filesort en cada carga de la home del centro de ayuda. show() también ordena related por views_count.
- **Fix:** Añadir migración con índice compuesto ['is_published','views_count'] (y opcionalmente published_at) en helpdesk_helpcenter_articles para cubrir el patrón WHERE is_published ORDER BY views_count.

#### ⚪ [LOW/quality] Validación inline en API controller del widget (rompe convención Form Request)
- **Archivo:** `app/Http/Controllers/Api/HelpcenterWidgetController.php:48-54`
- **Esfuerzo:** trivial
- **Problema:** apiArticleFeedback() usa $request->validate(['helpful' => ['required','boolean']]) inline. La convención del proyecto exige Form Request para toda validación (nunca inline en controller). El resto del módulo cumple (StoreHelpCenterArticleRequest, VoteArticleRequest, etc.).
- **Fix:** Extraer a un Form Request (p.ej. ArticleFeedbackRequest con authorize() apropiado para endpoint público) y tiparlo en la firma.

#### ⚪ [LOW/config] env() con cast en config (anti-patrón config:cache)
- **Archivo:** `config/config.php:22-33`
- **Esfuerzo:** trivial
- **Problema:** El config castea env() inline: (int) env('HELPCENTER_PAGINATE_MANAGERS', 20), etc. Aunque está dentro de config/ (permitido), el cast (int) sobre env() es frágil: con config:cache en producción el valor se congela como entero, correcto, pero si la env está ausente y se cachea, los cambios posteriores no aplican. Más relevante: el módulo no expone un .env.example con HELPCENTER_* ni OPENAI_*; los valores quedan implícitos.
- **Fix:** Mantener env() solo en config (ok) pero documentar las variables (HELPCENTER_PAGINATE_*, HELPCENTER_VOTE_IP_SALT, OPENAI_API_KEY) en .env.example. Considerar quitar el cast (int) inline y castear en el punto de uso o confiar en valores por defecto enteros.

#### ⚪ [LOW/quality] Permisos sembrados pero nunca usados: articles.embed y articles.vote-moderate
- **Archivo:** `database/seeders/HelpdeskHelpcenterPermissionsSeeder.php:26-27`
- **Esfuerzo:** trivial
- **Problema:** Se crean y asignan los permisos helpdesk.helpcenter.articles.embed y helpdesk.helpcenter.articles.vote-moderate, pero no se referencian en ninguna Policy, Form Request ni controller (verificado con grep). Permisos huérfanos que ensucian la matriz de roles y sugieren features incompletas (moderación de votos, gestión de embeddings).
- **Fix:** O implementar las features que los justifican (un endpoint de moderación de votos protegido por vote-moderate; un control de re-embed protegido por embed) o eliminarlos del seeder.

#### ⚪ [LOW/security] Body de artículo en JSON del widget sin sanitizar (potencial XSS en consumidor)
- **Archivo:** `app/Services/HelpcenterWidgetService.php:114-124`
- **Esfuerzo:** small
- **Problema:** getArticle() devuelve 'body' => $article->content ?? $article->body con HTML crudo en la respuesta JSON del widget público (apiArticle). La vista pública usa {!! clean($articleBody) !!} (HTML Purifier), pero el widget entrega el HTML sin purificar; si el JS del widget lo inyecta con innerHTML sin sanitizar, hay XSS almacenado (el body lo escribe un manager, pero rompe defensa en profundidad).
- **Fix:** Aplicar clean() también al body devuelto por el widget (getArticle), o garantizar que el widget renderiza con sanitización. Idealmente purificar el body al guardar (en storeArticle/updateArticle) y servir ya limpio.

#### ⚪ [LOW/bug] Comando programado de re-embed solo locale 'es' aunque hay multi-idioma
- **Archivo:** `app/Providers/HelpdeskHelpcenterServiceProvider.php:64-66`
- **Esfuerzo:** small
- **Problema:** El scheduler ejecuta 'helpcenter:embeddings:reindex' semanalmente sin --locale, y el comando default es 'es'. Pero el módulo soporta 7 locales (config supported_locales) y las traducciones disparan embeddings por locale. El reindex programado solo regenera embeddings de 'es', dejando los demás idiomas desactualizados (cuando la feature funcione).
- **Fix:** Iterar supported_locales en el comando o programar una invocación por locale. Secundario respecto a que la feature está inerte (ver hallazgos de config openai).

#### ⚪ [LOW/quality] Inconsistencia draft vs active en filtros (widget exige active=true; controller nunca lo gestiona)
- **Archivo:** `app/Services/HelpcenterWidgetService.php:18,40,51,73`
- **Esfuerzo:** small
- **Problema:** El widget filtra where('active', true) además de is_published, pero el flujo de creación/edición del controller manager nunca expone ni actualiza 'active' (solo draft/is_published). 'active' queda en su default de BD (true) y es una tercera bandera de estado legacy paralela a draft/is_published, fácil de desincronizar y confusa. El controller público y los tests ignoran 'active'.
- **Fix:** Decidir una única fuente de verdad de publicación (is_published). Eliminar el filtro 'active' del widget o documentar su semántica; evitar tres banderas (active/draft/is_published) para el mismo concepto.

### HelpdeskLivechat

_Módulo de live chat (widget React embebible) bien estructurado y con cobertura de tests notable (23 archivos, ~100 métodos test) y un modelo de seguridad razonado (HMAC opcional, trusted origins, pubsub tokens, IP anonymization GDPR, sanitización con HTMLPurifier). El problema más serio y transversal es el doble-encoding de la columna `metadata` (cast a array pero asignada con json_encode), que corrompe el `widget_pubsub_token` al enviar el formulario pre-chat y obliga a parches defensivos por todo el código. Hay además un bug de UI en el valor por defecto de `position`, y varias oportunidades de calidad/perf (controllers que repiten lookups que la FormRequest ya validó, catch genérico que enmascara 404 como 500, accessor legacy)._


#### 🔴 [CRITICAL/bug] metadata se asigna con json_encode() sobre una columna casteada a array → doble-encoding y corrupción
- **Archivo:** `app/Services/Widget/WidgetConversationService.php:130,186 y app/Http/Controllers/Api/PreChatFormApiController.php:48-52`
- **Esfuerzo:** small
- **Problema:** Conversation::metadata está casteado a 'array' en el modelo (Modules\Helpdesk\Models\Conversation:69). El servicio escribe 'metadata' => json_encode($metadata) tanto al crear (línea 186) como al backfill del pubsub_token (línea 130). Verificado con tinker: el cast array vuelve a JSON-encodear el string, dejando en BD un valor doble-codificado ("\"{...}\"") y al releer $conv->metadata devuelve un STRING, no un array. Por eso TODO el código compensa con `is_array($x->metadata) ? ... : json_decode(...)` (channels.php:67-69, EngagementBridgeListener:157-159, el propio servicio:122-124). El daño real está en PreChatFormApiController::submit: hace getRawOriginal('metadata') + json_decode() una sola vez → obtiene un STRING; luego $metadata['pre_chat']=$formData escribe sobre un offset de string y al re-guardar destruye el widget_pubsub_token, rompiendo la autorización del canal de tiempo real (helpdesk-widget-conversation.{id}.{token}) para esa conversación tras enviar el pre-chat.
- **Fix:** Dejar de hacer json_encode manual: como la columna ya está casteada a array, asignar el array directamente: $conversation->update(['metadata' => $metadata]) y Conversation::create([..., 'metadata' => $metadata]). En PreChatFormApiController usar $metadata = $conversation->metadata ?? []; (ya devuelve array) en vez de getRawOriginal+json_decode. Añadir test que cree conversación, lea pubsub_token, envíe pre-chat y verifique que el token sigue intacto y metadata['pre_chat'] se guardó.

#### 🟡 [MEDIUM/bug] position por defecto 'right' no coincide con el formato 'bottom-right' que espera el widget
- **Archivo:** `app/Models/Channels/Web.php:282,299`
- **Esfuerzo:** trivial
- **Problema:** getWidgetConfig() devuelve 'position' => $this->widget_position ?? 'right' y 'widgetPosition' => ... ?? 'right'. Pero el FormRequest valida position in:bottom-right,bottom-left,top-right,top-left, los defaults del controller usan 'bottom-right', y el widget React (widget-store.ts:127 default 'bottom-right'; ChatBubbleLauncher.tsx:22 compara `position === 'bottom-right'`). Cuando un Web aún no tiene widget_position guardado, el config emite 'right', que el launcher trata como NO bottom-right → lo coloca a la izquierda. Inconsistencia de formato entre el legacy 'right'/'left' y el actual 'bottom-right'.
- **Fix:** Cambiar el fallback a 'bottom-right' en ambas líneas: $this->widget_position ?? 'bottom-right'. Verificar que no quede ningún Web con widget_position='right'/'left' en BD (migración de datos si aplica) o normalizar en el accessor.

#### 🟡 [MEDIUM/quality] Catch genérico de Throwable mapea cualquier error a 500/404 ocultando 'no encontrado' y 'no autorizado'
- **Archivo:** `app/Http/Controllers/Api/WidgetConversationController.php:43-48,63-68,86-91,120-125,145-150`
- **Esfuerzo:** small
- **Problema:** El servicio lanza RuntimeException con mensajes semánticos ('Conversation not found', 'Unauthorized access to conversation', 'Invalid widget token') pero todos los controladores los capturan como genérico \Throwable y devuelven códigos fijos: store→500, show→404, getMessages→500, sendMessage→500, close→500. Un acceso no autorizado a una conversación ajena (resolveOwnedConversation lanza 'Unauthorized') se reporta como 500 en sendMessage/getMessages/close, no como 403. Además ensucia logs de error con condiciones normales de negocio.
- **Fix:** Definir excepciones tipadas (ConversationNotFoundException, ConversationAccessDeniedException) o mapear los RuntimeException por mensaje a 404/403, y reservar el 500 para fallos reales. Como mínimo en sendMessage/getMessages devolver 403/404 ante acceso/no-encontrado en lugar de 500.

#### 🟡 [MEDIUM/tests] Falta cobertura de test del bug de metadata/pubsub_token y del mapeo de position
- **Archivo:** `tests/Feature/PreChatFormOwnershipTest.php y WidgetConversationFlowTest.php`
- **Esfuerzo:** small
- **Problema:** Existen tests de ownership del pre-chat, pero ninguno verifica que tras enviar el formulario pre-chat el widget_pubsub_token de la conversación sobreviva (el bug crítico de doble-encode pasó desapercibido precisamente por esto). Tampoco hay test del valor 'position' devuelto por getWidgetConfig ni del round-trip metadata array↔BD.
- **Fix:** Añadir: (1) test que cree conversación vía servicio, capture pubsub_token, llame al endpoint submit del pre-chat y asserte que Conversation::find()->metadata['widget_pubsub_token'] sigue intacto y metadata['pre_chat'] contiene los datos; (2) test unit de Web::getWidgetConfig() asegurando position default 'bottom-right'; (3) test que verifique que metadata se persiste como JSON de objeto (no string doble-codificado) consultando getRawOriginal.

#### ⚪ [LOW/performance] LivestreamController re-consulta el Web que el FormRequest ya validó (query duplicada)
- **Archivo:** `app/Http/Controllers/Api/LivestreamController.php:19-29`
- **Esfuerzo:** small
- **Problema:** IngestLivestreamEventsRequest::authorize() ya hace Web::where('website_token',$token)->where('enable_live_view',true)->exists() en cada request (sin caché). Luego el controller vuelve a resolver el Web con Cache::remember para validar pertenencia del inbox. En el endpoint más caliente del módulo (throttle 200/min, flush rrweb ~500ms) se ejecuta una query no cacheada en authorize + lookup en el controller por cada batch. El authorize del FormRequest NO usa caché (a diferencia de HeartbeatRequest que sí cachea el ::exists()).
- **Fix:** Cachear el ::exists() en IngestLivestreamEventsRequest::authorize() igual que HeartbeatRequest (clave 'helpdesklivechat:web:token:{token}:liveview'), y reutilizar el Web ya resuelto (vía request attribute como hace VerifyWidgetHmac) en el controller para evitar el segundo lookup.

#### ⚪ [LOW/security] Form Requests del widget con authorize() return true sin caché de existencia del Web
- **Archivo:** `app/Http/Requests/Widget/StoreWidgetConversationRequest.php:11, SendWidgetMessageRequest.php:12, EmailTranscriptRequest.php:11, CloseWidgetConversationRequest.php:11, MarkAsReadRequest.php:11, SubmitPreChatFormRequest.php:11`
- **Esfuerzo:** small
- **Problema:** Varios Form Requests del widget tienen authorize(){return true;}. Para endpoints públicos del widget esto es aceptable POR DISEÑO (la autorización real es ownership por customer_id/customer_email en el controller/servicio + middleware ValidateTrustedOrigin/VerifyWidgetHmac en el grupo). Sin embargo es inconsistente: WebRtcOfferRequest/WebRtcIceRequest/HeartbeatRequest/IngestLivestreamEventsRequest SÍ validan el Web/feature-flag en authorize(), mientras StoreWidgetConversationRequest no valida que website_token corresponda a un Web (lo valida luego el servicio lanzando excepción→500). No es un bypass de seguridad (hay ownership posterior), pero rompe la convención del proyecto y produce el 500 del hallazgo anterior.
- **Fix:** Documentar explícitamente en cada Form Request público por qué authorize() es true (autorización delegada a ownership + middleware). Opcional: mover la validación de existencia del website_token a StoreWidgetConversationRequest::authorize() (con caché) para devolver 403 limpio en vez de 500. No tocar la lógica de ownership que ya es correcta.

#### ⚪ [LOW/quality] Accessor legacy getTimeOnSiteAttribute mezclado con sintaxis Attribute moderna
- **Archivo:** `app/Models/WidgetSession.php:59-62`
- **Esfuerzo:** trivial
- **Problema:** El modelo usa la sintaxis moderna Attribute para ipAddress() (línea 69) pero define getTimeOnSiteAttribute() con el estilo legacy get{X}Attribute. Además llama $this->started_at->diffInSeconds(now()) sin null-safety: si started_at fuera null (registro creado sin el default) lanzaría error. Incumple la regla models.md (Accessors con clase Attribute, Laravel 11+).
- **Fix:** Convertir a Attribute::make(get: fn() => (int) ($this->started_at?->diffInSeconds(now()) ?? 0)). Verificar si este accessor se usa (no aparece en $appends, así que no provoca N+1; si no se consume en ningún sitio, eliminarlo).

#### ⚪ [LOW/quality] Modelo Web no declara casts para columnas key/is_closed-style ni tiene SoftDeletes; columnas key/is_closed de ConversationStatus no en fillable
- **Archivo:** `modules/Helpdesk/app/Models/ConversationStatus.php:15-25`
- **Esfuerzo:** trivial
- **Problema:** WidgetConversationService::getConversation() lee $conversation->status->key y closeConversation() filtra ConversationStatus::where('key','closed')->orWhere('is_closed',true). Las columnas key e is_closed EXISTEN (migración 2026_04_19_000003) por lo que NO rompe en runtime, pero NO están en $fillable ni en casts() de ConversationStatus (is_closed debería castearse a boolean). Es deuda menor de consistencia del modelo del módulo Helpdesk consumido aquí.
- **Fix:** Añadir 'key' al $fillable y 'is_closed' => 'boolean' a casts() de ConversationStatus (módulo Helpdesk). No bloquea HelpdeskLivechat pero conviene cerrarlo para evitar futuras sorpresas con mass-assignment de seeders.

#### ⚪ [LOW/ux] Estilos inline (style="") en la vista de settings del widget
- **Archivo:** `resources/views/settings/livechat/index.blade.php:377,468,492,501,519,555,742,764,786,912,930,931,974`
- **Esfuerzo:** small
- **Problema:** La vista de configuración usa varios style="width:14px" / style="max-width:50px" / style="width:65px" / style="color:inherit;background:transparent" inline. La regla blade-views.md prohíbe style inline (usar clases Bootstrap/CSS). Los iconos son Font Awesome 6 correctos (fas fa-*), no hay Tabler ni Livewire, y los estilos inline del email (conversation-transcript.blade.php) son aceptables por ser plantilla de correo. El problema se limita a la vista de settings.
- **Fix:** Extraer los anchos fijos a utilidades/clases CSS del módulo (p.ej. .lc-icon-fixed{width:14px}, .lc-color-swatch{max-width:50px}, .lc-num-input{width:65px}). Mantener los estilos inline solo en la plantilla de email.

#### ⚪ [LOW/security] Pre-chat form submit no pasa por ValidateTrustedOrigin/VerifyWidgetHmac
- **Archivo:** `routes/api.php:6-7 y app/Providers/HelpdeskLivechatServiceProvider.php:142-154`
- **Esfuerzo:** small
- **Problema:** Las rutas pre-chat-form (api/v1/helpdesk-livechat) se montan solo con ['api','throttle:60,1'], sin los middlewares ValidateTrustedOrigin ni VerifyWidgetHmac que sí protegen el grupo del widget (hd/api). La autorización efectiva en submit es el ownership por customer_id/customer_email (PreChatFormApiController::ownsConversation), que es correcto, pero queda fuera del control de trusted-origins y de la verificación HMAC de identidad cuando enforce_identity_verification está activo. Un atacante con un customer_email válido y conversation_id podría inyectar datos pre_chat sin pasar el HMAC.
- **Fix:** Montar el grupo de rutas pre-chat-form bajo los mismos middlewares del widget (ValidateTrustedOrigin + VerifyWidgetHmac) o documentar explícitamente por qué se excluyen. Confirmar que enforce_identity_verification deba aplicar también a este endpoint.

#### ⚪ [LOW/quality] extractSettings genera y persiste secret_key en una petición GET (index)
- **Archivo:** `app/Http/Controllers/Settings/LivechatSettingsController.php:156-157`
- **Esfuerzo:** small
- **Problema:** En index() (GET), extractSettings hace Setting::get('livechat.secret_key') ?? tap(Str::random(40), fn($k)=>Setting::set(...)). Es idempotente (firstOrCreate-like vía ??), así que no regenera en cada request una vez creado — distinto del antipatrón clásico. Pero realiza una escritura (Setting::set) dentro de un GET en la primera carga, lo que viola la semántica idempotente de GET y puede crear la fila bajo carga concurrente (dos GET simultáneos generan dos secret keys distintas, la segunda pisa la primera). El secret_key además solo se usa en la vista (HMAC display).
- **Fix:** Generar/persistir el secret_key fuera del GET: o en el seeder de settings, o en un comando de instalación, o con un Setting::firstOrCreate atómico. Como mínimo no escribir en BD durante index().

#### ⚪ [LOW/performance] WebRtcSignal usa ShouldBroadcastNow (síncrono) en endpoint público con throttle 60/min
- **Archivo:** `app/Events/WebRtcSignal.php:12`
- **Esfuerzo:** small
- **Problema:** WebRtcSignal implementa ShouldBroadcastNow, por lo que el broadcast a Reverb ocurre de forma síncrona dentro del request HTTP del visitante (offer/ice/end). Para signaling WebRTC la baja latencia justifica el now, pero acopla la latencia del endpoint a la disponibilidad de Reverb; si Reverb está caído/lento, el request del visitante se bloquea/falla. LivestreamBatchReceived (ShouldBroadcast, asíncrono) es el patrón correcto para el resto.
- **Fix:** Aceptable si la latencia es prioritaria, pero envolver el broadcast en try/catch o evaluar timeout corto hacia Reverb para que un fallo de broadcast no tumbe el request de signaling. Documentar la decisión.

#### ⚪ [LOW/security] Canal público widget-session.{token} autoriza solo verificando existencia del token
- **Archivo:** `routes/channels.php:37-43`
- **Esfuerzo:** medium
- **Problema:** El canal widget-session.{sessionToken} (cuando Engagement está desactivado) es público y su callback solo comprueba WidgetSession::where('session_token',$token)->exists(). El comentario documenta extensamente el modelo de amenaza (token aleatorio de 32 chars como credencial, enumeración inviable). Es una decisión consciente y razonable, pero el token de sesión viaja en localStorage y en la URL del canal; cualquier XSS en el sitio host expondría el token y permitiría escuchar señales de personalización de ese visitante.
- **Fix:** Mantener el diseño actual (riesgo bajo), pero considerar la migración sugerida en el propio comentario (token secundario pubsub en el nombre del canal, como helpdesk-widget-conversation) si en el futuro se emiten datos sensibles por este canal. Sin acción inmediata.

### HelpdeskPrestashop

_Módulo stateless de integración con PrestaShop (vía alsernetbridge, HTTP+HMAC). La capa de servicio está bien diseñada (circuit breaker, stale-while-revalidate, idempotencia, timeouts, HMAC con timestamp). Sin embargo hay un bug de autorización crítico que rompe el endpoint de detalle de pedido, y una divergencia de casing camelCase/snake_case entre los API Resources y el JS consumidor (tickets.js) que deja la UI de pedidos PrestaShop renderizando datos incompletos pese a obtenerlos correctamente. Además faltan endpoints que el frontend ya invoca (notes, cache/warm), creando drift con HelpdeskErp._


#### 🔴 [CRITICAL/security] Endpoint order detail siempre devuelve 403: permiso inexistente
- **Archivo:** `app/Http/Requests/OrderDetailRequest.php:11`
- **Esfuerzo:** trivial
- **Problema:** OrderDetailRequest::authorize() comprueba 'helpdeskprestashop.orders.detail.view', pero el seeder NUNCA crea ese permiso: crea 'helpdeskprestashop.orders.view' (database/seeders/HelpdeskPrestashopPermissionsSeeder.php:22) y ADEMÁS renombra explícitamente el permiso antiguo 'helpdeskprestashop.orders.detail.view' → 'helpdeskprestashop.orders.view' (línea 50). Por tanto el permiso comprobado no existe en BD tras ejecutar el seeder. Con Spatie, $user->can('permiso-inexistente') vía Gate devuelve false (deniega), así que el endpoint GET /orders/{order}/detail responde 403 para todos los usuarios, incluidos admins. El test test_order_detail_returns_data (CustomerContextTest.php:213) otorga 'helpdeskprestashop.orders.view' y espera 200 — debe fallar.
- **Fix:** Cambiar OrderDetailRequest::authorize() a usar 'helpdeskprestashop.orders.view' (el nombre que el seeder realmente crea), alineándolo con el rename. Verificar ejecutando el test test_order_detail_* tras migrar el esquema de tests.

#### 🔴 [CRITICAL/integration] API Resources camelCase no coinciden con el JS consumidor (snake_case): UI de pedidos PrestaShop rota
- **Archivo:** `app/Http/Resources/CustomerContextResource.php:41`
- **Esfuerzo:** small
- **Problema:** CustomerContextResource remapea claves de nivel superior a camelCase ('ordersCount', 'lastOrderAt', 'currencySign', 'placedAt', 'requiresDocumentation', 'saleType') siguiendo la convención API del proyecto. Pero el frontend tickets.js (modules/HelpdeskTickets/public/js/tickets.js) consume esos campos en snake_case: renderPrestashopContext lee c.orders_count y c.last_order_at (siempre muestran 0 y '—'), buildOrderCard lee o.currency_sign, o.placed_at (fecha siempre vacía: ''.substring(0,10)), o.requires_documentation (alerta de documentación nunca aparece) y o.sale_type. Resultado: los datos SÍ se obtienen de PrestaShop pero la UI los renderiza incompletos/incorrectos. El módulo hermano HelpdeskErp/CustomerContextResource usa snake_case (credit_limit, payment_terms, expected_date) coincidiendo con su JS — los dos integraciones divergieron. Las claves anidadas (lines, totals, state) se pasan verbatim y sí funcionan; solo los campos remapeados de nivel superior están rotos.
- **Fix:** Decidir un único contrato. Opción A (mínimo riesgo, consistente con HelpdeskErp): devolver snake_case en el Resource para las claves que lee el JS (orders_count, last_order_at, currency_sign, placed_at, requires_documentation, sale_type). Opción B (consistente con convención API camelCase): actualizar tickets.js a camelCase. Añadir un test que asserte el JSON exacto del Resource para evitar regresiones.

#### 🟠 [HIGH/integration] Frontend llama endpoints que no existen en el módulo (notes, cache/warm)
- **Archivo:** `routes/api.php:1`
- **Esfuerzo:** medium
- **Problema:** tickets.js invoca POST /api/helpdeskprestashop/orders/{id}/notes (línea 983) y POST /api/helpdeskprestashop/cache/warm (línea 1053), pero routes/api.php solo define customers context/refresh y orders detail/start-return. Ambas llamadas dan 404: 'notes' muestra toastr.error('No se pudo añadir la nota.') al agente; 'cache/warm' falla en silencio. El servicio conoce la acción de escritura 'order.add_note' (PrestashopContextService.php:196) pero no expone método ni ruta. El módulo hermano HelpdeskErp SÍ tiene /cache/warm y /health (modules/HelpdeskErp/routes/api.php:40,36). Existe WarmPsCacheJob y WarmPsCacheCommand pero sin trigger HTTP.
- **Fix:** Añadir ruta+controlador POST /cache/warm (valida emails[], encola WarmPsCacheJob) y POST /orders/{order}/notes (acción PS order.add_note con idempotencia y Form Request con permiso Spatie). Si add_note está fuera de alcance, deshabilitar el botón/llamada en tickets.js. Considerar /health para paridad con HelpdeskErp.

#### 🟡 [MEDIUM/bug] OrderDetailRequest::rules() vacío: customer_email se descarta silenciosamente
- **Archivo:** `app/Http/Requests/OrderDetailRequest.php:15`
- **Esfuerzo:** trivial
- **Problema:** OrderController::detail (OrderController.php:21) hace $request->validated('customer_email') para acotar el pedido al cliente, pero OrderDetailRequest::rules() devuelve [] — por tanto validated() nunca contiene customer_email y siempre devuelve null. El scoping por email del detalle de pedido es código muerto: el parámetro se ignora aunque se envíe. Esto también es un hueco de seguridad menor: el detalle de pedido no se puede restringir al cliente del ticket.
- **Fix:** Añadir regla 'customer_email' => ['nullable', 'email:rfc'] (y messages/attributes en español) en OrderDetailRequest, o leer explícitamente $request->query('customer_email') con validación. Añadir test que verifique que el email se reenvía al payload PS lookup.

#### 🟡 [MEDIUM/bug] Fallo de red en detail/start-return se reporta como 404/422 (no encontrado/validación) en vez de error de upstream
- **Archivo:** `app/Http/Controllers/Api/OrderController.php:24`
- **Esfuerzo:** medium
- **Problema:** PrestashopContextService::callApi() devuelve null tanto para errores semánticos (ok=false, HTTP 200, p.ej. no encontrado) como para fallos de red/HTTP 5xx y circuit breaker abierto. OrderController::detail mapea cualquier null a 404 'Pedido no encontrado' y startReturn a 422 'No se pudo iniciar la devolución'. Un timeout o un PrestaShop caído se presenta al agente como 'pedido inexistente' / 'datos inválidos', enmascarando un problema de infraestructura y pudiendo inducir a reintentos incorrectos.
- **Fix:** Hacer que callApi distinga fallo de upstream (red/5xx/circuit) de error semántico (ok=false), p.ej. devolviendo un objeto resultado o lanzando una excepción tipada. En el controller, devolver 503/502 para fallos de upstream y 404/422 solo para respuestas semánticas reales.

#### 🟡 [MEDIUM/tests] Falta cobertura de tests para WarmPsCacheCommand, comando test-connection y shape del Resource
- **Archivo:** `tests/Feature/CustomerContextTest.php:213`
- **Esfuerzo:** medium
- **Problema:** No hay tests para WarmPsCacheCommand::collectEmails (dedup multi-tabla, límite, columnas faltantes), ni para TestConnectionCommand, ni un test que asserte las claves exactas del CustomerContextResource (un test así habría detectado el mismatch camelCase/snake_case que rompe la UI). Además, test_order_detail_returns_data otorga el permiso equivocado respecto al que exige el Request, por lo que el test está latentemente roto (verde solo si el rename del seeder no se aplica).
- **Fix:** Añadir test que valide el JSON exacto del CustomerContextResource (incluyendo casing). Añadir tests del comando de warm (con Queue::fake y Schema parcial). Corregir el permiso en el test de order detail tras arreglar el Request.

#### ⚪ [LOW/quality] Envelope de respuesta inconsistente entre controladores
- **Archivo:** `app/Http/Controllers/Api/CustomerContextController.php:53`
- **Esfuerzo:** small
- **Problema:** OrderController envuelve en {success, message, data} (convención del proyecto api-controllers.md), pero CustomerContextController::show/refresh devuelven response()->json(new CustomerContextResource(...)) sin envelope (el cliente recibe {customer, orders, carts} directo). Inconsistencia de contrato dentro del mismo módulo; complica el consumo uniforme y los tests.
- **Fix:** Unificar el envelope. Si el JS depende del shape plano de context, documentarlo explícitamente; si no, envolver en {success, message, data} como OrderController para consistencia.

#### ⚪ [LOW/config] loadMigrationsFrom apunta a directorio inexistente
- **Archivo:** `app/Providers/HelpdeskPrestashopServiceProvider.php:31`
- **Esfuerzo:** trivial
- **Problema:** boot() llama loadMigrationsFrom(module_path(..., 'database/migrations')) pero database/migrations no existe (el módulo es stateless, no tiene tablas propias). Laravel lo tolera (no encuentra ficheros, no crashea) pero es config muerta y confunde.
- **Fix:** Eliminar la línea loadMigrationsFrom si el módulo no tendrá migraciones, o crear database/migrations/.gitkeep si se prevén futuras.

#### ⚪ [LOW/quality] DB::table() en WarmPsCacheCommand en vez de Eloquent
- **Archivo:** `app/Console/Commands/WarmPsCacheCommand.php:57`
- **Esfuerzo:** small
- **Problema:** collectEmails() usa DB::table() sobre helpdesk_conversations/helpdesk_tickets (tablas de otros módulos). La convención del proyecto prefiere Model::query(). Aunque son lecturas cross-módulo y este módulo no tiene esos modelos, sigue siendo un uso de DB:: y además mezcla columnas (email/from_email/requester_email) con comparación de updated_at que puede ser null (whereNotNull solo cubre la columna de email).
- **Fix:** Aceptable como lectura cross-módulo, pero documentar el motivo o usar los modelos de Helpdesk/HelpdeskTickets si están disponibles. Añadir guarda para updated_at null en arsort/ordenación.

#### ⚪ [LOW/performance] Eventos PS se disparan síncronamente dentro del request del webhook tras marcar idempotencia
- **Archivo:** `app/Http/Controllers/Api/PsEventReceiverController.php:46`
- **Esfuerzo:** small
- **Problema:** handle() llama event($eventObject) dentro del request HTTP del webhook. Los 11 listeners en Remarketing/Engagement implementan ShouldQueue (verificado), así que el coste es solo encolar — aceptable. Riesgo residual: si en el futuro se registra un listener síncrono lento o pesado, el webhook de PrestaShop podría exceder timeouts y provocar reintentos. La promoción a 'done' (línea 61) ocurre tras dispatch; si el dispatch parcial encola jobs y luego falla, el reintento de PS podría re-encolar (doble notificación).
- **Fix:** Mantener todos los listeners de eventos PS como ShouldQueue (regla explícita en CONTEXT/tests). Considerar mover el dispatch a un job único 'ProcessPsWebhookJob' para que el receiver responda 200 inmediato y el procesamiento/idempotencia viva en el job.

### HelpdeskSocial

_Módulo grande y maduro (~15.5k LOC) con arquitectura sólida: contratos/interfaces, repositorios, channel registry, clasificadores de intent intercambiables, tokens cifrados con Crypt, índices completos y suite de tests amplia. El problema más grave es un drift de permisos crítico: una migración renombró los permisos a notación con puntos pero el código (Form Requests, Policies, controllers con abort_if) sigue verificando los nombres antiguos con guion, lo que en producción rompe TODA la autorización con PermissionDoesNotExist. Además hay autorización ausente en 2 endpoints de analítica, validación inline en el controller web, una respuesta web que no llega a publicar en la red social, y la integración OpenAI nunca funciona por una clave de config inexistente._


#### 🔴 [CRITICAL/security] Drift crítico de permisos: el código usa nombres antiguos que la migración ya renombró → autorización rota en producción
- **Archivo:** `database/migrations/2026_05_21_085720_rename_legacy_helpdesksocial_permissions.php + app/Policies/*.php + app/Http/Requests/*.php + app/Http/Controllers/Managers/SocialSettingsController.php`
- **Esfuerzo:** medium
- **Problema:** La migración database/migrations/2026_05_21_085720_rename_legacy_helpdesksocial_permissions.php renombra los permisos a notación con puntos (helpdesksocial.manage-accounts → helpdesksocial.accounts.manage, manage-rules → rules.manage, manage-templates → templates.manage, view-analytics → analytics.view). Pero TODO el código sigue verificando los nombres ANTIGUOS con guion: 4 Policies (SocialAccountPolicy/SocialRulePolicy/SocialTemplatePolicy), ~18 Form Requests, SocialSettingsController (abort_if en cada método) y SocialAnalyticsController. Tras correr la migración, esos permisos ya no existen; hasPermissionTo('helpdesksocial.manage-accounts') lanza Spatie\Permission\Exceptions\PermissionDoesNotExist (HTTP 500), no un 403. Además HelpdeskSocialPermissionsSeeder.php sigue sembrando los nombres antiguos. Los tests no lo detectan porque tests/TestCase.php siembra manualmente los nombres antiguos en setUp().
- **Fix:** Unificar a la convención del proyecto con puntos. Actualizar todas las llamadas hasPermissionTo()/can() en Policies, Form Requests y los abort_if() de SocialSettingsController y SocialAnalyticsController a accounts.manage / rules.manage / templates.manage / analytics.view. Actualizar HelpdeskSocialPermissionsSeeder y tests/TestCase para sembrar los nombres nuevos. Añadir un test que ejecute la migración de rename y verifique que un usuario con el permiso nuevo pasa la autorización.

#### 🟠 [HIGH/security] Permisos verificados que nunca se siembran (helpdesksocial.approver, .manage, .intent, .analytics.view, etc.)
- **Archivo:** `database/seeders/HelpdeskSocialPermissionsSeeder.php`
- **Esfuerzo:** medium
- **Problema:** Varios permisos se comprueban en authorize()/abort_if pero no aparecen en HelpdeskSocialPermissionsSeeder (que solo siembra 5): helpdesksocial.approver (RespondSocialApprovalRequestRequest), helpdesksocial.manage (StoreSocialApprovalRequestRequest, StoreSocialNoteRequest), helpdesksocial.intent, helpdesksocial.queues.processing/analytics, helpdesksocial.templates.view (PreviewSocialTemplateRequest), helpdesksocial.rules.view (SimulateSocialRuleRequest), helpdesksocial.mentions.update (UpdateSocialMentionRequest), helpdesksocial.analytics.view. Con hasPermissionTo() y permiso inexistente → 500; con can() → siempre 403. Nadie puede aprobar, crear notas, simular reglas, previsualizar plantillas ni actualizar menciones.
- **Fix:** Ampliar HelpdeskSocialPermissionsSeeder para incluir TODOS los permisos realmente usados en el código (inventariar con grep helpdesksocial.* sobre app/). Estandarizar a la convención con puntos del proyecto y asignarlos a los roles admin/super-admin. Añadir test que recorra las rutas protegidas con un usuario que tenga los permisos del seeder.

#### 🟠 [HIGH/security] Endpoints de analítica sin chequeo de autorización
- **Archivo:** `app/Http/Controllers/Api/SocialAnalyticsController.php:98 y :124`
- **Esfuerzo:** trivial
- **Problema:** En SocialAnalyticsController, metrics() (línea 98) y agentsPerformance() (línea 124) NO tienen abort_if de permiso, mientras todos sus hermanos (overview, agentPerformance, slaOverview, sentimentBreakdown, competitorComparison) sí lo tienen. Cualquier usuario autenticado por Sanctum puede leer métricas agregadas y rendimiento de agentes (incluye nombres de usuarios) sin permiso de analítica.
- **Fix:** Añadir abort_if(! auth()->user()?->can('helpdesksocial.analytics.view'), 403); al inicio de metrics() y agentsPerformance(). Mejor aún: extraer la autorización a Form Requests/Policy o middleware de ruta para no depender de chequeos repetidos por método.

#### 🟠 [HIGH/bug] replyComment del controller web no publica la respuesta en la red social (mentira de éxito)
- **Archivo:** `app/Http/Controllers/Managers/SocialSettingsController.php:113-125`
- **Esfuerzo:** small
- **Problema:** SocialSettingsController::replyComment (líneas 113-125) valida el body y llama $comment->markAsReplied(...) marcando el comentario como respondido, pero NUNCA invoca $apiClient->replyToComment(). El agente ve 'Respuesta enviada correctamente' pero el cliente en Facebook/Instagram nunca recibe nada. Contrasta con SocialInboxController::reply (API) que sí llama a la API y maneja el fallo. Es una ruta web activa (POST inbox/{comment}/reply).
- **Fix:** Inyectar SocialApiClientInterface y replicar la lógica de SocialInboxController::reply: llamar replyToComment con $comment->socialAccount->page_access_token y $comment->platform, comprobar el replyId, y solo entonces markAsReplied con el external_reply_id. Idealmente extraer esa lógica a un método de servicio compartido para evitar duplicación entre el controller web y el de API.

#### 🟠 [HIGH/integration] Integración OpenAI inoperante: clave de config inexistente
- **Archivo:** `config/config.php:29-35 (intent_classification) vs app/Services/Classifiers/OpenAiIntentClassifier.php:19`
- **Esfuerzo:** trivial
- **Problema:** OpenAiIntentClassifier (línea 19) lee config('helpdesksocial.intent_classification.openai_api_key'), pero config/config.php NO define esa clave bajo intent_classification (solo define openai_model). Por tanto $this->apiKey siempre es null, isAvailable() siempre devuelve false y la clasificación por OpenAI SIEMPRE cae al fallback de reglas, aunque el operador configure provider=openai o hybrid. La función pagada/avanzada nunca se ejecuta y nadie se entera (solo un Log::warning).
- **Fix:** Añadir 'openai_api_key' => env('HELPDESK_SOCIAL_OPENAI_API_KEY') (o reutilizar config('services.openai.key')) dentro de intent_classification en config/config.php. Añadir un test que con Http::fake() y la clave configurada verifique que OpenAiIntentClassifier hace la petición HTTP en vez del fallback.

#### 🟠 [HIGH/security] Webhook Meta acepta peticiones sin verificar firma cuando app_secret no está configurado
- **Archivo:** `app/Http/Controllers/Webhooks/MetaWebhookController.php:49-54`
- **Esfuerzo:** small
- **Problema:** MetaWebhookController::handle (línea 50): if (filled($appSecret) && ! $this->verifier->verify(...)). Si HELPDESK_SOCIAL_META_APP_SECRET no está seteado, la condición es falsa y se SALTA la verificación de firma, procesando cualquier POST sin autenticar. Un endpoint público (sin auth, solo throttle) que crea comentarios/conversaciones y dispara jobs queda abierto a inyección de eventos falsos si el secret no está configurado en algún entorno.
- **Fix:** Rechazar (401) cuando app_secret no esté configurado en lugar de aceptar sin verificar, o exigir explícitamente el secret en producción. Como mínimo, registrar un Log::critical y devolver 403 si filled($appSecret) es false en un entorno que no sea local. La firma debe ser obligatoria para procesar payloads.

#### 🟡 [MEDIUM/quality] Validación inline en controller en vez de Form Request
- **Archivo:** `app/Http/Controllers/Api/SocialInboxController.php:131 + app/Http/Controllers/Managers/SocialSettingsController.php:117,131`
- **Esfuerzo:** small
- **Problema:** Violación de convención (Form Request obligatorio). SocialInboxController::assign (línea 131) usa $request->validate(['user_id' => 'required|exists:users,id']). SocialSettingsController::replyComment (línea 117) y assignComment (línea 131) también usan $request->validate() inline. El proyecto prohíbe validación inline y exige Form Requests con authorize()/messages()/attributes() en español.
- **Fix:** Crear AssignSocialCommentRequest (ya existe uno) y usarlo en SocialInboxController::assign; crear ReplySocialCommentRequest/AssignSocialCommentRequest para los métodos web equivalentes y reemplazar las llamadas $request->validate(). Reutilizar los Form Requests existentes entre web y API.

#### 🟡 [MEDIUM/bug] Bulk action 'reply' permitida en validación pero no implementada (no-op silencioso)
- **Archivo:** `app/Http/Controllers/Api/SocialInboxController.php:144-173 + app/Http/Requests/BulkSocialCommentRequest.php:19`
- **Esfuerzo:** small
- **Problema:** BulkSocialCommentRequest::rules permite action 'in:spam,escalate,assign,reply' (línea 19), pero SocialInboxController::bulk (líneas 157-162) tiene un match con casos spam/escalate/assign y default => null. Una acción masiva 'reply' cae en default, no hace nada, pero se cuenta como processed++ devolviendo 'Procesados N comentarios'. El usuario cree que respondió en masa cuando no se envió nada. Además el catch (línea 164) traga la excepción sin loguear, ocultando fallos reales (p.ej. assign sin user_id).
- **Fix:** O bien implementar el caso 'reply' (no recomendado en bulk sin texto por comentario) o quitar 'reply' de las acciones válidas en BulkSocialCommentRequest. Añadir Log::warning en el catch con comment_id y error. Validar que params.user_id esté presente cuando action es assign/escalate.

#### 🟡 [MEDIUM/quality] Jobs incumplen convención: faltan $timeout, $backoff y failed()
- **Archivo:** `app/Jobs/CalculateSocialMetricsJob.php:14-25 + app/Jobs/ClassifyIntentJob.php + app/Jobs/SyncSocialCommentsJob.php`
- **Esfuerzo:** small
- **Problema:** Convención de jobs exige $tries, $timeout, $backoff y failed(). CalculateSocialMetricsJob no define $timeout, $backoff ni failed(). ClassifyIntentJob y SyncSocialCommentsJob no definen failed() (aunque relanzan la excepción). Ninguno usa el trait recomendado Illuminate\Foundation\Queue\Queueable (usan la combinación legacy Bus\Queueable + Dispatchable + InteractsWithQueue). La mayoría tampoco define $backoff.
- **Fix:** Añadir $timeout, $backoff y un método failed() con Log::error contextual a CalculateSocialMetricsJob, ClassifyIntentJob y SyncSocialCommentsJob. Considerar migrar al trait Queueable de Foundation para alinear con la convención del proyecto.

#### 🟡 [MEDIUM/performance] Pipeline de procesamiento síncrono con servicios pesados encadenados en un solo job
- **Archivo:** `app/Jobs/ProcessSocialCommentJob.php:101-135`
- **Esfuerzo:** medium
- **Problema:** ProcessSocialCommentJob::handle (líneas 102-135) ejecuta secuencialmente threading, sentiment, SLA (carga TODAS las SocialSlaPolicy y evalúa una a una), auto-assign (SmartAssignmentService::assign carga TODAS las reglas activas, y para least_busy/round_robin hace count() de comentarios por agente), social listening (carga TODAS las keywords activas y por cada match crea mention), broadcast, classify (posible llamada HTTP a OpenAI con timeout 30s) y auto-reply (otra llamada HTTP). Todo dentro del mismo job con timeout=60s; un pico de comentarios o latencia de OpenAI puede agotar el timeout y reintentar el pipeline entero (efectos colaterales repetidos como reasignaciones).
- **Fix:** Desacoplar la clasificación de intent (ClassifyIntentJob ya existe) y el listening a jobs separados en cola, en vez de ejecutarlos inline. Cachear SocialSlaPolicy::active() y SocialAssignmentRule::active() (cambian poco) para evitar recargarlas en cada comentario. Subir/ajustar timeout o usar un Bus::chain idempotente. Garantizar idempotencia antes de reintentos.

#### 🟡 [MEDIUM/security] preg_match con patrón regex provisto por el usuario (ReDoS / warning) en social listening
- **Archivo:** `app/Services/SocialListeningService.php:98-109`
- **Esfuerzo:** small
- **Problema:** SocialListeningService::matchesKeyword (línea 106) ejecuta 'regex' => (bool) preg_match($needle, $text) donde $needle es SocialListeningKeyword->keyword almacenado por el operador. Un patrón mal formado emite warning de PHP (puede romper el flujo si se tratan como errores) y un patrón patológico permite ReDoS bloqueando el worker. Se ejecuta dentro del pipeline de cada comentario entrante.
- **Fix:** Validar/sanear el patrón regex al guardar la keyword (try preg_match con @ y comprobar === false). En matchesKeyword envolver con @preg_match y comprobar el retorno false para ignorar patrones inválidos. Considerar un límite de pcre.backtrack_limit o set_time_limit defensivo. Restringir match_type 'regex' a admins.

#### 🟡 [MEDIUM/config] ServiceProvider registra comandos que viven fuera del módulo (App\Console\Commands)
- **Archivo:** `app/Providers/HelpdeskSocialServiceProvider.php:5-6,88-89`
- **Esfuerzo:** small
- **Problema:** HelpdeskSocialServiceProvider::registerCommands (líneas 88-89) registra App\Console\Commands\ExportSocialUserDataCommand y AnonymizeSocialUserCommand, que residen en app/ (fuera del módulo) y en git aparecen como eliminados del árbol rastreado (presentes solo como untracked). Si alguien limpia archivos sin seguimiento o se hace checkout limpio, registerCommands lanzará class-not-found y romperá el boot de toda la app. Acoplamiento frágil cross-boundary.
- **Fix:** Mover esos dos comandos al namespace del módulo (Modules\HelpdeskSocial\Console\Commands) junto al resto, o envolver el registro en class_exists() para tolerar su ausencia. No registrar clases de App\ desde un módulo opcional.

#### ⚪ [LOW/integration] Respuesta API no sigue el formato estándar {success, message, data} del proyecto
- **Archivo:** `app/Http/Controllers/Api/SocialInboxController.php:54-62`
- **Esfuerzo:** medium
- **Problema:** La convención api-controllers.md exige formato JSON { success: bool, message: string, data: Resource } y paginación con per_page. SocialInboxController y SocialAnalyticsController devuelven estructuras ad-hoc ({data, meta}, {period, summary, ...}) sin la clave success ni un mensaje uniforme. SocialInboxController::index construye 'meta' manualmente en vez de delegar la paginación al Resource collection. Inconsistencia con el resto de la API del sistema.
- **Fix:** Adoptar el envoltorio estándar {success, message, data} (o documentar la excepción). Usar SocialCommentResource::collection($paginator) que ya emite meta de paginación correctamente en vez de armar 'meta' a mano.

#### ⚪ [LOW/quality] DB:: usado en lugar de modelo en WebPushService
- **Archivo:** `app/Services/WebPushService.php:81`
- **Esfuerzo:** trivial
- **Problema:** WebPushService::getSubscriptionsForUsers (línea 81) usa DB::table('push_subscriptions'). La convención prefiere Model::query(). Aunque push_subscriptions es probablemente de otro módulo/paquete, sería más limpio usar el modelo correspondiente si existe, o documentar por qué se accede por tabla cruda.
- **Fix:** Si existe un modelo PushSubscription (módulo Notifications/WebPush) usarlo. Si no, dejar comentario PHPDoc justificando el acceso por tabla cruda inter-módulo.

#### ⚪ [LOW/ux] Estilos inline en vistas Blade (violación menor de convención)
- **Archivo:** `resources/views/managers/social-analytics/agents.blade.php + social-inbox/index.blade.php:88`
- **Esfuerzo:** small
- **Problema:** Varias vistas usan style="" inline, prohibido por la convención (preferir clases Bootstrap/CSS): social-analytics/agents.blade.php (5 ocurrencias, probablemente barras de progreso), social-inbox/index.blade.php (style=max-width:300px en text-truncate), social-tags/index.blade.php (2), y otras. No se detectaron iconos Tabler ni Livewire (cumple en ese punto).
- **Fix:** Mover los anchos fijos/colores a clases CSS en public/css/social.css. Para barras de progreso usar utilidades de Bootstrap o atributos data-* + CSS en lugar de style inline.

#### ⚪ [LOW/performance] SmartAssignmentService recalcula el conteo de carga del agente con un COUNT por asignación
- **Archivo:** `app/Services/SmartAssignmentService.php:172-191`
- **Esfuerzo:** small
- **Problema:** performAssignment (líneas 176-185) hace un updateOrCreate de SocialAgentWorkload cuyo active_assigned_count se recalcula con un SELECT COUNT(*) sobre helpdesk_social_comments por cada asignación. En picos (auto-asignación en cada comentario entrante) esto añade un count() por comentario. Existe índice (social_account_id,status) pero el filtro es por assigned_to_user_id + whereNotIn status, no perfectamente cubierto por índice.
- **Fix:** Mantener el contador de forma incremental (increment/decrement del active_assigned_count al asignar y al cerrar/responder) en vez de recontar, o cachear. Añadir índice compuesto (assigned_to_user_id, status) si se mantiene el recount.

### HelpdeskTickets

_Módulo muy grande y funcionalmente rico (tickets, SLA, escalado, automatizaciones, macros, portal cliente, widget, email IMAP, API v1, reports). La arquitectura general es sólida (policies registradas, jobs con ShouldQueue/failed(), events/listeners sin closures, índices compuestos). Sin embargo arrastra un cluster crítico de drift de esquema/dependencias: SlaService y TicketService dependen de Modules\Helpdesk\Services\NotificationService (inexistente), por lo que son irresolubles por el contenedor y rompen en runtime el formulario público, el widget, el bridge de conversaciones y los jobs programados CheckSlaBreaches/SendSlaWarnings. Además TicketStatusChanged se dispara con argumentos incorrectos (ArgumentCountError en cada cambio de estado vía manager), hay doble/triple registro de historial (model booted + Observer + listener), y drift de prioridades ('medium' vs 'normal') que invalida la validación del formulario manager._


#### 🔴 [CRITICAL/bug] SlaService y TicketService inyectan Modules\Helpdesk\Services\NotificationService (clase inexistente) → irresolubles por el contenedor
- **Archivo:** `app/Services/SlaService.php:8,16 y app/Services/TicketService.php:22`
- **Esfuerzo:** small
- **Problema:** SlaService::__construct(private NotificationService $notificationService) importa Modules\Helpdesk\Services\NotificationService que NO existe (el más cercano es SlackNotificationService). Verificado por reflexión: class_exists devuelve NO. SlaService está registrado como singleton y se inyecta en CheckSlaBreaches (job programado cada 15 min), SendSlaWarnings (cada 30 min) y TicketService. TicketService, a su vez, inyecta otro NotificationService sin import (resuelve a Modules\HelpdeskTickets\Services\NotificationService, también inexistente). Resultado: BindingResolutionException en runtime al resolver cualquiera de los dos.
- **Fix:** Reemplazar la dependencia por la clase real (TicketNotificationService del módulo, registrada como singleton) o por el servicio de notificaciones de Helpdesk que sí exista, corrigiendo el import en SlaService.php y el type-hint en TicketService.php. Añadir un test que resuelva ambos servicios desde app()->make() para evitar regresiones.

#### 🔴 [CRITICAL/bug] Formulario público de tickets y widget rotos: controllers inyectan TicketService irresoluble
- **Archivo:** `app/Http/Controllers/PublicTicketFormController.php:19-20,96 y app/Http/Controllers/Api/WidgetTicketsController.php:20-22`
- **Esfuerzo:** small
- **Problema:** PublicTicketFormController y WidgetTicketsController hacen constructor injection de TicketService (que es irresoluble, ver hallazgo anterior). Como el contenedor instancia el controller antes de ejecutar cualquier método, la página del formulario público y el endpoint del widget (POST /tickets del widget en HelpdeskLivechat) fallan con 500 incluso en GET. Son puntos de entrada de cara al cliente.
- **Fix:** Tras arreglar la dependencia de TicketService/SlaService, añadir tests de integración que hagan un GET al formulario público y un POST al widget resolviendo el controller vía contenedor real (no solo unitario del servicio).

#### 🔴 [CRITICAL/bug] TicketStatusChanged se dispara con 1 argumento pero requiere 3 → ArgumentCountError en cada cambio de estado vía manager
- **Archivo:** `app/Services/TicketUpdateService.php:41 (y app/Events/TicketStatusChanged.php:27)`
- **Esfuerzo:** trivial
- **Problema:** TicketUpdateService::applyChanges hace broadcast(new TicketStatusChanged($ticket)) con un solo argumento, pero el constructor del evento es __construct(Ticket $ticket, TicketStatus $previousStatus, TicketStatus $newStatus). Esto lanza ArgumentCountError cada vez que un manager cambia el estado de un ticket (TicketsCrudController::update → applyChanges). No hay test que cubra esta ruta, por eso pasa desapercibido.
- **Fix:** Pasar los tres argumentos: new TicketStatusChanged($ticket, $oldStatus, $newStatus) usando los objetos $oldStatus/$newStatus ya cargados en applyChanges. Añadir test manager que actualice status_id y assertOk.

#### 🔴 [CRITICAL/bug] Job programado SendSlaWarnings inutilizable: inyecta NotificationService inexistente en handle()
- **Archivo:** `app/Jobs/SendSlaWarnings.php:12,49,79`
- **Esfuerzo:** small
- **Problema:** SendSlaWarnings::handle(SlaService $slaService, NotificationService $notificationService) importa Modules\Helpdesk\Services\NotificationService (inexistente) y además llama $notificationService->sendSlaWarning(). Está programado cada 30 min en el ServiceProvider. El job se dispara, el contenedor falla al resolver handle() y termina en failed() logueando el error indefinidamente. Mismo problema con SlaService (CheckSlaBreaches cada 15 min).
- **Fix:** Sustituir por el servicio de notificaciones real (TicketNotificationService o el listener basado en eventos SlaWarning ya existente). Eliminar la dependencia directa si el evento SlaWarning ya cubre el envío.

#### 🟠 [HIGH/bug] Listener RecordTicketHistory lee $event->oldStatus que no existe en TicketStatusChanged (propiedad real: previousStatus)
- **Archivo:** `app/Listeners/RecordTicketHistory.php:57-59`
- **Esfuerzo:** small
- **Problema:** El listener accede a $event->oldStatus y $event->newStatus para describir el cambio, pero TicketStatusChanged declara public $previousStatus (no oldStatus). Aunque newStatus sí existe, oldStatus es undefined → null en el historial y warning de propiedad dinámica. Además broadcastWith() del evento accede a $this->ticket->category->id y ->customer->id sin null-check y a $this->ticket->sla_paused (atributo inexistente; el modelo tiene sla_paused_at), produciendo null o errores cuando faltan category/customer.
- **Fix:** Usar $event->previousStatus en el listener (o renombrar la propiedad del evento a oldStatus de forma consistente). En broadcastWith añadir null-safe (?->) en category/customer y reemplazar sla_paused por isSlaPaused()/sla_paused_at.

#### 🟠 [HIGH/bug] Vista de ticket de agente actualiza columna read_at inexistente en helpdesk_ticket_items
- **Archivo:** `app/Http/Controllers/Agents/TicketsController.php:77-79`
- **Esfuerzo:** small
- **Problema:** Agents\TicketsController::show ejecuta $ticket->items()->whereNull('read_at')->update(['read_at' => now()]). La tabla helpdesk_ticket_items (migración 2025_12_29_020907) no tiene columna read_at; el seguimiento de lectura vive en helpdesk_ticket_reads (pivote por usuario, usado correctamente en TicketsCrudController::show). Esta query lanzará SQLSTATE 'columna desconocida' al abrir cualquier ticket como agente, rompiendo la vista de agente.
- **Fix:** Reemplazar por el patrón de TicketsCrudController: insertar/actualizar registros en TicketRead para los items no leídos del agente. Eliminar el update directo a read_at.

#### 🟠 [HIGH/bug] Drift de prioridades: validación manager acepta 'medium' y rechaza 'normal' (default del sistema)
- **Archivo:** `app/Http/Requests/StoreTicketRequest.php:29 y app/Http/Requests/UpdateTicketRequest.php:37`
- **Esfuerzo:** small
- **Problema:** Los Form Requests del panel manager validan priority con in:low,medium,high,urgent, pero el sistema usa low/normal/high/urgent (default DB 'normal', migración create_helpdesk_tickets:24; multiplicadores SLA en Ticket.php:583-588 usan 'normal'; agent/api requests usan correctamente in:low,normal,high,urgent). Consecuencia: crear/editar un ticket con la prioridad por defecto 'normal' FALLA validación en el panel principal, y 'medium' (aceptado) no tiene multiplicador SLA ni cadena de escalado, por lo que esos tickets quedan sin SLA/escalado correctos.
- **Fix:** Cambiar ambas reglas a in:low,normal,high,urgent. Buscar y eliminar 'medium' en HelpdeskTicketBridgeService.php:45 (priority default 'medium') y TicketAiService.php (suggestPriority retorna 'medium' y autoClassify compara === 'medium'). Unificar a 'normal'.

#### 🟠 [HIGH/performance] Triple registro de historial de ticket: Ticket::booted() + TicketObserver + listener RecordTicketHistory + LogsActivity
- **Archivo:** `app/Models/Ticket.php:125-211 y app/Observers/TicketObserver.php`
- **Esfuerzo:** medium
- **Problema:** Tanto Ticket::booted() (creating/created/saved/deleted/updated) como TicketObserver duplican exactamente la misma lógica: generación de ticket_number, set de status por defecto, calculateSlaDueDates en created (se ejecuta DOS veces), Cache::forget('helpdesk:reports') en saved/deleted, y logueo de TicketHistory en updated. El booted::updated loguea cambios de status/assignee Y campos genéricos; el Observer::updated loguea campos genéricos otra vez → cada cambio de campo genérico crea DOS registros de historial. Sumado al listener RecordTicketHistory (que también loguea en eventos) y al trait LogsActivity de Spatie, hay 3-4 fuentes solapadas de auditoría, duplicando filas y queries en cada save.
- **Fix:** Elegir UNA fuente de verdad para el historial (preferible el Observer + eventos) y eliminar la lógica duplicada de Ticket::booted(). Verificar que calculateSlaDueDates solo corra una vez en created.

#### 🟠 [HIGH/bug] API: eager load 'assignee:id,name' y TicketResource usan columna/atributo 'name' inexistente en users
- **Archivo:** `app/Http/Controllers/Api/TicketsController.php:74,88 y app/Http/Resources/TicketResource.php:36-39`
- **Esfuerzo:** small
- **Problema:** El modelo User no tiene columna 'name' (usa firstname/lastname; el accessor es full_name, en $appends). El eager load with(['assignee:id,name']) selecciona una columna inexistente → SQLSTATE columna desconocida al hacer show/update de un ticket con assignee vía API. Además TicketResource emite $this->assignee->name (siempre null). En TicketsCrudController::details:341 también se usa $ticket->assignee->name.
- **Fix:** Seleccionar assignee:id,firstname,lastname y exponer full_name (o construir name con firstname.' '.lastname) en TicketResource y en details(). Revisar todos los 'assignee:id,name' del módulo.

#### 🟠 [HIGH/security] rateTicketFromEmail muta estado vía GET sin URL firmada ni autenticación
- **Archivo:** `app/Http/Controllers/Portal/CustomerPortalController.php:310-329`
- **Esfuerzo:** small
- **Problema:** GET /portal/tickets/{ticketNumber}/rate/{rating} actualiza rating/rated_at del ticket sin sesión, sin URL firmada y sin verificar pertenencia al cliente. Cualquiera que conozca o adivine un número de ticket (formato predecible TCK-YYYY-NNNNN secuencial) puede fijar la valoración. Además permite enumerar tickets cerrados sin valorar (firstOrFail revela existencia). Mutación de estado por GET también es propensa a prefetch de navegadores/escáneres.
- **Fix:** Usar URLs firmadas (URL::signedRoute) en el email de satisfacción y validar la firma con middleware 'signed'. Considerar cambiar a POST con token. Verificar que el rating no se haya enviado ya (whereNull('rated_at') ya está, bien).

#### 🟠 [HIGH/tests] Hueco de cobertura: ruta crítica manager update→cambio de estado y store con priority 'normal' no testeadas
- **Archivo:** `tests/Feature/Managers/ManagersTicketsCrudTest.php`
- **Esfuerzo:** small
- **Problema:** No existe test que actualice un ticket vía manager cambiando status_id (que dispararía el ArgumentCountError de TicketStatusChanged), ni un test que cree un ticket manager con priority='normal' y assertOk (que detectaría que StoreTicketRequest rechaza 'normal'). Los tests existentes solo verifican 403, redirect a login y errores de description/priority inválida. Esto deja pasar dos bugs críticos.
- **Fix:** Añadir test_manager_can_change_ticket_status (PUT status_id, assertOk + assertDatabaseHas) y test_manager_can_store_ticket_with_normal_priority. Ejecutar la suite con la BD de test migrada (actualmente 14 fallos por tabla migrations inexistente en system_test_pristine).

#### 🟡 [MEDIUM/bug] AI autoClassify de prioridad es código muerto por comparación con 'medium'
- **Archivo:** `app/Services/TicketAiService.php:102,192-198`
- **Esfuerzo:** small
- **Problema:** suggestPriority() retorna 'medium' por defecto y autoClassify() solo sugiere prioridad si $currentPriority === 'medium'. Como la prioridad por defecto real es 'normal' (no 'medium'), la condición nunca se cumple para tickets normales y la sugerencia de prioridad IA nunca se ejecuta. Además suggestCategory()/suggestPriority() leen $ticket->title (la columna/accessor 'title' NO existe en Ticket; el campo real es 'subject'), por lo que la clasificación ignora el asunto y solo mira la descripción.
- **Fix:** Cambiar el default y la comparación a 'normal'. Reemplazar $ticket->title por $ticket->subject en suggestCategory y suggestPriority.

#### 🟡 [MEDIUM/bug] Mass assignment silencioso: Portal/IMAP/Service escriben customer_name/customer_email/closed_by/due_at/priority_id que no están en $fillable
- **Archivo:** `app/Http/Controllers/Portal/CustomerPortalController.php:232-233 y app/Services/TicketService.php:35,47,55,84,146,185`
- **Esfuerzo:** medium
- **Problema:** CustomerPortalController::storeTicket pasa customer_name y customer_email a Ticket::create, pero no están en Ticket::$fillable → se descartan silenciosamente (datos perdidos). TicketService referencia columnas inexistentes en el esquema actual: priority_id (modelo usa 'priority' string), due_at (modelo usa sla_resolution_due_at), closed_by, last_activity_at, customer_email; y usa prefijo de número 'TKT-' mientras el modelo genera 'TCK-'. TicketService está totalmente desincronizado del esquema y modelo actuales.
- **Fix:** Eliminar customer_name/customer_email del create del portal (la relación es customer_id). Reescribir o eliminar TicketService alineándolo con el modelo real (priority string, sla_resolution_due_at, prefijo TCK-) o consolidar toda la lógica en el flujo de eventos/Observer ya existente.

#### 🟡 [MEDIUM/integration] FetchTicketEmailsJob: TypeError potencial con From nulo y URL de adjunto incorrecta
- **Archivo:** `app/Jobs/Helpdesks/FetchTicketEmailsJob.php:127,192,248-250,330`
- **Esfuerzo:** medium
- **Problema:** processIncomingEmail setea 'from' => $message->from ?? null, pero extractEmailAddress(string $from) exige string no-nulo → TypeError si el correo no trae From. Los adjuntos se guardan en disco 'local' (saveAttachment:272) pero parseAttachments expone asset('storage/'.$filePath) (250), URL que solo resuelve para el disco 'public', y mime_content_type sobre un path de 'local' que puede no existir. detectPriority mapea asunto 'urgent' → 'high' (no 'urgent'), inconsistente. Acceso directo a $config['connection']/'username'/'password' sin guardas si la config IMAP está parcial.
- **Fix:** Validar/normalizar 'from' antes de extractEmailAddress (default a string vacío y skip si vacío). Generar la URL del adjunto desde el disco real (Storage::disk(...)->url o ruta de descarga interna). Corregir detectPriority('urgent') => 'urgent'. Añadir null-checks a la config IMAP.

#### 🟡 [MEDIUM/performance] Accessors getCategoryAttribute/getStatusAttribute fuerzan lazy-load y rompen semántica de whenLoaded
- **Archivo:** `app/Models/Ticket.php:248-259,269-280 y app/Http/Resources/TicketResource.php:25-35`
- **Esfuerzo:** medium
- **Problema:** Ticket define accessors getCategoryAttribute/getStatusAttribute que sobreescriben las relaciones y hacen $this->load(...) si no están cargadas. Esto provoca N+1 al iterar tickets que acceden a ->status/->category sin eager loading, y degrada whenLoaded('status') en TicketResource (el accessor puede cargar la relación a demanda, haciendo que la API ejecute queries por fila pese al with()). También complica el control explícito de eager loading.
- **Fix:** Eliminar estos accessors y confiar en eager loading explícito (with(['status','category'])) que ya se usa en los listados/Resources. Si se necesita auto-carga puntual, hacerla en el controller, no en el modelo.

#### 🟡 [MEDIUM/bug] scopeSearch en Ticket sin agrupar where/orWhere puede filtrar de más
- **Archivo:** `app/Models/Ticket.php:537-542`
- **Esfuerzo:** trivial
- **Problema:** scopeSearch hace ->where('ticket_number','like',...)->orWhere('subject','like',...)->orWhereHas('customer',...) sin envolver en una closure. Si el scope se combina con otras condiciones (p.ej. status, customer_id), los orWhere escapan del grupo y rompen el filtro (devuelve tickets fuera del scope esperado). Los controllers que sí lo hacen bien envuelven en where(fn($q)=>...) (ver Agents/TicketsController:34 y Api/TicketsController:28), pero el scope del modelo no.
- **Fix:** Envolver el cuerpo del scope en $query->where(function($q) use($term){ ... }); para mantener los OR agrupados.

#### 🟡 [MEDIUM/quality] Validación inline en controllers en vez de Form Requests (violación de convención)
- **Archivo:** `app/Http/Controllers/Agents/TicketsController.php:100-106,127; app/Http/Controllers/Portal/CustomerPortalController.php:52,164,218,269,296; app/Http/Controllers/Managers/TimeEntriesController.php:39; app/Http/Controllers/Managers/BulkTicketsController.php:26; varios Settings`
- **Esfuerzo:** large
- **Problema:** Numerosos controllers usan $request->validate(...) inline con sintaxis pipe ('sometimes|string|max:255') en lugar de Form Requests con reglas en array, messages() y attributes() en español, contrario a las reglas del proyecto. Agents update y assign validan inline; CustomerPortalController valida inline en 5 métodos; varios Settings también.
- **Fix:** Extraer a Form Requests en app/Http/Requests/{Agents,Portal,Managers,Settings}/ con authorize() basado en permiso Spatie real, reglas en array y mensajes/atributos en español.

#### 🟡 [MEDIUM/tests] Namespace de tests 'Tests' mapea a directorio 'tests' (case-sensitive en Linux/CI)
- **Archivo:** `composer.json (autoload-dev) + tests/Feature/*.php namespace Modules\HelpdeskTickets\Tests\`
- **Esfuerzo:** trivial
- **Problema:** composer.json mapea PSR-4 "Modules\\HelpdeskTickets\\Tests\\": "tests/" y los tests declaran namespace Modules\HelpdeskTickets\Tests\.... PSR-4 con prefijo 'Tests\' apuntando a un directorio 'tests/' funciona en macOS (case-insensitive) pero el autoload puede fallar en filesystems case-sensitive (CI/Linux) si en algún punto se espera 'Tests/'. Es un patrón explícitamente marcado como riesgo en las convenciones del proyecto.
- **Fix:** Verificar consistencia y, si se observan fallos de autoload en CI, alinear prefijo y carpeta (mantener todo lowercase 'tests' tanto en mapeo como en uso, que ya es el caso aquí). Documentar y añadir a CI un dump-autoload --optimize que falle ante mismatch.

#### ⚪ [LOW/quality] Form Requests Store/UpdateTicketRequest usan sintaxis pipe y carecen de messages()/attributes() en español; assignee_id sin exists
- **Archivo:** `app/Http/Requests/StoreTicketRequest.php:24-42 y app/Http/Requests/UpdateTicketRequest.php:31-44`
- **Esfuerzo:** small
- **Problema:** Ambos usan reglas pipe ('nullable|string|max:255') en lugar de array, no definen messages() ni attributes() en español (la convención los exige), y 'assignee_id' no valida exists:users,id (acepta cualquier entero). UpdateTicketRequest incluye 'title' que no es columna del modelo.
- **Fix:** Migrar a reglas en array, añadir messages()/attributes() en español, agregar exists para assignee_id y eliminar el campo 'title' inexistente.

#### ⚪ [LOW/quality] DB:: en lugar de Model::query() en reportes y reportes en job
- **Archivo:** `app/Http/Controllers/Managers/HelpdeskReportsController.php:51-226, app/Http/Controllers/Managers/Settings/TicketGroupsController.php:44, app/Jobs/GenerateTicketReports.php:73-94`
- **Esfuerzo:** medium
- **Problema:** Uso de DB::connection('helpdesk')->table()/raw() y DB::raw() para agregaciones de reportes en vez de Model::query() con selectRaw/groupBy sobre los modelos Eloquent, contra la convención de evitar DB:: para datos de modelos. HelpdeskTicketBridgeService usa selectRaw sobre el modelo (mejor patrón) — incoherencia de estilo entre módulos.
- **Fix:** Reescribir las agregaciones con Ticket::query()->selectRaw(...)->groupBy(...) reutilizando la conexión del modelo. Mantener DB::raw solo para expresiones SQL no expresables en el builder.

#### ⚪ [LOW/ux] Inline styles generalizados en vistas manager/agent/portal (no email)
- **Archivo:** `resources/views/managers/ticket-categories/index.blade.php (41 ocurrencias), edit/create de ticket-statuses y ticket-views, managers/tickets/* y agents/*`
- **Esfuerzo:** medium
- **Problema:** Las vistas no-email contienen ~287 atributos style= inline (mayoría width fijos y colores dinámicos de estado/categoría), contra la regla de no usar style inline y preferir clases Bootstrap. Los colores dinámicos podrían ir como CSS custom properties (--c: {{ $color }}) + clase. No se detectaron iconos Tabler ni Livewire (cumple esas reglas).
- **Fix:** Extraer anchos a clases utilitarias y mover colores dinámicos a variables CSS/atributos data-* con CSS asociado. Priorizar ticket-categories/index.blade.php por volumen.

#### ⚪ [LOW/quality] TicketsCrudController::store usa $ticket por referencia fuera del closure de transacción
- **Archivo:** `app/Http/Controllers/Managers/TicketsCrudController.php:115-161`
- **Esfuerzo:** trivial
- **Problema:** store() declara $ticket solo dentro de DB::transaction(function() use(...,&$ticket)) y lo usa después en el redirect. Si la transacción lanza, $ticket queda indefinido (aunque la excepción propaga antes). Es frágil y poco legible; mejor retornar el ticket desde el closure.
- **Fix:** Cambiar a $ticket = DB::transaction(fn() => ...); return ...; devolviendo el modelo creado desde el closure.

#### ⚪ [LOW/performance] broadcast() de TicketStatusChanged dispara load() de relaciones dentro del constructor del evento (efecto secundario + queries)
- **Archivo:** `app/Events/TicketStatusChanged.php:33`
- **Esfuerzo:** trivial
- **Problema:** El constructor del evento ejecuta $this->ticket->load(['customer','status','category','assignee']) — efecto secundario (queries) en el constructor, contra la regla de constructores solo para datos. Se ejecuta en el hilo web antes de encolar el broadcast, añadiendo queries síncronas a la petición de actualización.
- **Fix:** Mover la carga de relaciones a broadcastWith() (lazy, en el worker) o pasar los datos ya cargados desde el servicio.

### HelpdeskTranslate

_Módulo de traducción de helpdesk bien estructurado y con cobertura de tests notablemente alta (8 archivos Feature cubriendo autorización, validación, fallback de proveedor y listeners). Sigue las convenciones del proyecto (Form Requests con permisos Spatie reales, fillable explícito, casts(), routes con prefijo/nombre helpdesk, iconos Font Awesome, jQuery). Los problemas reales son de performance (consultas Setting sin caché repetidas por mensaje en listeners), un puñado de correctness/quality menores y algunas consideraciones de seguridad (API keys en texto plano, validación laxa de default_target). No hay bugs críticos de bypass de seguridad._


#### 🟠 [HIGH/performance] Lecturas de Setting sin caché repetidas en cada mensaje (auto-translate listeners)
- **Archivo:** `app/Services/CachedTranslator.php:90-98 y app/Concerns/TranslatesMessage.php:29-46`
- **Esfuerzo:** small
- **Problema:** Modules\Helpdesk\Models\Setting::get() ejecuta `static::where('key',...)->first()` sin ninguna capa de caché (verificado en Helpdesk/app/Models/Setting.php:18-23). En el flujo de auto-traducción cada MessageReceived dispara DOS listeners (TranslateIncomingMessage + TranslateOutgoingMessage); cada listener llama settingValue() (auto_translate_*), agentLocale() (default_target) y, vía CachedTranslator, resolveProvider() (provider) y detectLanguage() que vuelve a llamar resolveProvider(). Resultado: 5-8 SELECT a helpdesk_settings por cada mensaje recibido, todos por la misma fila de configuración global que casi nunca cambia.
- **Fix:** Memoizar las lecturas de provider/default_target dentro del request (propiedad estática o `once()`), o cachear con Cache::remember(key, ttl) en Setting::get para el grupo 'helpdesktranslate'. Como mínimo cachear resolveProvider() en una propiedad de instancia de CachedTranslator (es singleton-resoluble por contenedor) y el resultado de los settings en el trait por la duración del job.

#### 🟡 [MEDIUM/performance] Detección de idioma con DeepL consume cuota traduciendo (no es gratis)
- **Archivo:** `app/Services/CachedTranslator.php:131-145 y app/Services/DeepLTranslationService.php:38-79`
- **Esfuerzo:** medium
- **Problema:** Cuando el proveedor primario es DeepL, detectViaProvider() llama translateWithDetection() que hace una traducción real (POST /v2/translate) de un sample de 80 chars solo para leer `detected_source_language`. El comentario del código dice 'repeats are free' por el Cache::remember de 24h, pero cada mensaje DISTINTO (que es lo normal en soporte) cuesta una llamada de traducción facturable a DeepL únicamente para detectar idioma. En el listener incoming esto ocurre en el primer mensaje de cada cliente nuevo. El coste real puede ser relevante con volumen alto.
- **Fix:** Documentar/medir el coste, o reducir el sample (p.ej. 30-40 chars) y considerar un detector local ligero (heurística o LibreTranslate /detect que es gratuito) como primer intento para detección incluso cuando DeepL es el proveedor de traducción. Alternativamente exponer un toggle para deshabilitar la auto-detección por DeepL.

#### 🟡 [MEDIUM/security] API keys (DeepL / LibreTranslate) almacenadas en texto plano en helpdesk_settings
- **Archivo:** `app/Http/Controllers/Settings/TranslateSettingsController.php:40-54`
- **Esfuerzo:** medium
- **Problema:** deepl_key y libretranslate_api_key se persisten con Setting::set() en la columna `value` (text) sin cifrado (Helpdesk/app/Models/Setting.php no aplica encrypted cast). Cualquier acceso de lectura a la BD helpdesk expone las credenciales del proveedor de pago. El módulo ya es cuidadoso de no devolver la key en la vista (usa has_deepl_key/has_libretranslate_key), pero el almacenamiento subyacente es plano.
- **Fix:** Cifrar el valor al guardar (Crypt::encryptString) y descifrar al leer en DeepLTranslationService::resolveApiKey()/TranslationService, o usar un cast encrypted en una capa de settings dedicada. Mantener el patrón has_*_key en la vista (ya correcto).

#### ⚪ [LOW/bug] Validación laxa de default_target permite códigos de idioma no soportados
- **Archivo:** `app/Http/Requests/UpdateTranslateSettingsRequest.php:18`
- **Esfuerzo:** trivial
- **Problema:** La regla es `['required','string','min:2','max:5']`, por lo que acepta cualquier cadena de 2-5 chars (p.ej. 'xx', 'zzz'). El select de la vista limita a {es,en,fr,de,pt,it}, pero un POST manual podría guardar un target inválido que luego se envía a DeepL/LibreTranslate y falla silenciosamente en cada traducción automática. El controller hace strtolower pero no valida contra config('helpdesktranslate.supported_languages').
- **Fix:** Añadir `Rule::in(config('helpdesktranslate.supported_languages'))` (o el subconjunto soportado en minúsculas) a la regla de default_target. Idem considerar validar `provider` ya está con in: correcto.

#### ⚪ [LOW/quality] Parámetro Request no usado y lógica HTTP inline en el endpoint de test
- **Archivo:** `app/Http/Controllers/Settings/TranslateSettingsController.php:62`
- **Esfuerzo:** small
- **Problema:** test(Request $request) recibe $request pero nunca lo usa (parámetro muerto). Además toda la lógica de conexión DeepL vive en el controller; aunque es una comprobación de conectividad (no validación de input), engrosa el controller con resolución de key/url duplicada respecto a DeepLTranslationService.
- **Fix:** Quitar el parámetro $request. Extraer la comprobación de uso/usage a un método de DeepLTranslationService (p.ej. checkUsage(): array) reutilizando resolveApiKey()/resolveBaseUrl() que ya son privados, para evitar la duplicación de la cadena de fallback de key/url.

#### ⚪ [LOW/bug] chars_saved nunca se incrementa en la creación, solo en cache-hits posteriores
- **Archivo:** `app/Services/CachedTranslator.php:72-82 y :44-48`
- **Esfuerzo:** trivial
- **Problema:** Al crear la fila de caché se fija chars_saved=0 (línea 80) y solo se incrementa en hits subsiguientes (líneas 44-48). El stat 'Ahorro estimado' del panel (TranslateSettingsController::extractStats) por tanto subestima ligeramente y, si el mismo texto solo se traduce dos veces, registra el ahorro de la segunda pero no expone que la primera fue de pago — el modelo es coherente (chars_saved = ahorro real por hits de caché), pero el comentario de la migración ('Source-text length × hits') no concuerda con la implementación.
- **Fix:** Alinear el comentario de la migración 2026_05_06_221029...:22 con la semántica real (chars ahorrados = suma de longitudes en cache-hits), o ajustar el cálculo si se desea contar también la creación. Es solo precisión de métricas, no afecta funcionalidad.

#### ⚪ [LOW/bug] Caché estática de existencia de columnas persiste en workers de cola de larga vida
- **Archivo:** `app/Listeners/TranslateIncomingMessage.php:125-135 y TranslateOutgoingMessage.php:98-108`
- **Esfuerzo:** small
- **Problema:** private static ?bool $columnsExist memoiza Schema::hasColumn a nivel de proceso. En un worker de cola persistente (horizon/supervisor) el valor se cachea durante toda la vida del worker; si la migración que añade translated_body/source_locale corre DESPUÉS de arrancar el worker, el listener seguirá viendo false y omitirá la traducción silenciosamente hasta reiniciar el worker. Es un caso de despliegue concreto pero real en este stack (Horizon).
- **Fix:** Aceptable en la práctica si el deploy reinicia workers tras migrar. Si se quiere robustez, mover la comprobación a un Cache con TTL corto, o simplemente quitar la guard una vez las columnas son parte estable del esquema (las migraciones ya están aplicadas).

#### ⚪ [LOW/config] Falta carpeta lang/en — locale inglés cae a claves crudas
- **Archivo:** `lang/es/messages.php (no existe lang/en)`
- **Esfuerzo:** small
- **Problema:** Solo existe lang/es/messages.php. El proyecto es principalmente español, pero la UI del panel y los strings de error (__('helpdesktranslate::messages...')) mostrarían la clave literal si el usuario opera con locale 'en'. Dado que el módulo es de traducción multi-idioma, la ausencia de en es incoherente con su propósito.
- **Fix:** Añadir lang/en/messages.php con las mismas claves traducidas, o confirmar que el locale de la app siempre es 'es'. Bajo impacto si el panel solo se usa en español.

#### ⚪ [LOW/quality] Endpoint /translate sin límite de longitud coherente con el panel y sin restricción de 'to' a idiomas soportados
- **Archivo:** `app/Http/Requests/TranslateRequest.php:19-22`
- **Esfuerzo:** trivial
- **Problema:** `to` se valida con regex de formato ISO (`/^[A-Za-z]{2}(-[A-Za-z]{2})?$/`) pero no contra la lista soportada, mientras que `from` sí se restringe a config('helpdesktranslate.source_languages'). Inconsistencia: un usuario puede pedir target a un idioma que el proveedor no soporta y obtener un 503/fallo en vez de un 422 claro. text max:2000 es razonable.
- **Fix:** Para consistencia con TranslateItemRequest (mismo patrón) considerar validar `to` contra supported_languages, o documentar que el formato ISO es suficiente y delegar el rechazo al proveedor. Bajo impacto porque el proveedor degrada con gracia.

#### ⚪ [LOW/quality] EventServiceProvider registra listeners en boot() en vez de la propiedad $listen nativa
- **Archivo:** `app/Providers/EventServiceProvider.php:18-32`
- **Esfuerzo:** trivial
- **Problema:** Se define un array $listen privado y se itera en boot() con Event::listen(). Funciona y NO usa closures (compatible con event:cache porque referencia class-strings), pero reimplementa manualmente lo que el ServiceProvider/EventServiceProvider de Laravel ya hace declarativamente. No es un bug, es deuda menor de estilo respecto a events-listeners.md.
- **Fix:** Opcional: extender Illuminate\Foundation\Support\Providers\EventServiceProvider y usar la propiedad protegida $listen estándar, o mantener el enfoque actual (es válido y cacheable). Sin urgencia.

#### ⚪ [LOW/performance] Relación customer no precargada en MessageReceived para los listeners
- **Archivo:** `app/Listeners/TranslateIncomingMessage.php:93 y TranslateOutgoingMessage.php:63 (origen: Helpdesk/app/Events/MessageReceived.php:31)`
- **Esfuerzo:** trivial
- **Problema:** MessageReceived sólo eager-loadea ['author','user'] del mensaje, no la relación customer de la conversación. Ambos listeners acceden a $conversation->customer, que se resuelve con una query lazy adicional por mensaje. Como los listeners se ejecutan uno por mensaje (no en bucle) no es N+1 clásico, pero suma una query evitable por cada mensaje cuando auto-translate está activo.
- **Fix:** Si se prioriza performance del flujo de auto-traducción, precargar customer en el evento o en el listener con $conversation->loadMissing('customer'). Impacto bajo dado el volumen por-mensaje.

---

## Validación de integraciones (en vivo)


### ERP/Oracle

**Estado live:** El manager ERP está VIVO en http://localhost:8080 y responde JSON. FORMATO REAL confirmado en vivo: search → {success:true, data:[...], pagination:{limit,offset,count,hasMore}} (NO trae 'meta'); customer/{id} → {success, data:{...customer...}, meta:{cached,execution_time_ms}}; customer/{id}/orders → {success:true, data:[], pagination:{...}, meta:{cached,available,loading,retry_after}} (SÍ trae 'meta'); customer/{id}/balance y /invoices → en error devuelven {success:false, error:'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.'} con HTTP 200. Datos Oracle anonimizados (NOMBRE-2, CIF-2, 2@MAIL.ES). Búsquedas genéricas (test, info, maria) → data:[] vacío (la BD enmascarada solo tiene emails N@MAIL.ES); búsqueda por email/CIF/card exacto SÍ devuelve resultados. PROBLEMA RAÍZ Oracle: customer/1, customer/2 y customer/55555 devolvieron intermitentemente HTTP 500 {success:false,error:'Lost connection and no reconnector available.'} en la PRIMERA llamada tras un periodo idle, y luego 8/8 OK en llamadas seguidas — conexión OCI8 persistente que caduca sin lógica de reconexión en el manager. El endpoint /balance e /invoices devuelven SIEMPRE el error de GRANT (usuario lectura sin GRANT SELECT). El scan de pedidos en background SÍ completa: customer/100 pasó de loading:true a meta:{cached:true} con data:[] tras ~60-70s.


**Config issues:**
- ERP_BRIDGE_TOKEN vacío en .env: el bridge llama al manager sin token Sanctum. Funciona hoy porque el manager no exige auth, pero es un riesgo (endpoints ERP expuestos sin auth y romperá si el manager activa Sanctum).
- Coexisten ERP_URL=http://192.168.1.3:58002 (no usado por HelpdeskErp) y ERP_MANAGER_URL=http://localhost:8080 (el que usa el módulo) — confuso, posible config legacy.
- ERP_WEBHOOK_SECRET está configurado (741fba...) y el WebhookController lo valida; pero requiere que el manager realmente firme y llame al webhook orders-ready al terminar el scan — verificar que el manager esté configurado con el mismo secret.
- http_timeout=15s y RefreshErpContextJob timeout=90s: el scan de pedidos tarda ~60-70s en completar; una sola llamada síncrona con timeout 15s nunca verá los pedidos (siempre loading:true), dependiendo del webhook/refresh para traerlos.

**Funciona:**
- El manager está vivo y responde JSON correctamente en http://localhost:8080
- search por email/CIF/card exacto devuelve resultados con formato {success,data,pagination}
- customer/{id} devuelve el resumen completo (label, surnames, cif, email, phones[].number, addresses[].city/province, lopd, statistics) — los keys coinciden con lo que ErpContextService.fetchCustomerData() parsea
- El scan de pedidos en background SÍ completa: pasa de loading:true a cached:true en ~60-70s (no se queda colgado infinitamente)
- ErpContextService lee correctamente json('meta') para el endpoint orders (que SÍ trae meta) y json('data') para summary/balance/invoices
- Http::pool() paraleliza las 4 llamadas (summary/balance/orders/invoices) correctamente
- Negative cache (miss_ttl 60s), stale-while-revalidate (stale_grace) y no-cachear-mientras-loading están bien diseñados a nivel de TTL
- ErpCustomerLinkerService implementa fallback email→teléfono(WhatsApp,phone)→email PrestaShop, descarta @anonymous.local, y es idempotente (no re-consulta si ya hay link 'erp')
- WebhookController valida HMAC-SHA256 con timestamp ±5min y hash_equals — seguro
- Manejo de ConnectionException distingue error de conexión vs cliente no encontrado, con TTL corto (5s) para errores transitorios

**Roto:**
- MANAGER: /balance e /invoices devuelven SIEMPRE 'Acceso denegado a la tabla Oracle. Solicitar GRANT SELECT al DBA.' — el usuario 'lectura' Oracle NO tiene GRANT SELECT (la nota de memoria del proyecto sigue vigente). Confirmado en vivo para customer/2 y customer/100.
- MANAGER: conexión OCI8 intermitente — la 1ª llamada tras idle devuelve HTTP 500 'Lost connection and no reconnector available.' (visto en customer/1, customer/2, customer/55555). Sin lógica de reconexión: la 1ª request de cada sesión idle puede fallar. Esta es la causa real de 'la conexión con Oracle es mala'.
- MANAGER: devuelve errores Oracle (GRANT, lost connection en balance) con HTTP 200 en lugar de 4xx/5xx — esto engaña al check successful() del system.
- SYSTEM: meta.retry_after NUNCA llega al frontend. fetchCustomerData() consume $ordersMeta['loading'] pero NO propaga $ordersMeta['retry_after'] al array de resultado; CustomerContextResource.buildMeta() lee $this->data['meta']['retry_after'] que es SIEMPRE null.
- SYSTEM: no inspecciona json('success')===false. Un error Oracle 200 (GRANT denegado) se interpreta como 'balance vacío/sin facturas' silenciosamente — el agente ve datos incompletos sin saber que hubo error.
- CONFIG: ERP_BRIDGE_TOKEN está VACÍO en .env — el manager hoy acepta requests sin token (sin auth), pero si el manager activa Sanctum las llamadas fallarán con 401.

**Endpoints:**
- GET http://localhost:8080/api/erp/customer/search?q=&limit=&type= → {success,data[],pagination} (VIVO, OK)
- GET http://localhost:8080/api/erp/customer/{id} → {success,data,meta:{cached,execution_time_ms}} (VIVO, intermitente HTTP500 en cold-connection)
- GET http://localhost:8080/api/erp/customer/{id}/balance → {success:false,error GRANT} HTTP200 (ROTO por GRANT)
- GET http://localhost:8080/api/erp/customer/{id}/orders?limit= → {success,data[],pagination,meta:{loading,retry_after,cached,available}} (VIVO, scan async completa ~60-70s)
- GET http://localhost:8080/api/erp/customer/{id}/invoices?limit= → {success:false,error GRANT} HTTP200 (ROTO por GRANT)
- GET http://localhost:8080/api/erp/customer/{id}/orders/{orderId} → {success:false,error 'Order not found'} HTTP404 (VIVO)
- GET http://localhost:8080/api/health → HTTP200 (manager Laravel up)
- SYSTEM GET /api/helpdeskErp/customers/search (auth:sanctum)
- SYSTEM GET /api/helpdeskErp/customers/{email}/context (auth:sanctum)
- SYSTEM GET /api/helpdeskErp/customers/{email}/timeline (auth:sanctum)
- SYSTEM POST /api/helpdeskErp/customers/{email}/context/refresh (auth:sanctum)
- SYSTEM GET /api/helpdeskErp/customers/{customer}/orders/{order} (auth:sanctum)
- SYSTEM GET /api/helpdeskErp/health (auth:sanctum)
- SYSTEM POST /api/helpdeskErp/cache/warm (auth:sanctum)
- SYSTEM POST /api/helpdeskErp/webhooks/orders-ready (HMAC)

**Hallazgos:**
- 🔴 **GRANT SELECT faltante en Oracle: /balance e /invoices siempre fallan** — En el MANAGER/DBA: ejecutar GRANT SELECT al usuario 'lectura' sobre las tablas Oracle de balance y facturas. Es un fix del lado Oracle/manager, no del system. Mientras tanto, el system debería detectar el error y mostrarlo como 'datos financieros no disponibles' en vez de balance vacío.
- 🔴 **Conexión OCI8 intermitente: 'Lost connection and no reconnector available' en cold-start** — En el MANAGER: añadir reconexión OCI8 (oci_ping / detectar ORA-03113/03114 y reconectar) o usar conexión no persistente / pool con healthcheck. En el SYSTEM: añadir reintento con backoff ante HTTP 500 transitorio en searchCustomer/fetchCustomerData (hoy un 500 = contexto vacío sin reintento).
- 🟠 **El system no distingue error Oracle (HTTP 200 success:false) de 'sin datos'** — En fetchCustomerData() y searchCustomer(), comprobar $resp->json('success')===false (o presencia de json('error')) y, en ese caso, marcar _error en el contexto en lugar de devolver datos vacíos. Propagar el error al CustomerContextResource.meta para que el frontend lo muestre.
- 🟠 **meta.retry_after se pierde: el frontend nunca sabe cuándo re-pollear pedidos** — En fetchCustomerData() añadir al array de retorno 'meta' => ['retry_after' => $ordersMeta['retry_after'] ?? null] cuando orders_loading sea true, para que CustomerContextResource lo exponga como retryAfter.
- 🟡 **ERP_BRIDGE_TOKEN vacío: llamadas al manager sin autenticación** — Generar el token en el manager (php artisan erp:issue-bridge-token), configurar ERP_BRIDGE_TOKEN en .env del system, y activar auth:sanctum en la API ERP del manager. Verificar el GRANT del usuario que valida el token.
- 🟡 **El scan de pedidos depende del webhook orders-ready, que debe estar configurado en el manager** — Verificar en el manager que: el secret HMAC coincide con ERP_WEBHOOK_SECRET, y que el manager dispara POST a /api/helpdeskErp/webhooks/orders-ready al finalizar cada scan. Confirmar que el frontend escucha el canal broadcast erp-orders-ready.{md5(email)} evento .erp.orders.ready. Sin esto, los pedidos nunca aparecen aunque el scan complete.
- ⚪ **Búsquedas genéricas devuelven vacío por datos Oracle enmascarados (no es bug del system)** — Ninguna acción en código. Documentar que el entorno staging usa Oracle enmascarada; para pruebas reales usar IDs/emails con el patrón N@MAIL.ES. El parámetro type=email del system se respeta correctamente por el manager.
- ⚪ **search del manager devuelve id como string, no integer** — Sin cambio urgente. Mantener el casting (int) explícito en el system. Opcionalmente pedir al manager que normalice id a integer en search para consistencia con customer/{id}.

**Preparación para traer pedidos:** Para traer pedidos de forma robusta falta: (1) En el MANAGER: arreglar el GRANT SELECT al usuario 'lectura' sobre las tablas de balance/facturas (el scan de pedidos sí funciona, balance/invoices NO por GRANT); arreglar la reconexión OCI8 (la 1ª llamada tras idle da 'Lost connection and no reconnector available' HTTP 500). (2) En el SYSTEM: propagar meta.retry_after desde fetchCustomerData() al array de resultado para que CustomerContextResource.buildMeta() pueda devolver retryAfter al frontend (hoy es SIEMPRE null → el frontend no sabe cuándo re-pollear; solo funciona si llega el webhook orders-ready vía Reverb). (3) Inspeccionar json('success')===false aunque el HTTP sea 200, para distinguir error Oracle de 'cliente sin pedidos' (hoy un GRANT denegado se trata como balance vacío). (4) Implementar el polling/escucha del canal broadcast erp-orders-ready.{md5(email)} en el frontend; el backend ya emite ErpOrdersReady vía webhook HMAC pero requiere que el manager realmente llame al webhook al terminar el scan. (5) Reintento con backoff ante el HTTP 500 transitorio de cold-connection (hoy una conexión caída = contexto vacío sin reintento).

### PrestaShop

**Estado live:** Bridge confirmado VIVO en http://localhost:8091/modules/alsernetbridge/api.php (contenedor alvarez_app, Apache/2.4.54). Respuestas en vivo: GET/POST sin firma -> HTTP 401 {"ok":false,"error":"invalid or expired timestamp"}; POST con timestamp valido pero firma incorrecta -> HTTP 401 {"ok":false,"error":"invalid signature"}; POST FIRMADO con HMAC-SHA256 correcto Y el secreto provisionado en el bridge -> HTTP 200 {"ok":true,"data":{...}}. El puerto 8090 NO es el bridge: es nuestro propio system-nginx (nginx/1.29.7) que devuelve 404 con la pagina de error del Theme. El esquema de firma del bridge (leido de su api.php) coincide EXACTAMENTE con HmacSigner: hash_hmac('sha256', "{timestamp}:{rawBody}", secret), header X-Alsernet-Signature + X-Alsernet-Timestamp + X-Alsernet-Action, ventana anti-replay de 300s. El test-connection del modulo via Herd ahora resuelve URL=8091, Secreto=configurado, pero Estado=ERROR/latencia 32ms porque el bridge rechaza la firma.


**Config issues:**
- ALSERNETBRIDGE_WEBHOOK_SECRET vacio en el bridge (PrestaShop Configuration) — debe provisionarse con el mismo valor del .env de Laravel para que coincida la firma
- Historial: el .env apuntaba a 8090 (system-nginx propio -> 404); ya corregido a 8091 con comentario documentando el riesgo. Verificar que ningun config:cache antiguo persista el 8090
- Contenedor system-app/worker sin ALSERNETBRIDGE_API_URL ni ALSERNETBRIDGE_WEBHOOK_SECRET en su entorno -> los jobs en cola (queue helpdesk-ps) ejecutaran con config vacia y devolveran null silenciosamente
- PRESTASHOP_ENABLED=false en .env (no afecta a este modulo directamente pero indica integracion PS global deshabilitada)
- ALSERNETBRIDGE_LEGACY_HMAC vacio/desactivado en el bridge (correcto: solo se acepta el formato nuevo con timestamp)

**Funciona:**
- Esquema HMAC-SHA256 Laravel<->bridge identico y correcto (verificado leyendo api.php del bridge y obteniendo HTTP 200 con firma correcta)
- El bridge esta VIVO y responde en 8091 con tiempos de ~27-32ms
- El .env YA fue corregido a 8091 (con comentario que documenta la trampa del 8090); Herd resuelve config('helpdeskprestashop.api_url')=8091
- Anti-replay con ventana de 300s implementado en ambos lados (HmacSigner::TIMESTAMP_TOLERANCE_SECONDS y maxSkew del bridge)
- Circuit breaker en PrestashopContextService (umbral 5 fallos / 30s abierto) que distingue HTTP 200 ok=false (error semantico, NO abre circuito) de fallos de red (SI abren)
- Timeouts configurados: connectTimeout 2s + timeout 10s
- Cache stale-while-revalidate con TTL diferenciado (hit 300s, miss 60s) y revalidacion en background via RefreshPsContextJob (ShouldBeUnique, dedup por email durante el TTL)
- Idempotencia en escrituras: header X-Alsernet-Idempotency-Key generado/propagado en Laravel y persistido/replayed en el bridge (AlsernetIdempotency); OrderController genera key estable sha1(user:order:items)
- Webhook receiver con idempotencia de dos fases (processing/done) y liberacion de lock en error para permitir reintentos de PS
- El bridge YA soporta listado de pedidos paginado (customer.orders) — no requiere desarrollo en PrestaShop

**Roto:**
- CRITICO: el secreto ALSERNETBRIDGE_WEBHOOK_SECRET esta VACIO en la config de PrestaShop (verificado: Configuration::get devuelve cadena vacia, y no existe fila en configuration ni configuration_shop). Como el bridge exige $secret truthy, TODA peticion firmada devuelve 401 'invalid signature'. La integracion esta rota end-to-end aunque la URL ya este corregida
- El runtime del contenedor system-app NO tiene las variables ALSERNETBRIDGE_* en su .env (URL y secreto vacios alli) — divergencia de config entre Herd (8091 ok) y el contenedor app/worker que ejecuta los jobs en cola (helpdesk-ps). Los RefreshPsContextJob/WarmPsCacheJob corren en worker y fallarian por config vacia
- test-connection en vivo via Herd: Estado=ERROR (el bridge rechaza la firma por secreto vacio)
- OrderDetailRequest.rules() esta vacio pero el controller hace $request->validated('customer_email') -> siempre null, asi que order.detail nunca envia el email de verificacion de propiedad al bridge

**Endpoints:**
- Bridge (PrestaShop) acciones via api.php POST firmado: customer.profile
- customer.orders (PAGINADO: limit/offset/from/to/status)
- customer.returns
- customer.vouchers
- customer.cart
- customer.messages
- customer.addresses
- customer.helpdesk_context (la unica que usa Laravel para contexto)
- customer.batch_context (emails[] -> warming masivo)
- customer.add_message (write, idempotente)
- order.detail
- order.add_note (write, idempotente)
- order.start_return (write, idempotente)
- Laravel API (auth:sanctum, throttle:60,1, prefix api/helpdeskprestashop): GET /customers/{email}/context
- POST /customers/{email}/context/refresh
- GET /orders/{order}/detail
- POST /orders/{order}/start-return
- Webhook receiver (HMAC, no sanctum, throttle:120,1): POST /api/helpdeskprestashop/webhooks/event (eventos: order.created, order.status_changed, order.return_requested, cart.abandoned, product.price_dropped, product.back_in_stock)

**Hallazgos:**
- 🔴 **El secreto HMAC esta sin provisionar en el bridge PrestaShop -> 401 en toda peticion** — Provisionar ALSERNETBRIDGE_WEBHOOK_SECRET en PrestaShop (panel del modulo alsernetbridge o Configuration::updateValue) con EXACTAMENTE el mismo valor que el .env de Laravel (fba6ea7e...434f). Tras setearlo, re-ejecutar 'php artisan helpdeskprestashop:test-connection --verbose-config' y confirmar Estado: OK.
- 🟠 **Bug de config confirmado: 8090 es nuestro propio sistema, no el bridge (ya corregido a 8091)** — Mantener el 8091 fijado y el comentario. Verificar que no haya config:cache antiguo con el 8090 (no hay bootstrap/cache/config.php). Documentar el puerto del bridge en CONTEXT.md del modulo para evitar regresiones.
- 🟠 **Divergencia de config: el worker/app en contenedor no tiene las variables del bridge** — Asegurar que el entorno del contenedor system-worker/system-app reciba las mismas variables ALSERNETBRIDGE_* (via .env montado, docker-compose env_file o secrets). Validar ejecutando un job de prueba en el worker y revisando logs.
- 🟡 **Listado de pedidos paginado disponible en el bridge pero no consumido por Laravel** — Anadir metodo getCustomerOrders(string $email, array $opts) en PrestashopContextService que llame a 'customer.orders' propagando limit/offset/from/to/status; crear un nuevo Resource (p.ej. PaginatedOrdersResource) mapeando el shape real; exponer GET /customers/{email}/orders en api.php con paginacion. Para sync completa, iterar con offset hasta has_more=false.
- ⚪ **OrderDetailRequest sin reglas: customer_email nunca llega al bridge** — Anadir 'customer_email' => ['nullable','email:rfc'] a OrderDetailRequest::rules() (y messages/attributes en espanol) para que el email se valide y se propague correctamente al bridge para la verificacion de propiedad.
- ⚪ **Manejo de errores, timeouts y circuit breaker: solido** — Sin cambios funcionales necesarios. Opcional: exponer metricas del circuit breaker y un alert cuando el circuito permanezca abierto, y considerar reintentos con backoff exponencial para acciones de lectura criticas.

**Preparación para traer pedidos:** PARCIALMENTE LISTO en el bridge, NO implementado en Laravel. El bridge YA expone la accion `customer.orders` con paginacion y filtros nativos: payload acepta limit (def 10), offset (def 0), from, to, status. Verificado en vivo con cliente real (alvarez@alsernet.es): devuelve {"ok":true,"data":{"data":[{id,reference,total,subtotal,shipping,discount,currency,payment,state_id,state_name,state_color,created_at,updated_at}],"pagination":{limit,offset,total:40,has_more:true}}}. Por tanto NO hace falta crear un endpoint nuevo en PrestaShop. Lo que FALTA en el lado Laravel: (1) PrestashopContextService solo implementa customer.helpdesk_context, order.detail y order.start_return — NO existe metodo para customer.orders ni se propaga limit/offset/from/to/status; (2) customer.helpdesk_context devuelve orders:[] y carts:[] (es un resumen ligero, no trae el listado de pedidos en este dataset) — para traer pedidos hay que llamar a customer.orders explicitamente; (3) MISMATCH de shape: CustomerContextResource.formatOrder() y OrderDetailResource esperan claves placed_at, currency_sign, totals, lines, discounts, payments, tracking, mientras que customer.orders devuelve created_at, currency, total, subtotal, shipping, discount, payment, state_name — se necesita un Resource/mapeo nuevo para el listado paginado; (4) falta un endpoint web/API en el modulo (p.ej. GET /customers/{email}/orders?limit&offset) y un metodo getCustomerOrders(email, opts) que pase la paginacion. Tambien conviene un cursor/`has_more` loop para sincronizacion completa. Existe ademas customer.batch_context para warming masivo.