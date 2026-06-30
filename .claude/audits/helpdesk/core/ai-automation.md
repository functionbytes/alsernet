# Auditoría core Helpdesk — IA & Automatización

> Fecha: 2026-06-29 · Health score: 46/100 · Estado: half-wired (a medio cablear)

**Resumen:** El núcleo de acciones de automatización y macros está completamente inerte (el registry nunca se puebla), hay prompt-injection sin mitigar en todos los servicios LLM, y un endpoint de sugerencias IA hardcodeado; el resto del subsistema está razonablemente cableado con un buen guard SSRF centralizado. Diagnóstico: la fachada de IA/automatización aparenta estar completa, pero las dos piezas de ejecución más visibles (acciones de reglas + macros) no hacen nada en runtime y la superficie LLM carece de defensas frente a entrada no confiable del cliente. Prioridad absoluta: poblar `AutomationActionRegistry`, blindar los prompts y eliminar el endpoint falso de sugerencias.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| AI-AUTO-01 | Crítica | wiring | `Services/Automation/AutomationActionRegistry.php:15-32` | [CONFIRMADO] | M | Registry de acciones nunca se puebla: automatizaciones y macros 100% inertes |
| AI-SEC-01 | Alta | security | `Services/AI/AiAgentService.php:166-171` | [CONFIRMADO] | M | Prompt-injection: input de cliente en prompts LLM sin saneamiento |
| AI-WIRE-02 | Alta | wiring | `Http/Controllers/Managers/ConversationsController.php:1771` | [CONFIRMADO] | S | Endpoint `aiSuggestions` devuelve sugerencias hardcodeadas |
| AI-WF-01 | Media | wiring | `Services/Workflow/WorkflowEngine.php:108` | [CONFIRMADO] | M | Nodo `branch` es un stub: siempre toma la primera rama |
| AI-WF-02 | Media | security | `Services/Workflow/WorkflowEngine.php:240-242` | [CONFIRMADO] | S | `http_request` envía headers arbitrarios y todo el contexto (fuga PII) |
| AI-RR-01 | Media | security | `Models/RoutingRule.php` (matches/regex) | [CONFIRMADO] | M | Regex de routing/condiciones sin validar: ReDoS sobre mensaje de cliente |
| AI-QUAL-01 | Media | conventions | `Http/Controllers/Managers/AiController.php:44-46` | [CONFIRMADO] | S | Validación inline en `translateItem` (viola convención Form Request) |
| AI-PERF-01 | Baja | performance | `Http/Controllers/Managers/AiController.php:29` | [CONFIRMADO] | M | Llamadas OpenAI síncronas en request web sin caché en SuggestReply |
| AI-QUAL-02 | Baja | quality | `Services/AI/AiClient.php:144-153` | [CONFIRMADO] | S | `chatWithTools` no valida tipos de los args de la tool |
| AI-CONV-01 | Baja | conventions | `Http/Requests/Managers/Settings/StoreRoutingRuleRequest.php:21` | [CONFIRMADO] | S | Gaps de validación en Form Requests de routing |
| AI-TEST-01 | Baja | tests | `tests/Feature` | [CONFIRMADO] | L | Lógica núcleo IA/automatización/workflow/macros sin tests |

> Nota de verdicts: los 3 hallazgos de mayor severidad fueron re-verificados estáticamente uno a uno (ver notas en su detalle) y se mantienen [CONFIRMADO] sin ajuste. El resto se reporta con su severidad original; ninguno fue refutado.

## Hallazgos detallados

### Crítica

#### AI-AUTO-01 · Registry de acciones nunca se puebla: automatizaciones y macros 100% inertes — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Services/Automation/AutomationActionRegistry.php:15-32`
- **Evidencia:** `register()` solo está definido, jamás invocado en todo el módulo; el registry NO está en la lista de singletons de `HelpdeskServiceProvider::register()` (líneas 298-325, que solo bindean LiquidRenderer, ConversationTagService, CustomerStatsService, CannedReplyService, WhatsAppBusinessService, FacebookMessengerService, InstagramService, OutboundMessageService, EmailInboundService y TicketServiceContract). Por tanto `app(AutomationActionRegistry::class)` devuelve una instancia nueva con `$actions=[]`. Un grep en todo el módulo no encuentra ninguna llamada a `->register()` sobre el registry fuera de la propia clase. Las clases `AssignAgentAction`/`ChangeStatusAction`/`SendMessageAction`... no se referencian en ningún otro sitio. `resolve()` siempre lanza `InvalidArgumentException 'is not registered'`.
- **Impacto:** `AutomationEngine::executeActions` (`AutomationEngine.php:71-79`) captura `\Throwable`, registra `last_error` y NO ejecuta ninguna acción → cada regla de automatización marca "matched" pero no hace nada. `MacroExecutorService::apply` (`MacroExecutorService.php:72`) falla todas las acciones de toda macro. Tanto el engine como el executor reciben el registry por inyección de constructor; al no ser singleton, cada resolución obtiene una instancia fresca vacía. Toda la cadena de acciones del subsistema está rota en runtime y la UI no puede listar el schema (`registry->all()` nunca se usa).
- **Recomendación:** Bind del registry como singleton y poblarlo en `register()`/`boot()` del ServiceProvider iterando las clases de `Services/Automation/Actions` (glob o lista explícita) llamando `$registry->register($class::actionType(), $class)`. Añadir test que asserte `resolve()` de cada tipo declarado.
- **Esfuerzo:** M

### Alta

#### AI-SEC-01 · Prompt-injection: input de cliente en prompts LLM sin saneamiento — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Services/AI/AiAgentService.php:166-171`
- **Evidencia:** El `PromptSanitizer` mencionado en el alcance NO existe (grep sin resultados en todo el módulo). Cinco servicios inyectan texto crudo del cliente en los prompts LLM con solo `strip_tags()`: `AiAgentService::buildMessages:168` (mensajes al agente autónomo), `SentimentService::detect:34`, `SuggestReplyService::buildContext:63`, `ConversationSummaryService:64` y `AutoTagService::categorize:47`. Ninguno envuelve el contenido no confiable en un delimitador semántico ni instruye al system prompt a ignorar instrucciones del lado de los datos. `AiAgentService::postBotReply` envía la salida del LLM directamente al cliente cuando el agente está habilitado.
- **Impacto:** Un cliente puede inyectar instrucciones ("ignora las instrucciones anteriores...") para manipular las respuestas del agente autónomo, exfiltrar el `system_prompt` o la base de conocimiento embebida (`buildKnowledgeContext:203`), o falsear clasificación/sentimiento. Riesgo mayor con `HELPDESK_AI_AGENT_ENABLED=true`. `AutoTagService` mitiga parcialmente al validar su salida contra el whitelist `CATEGORIES`, pero los otros cuatro servicios no tienen guarda equivalente.
- **Recomendación:** Crear un `PromptSanitizer` que delimite el contenido no confiable (envolver el texto del cliente en bloques claramente etiquetados como datos, no instrucciones), instruir al system prompt a no obedecer instrucciones dentro de los datos, y validar/whitelistear la salida. Aplicarlo en los cinco servicios.
- **Esfuerzo:** M

#### AI-WIRE-02 · Endpoint `aiSuggestions` devuelve sugerencias hardcodeadas — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1771`
- **Evidencia:** `aiSuggestions()` (líneas 1771-1796) retorna un array estático de 3 textos fijos con un TODO en línea 1769 ("integrar OpenAI/Claude API"), sin consultar la conversación ni llamar a `SuggestReplyService`. El campo `context` validado por `RequestAiSuggestionsRequest` nunca se lee. Coexisten dos rutas: `manager.helpdesk.conversations.ai-suggestions` (`managers.php:201`, stub falso) y `manager.helpdesk.conversations.ai.suggest-replies` (`managers.php:163-165` → `AiController::suggestReplies`, real con OpenAI vía `SuggestReplyService::suggest`).
- **Impacto:** Feature de IA aparente pero falsa/muerta; los agentes reciben respuestas genéricas idénticas en cada conversación. Duplicación confusa de endpoints; la validación del Form Request es inútil.
- **Recomendación:** Eliminar el método y su ruta y dirigir el front a `ai/suggest-replies`, o reimplementar `aiSuggestions` delegando en `SuggestReplyService::suggest($conversation)`.
- **Esfuerzo:** S

### Media

#### AI-WF-01 · Nodo `branch` del WorkflowEngine es un stub — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Services/Workflow/WorkflowEngine.php:108`
- **Evidencia:** `executeNode`: `'branch' => $node['config']['branches'][0]['next'] ?? null` — nunca evalúa condiciones de rama, siempre sigue `branches[0]`. Además `Workflow.php:63` documenta que `send_email` se retiró por falta de handler en `executeAction`.
- **Impacto:** Workflows con ramificación múltiple se comportan incorrectamente (flujo determinista hacia la primera rama); acción `send_email` silenciosamente inexistente.
- **Recomendación:** Implementar evaluación de condiciones por rama (reutilizar `ConditionEvaluator`) y devolver el `next` de la rama coincidente; o eliminar el tipo `branch` hasta implementarlo. Implementar o documentar la ausencia de `send_email`.
- **Esfuerzo:** M

#### AI-WF-02 · `http_request` de workflow envía headers arbitrarios y todo el contexto — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Services/Workflow/WorkflowEngine.php:240-242`
- **Evidencia:** `actionHttpRequest` hace `Http::withHeaders($config['headers'])->{$method}($url, $context)` enviando el `$context` completo del run como body a una URL configurada. El guard SSRF se aplica (bien), pero no hay control sobre qué se envía.
- **Impacto:** El `$context` puede contener datos de conversación/cliente; un workflow mal configurado los exfiltra a un tercero. Headers arbitrarios permiten enviar credenciales internas no intencionadas.
- **Recomendación:** Enviar solo un payload acotado y explícito (ids/campos seleccionados) en lugar de `$context` completo; restringir/whitelistear headers permitidos.
- **Esfuerzo:** S

#### AI-RR-01 · Regex de RoutingRule y ConditionEvaluator sin validar: ReDoS — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Models/RoutingRule.php` (`matches()`, match_type `regex`)
- **Evidencia:** `matches()`: `'regex' => (bool) @preg_match($this->keyword, $text)` usa el patrón del admin tal cual; `StoreRoutingRuleRequest:18-19` permite `match_type=regex` pero solo valida `keyword` como string/max:255 (no que sea patrón válido/seguro). Se ejecuta sobre `$text` = cuerpo del mensaje del cliente dentro del listener encolado `AutoAssignNewConversation`. `ConditionEvaluator::checkRegex` (línea 200) tiene el mismo patrón con input de cliente.
- **Impacto:** Un patrón catastrófico (admin, por error o malicia interna) combinado con un mensaje de cliente diseñado puede causar ReDoS y bloquear el worker de la cola `helpdesk`.
- **Recomendación:** Validar el regex en el Form Request (probar `preg_match` contra `''` y rechazar patrones inválidos), aplicar límites `pcre.backtrack_limit`, y considerar timeouts/T-RE2 o longitud máxima del patrón.
- **Esfuerzo:** M

#### AI-QUAL-01 · Validación inline en `AiController::translateItem` — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Managers/AiController.php:44-46`
- **Evidencia:** `$request->validate(['target' => [...]])` inline en el controlador; la convención del proyecto exige Form Request para toda validación. El resto del módulo usa Form Requests (p.ej. `RequestAiSuggestionsRequest`).
- **Impacto:** Inconsistencia con las reglas del proyecto; mensajes no centralizados ni en español.
- **Recomendación:** Extraer a un `TranslateItemRequest` con `rules`/`messages`/`attributes` y `authorize()` real.
- **Esfuerzo:** S

### Baja

#### AI-PERF-01 · Llamadas OpenAI síncronas en la request web sin caché en SuggestReply — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Managers/AiController.php:29`
- **Evidencia:** `suggestReplies()` llama `SuggestReplyService::suggest()` que hace `Http` con timeout 30s en plena request AJAX; a diferencia de `ConversationSummaryService` (cacheado 24h, línea 23), `SuggestReplyService` no cachea.
- **Impacto:** La respuesta del endpoint puede bloquear hasta 30s; coste por cada clic sin reuso. El throttle 30/min mitiga abuso pero no latencia/coste.
- **Recomendación:** Cachear sugerencias por hash del último estado de la conversación durante minutos, o mover a job + entrega por broadcast.
- **Esfuerzo:** M

#### AI-QUAL-02 · `chatWithTools` no valida tipos de los args de la tool — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Services/AI/AiClient.php:144-153`
- **Evidencia:** `parseToolCallResponse` hace `json_decode` de `function.arguments` y usa `$args['text'] ?? '' / $args['reason'] ?? ''` sin verificar que sean strings; el texto se publica como respuesta al cliente vía `AiAgentService::postBotReply`.
- **Impacto:** Si el modelo devuelve `text` como array/objeto, se inserta un valor no controlado en el mensaje al cliente (`e()` lo escapa, pero el cuerpo puede ser basura/serializado).
- **Recomendación:** Validar `is_string` y no vacío de `text`/`reason`; degradar a `escalate` si el arg no cumple el schema declarado.
- **Esfuerzo:** S

#### AI-CONV-01 · Gaps de validación en Form Requests de routing — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/app/Http/Requests/Managers/Settings/StoreRoutingRuleRequest.php:21`
- **Evidencia:** `assign_to_team_id` solo valida `integer` (sin `exists`), mientras `assign_to_user_id` sí usa `exists:users,id` (línea 20). Mismo patrón en `UpdateRoutingRuleRequest`.
- **Impacto:** Se pueden guardar reglas que apuntan a equipos inexistentes; `matchAndAssign` luego intenta `assignToGroup` con id inválido.
- **Recomendación:** Añadir `exists:groups,id` (o la tabla correspondiente) a `assign_to_team_id` en ambos Form Requests.
- **Esfuerzo:** S

#### AI-TEST-01 · Lógica núcleo IA/automatización/workflow/macros sin tests — [CONFIRMADO]
- **Archivo:línea:** `modules/Helpdesk/tests/Feature`
- **Evidencia:** Solo existen `RoutingRulesControllerTest`, `MacrosControllerTest` y `Workflow/WorkflowTriggerWiringTest` (nivel controlador/wiring). No hay tests de `AiClient`, `SentimentService`, `SuggestReplyService`, `ConversationSummaryService`, `AiAgentService`, `AutomationEngine`, `ConditionEvaluator`, `WorkflowEngine` ni `MacroExecutorService`.
- **Impacto:** El bug crítico AI-AUTO-01 (registry vacío) y el branch stub AI-WF-01 pasaron sin detección; sin red de seguridad para cambios en evaluadores y prompts.
- **Recomendación:** Tests unitarios de `ConditionEvaluator` (operadores), de resolución del registry (cada actionType), del parseo de `SentimentService`/`SuggestReply`/`AiClient` (con `Http::fake`) y del flujo de escalado de `AiAgentService`.
- **Esfuerzo:** L

## Plan de ataque priorizado

1. **AI-AUTO-01 (Crítica, M)** — Bind singleton + poblar `AutomationActionRegistry` con las 11 acciones. Sin esto nada del subsistema de acciones ejecuta. Bloquea cualquier valor de automatizaciones y macros.
2. **AI-SEC-01 (Alta, M)** — Implementar `PromptSanitizer` y aplicarlo a los 5 servicios LLM; reforzar el system prompt del agente autónomo y whitelistear/validar salidas.
3. **AI-WIRE-02 (Alta, S)** — Eliminar `aiSuggestions` hardcodeado y su ruta; apuntar el front a `ai/suggest-replies`.
4. **AI-RR-01 (Media, M)** — Validar regex en Form Requests + límites PCRE para cerrar el ReDoS sobre la cola.
5. **AI-WF-01 (Media, M)** — Implementar evaluación de ramas en `branch` (o retirarlo) y resolver `send_email`.
6. **AI-WF-02 (Media, S)** — Acotar payload de `http_request` y whitelistear headers.
7. **AI-QUAL-01 (Media, S)** — Extraer `translateItem` a Form Request.
8. **AI-TEST-01 (Baja, L)** — Cobertura unitaria del núcleo (habría atrapado AI-AUTO-01 y AI-WF-01).
9. **AI-PERF-01 / AI-QUAL-02 / AI-CONV-01 (Baja)** — Caché de sugerencias, validación de tipos de tool args, `exists:groups,id`.

## Quick wins

- Borrar la ruta/método `aiSuggestions` hardcodeado y apuntar el front a `ai/suggest-replies` (AI-WIRE-02).
- Añadir `exists:groups,id` a `assign_to_team_id` en `Store/UpdateRoutingRuleRequest` (AI-CONV-01).
- Validar que `keyword` es regex válido cuando `match_type=regex` en los Form Requests de routing (parte de AI-RR-01).
- Convertir el `$request->validate()` inline de `AiController::translateItem` en un Form Request (AI-QUAL-01).
- Validar `is_string` de `text`/`reason` en `AiClient::parseToolCallResponse` antes de responder al cliente (AI-QUAL-02).

## Fortalezas

- **`OutboundUrlGuard` centralizado y bien documentado** protege el webhook directo y el `http_request` del workflow contra SSRF (loopback/RFC1918/link-local/169.254.169.254), usado consistentemente.
- **Listeners de automatización/workflow/sentiment todos encolados** (`ShouldQueue`) con `tries`/`backoff`/`failed()` y guardas anti-recursión (`TriggerWorkflowsOnMessageReceived`).
- **`AiClient` degrada de forma segura**: si la feature está off o falta la API key devuelve `null`/`escalate` sin lanzar excepción; `chatWithTools` usa `tool_choice 'required'`.
- **Feature gates de IA centralizados** en `config/helpdesk.php` para sobrevivir a `config:cache`.
- **`ConditionEvaluator` es un evaluador puro** sin efectos colaterales y captura regex inválidos.

## Cobertura de la auditoría

Cobertura completa (no muestreo) de todos los archivos del alcance: `AiClient`, `SentimentService`, `ConversationSummaryService`, `SuggestReplyService`, `AiAgentService`, `AutoTagService`, `AiController`; `Automation/*` (Engine, Registry, ConditionEvaluator, Contract y las 11 Actions, revisando `SendWebhook`/`SendMessage`/`AssignAgent` en detalle); `Workflow/WorkflowEngine`; `RoutingRuleService` + `RoutingRule::matches` + Form Requests; `Macros/MacroExecutorService`; `OutboundUrlGuard`; wiring en `HelpdeskServiceProvider` y listeners; rutas en `managers.php`; `config/helpdesk.php`.

Verificado estáticamente (BD de test bloqueada, sin ejecutar tests). No se re-listan hallazgos ya conocidos (OBS-01 colas, ARCH-01 key OpenAI, RW-01 summary sin ruta, PERF-08, SSRF link-preview) salvo detalle nuevo. **`PromptSanitizer` nombrado en el alcance NO existe en el código** (ver AI-SEC-01).

## Descartados en verificación

Ningún hallazgo fue refutado durante la verificación. Los 3 hallazgos de mayor severidad se re-verificaron estáticamente uno a uno y se mantienen [CONFIRMADO] sin ajuste de severidad; el resto se reporta con su severidad original.
