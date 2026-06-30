# Mejoras Helpdesk — Roadmap de oportunidades (2026-06-29)

> Este documento complementa la auditoría de bugs (`INDEX.md`) con **mejoras estratégicas**
> (arquitectura, ROI de producto, performance/escala, seguridad/cumplimiento, observabilidad/operación
> y consistencia de frontend/i18n/accesibilidad). Mientras `INDEX.md` cataloga fallos puntuales,
> aquí se priorizan iniciativas transversales que reducen deuda estructural y desbloquean valor ya construido.
>
> Subsistema analizado: **17 módulos Helpdesk**. Análisis organizado en **6 ejes transversales**.
> Cada oportunidad trae impacto, esfuerzo, ROI, módulos afectados y una recomendación concreta accionable.
>
> Convención de esfuerzo: `s` = horas/1 día · `m` = días · `l` = 1-2 semanas · `xl` = iniciativa multi-semana.

---

## Top 10 por ROI

Ranking por ROI, desempatando por impacto (high > medium) y luego por menor esfuerzo. Algunas filas
absorben oportunidades temáticamente solapadas de otros ejes (indicado en la columna Oportunidad).

| # | Oportunidad | Eje | Impacto | Esfuerzo | ROI | Módulos clave |
|---|-------------|-----|---------|----------|-----|---------------|
| 1 | **ARCH-01** Unificar la clave de config de OpenAI (bug latente de fragmentación) | Arquitectura | high | s | high | Helpdesk, Agents, ChatFlow, Helpcenter, Social |
| 2 | **OBS-01** Cablear las ~11 colas Helpdesk huérfanas a supervisores de Horizon | Observabilidad | high | s | high | Helpdesk, Social, Erp, Prestashop, Agents, Campaigns |
| 3 | **RW-01** Cablear ConversationSummaryService + modal ai-summary (resumen IA al handoff) | Producto | high | s | high | Helpdesk |
| 4 | **SEC-01** Promover OutboundUrlGuard a guard SSRF transversal (absorbe ARCH-04) | Seguridad | high | m | high | Helpdesk, Translate, Document, Agents |
| 5 | **SEC-02** Centralizar PromptSanitizer y aplicarlo a todos los LLM (absorbe ARCH-03) | Seguridad | high | m | high | Agents, Helpdesk, ChatFlow, Social |
| 6 | **OBS-04** Alerting de fallos de cola y estrategia de dead-letter | Observabilidad | high | m | high | Helpdesk, Social, Tickets, Campaigns |
| 7 | **PERF-01** Unificar el procesamiento de mensajes entrantes en listeners encolados | Performance | high | m | high | Helpdesk, ChatFlow |
| 8 | **OBS-03** Idempotencia y transaccionalidad de ProcessSocialCommentJob | Observabilidad | high | m | high | Social, Helpdesk |
| 9 | **RW-02** Activar deflection semántica de Helpcenter (EmbeddingsService::search al widget) | Producto | high | m | high | Helpcenter, Livechat |
| 10 | **SEC-07** Cifrar secretos de terceros en reposo y nunca enviarlos en query string | Seguridad | high | m | high | Translate, Agents, Helpdesk |

**Nota de solapamientos**: SEC-01 ≡ ARCH-04 (consolidación SSRF); SEC-02 ≡ ARCH-03 (PromptSanitizer);
ambos pares deben ejecutarse como una sola pieza compartida en el núcleo. ARCH-02 (gateway LLM) es el
habilitador grande detrás de varias filas de IA pero queda fuera del Top 10 por su esfuerzo `l`.

---

## Quick wins estratégicos (esfuerzo s/m, ROI high)

Lista accionable de máximo retorno por unidad de esfuerzo. Marcadas con `[s]` las de horas.

- **[s] ARCH-01** — Estandarizar una única clave canónica de OpenAI (`services.openai.key`) y apuntar
  todos los servicios a ella. Hoy hay 4 rutas de config distintas para la misma credencial; si solo una
  está poblada, la mitad de los módulos LLM están silenciosamente no-op. Habilitador de todo lo demás de IA.
- **[s] OBS-01** — Ampliar los supervisores de Horizon (`production`) para cubrir `helpdesk-events`,
  `helpdesk`, `notifications-high`, `helpdesk-embeddings`, `helpdesk-erp(-warming)`, `helpdesk-ps(-warming)`,
  `helpdesk-social-*`, `impressions`, `campaigns-scheduler`, `helpdesk-broadcasts`. Cambio de config;
  restaura activity-log, automatizaciones, escalaciones, enriquecimiento ERP/PS, social e impresiones.
- **[s] RW-01** — Añadir `AiController::summary()` + ruta `POST /conversations/{c}/ai/summary`; el servicio
  y el modal ya existen (hoy 404ea). Extra: auto-resumen como nota interna al reasignar.
- **[s] RW-03** — Añadir `openai_api_key` ausente en `config/config.php` de Social: tres features de IA
  (intent/sentiment/copilot) pasan de muertas a funcionales con una línea.
- **[s] RW-04** — Reemplazar `aiSuggestions()` hardcodeado del core por `SuggestReplyService` real (o borrar
  el stub y apuntar la UI a `ai/suggest-replies`). Quita IA falsa que erosiona la confianza del agente.
- **[s] RW-05** — Completar las ~42 claves EN faltantes de HelpdeskTranslate (`settings.*`): traducción
  pura, sin regresión; hoy la pantalla de settings en inglés renderiza claves crudas.
- **[s] PERF-04** — Migración idempotente en core: índices `conversations(closed_at)`,
  `(assignee_id, closed_at)`, `(channel, created_at)` y `conversation_items(user_id, type, created_at)`.
  Analytics no tiene migraciones propias y depende de estos índices.
- **[s] OBS-06** — Añadir `failed()` a `EndExpiredCampaignsJob`/`PublishScheduledCampaignsJob`/
  `CleanupOldImpressionsJob` y estandarizar `tries/backoff/maxExceptions` por tipo de job.
- **[s] OBS-08** — Añadir `withoutOverlapping()->onOneServer()` a `helpdesk:check-sla` y
  `helpdesk:process-broadcasts` del core (evita SLA y broadcasts duplicados en multi-servidor).
- **[s] FE-06** — Helper `helpdesk_asset()` robusto (manifest Vite o `filemtime` con guard) + SRI/local en
  Chart.js. Hoy `filemtime(public_path())` sin guard rompe la página 360 si el asset no se publicó.
- **[s] SEC-07 (parte Gemini)** — Enviar la API key de Gemini por header `x-goog-api-key` en lugar de
  query string (`?key=`). Una línea; elimina filtración de secreto a logs/proxies/APM.
- **ARCH-03 / SEC-02** — Mover `PromptSanitizer` al núcleo (`Helpdesk\Services\AI`) y aplicarlo a todo
  input de usuario que llegue al LLM (core AI, ChatFlow, Social). Defensa anti prompt-injection generalizada.
- **ARCH-04 / SEC-01** — Unificar el guard SSRF en un único `OutboundUrlGuard` y aplicarlo en
  `LinkPreviewService`, `libretranslate_endpoint`, `ChatGalleryDocumentController` y reemplazando el guard
  duplicado de Agents.
- **ARCH-05 / FE-07** — Trait `RespondsWithJson` (o BaseHelpdeskController) que fije `{ success, message, data }`
  + camelCase; migración mecánica por módulo. Cierra el drift de formato (93 archivos repiten `'success'=>true`;
  Social diverge con `{data, meta}` snake_case).
- **ARCH-06** — Hacer de `Helpdesk\Services\AI\SentimentService` la única implementación; Social inyecta la
  del core y ChatFlow tipa contra la clase real. Unificar job/listener de sentimiento.
- **RW-02** — Conectar `EmbeddingsService::search()` como capa de ranking semántico en el widget de Helpcenter
  y en el pre-ticket de Livechat (híbrido con FULLTEXT como fallback). Cada ticket deflectado tiene ROI directo.
- **PERF-01** — Migrar los observers Eloquent síncronos (link-preview core, `processMessage` ChatFlow) a
  listeners `ShouldQueue` de `ConversationItemCreated`. Saca bloqueos de 6-30s del hilo del webhook.
- **PERF-02** — Eliminar accessors auto-cargadores (Ticket `getCategory/getStatus`, SocialComment `intent`,
  Campaign legacy, ConversationDocumentLinker) que anulan el eager loading y reintroducen N+1. Cerrar con
  `assertQueryCount`.
- **PERF-03** — Reescribir métricas de Social analytics con `groupBy + selectRaw` (hoy `->get()` de toda la
  ventana y agregación en PHP) y acotar el rango de fechas (máx ~366 días).
- **PERF-06** — Cachear en Redis (TTL corto por `campaign_id`) la selección de variantes y la campaña activa
  en la ruta pública de impresiones.
- **OBS-02** — Fuente única de nombres de cola (config/enum) + test de contrato que falle en CI si un job
  apunta a una cola sin supervisor. Convierte el fallo silencioso de OBS-01 en fallo de CI determinista.
- **OBS-03** — `firstOrCreate` atómico + unique index `(platform, external_comment_id)` + `DB::transaction()`
  + etapas idempotentes en `ProcessSocialCommentJob`. Hoy un retry aborta y deja threading/sentiment/SLA sin hacer.
- **OBS-04** — Registrar `Horizon::routeSlack/MailNotificationsTo`, ampliar `waits` a todas las colas, y un
  handler `Queue::failing` global (reutilizar `NotifySlackOnSlaBreached`).
- **FE-02** — Helper único `window.Helpdesk.esc()` + renderizadores seguros; migrar las 99 reimplementaciones
  de `$('<div>').text(x).html()`. Cierra XSS reales (Contacts merge, Document gallery).

---

## Iniciativas grandes (esfuerzo l/xl)

### ARCH-02 — Gateway LLM compartido (chat + embeddings + transcripción) `[l]`
- **Valor**: punto único para timeout/retry/logging/coste y para aplicar Sanitizer (SEC-02), rate-limit y
  observabilidad de coste de IA. Hoy 7+ servicios golpean `api.openai.com` con manejo propio.
- **Alcance**: promover/expandir `Helpdesk\Services\AI\AiClient`; inyectarlo en todos los satélites; eliminar
  los `Http::post('.../chat/completions')` dispersos.
- **Dependencias**: ARCH-01 (clave unificada). Habilita ARCH-06 y ARCH-09.

### ARCH-07 — Unificar abstracción de canales bajo SocialChannelRegistry `[l]`
- **Valor**: un solo punto de webhook-verify y de llamada a Meta Graph; elimina la integración Meta duplicada
  entre core (`MetaGraphChannelDriver`/`FacebookMessengerService`/`InstagramService`) y Social.
- **Alcance**: adoptar `SocialChannelInterface` (parser/verifier/apiClient separados) como contrato canónico;
  reescribir los servicios del core como providers.
- **Dependencias**: alinear el modelo de mensajes (`HasMessageThread` ya unifica Conversation/Ticket).

### ARCH-08 — Base ServiceProvider común + registro homogéneo de policies `[l]`
- **Valor**: centraliza boilerplate (rutas, config, vistas, lang, NavService, `registerPolicies` por glob) y
  fuerza que Contacts/Document/Analytics/Livechat (hoy 0 policies) registren autorización.
- **Alcance**: `BaseHelpdeskServiceProvider`; normalizar `ModuleServiceProvider` vs `ServiceProvider` (Translate
  es el outlier).
- **Dependencias**: coordinar con la remediación de autorización (SEC-05).

### ARCH-10 — Formalizar un kernel HelpdeskCore `[xl]`
- **Valor**: convierte 17 módulos acoplados informalmente en hub-and-spoke con frontera explícita.
- **Alcance**: declarar `Helpdesk` como kernel y mover allí las piezas compartidas de ARCH-02/03/04/05/06/07/09;
  documentar contratos públicos. Ejecución incremental, no big-bang.
- **Dependencias**: agregado de ARCH-02..09; considerar ADR Mailer/Mailrelay para fronteras de email.

### RW-10 — Runtime de IA de HelpdeskAgents (motor huérfano) `[l]`
- **Valor**: desbloquea la propuesta central de agentes IA; hoy `StartAiAgentSessionJob` nunca se despacha y todo
  el motor (flow engine, tools, knowledge) es inalcanzable.
- **Alcance**: listener/observer sobre mensajes entrantes que despache el job para un agente activo, tras feature
  flag `helpdeskagents.enabled` y cola acotada.
- **Dependencias**: RW-06 (config efectiva), HA-07 (escalabilidad embeddings), SEC-02 (sanitizer). Evitar dos
  runtimes en competencia con el agente IA del core (HD-13).

### PERF-08 — Descomponer el ConversationsController-dios (2.762 líneas) `[xl]`
- **Valor**: testabilidad, razonamiento de N+1 y reducción de carga de BD por inbox; colapsa ~13 COUNT por tab
  (5 por canal) en un `GROUP BY channel` y un read-model cacheado.
- **Alcance**: extraer servicios/sub-controladores cohesivos + `InboxReadModel`. Incremental para acotar blast radius.
- **Dependencias**: PERF-04 (índices) y PERF-05 (caché).

### SEC-05 — Migrar autorización a policies/authorize() con ownership (Social + Document) `[l]`
- **Valor**: elimina el patrón inconsistente `abort_if(hasPermissionTo)` (14 controllers Social + Document), habilita
  ownership por recurso (hoy cualquier aprobador aprueba solicitudes ajenas) y cierra el bypass de Document.
- **Alcance**: canalizar por `authorize()/authorizeResource()`; replicar el patrón seguro de `DocumentFileController`.
- **Dependencias**: HS-01 (nombres de permisos, ya parcheado); rutas proxy helpdesk-scoped en Document.

### SEC-03 — Extender el orquestador GDPR (Compliance) a todos los módulos con PII `[l]`
- **Valor**: cierra el gap legal; hoy la cascada solo cubre Tickets+ChatFlow, dejando fuera Social, EmailLog,
  Document, Translate, Livechat y Contacts (todos con PII).
- **Alcance**: handler por módulo gateado por `Module::isEnabled()+class_exists()`; enganchar los comandos GDPR de
  Social al evento; persistir `ComplianceRequest` estado `failed` ante error de handler.
- **Dependencias**: cada módulo expone un servicio de borrado idempotente; reusa `CustomerGdprDeleted`.

### SEC-04 — Programa de retención/purga de PII uniforme con scheduler por módulo `[l]`
- **Valor**: minimización de datos; hoy la retención es fragmentaria (solo EmailLog/Campaigns/core).
- **Alcance**: política configurable por módulo + comando de purga encolado en el scheduler de cada SP, patrón
  `PruneEmailLogsCommand`. Cubrir TranslationCache, Social, Document media, Analytics, WidgetSession, AiToolExecution.
- **Dependencias**: sinergia con SEC-03 (mismos servicios de borrado); acuerdo de negocio sobre plazos.

### OBS-05 — Métricas de negocio (SLA breach, CSAT, deflection) como serie temporal con umbrales `[l]`
- **Valor**: SLOs operativos en vez de inspección manual; hoy todo se calcula on-demand al abrir el dashboard.
- **Alcance**: recorders de Pulse o tabla de snapshots horaria poblada por job programado; alertas sobre la serie.
- **Dependencias**: definir SLOs con negocio; apoyarse en OBS-04 para el canal de alerta.

### FE-01 — Librería de componentes Blade compartida del subsistema `[l]`
- **Valor**: 0 directorios `components` en 17 módulos; cada uno reimplementa modales/dropdowns/pills/avatares.
  Centraliza convención, accesibilidad (FE-08) y escape (FE-02).
- **Alcance**: `x-helpdesk::modal/action-dropdown/status-pill/avatar` en el core (generalizar el sistema `docs-*`
  de Document), namespaceados para todos los módulos.
- **Dependencias**: base de FE-02, FE-05 y FE-08.

### FE-03 — Decisión y normalización de i18n `[xl]`
- **Valor**: 15 de 17 módulos con UI 100% hardcodeada en español pese a la regla multi-idioma; 11 con `lang/` muertos.
- **Alcance**: decisión explícita — (a) comprometerse con i18n y extraer cadenas a `lang` (XL), o (b) borrar los
  `lang/` muertos y documentar "es-only" (S). El gap de UI de agente multilingüe es de producto.
- **Dependencias**: decisión de alcance multipaís; combinable con FE-01 (slots i18n).

### FE-04 — Registro de inbox-slots con carga AJAX diferida `[l]`
- **Valor**: desacopla core↔módulos (hoy `@include` hardcodeado + `class_exists` inline) y saca HTTP/DB del render
  (Prestashop, Document, Translate ejecutan trabajo síncrono al renderizar).
- **Alcance**: servicio de registro de slots (view/endpoint/orden) + carga por AJAX tras el primer paint.
- **Dependencias**: nuevo servicio en core; endpoints JSON por slot.

### FE-09 — Resolver la contradicción de stack React/TSX vs jQuery-only `[l]`
- **Valor**: ChatFlow (`ChatFlowEditor.tsx`) y core (`MessageComposer.tsx`) violan la regla jQuery+AJAX/no-React.
- **Alcance**: decisión documentada — sancionar un patrón acotado de "isla React" o reescribir en jQuery. Quick win
  adjunto: sustituir el residuo Tabler (`class="ti ..."`) por Font Awesome 6.
- **Dependencias**: decisión de arquitectura frontend.

---

## Secciones por eje

### Eje 1 — Arquitectura, consolidación y DRY entre módulos

El subsistema creció por acreción: cada módulo reimplementó capas transversales en vez de depender de un núcleo
común. La oportunidad de fondo es extraer un kernel **HelpdeskCore** (AI gateway, SSRF, sanitizer, respuesta API,
canales) del que dependan los satélites. Referencias canónicas a promover ya existen: `GuardsAgainstSsrf`,
`PromptSanitizer`, `SocialChannelRegistry`, `HasMessageThread`.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| ARCH-01 | Unificar la clave de config de OpenAI (bug latente) | high | s | high |
| ARCH-02 | Gateway LLM compartido (chat + embeddings + transcripción) | high | l | high |
| ARCH-03 | Promover PromptSanitizer a pieza compartida y aplicarlo a todo input LLM | high | m | high |
| ARCH-04 | Consolidar el guard SSRF en un solo trait/servicio y cubrir módulos vulnerables | high | m | high |
| ARCH-05 | Trait de respuesta JSON / BaseHelpdeskApiController | medium | m | high |
| ARCH-06 | Consolidar análisis de sentimiento (triplicado) | medium | m | high |
| ARCH-07 | Unificar la abstracción de canales bajo SocialChannelRegistry | medium | l | medium |
| ARCH-08 | Base ServiceProvider común + registro homogéneo de policies | medium | l | medium |
| ARCH-09 | Consolidar transcripción de audio/voz | low | m | medium |
| ARCH-10 | Formalizar un kernel HelpdeskCore (módulo base común) | high | xl | medium |

- **ARCH-01**: estandarizar `services.openai.key` como clave canónica; apuntar todos los servicios; deprecar las
  claves por-módulo dejando solo overrides de modelo. Primer paso y habilitador de ARCH-02.
- **ARCH-02**: ver Iniciativas grandes.
- **ARCH-03**: mover `PromptSanitizer` a `Helpdesk\Services\AI` y aplicarlo dentro del gateway (ARCH-02) para sanear
  por defecto toda entrada de usuario. Canal de log `security` unificado.
- **ARCH-04**: unificar `OutboundUrlGuard` (pinning de IP de ChatFlow + allowlist de Agents); aplicarlo en
  `LinkPreviewService` y en el cliente de proveedores de Translate. Elimina dos implementaciones y dos vectores SSRF.
- **ARCH-05**: trait `RespondsWithJson` con `successResponse()/errorResponse()/paginatedResponse()` fijando
  `{ success, message, data }` y status codes. Migración incremental por módulo.
- **ARCH-06**: `SentimentService` del core como única implementación (sobre ARCH-02); Social inyecta; ChatFlow tipa
  contra la clase real; unificar job/listener.
- **ARCH-07/08/10**: ver Iniciativas grandes.
- **ARCH-09**: exponer la transcripción vía gateway; `ChatFlowVoiceTranscriber` consume `AudioTranscriptionService`
  del core. Agrupar con ARCH-06 en una pasada de consolidación AI.

### Eje 2 — ROI de producto: cablear lo construido-pero-desconectado

Funcionalidad de alto valor totalmente construida pero inerte: motor de IA de Agents sin invocar, resumen IA sin
ruta, búsqueda semántica de Helpcenter que genera vectores que nadie consulta, `aiSuggestions` hardcodeado, IA de
Social siempre en fallback por config ausente, traducciones EN incompletas, y features semi-cableadas en Document,
Analytics, Compliance, SLA y Social.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| RW-01 | Cablear ConversationSummaryService + modal ai-summary | high | s | high |
| RW-02 | Activar deflection semántica de Helpcenter (EmbeddingsService::search) | high | m | high |
| RW-03 | Reactivar IA de Social: añadir openai_api_key ausente | medium | s | high |
| RW-04 | Reemplazar aiSuggestions() hardcodeado por SuggestReplyService real | medium | s | high |
| RW-05 | Completar el set de traducciones EN de HelpdeskTranslate | medium | s | high |
| RW-06 | Hacer efectiva la config del agente IA (leer parameters/getModelConfig) | medium | s | medium |
| RW-07 | Completar el flujo de aprobación de Social (aprobar publica y respeta aprobador) | medium | m | medium |
| RW-08 | Arreglar o retirar el device-upload de HelpdeskDocument | medium | m | medium |
| RW-09 | Renderizar el heatmap de Analytics ya calculado | low | m | medium |
| RW-10 | Implementar el runtime de IA de HelpdeskAgents tras feature flag | high | l | medium |
| RW-11 | Integrar disponibilidad/turnos de agentes en el enrutado de asignación | medium | m | medium |
| RW-12 | Cerrar exports semi-cableados: GDPR (Compliance) y export (Analytics) | medium | m | low |
| RW-13 | Implementar el panel de settings de HelpdeskSla (permisos sin UI) | low | m | low |

- **RW-01..05**: ver Quick wins.
- **RW-06**: leer la config vía `$agent->getModelConfig()` en `callAiProvider()` (prioriza `parameters ?? backups`).
  Su valor se materializa con RW-10; hacer juntos.
- **RW-07**: al aprobar, despachar la respuesta real vía `RuleBasedAutoReplyEngine + MetaApiClient` y restringir
  approve/reject al aprobador designado. Depende de HS-01.
- **RW-08**: cablear el device-upload a un endpoint helpdesk-scoped multipart (patrón `DocumentFileController`), o
  retirar el control. Resolver junto con HD-DOC-01.
- **RW-09**: añadir el canvas del heatmap en `dashboard/index.blade.php` (Chart.js ya cargado), o eliminar la clave
  del controller si no se quiere.
- **RW-10, RW-12**: ver Iniciativas grandes (RW-12 es esfuerzo m pero ROI low; alternativa: eliminar tipo/filtro/
  permiso muertos).
- **RW-11**: consumir `availableAgents()/isAgentAvailableNow()` en la ruta de auto-asignación o exponer los Resources.
- **RW-13**: controller+ruta+vista bajo `panel/settings/helpdesksla` con los permisos ya sembrados, o eliminar los
  dos permisos hasta que exista la pantalla.

### Eje 3 — Performance y escala

Fundamentos mejores de lo que sugiere el INDEX (índices del inbox, contadores cacheados, ERP/PS con `Http::pool`,
IA encolada). Los problemas reales son patrones transversales: dos pipelines paralelos para mensajes entrantes,
accessors auto-cargadores, agregación en memoria PHP, huecos de índices analíticos, y el controlador-dios.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| PERF-01 | Unificar procesamiento de mensajes en listeners encolados (quitar observers síncronos) | high | m | high |
| PERF-02 | Eliminar accessors auto-cargadores que anulan el eager loading | medium | m | high |
| PERF-03 | Agregar métricas en SQL en vez de cargar colecciones completas (Social) + acotar rangos | medium | m | high |
| PERF-04 | Índices compuestos orientados a analítica/SLA en tablas del core | medium | s | high |
| PERF-05 | Endurecer caché: stale-while-revalidate, separar contadores globales, invalidación consistente | medium | m | medium |
| PERF-06 | Cachear selección de variantes y campaña activa en la ruta pública de impresiones | medium | m | high |
| PERF-07 | Forzar límites de paginación/rango en lecturas no acotadas (Document, Analytics) | low | s | medium |
| PERF-08 | Descomponer el ConversationsController-dios + read-model del inbox | high | xl | medium |

- **PERF-01..04, PERF-06**: ver Quick wins.
- **PERF-05**: `Cache::flexible()` (Laravel 12) para `sidebarCounters`/`statusbarMetrics`; mover la métrica global
  `open` de Analytics a key propia; invalidar caches de EmailLog en purga; guard en `filemtime(public_path())`.
  Documentar TTLs/keys como contrato de caché.
- **PERF-07**: limitar el expediente de cliente (paginación/limit + orden en SQL) en `ConversationDocumentLinker`;
  aplicar el tope de rango de PERF-03 a Analytics.
- **PERF-08**: ver Iniciativas grandes.

### Eje 4 — Endurecimiento de seguridad y cumplimiento transversal

Piezas de seguridad de buena calidad pero AISLADAS en un solo módulo cada una. La oportunidad de mayor ROI es
promoverlas a infraestructura compartida: `OutboundUrlGuard`, `PromptSanitizer`, el orquestador GDPR. Notas: el
fail-open del webhook de Social (HS-03) y la allowlist de `deepl_url` (HT-03) ya están parcheados.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| SEC-01 | Promover OutboundUrlGuard a guard SSRF transversal | high | m | high |
| SEC-02 | Centralizar PromptSanitizer y aplicarlo a todos los servicios LLM | high | m | high |
| SEC-03 | Extender el orquestador GDPR a todos los módulos con PII + registrar fallo de cascada | high | l | high |
| SEC-04 | Programa de retención/purga de PII uniforme con scheduler por módulo | medium | l | medium |
| SEC-05 | Migrar autorización a policies/authorize() con ownership (Social + Document) | high | l | high |
| SEC-06 | Autorización granular en los ~34 controllers de Settings | medium | m | medium |
| SEC-07 | Cifrar secretos de terceros en reposo y nunca enviarlos en query string | high | m | high |
| SEC-08 | Unificar verificación de firma de webhooks con ventana anti-replay | medium | m | medium |
| SEC-09 | Estándar de purificación HTML para todo body renderizado en crudo en el inbox | medium | m | medium |

- **SEC-01**: `OutboundUrlGuard` (ya correcto y probado) como único punto de verdad SSRF; aplicarlo en
  `LinkPreviewService`, `libretranslate_endpoint`, `ChatGalleryDocumentController::addMediaFromUrl` y reemplazar el
  guard duplicado de Agents (`ToolExecutionService`, `LlmConnectionTesterService::testLocal`).
- **SEC-02**: extraer `PromptSanitizer` al core e inyectarlo en cada servicio LLM antes de construir los mensajes;
  reforzar el system prompt de los agentes con tool-calling para ignorar directivas embebidas y validar args en servidor.
- **SEC-03, SEC-04, SEC-05**: ver Iniciativas grandes.
- **SEC-06**: middleware `can:{permiso}` por grupo/entidad de Settings (hoy solo `role:super-admin|super-settings`);
  los permisos granulares ya existen en el seeder, solo falta cablearlos. Verificar asignación a roles existentes.
- **SEC-07**: ver Quick wins (parte Gemini-header). Cifrar en reposo (`Crypt` cast/mutator) todo secreto de tercero
  en la tabla `Setting` (DeepL/LibreTranslate hoy en texto plano); requiere migración de valores existentes.
- **SEC-08**: extraer un `SignedWebhookVerifier` compartido (`hash_equals` + ventana de timestamp/nonce + fail-closed)
  usando el patrón de Erp como referencia; migrar los entrypoints Meta/WhatsApp del core.
- **SEC-09**: una única pasada de HTMLPurifier en el render para cualquier `html_body` con datos de cliente (en vez de
  confiar en el escape disperso); test de regresión con nombre `<script>`. HTML Purifier ya está en el stack.

### Eje 5 — Observabilidad, colas y operación

Arquitectura de colas disciplinada por job pero que falla en la operación end-to-end. Hallazgo dominante: la
topología de Horizon en `production` NO cubre ~11 colas a las que el código despacha (entre ellas las críticas
`helpdesk-events`, `helpdesk`, `notifications-high`). Se suman ausencia de alerting, métricas de negocio on-demand,
un job social no idempotente, y logging sin correlation-id.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| OBS-01 | Cablear las ~11 colas Helpdesk huérfanas a supervisores de Horizon | high | s | high |
| OBS-02 | Fuente única de nombres de cola + test de contrato en CI | high | m | high |
| OBS-03 | Idempotencia y transaccionalidad de ProcessSocialCommentJob | high | m | high |
| OBS-04 | Alerting de fallos de cola y estrategia de dead-letter | high | m | high |
| OBS-05 | Métricas de negocio (SLA breach, CSAT, deflection) como serie temporal | high | l | medium |
| OBS-06 | Estandarizar retry/backoff/maxExceptions y completar failed() en jobs cron de Campaigns | medium | s | high |
| OBS-07 | Correlation-id (conversation_id/channel) en logging | medium | m | medium |
| OBS-08 | Comandos programados del core sin withoutOverlapping()->onOneServer() | medium | s | high |
| OBS-09 | No filtrar mensajes de excepción crudos en respuestas JSON de gestión | low | s | medium |

- **OBS-01..04, OBS-06, OBS-08**: ver Quick wins.
- **OBS-05**: ver Iniciativas grandes.
- **OBS-07**: `Log::withContext(['conversation_id','channel','correlation_id'])` vía job middleware compartido que
  propague el id desde el webhook. Mejora drásticamente el MTTR sin coste de infraestructura.
- **OBS-09**: en `BulkTicketsController` loguear con contexto y responder mensaje genérico + código (no
  `$e->getMessage()`); auditar que el `getMessage` de `ContactCartController` sean siempre mensajes seguros de negocio.

### Eje 6 — Consistencia de frontend, i18n y accesibilidad

El subsistema no tiene NINGUNA capa de UI compartida (0 directorios `components`), el escape XSS en JS está
reimplementado 99 veces (con XSS reales donde se olvida), 15 de 17 módulos están 100% hardcodeados en español, el
core acopla rígidamente los slots del inbox, y la accesibilidad no aparece en ninguna auditoría (gap total).

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| FE-01 | Librería de componentes Blade compartida (modal, action-dropdown, status-pill, avatar) | high | l | high |
| FE-02 | Helper JS único de escape/render (window.Helpdesk.esc) — cierra XSS recurrentes | high | m | high |
| FE-03 | Decisión y normalización de i18n | high | xl | medium |
| FE-04 | Registro de inbox-slots con carga AJAX diferida | high | l | medium |
| FE-05 | Erradicar style="" inline (~107 blades) con utilidades CSS + custom properties | medium | m | medium |
| FE-06 | Helper de cache-busting robusto + SRI en CDNs | medium | s | high |
| FE-07 | Normalizar el contrato de respuesta API ({success,message,data}+camelCase) | medium | m | medium |
| FE-08 | Baseline de accesibilidad horneado en los componentes compartidos | medium | m | medium |
| FE-09 | Resolver la contradicción de stack React/TSX vs jQuery-only + residuo Tabler | medium | l | medium |

- **FE-02, FE-06**: ver Quick wins.
- **FE-01, FE-03, FE-04, FE-09**: ver Iniciativas grandes.
- **FE-05**: estáticos a clases utilitarias; dinámicos legítimos vía CSS custom properties
  (`style="--pill-color: {{ $color }}"`) o data-attrs. Mejor tras FE-01.
- **FE-07**: trait `ApiResponse`/BaseApiController compartido; migrar los controladores API de Social (hoy
  `{data, meta}` snake_case) al contrato del proyecto. Alineado con ARCH-05. Inventariar consumidores JS antes.
- **FE-08**: hornear a11y en los componentes de FE-01 (`aria-label` en acciones icon-only, `role=dialog`+`aria-modal`
  +focus trap en modal, texto+icono en status-pill, `aria-live=polite` en toasts, contraste ≥4.5:1 para `#90bb13`).

### Eje 7 — Testing, CI y developer experience

Hay **mucha** prueba escrita —~245 archivos de test y ~1.880 métodos `test_*` en los 17 módulos— pero la red
de seguridad está rota en la base y desigual en la cobertura. Inventario aproximado por módulo (Feature/Unit ·
métodos): Helpdesk core 36/10·~455, Tickets 35/23·~442, ChatFlow 8/19·~208, Social 28/3·~190, Livechat 20/5·~130,
Agents 7/8·~95, Erp 10/0·~81, Campaigns 4/1·~69, Translate 9/0·~55, Helpcenter 4/0·~42, EmailLog 5/0·~40,
Prestashop 4/0·~36, Contacts 3/0·~24, **Sla 1/0·~9, Analytics 1/0·~4, Compliance 1/0·~3 y Document 0/0·0**. El
problema no es cantidad sino fiabilidad: la suite DB-backed **no se ejecuta** (la BD apunta al snapshot roto
`system_test_pristine`), el CI solo corre 1 de 17 módulos Helpdesk, el antipatrón de permisos ad-hoc que enmascaró
el crítico HS-01 sigue vivo en ~21 puntos de 7 módulos, no existe una base de test compartida (235 tests extienden
el `TestCase` global pelado) ni helper de `assertQueryCount`, y 42 archivos aún usan `RefreshDatabase` pese a la
regla del proyecto. La oportunidad de fondo es **convertir la suite existente en un gate de merge confiable**:
desbloquear la BD, forzar seeders reales, compartir una base/helpers, y mover el contrato a CI.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| TEST-01 | Desbloquear la BD de test (snapshot limpio + orden de migraciones determinista) | high | m | high |
| TEST-02 | Erradicar permisos ad-hoc en tests: arrancar siempre los seeders reales | high | m | high |
| TEST-03 | `BaseHelpdeskTestCase` + helpers y factories compartidos del subsistema | high | m | high |
| TEST-04 | Migrar los 42 tests con `RefreshDatabase` a `DatabaseTransactions` | high | s | high |
| TEST-05 | Helper compartido `assertQueryCount`/`assertNoNPlusOne` para blindar N+1 | medium | s | high |
| TEST-06 | Cubrir los módulos sin red de seguridad (Document 0 tests, Helpcenter `EmbeddingsService`, rutas de Contacts) | high | l | high |
| TEST-07 | Completar la cobertura de los MVP nuevos (Sla, Compliance, Analytics) | medium | m | medium |
| TEST-08 | CI que ejecute TODO el subsistema Helpdesk y bloquee el merge | high | m | high |
| TEST-09 | Tests de contrato en CI: permisos y nombres de cola (anti-HS-01 / absorbe OBS-02) | high | m | high |
| TEST-10 | Cobertura medible: ampliar `<source>`, umbral mínimo y Larastan en el gate | low | s | medium |

- **TEST-01** (Ola 0, ya señalada como habilitador): `src/phpunit.xml` fija `DB_DATABASE=system_test_pristine`
  (y la conexión `helpdesk` apunta al mismo), un snapshot de enero-2026 con ~494 migraciones pendientes que
  rompen por ordering cross-módulo, así que **ningún test DB-backed corre en local** y OBS-02 tuvo que hacerse
  estático. Estrategia recomendada: (a) crear el esquema desde cero con `migrate --force` sobre una BD vacía
  resolviendo el ordering (prefijos de timestamp coherentes o `--path` por dependencia: core → satélites), (b)
  regenerar un snapshot limpio versionado (dump SQL solo-esquema en `database/`) que los runners y devs restauren
  en segundos, y (c) cablear ese flujo como step previo del CI. Sin esto, la "Test Enforcement" exigida por las
  reglas del proyecto es papel mojado para todo el resto del roadmap.
- **TEST-02**: el antipatrón que enmascaró HS-01 (`tests/TestCase.php` de Social sembrando permisos a mano) ya se
  parcheó en la base, **pero los tests individuales siguen creándolos ad-hoc**: 8 en Social, 5 en Tickets, 5 en
  Translate y 1 en Agents/ChatFlow/EmailLog/Prestashop (~21 puntos). Dos olores concretos: `SocialModuleSettings\
  ControllerTest.php:97` crea `helpdesksocial.manage-rules` cuando el seeder envía `helpdesksocial.rules.manage`
  (la divergencia exacta que oculta drift), y `TranslateItemControllerTest.php:29` **recrea un permiso del core**
  (`helpdesk.conversations.view`) en vez de sembrar el `PermissionsSeeder` propietario. Recomendación: prohibir
  `Permission::create/firstOrCreate` en `tests/` (regla Pint/grep en CI) y que toda autorización venga de
  `$this->seed(...PermissionsSeeder::class)` del módulo dueño del permiso. Es el cambio de mayor retorno de
  seguridad-por-test del eje.
- **TEST-03**: solo el core tiene base sólida (`Helpdesk/tests/HelpdeskTestCase.php`: `DatabaseTransactions` sobre
  `['mariadb','helpdesk']` + `PermissionsSeeder` real + `manager`/`openStatus`). Los 14 satélites reimplementan su
  `setUp()` y **235 tests extienden el `Tests\TestCase` global pelado** (solo tiene `actingAsManager`). Crear un
  `BaseHelpdeskTestCase` compartido (transacción multi-conexión, `actingAsManagerWith([...perms])`, seed encadenado
  de los seeders reales que el caso necesite) y un set de helpers/factories transversales (Conversation, Customer,
  ConversationItem ya existen en core: exponerlos a los satélites). Elimina el caldo de cultivo de TEST-02 y baja
  el coste marginal de escribir un test nuevo.
- **TEST-04**: 42 archivos usan `RefreshDatabase` frente a 112 con `DatabaseTransactions`. `RefreshDatabase`
  dispara `migrate:fresh`, que sobre el snapshot revienta por el mismo ordering de TEST-01 y contradice la regla
  documentada del proyecto ("solo `DatabaseTransactions`, nunca `RefreshDatabase`"). Migración mecánica a la base
  de TEST-03; cierra una causa de flakiness y de borrado accidental de esquema. `s` una vez exista la base común.
- **TEST-05**: PERF-02 pide "cerrar con `assertQueryCount`" pero **no existe helper reutilizable**: solo 3 tests
  lo hacen a mano (`ConversationListNPlusOneTest.php:36-49`, `AnalyticsAggregatorTest`, `WidgetConversationFlow\
  Test`) repitiendo `enableQueryLog()`+`assertLessThanOrEqual`. Añadir a `BaseHelpdeskTestCase` un
  `assertQueryCount(int $max, Closure $fn)` / `assertNoNPlusOne()` (sobre la conexión `helpdesk`) convierte cada
  fix de N+1 (accessors auto-cargadores de PERF-02, agregación de PERF-03) en un test de regresión barato y
  legible. Esfuerzo de horas, leverage sobre todo el Eje 3.
- **TEST-06**: módulos sin red de seguridad. **HelpdeskDocument tiene 0 tests, 0 factories, 0 seeders y 0
  PermissionsSeeder** pese a ser `needs-work` con bug de autorización (rutas `api.documents.*` solo `auth:web`):
  necesita factories + perm seeder + tests de ownership antes de tocar SEC-05. **`HelpdeskHelpcenter\Embeddings\
  Service`** (habilitador de RW-02, deflection semántica) no tiene ni un test pese a ser el activo de IA de mayor
  ROI de producto. Y **Contacts** (crítico de route-model-binding en el merge + XSS) solo tiene 3 archivos: faltan
  tests de las rutas que ejercitan el binding `{customer}` y el escape del JS de merge.
- **TEST-07**: los tres MVP recién entregados van con un único archivo de test cada uno —Sla (~9 métodos),
  Compliance (~3), Analytics (~4)— justo donde el INDEX marca bugs de Carbon y half-wiring. Cubrir: cascada GDPR
  de Compliance (handler por módulo + `ComplianceRequest` en estado `failed`), breach/warning de Sla (la
  inconsistencia SLA-02 con el horario laboral) y el `HealthScoreBatchService::scoresFor` de Analytics (signed-diff
  de Carbon, penalización >6 meses que nunca aplica). Encadena con la corrección de cada bug como test de regresión.
- **TEST-08**: el CI (`/.github/workflows/ci.yml`) corre Pint y migraciones limpias, pero de los 17 módulos
  Helpdesk **solo ejecuta `modules/HelpdeskPrestashop/tests/`** (líneas 89-96, junto a Ecommerce/EcommercePayment):
  los otros 16 no se ejecutan nunca, así que un PR puede romper Social/Tickets/core sin que el gate lo note. Tras
  TEST-01, ampliar el job a `php artisan test --compact modules/Helpdesk*/tests/` (o por suites), exigirlo como
  required check de la rama protegida, y dividir en matriz para paralelizar. Sin esto, TEST-02/04/06/07 no se
  defienden de regresiones futuras.
- **TEST-09**: dos tests de contrato baratos que convierten fallos silenciosos en fallos de CI deterministas.
  (1) **Contrato de permisos** (absorbe la causa de HS-01): recorrer cada `can('…')`/`authorize` de controllers y
  Form Requests del subsistema y aseverar que cada nombre existe tras sembrar todos los `PermissionsSeeder` —
  hubiera atrapado el crítico de Social en CI. (2) **Contrato de colas** (OBS-02): un test que falle si un job
  despacha a una cola sin supervisor de Horizon en `production`, fijando una fuente única de nombres de cola. Ambos
  dependen de TEST-01 (BD) y se ejecutan en el gate de TEST-08.
- **TEST-10**: hoy `phpunit.xml` solo incluye en `<source>` `modules/Helpdesk/app` y `modules/HelpdeskPrestashop/
  app`, así que la cobertura ni se mide para 15 módulos. Ampliar el `<source>` a `modules/Helpdesk*/app`, publicar
  el `coverage.txt`/clover como artefacto del CI con un umbral mínimo no-regresivo, y añadir Larastan al gate
  (análisis estático que cubre lo que la BD bloqueada impide probar en runtime). Bajo esfuerzo; da visibilidad a
  los huecos de TEST-06/07.

---

## Orden recomendado (olas de trabajo)

### Ola 0 — Habilitadores (sin estos, lo demás es más lento o no verificable)
1. **Desbloquear la BD de test** (referencia: `system_test_pristine` con migraciones pendientes que rompen por
   ordering cross-módulo). Hoy los tests DB-backed no corren y OBS-02 tiene que hacerse estático. Restaurar una BD
   de test limpia habilita el "Test Enforcement" exigido por las reglas para TODA la demás obra.
2. **ARCH-01** — Unificar la clave de OpenAI. Habilitador de todo el trabajo de IA (ARCH-02, ARCH-06, RW-01/02/03/04,
   SEC-02). Sin esto, la mitad de los módulos LLM pueden estar no-op.
3. **OBS-01** — Cablear las colas huérfanas de Horizon. Config-only; restaura funcionalidad de producción
   silenciosamente perdida (activity-log, automatizaciones, escalaciones, enriquecimiento, social, impresiones).

### Ola 1 — Quick wins de seguridad, producto y robustez (esfuerzo s/m, ROI high)
Cierra riesgos vivos y desbloquea valor barato, apoyándose en la Ola 0:
- Seguridad compartida: **SEC-01/ARCH-04** (SSRF), **SEC-02/ARCH-03** (PromptSanitizer), **SEC-07** (secretos;
  Gemini-header inmediato).
- Producto: **RW-01**, **RW-03**, **RW-04**, **RW-05** (features muertas y traducciones).
- Operación: **OBS-04** (alerting — hace detectables OBS-01/03), **OBS-02** (test de contrato de colas, ya con BD
  habilitada), **OBS-06**, **OBS-08**.
- Performance barata: **PERF-04** (índices), **FE-06** (cache-busting/SRI).
- **OBS-03** (idempotencia del job social) — requiere migración de unique index.

### Ola 2 — Consolidación de plataforma media (esfuerzo m, ROI high/medium)
Construye el núcleo compartido sobre los habilitadores:
- **ARCH-02** (gateway LLM) → encadena **ARCH-06** (sentimiento) y **ARCH-09** (transcripción).
- **ARCH-05/FE-07** (contrato JSON) y **FE-02** (esc helper) + **FE-01** (componentes Blade) → base de FE-05/FE-08.
- Performance: **PERF-01** (pipeline de mensajes), **PERF-02**, **PERF-03**, **PERF-05**, **PERF-06**, **PERF-07**.
- Producto/IA: **RW-02** (deflection Helpcenter), **RW-06**.
- Seguridad: **SEC-06**, **SEC-08**, **SEC-09**.

### Ola 3 — Iniciativas grandes y decisiones de producto (esfuerzo l/xl)
Una vez estabilizado el núcleo y con frontera clara:
- Kernel y arquitectura: **ARCH-10** (kernel), **ARCH-07** (canales), **ARCH-08** (base SP).
- IA y enrutado: **RW-10** (runtime de agentes, tras RW-06/SEC-02), **RW-11**.
- Performance estructural: **PERF-08** (descomponer el controlador; tras PERF-04/05).
- Cumplimiento y autorización: **SEC-05** (policies/ownership), **SEC-03** (GDPR cascade), **SEC-04** (retención).
- Observabilidad de negocio: **OBS-05** (serie temporal + SLOs, sobre OBS-04).
- Frontend: **FE-03** (i18n), **FE-04** (inbox-slots), **FE-08** (a11y), **FE-09** (stack), **FE-05**.
- Producto residual: **RW-07**, **RW-08**, **RW-09**, **RW-12**, **RW-13**.

**Lógica de orden**: las olas respetan dependencias duras (ARCH-01→ARCH-02→ARCH-06/09; FE-01→FE-05/FE-08;
OBS-01→OBS-04→OBS-05; PERF-04→PERF-08) y maximizan ROI temprano: primero lo que es config-only o de horas y cierra
riesgo (Ola 0/1), luego la plataforma compartida que multiplica el resto (Ola 2), y por último las iniciativas
grandes que requieren decisión de producto o reescritura amplia (Ola 3).
