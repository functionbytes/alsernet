# Roadmap de mejoras — Ecosistema Helpdesk (18 módulos)

> Auditoría prospectiva · 2026-07-05 · organizado **por etapas** (orden de ejecución) y **por módulos** (referencia de trabajo).
> Las referencias `archivo:línea` apuntan al código real revisado. Esfuerzo: **S** = bajo · **M** = medio · **L** = alto.
> No incluye lo ya corregido en rondas anteriores (webhooks Document HMAC, gating de toggles, cascada GDPR async, SSRF Translate/Campaign, IDOR ERP/PS/Tickets, índices Contacts/Tickets/SLA, factories Ticket/ChatFlow, etc.).

**Leyenda de categorías:** 🔒 Seguridad · 🐞 Bug/correctitud · ⚡ Performance/resiliencia · 🧹 Calidad/convención · 👁 Observabilidad · 🌐 i18n · 🧪 Tests · ✨ Nueva feature

---

## PARTE A — Plan por etapas (orden de ejecución)

### Etapa 0 — Seguridad y riesgo crítico (hacer primero)

- [x] 🔒 **HelpdeskSocial** · `assign()` ~~sin autorización~~ — FALSO POSITIVO (el Form Request ya lo gateaba); añadido `abort_if` por consistencia. — cualquier usuario autenticado reasigna comentarios; invocar `SocialCommentPolicy::assign()`. `SocialInboxController.php:130` · **S**
- [x] 🔒 **HelpdeskAgents** · tool `database`: añadido allowlist de tablas (fail-closed) + bloqueo multi-statement/comentarios + 7 tests. `SELECT` arbitrario sobre cualquier tabla con permiso genérico; añadir permiso privilegiado + allowlist de tablas. `ToolExecutionService.php:140` · **M**
- [x] 🔒 **Helpdesk core** · `merge()` ahora `authorize('update', $target)` + 2 tests de scoping por inbox. ~~no autoriza la conversación destino~~ (cruce de inbox); `authorize('update', $target)` + chequeo de inbox. `ConversationsController.php:1184` · **S**
- [x] 🔒🐞 **HelpdeskPrestashop** · `categories()` — FALSO POSITIVO (Spatie registra el permiso como gate; `authorize('helpdeskprestashop.view')` sí funciona). ~~usa ability sin Policy~~ → 403 para agentes; cambiar a `authorize('view', $customer)`. `ProductSearchController.php:190` · **S**
- [x] 🔒 **HelpdeskTranslate** · añadido `integration.enabled:translate` a las 3 rutas + test 404. ~~endpoints manuales sin gating~~ de integración; añadir `integration.enabled:translate` a las 3 rutas/controladores. · **M**
- [x] 🔒 **HelpdeskCompliance** · NO cambiado a propósito (documentado): la cascada de borrado GDPR DEBE erradicar PII aunque el toggle de UI esté off; atarlo al toggle sería una violación de GDPR. ~~ignora toggles~~; `moduleReady()` debe comprobar `helpdesk_tickets_enabled()`/`helpdesk_chatflow_enabled()`. `ProcessComplianceCascadeJob.php:102` · **S**
- [x] 🔒 **HelpdeskAnalytics** · tope de 366 días sobre el rango efectivo + 2 tests. ~~sin tope de rango~~ (DoS); añadir máximo (p.ej. 366 días). `:16` · **S**
- [x] 🔒 **HelpdeskDocument** · nuevo `helpdesk.documents.manage` en rutas mutadoras (lectura queda en `conversations.view`) + backfill a roles con `conversations.update` + 2 tests. ~~un solo permiso `view` autoriza~~ aprobar/rechazar/eliminar; introducir `helpdesk.documents.manage`. `routes/managers.php:39` · **M**
- [x] 🔒 **HelpdeskTickets** · `store()` con `authorize('create')` (consistencia; el FR ya gateaba). ~~sin `authorize()`~~ explícito. `:117` · **S**
- [x] 🔒 **HelpdeskEmailLog** · `bulkDestroy()` con `authorize('deleteAny')` (consistencia; el FR ya gateaba). ~~sin `authorize('deleteAny')`~~ (la policy ya existe). `EmailLogController.php:108` · **S**
- [x] 🔒 **HelpdeskErp** · `audit.access` añadido a `/health` y `/cache/warm`. ~~sin middleware `audit.access`~~. `api.php:37` · **S**
- [x] 🔒 **HelpdeskContacts** · `audit.access:contacts,merge` en la ruta de merge. (evento `ContactsMerged` queda para Etapa 5). ~~sin `audit.access`~~ ni evento. `routes/web.php:51` · **S**
- [x] 🔒 **HelpdeskPrestashop** · `generateOrder()` idempotente (devuelve pedido existente + Cache::lock) + test. ~~sin idempotencia~~ → pedidos duplicados (dinero real); header `Idempotency-Key` + hash. · **M**
- [x] 🔒 **HelpdeskPrestashop** · password de relleno → `Str::random(40)`. ~~`uniqid()`~~; usar `Str::random(40)`. `AssistedCartService.php:315` · **S**
- [x] 🔒 **HelpdeskChatFlow** · tope de nodos configurable en `import()` (el tamaño en bytes ya estaba limitado). ~~sin límite de nodos~~/tamaño de JSON. · **S**
- [x] 🔒 **HelpdeskIntegration** · `requestCode()` invalida los códigos previos pendientes + test. ~~no invalidados~~ al reemitir. `CustomerIdentityVerificationService.php:28` · **S**

### Etapa 1 — Bugs de correctitud (funciona mal hoy)

- [x] 🐞 **HelpdeskTickets** · SLA ahora mapea el slug de prioridad EN→ES (`high`→`alta`) a `priority_id` antes de casar la política; `TicketService::getApplicablePolicy` delega en `SlaService` (fin del duplicado) y `updateTicket`/`createTicket` usan la columna real `priority`. 3 tests Feature. ~~priority_id inexistente~~
- [x] 🐞 **HelpdeskTickets** · vistas corregidas a `helpdesktickets::emails.*` (con guion) + creadas las 3 que faltaban (`ticket-closed`, `new-message`, `internal-note`); eliminadas las llamadas `notify*` redundantes de servicios/job (los listeners canónicos ya entregan cada Mailable → sin duplicados ni `failed_jobs`). Test data-provider que verifica que las 7 vistas existen. ~~vistas helpdesk.emails.* inexistentes~~
- [x] 🐞 **HelpdeskHelpcenter** · feedback del widget ahora crea/actualiza una fila `HelpCenterArticleVote` (dedup cookie+ip, filtro is_published) → el observer lo cuenta y ya NO se pierde; 2 tests de regresión. ~~se pierde~~
- [x] 🐞 **HelpdeskCampaigns** · `dispatch()` de ciclo de vida movido a los métodos del modelo `Campaign` (`publish/pause/resume/end`, fuente única de verdad) → el panel (acciones individuales y bulk, únicos callers de esos métodos) ahora dispara actividad/notificaciones/webhooks. API y scheduler ya disparaban por su cuenta (sin doble dispatch). 5 tests. ~~no se disparan desde el panel~~
- [x] 🐞 **HelpdeskCampaigns** · `UpdateCampaignImpressionCounters` ahora incrementa también los contadores de la variante (`impressions_count`/`clicks_count`) cuando la impresión trae `variant_id` → el accessor `ctr` deja de ser 0. Añadido `HasFactory`+`newFactory()` a `CampaignVariant` (factory estaba huérfana); 2 tests Feature. ~~CTR por variante siempre 0~~
- [~] 🐞👁 **HelpdeskEmailLog** · FALSO POSITIVO parcial: el reenvío SÍ crea fila (el listener `LogEmailQueued` registra todo `MessageSending`, incluido `Mail::html`). Lo que faltaba era **trazabilidad**: la fila salía con `module=null` y sin enlace al original. Arreglado añadiendo cabeceras X-* (module + entity `EmailLog#id` + `external_id='resend:<id>'`) que el listener ya sabe leer y limpiar. Test de regresión. ~~no crea fila~~
- [x] 🐞 **HelpdeskAnalytics** · `customerSegments()` ahora puntúa por lotes hasta 5000 clientes distintos (era cap silencioso de 500) y expone `sampled:true` cuando se supera; el dashboard muestra un aviso de muestreo. Test de regresión. ~~trunca a 500 sin aviso~~
- [x] 🐞 **HelpdeskDocument** · FK de `DocumentRequirement::langs()` ya declarado correcto (`document_type_requirement_id`; verificado: langs() carga) → la reversión ya no lanza QueryException. Además `destroy()` envuelve la reversión en `try/catch` (el fichero ya está borrado, un fallo cross-módulo se loguea y no da 500) y reusa `$missingDocuments`. Nota del test actualizada. ~~FK mal adivinada rompe la reversión~~
- [x] 🐞👁 **HelpdeskCompliance** · `failed()` ahora registra `AuditLogService::record('gdpr.cascade.failed', ...)` además del `Log::error` y la `ComplianceRequest` fallida — un fallo de cascada GDPR queda en el audit trail. Test de regresión. ~~failed() no audita~~
- [x] 🐞 **HelpdeskChatFlow** · A/B ahora deriva el brazo de forma determinista por conversación (`crc32(flow:conv) % 100`) en vez de `random_int` en cada evaluación → misma conversación, mismo brazo, sin contaminar la estadística; `random_int` solo como fallback sin contexto. Test de regresión. ~~random_int en cada evaluación~~

### Etapa 1b — Bug estructural mayor (proyecto aparte)

- [ ] 🐞 **HelpdeskTickets** · doble modelo de mensajería `TicketItem` vs `TicketMessage`: mensajes de portal/widget/formulario pueden no aparecer en el hilo del agente. Consolidar sobre `TicketItem` como única fuente de verdad. · **L** _(planificar como migración con panel de "mensajes huérfanos" de apoyo)_

### Etapa 2 — Performance y resiliencia

- [x] ⚡ **HelpdeskErp** · circuit breaker para el manager (hoy 15s de timeout por request en caídas); replicar el de PrestaShop. `ErpContextService.php:178` · **M**
- [x] ⚡ **HelpdeskTranslate** · circuit breaker/backoff en el path manual síncrono (peor caso ~25s). · **M**
- [x] ⚡ **HelpdeskHelpcenter** · `getWidgetData()` envuelto en `Cache::remember` (TTL 1h, `WIDGET_CACHE_KEY`); invalidación en los observers de artículo y traducción. 2 tests. ~~sin cachear~~
- [x] ⚡ **HelpdeskChatFlow** · `analytics()` cacheado por `(flow, days)` (TTL 15m); listener `InvalidateFlowAnalyticsCache` sobre `ChatFlowCompleted` olvida las 5 ventanas del flujo. 2 tests. ~~sin caché~~
- [ ] ⚡ **HelpdeskDocument** · diferir `documentsForConversation()` a AJAX on-tab-open (hoy query+escritura en cada carga de conversación). `right-panel.blade.php:97` · **M**
- [x] ⚡ **HelpdeskDocument** · quitado el `LOWER()`; la columna es `utf8mb4_unicode_ci` (case-insensitive) e indexada → EXPLAIN usa `documents_customer_email_index` (rows 3→1). Test de match case-insensitive. ~~LOWER anula índice~~
- [ ] ⚡ **HelpdeskDocument** · encolar `attachToDocument()` (media pesada síncrona en la request). `:218` · **M**
- [ ] ⚡ **HelpdeskContacts** · reescribir import CSV a batch (`whereIn`) + `DB::transaction()` + cola. `ContactsController.php:88` · **L**
- [x] ⚡ **HelpdeskTickets** · `checkBreaches()` marca el flag con `updateQuietly()` (no dispara los observers del ticket por cada fila del sweep); el aviso sigue por el evento `SlaBreached`. 4 tests `SlaBreachTest` verdes. ~~update() por fila~~
- [x] ⚡ **HelpdeskSla** · `recordBreach()` + `updateQuietly()` envueltos en `DB::connection('helpdesk')->transaction()` por conversación (evita breach sin flag → duplicados); evento tras el commit. 15 tests SLA verdes. ~~sin transacción~~
- [x] ⚡ **HelpdeskLivechat** · `queuePosition` cuenta los que van por delante en la cola: mayor prioridad, o misma prioridad y `created_at` anterior (id solo como desempate). 2 tests. ~~ordenaba por id~~
- [x] ⚡ **HelpdeskLivechat** · resolver geoIP en background en el primer heartbeat (alto volumen). `WidgetSessionService.php:171` · **M**
- [x] ⚡ **HelpdeskAgents** · ventana deslizante/resumen para los 100 mensajes por turno enviados al LLM. `:175` · **M**
- [x] ⚡ **HelpdeskCampaigns** · `index()` lee las columnas denormalizadas (`impressions_count`/`clicks_count`, mantenidas por `UpdateCampaignImpressionCounters`) en vez de 2 subqueries COUNT por fila (`withCount`). Query sin subqueries verificada. ~~withCount~~
- [x] ⚡ **HelpdeskPrestashop** · caché de `getCategories()` versionada (`ps.categories.v{n}.{lang}`); listener `InvalidateCatalogCache` sobre `PsPriceDropped`/`PsBackInStock` sube la versión (invalida todos los idiomas de golpe). 2 tests. ~~caché no invalidada~~
- [x] ⚡ **HelpdeskErp** · `getOrderDetail()` con caché corta (60s, `Cache::remember` no persiste null → no cachea fallos) + `->retry(2, 200, throw:false)` en la llamada HTTP. Test de cacheo (2 llamadas → 1 request). ~~sin caché ni retry~~
- [ ] ⚡ **Helpdesk core** · adjuntos como modelo indexado (elimina el scan `LIKE` de `downloadAttachment()`). `:1520` · **L** _(ligado a la feature de adjuntos, ver Parte A/Etapa 4)_

### Etapa 3 — Calidad, convenciones, observabilidad, i18n

- [x] 🧹 **HelpdeskTickets** · `StoreTicketRequest` reglas pipe → array (convención del proyecto). Validación verificada (required + in-list). ~~sintaxis pipe~~
- [x] 🧹 **HelpdeskPrestashop** · `PsRecommendationController::store()` usa `StorePsRecommendationRequest` (validación + authorize replicando la policy `view` de la conversación). 2 tests (reglas + 403). ~~valida inline~~
- [x] 🧹 **HelpdeskContacts** · `bulkAction()` usa `BulkContactActionRequest` (authorize `contacts.update` + reglas con `exists:helpdesk.helpdesk_customers` para la conexión correcta). 3 tests (403/422/ban). ~~valida inline~~
- [x] 🧹 **HelpdeskContacts/Prestashop** · `ContactCartController` usa `StoreContactCartItemRequest`/`ApplyContactCartDiscountRequest` que **extienden** los de PS (reglas heredadas → fuente única) y solo sobrescriben `authorize()` a `contacts.update` (sin cambiar el gate). 2 tests de herencia. ~~validación duplicada~~
- [x] 🧹 **HelpdeskErp** · unificar autorización de `health/search/warmCache` en Form Requests. · **S**
- [x] 🧹 **HelpdeskSla** · `getTypeLabelAttribute()` → método `typeLabel(): Attribute` (Laravel 11+). Verificado `type_label` sigue resolviendo. ~~accessor legacy~~
- [x] 🧹 **HelpdeskEmailLog** · 5 accessors legacy (`status_color`, `status_label`, `display_date`, `has_attachments`, `entity_label`) → métodos `Attribute` (Laravel 11+). Verificados + 28 tests EmailLog verdes. ~~accessors legacy~~
- [ ] 🧹 **Helpdesk core** · separar `HelpdeskServiceProvider` (504 líneas) en sub-providers. · **S**
- [x] 👁 **Helpdesk core** · los 4 `\Log::` globales de `ConversationsController` → `Log::channel('helpdesk')->…` (canal dedicado ya existente en config/logging.php). 0 `\Log::` globales restantes en el core. ~~\Log:: global~~
- [x] 👁 **HelpdeskErp** · `searchCustomers()` loguea `Log::warning` en el catch (antes `catch (\Throwable) { return []; }` mudo). ~~catch silencioso~~
- [x] 👁 **HelpdeskDocument** · FALSO POSITIVO: los 5 catch ya llaman `logSectionFailure()` → `Log::warning` estructurado (document_id/uid/error). Verificado. ~~catches silenciados~~
- [x] 👁 **HelpdeskEmailLog** · `entity_url` → `Attribute` y loguea `Log::warning` en el catch (antes silencioso). 28 tests EmailLog verdes. ~~catch silencioso~~
- [x] 👁 **HelpdeskSla** · `Log::info` estructurado con conteo al final de `checkBreaches()`/`sendWarnings()` (solo cuando hay >0). 9 tests SLA verdes. ~~sin logging~~
- [ ] 👁 **HelpdeskLivechat** · telemetría de fallos de señalización WebRTC (`webrtc.ts:130`). · **S**
- [x] 👁 **HelpdeskChatFlow** · FALSO POSITIVO: ambos casos ya logueaban `Log::warning` (`ChatFlow max depth reached` :765 y `ChatFlow next node not found` :753) + `failSession`. Verificado. ~~sin alerta~~
- [x] 👁 **HelpdeskCampaigns** · `EndExpiredCampaignsJob`/`PublishScheduledCampaignsJob` avisan con `Log::warning` cuando el lote llena el cap de 100 (puede quedar más para la siguiente pasada). ~~cap silencioso~~
- [x] 🌐 **Helpdesk core** · los 3 mensajes de `merge()` (self / distinto contacto / éxito) → `__('helpdesk::helpdesk.messages.merge_*')` (claves es/en). 6 tests MergeTest verdes. ~~hardcoded~~
- [x] 🌐 **HelpdeskAgents** · los 8 resultados de acción del flow (`ticket_closed`, `ticket_assigned`, `action_failed`…) → `__('helpdeskagents::helpdeskagents.flow_results.*')` (es/en, 8 claves). Verificado; 2 fallos del test son preexistentes (stash). ~~hardcoded~~
- [x] 🌐 **HelpdeskAnalytics** · `dashboard/index.blade.php` a `__('helpdeskanalytics::messages.*')` (25 claves es/en, incl. KPIs, gráficos, tabla y aviso de muestreo). Verificado en navegador (sin claves crudas). ~~hardcoded~~
- [x] 🌐 **HelpdeskSla** · `'Sin asunto'`/`'Sin cliente'` → `__('helpdesksla::messages.no_subject|no_customer')` (claves añadidas en es/en). Verificado. ~~hardcoded~~
- [x] 🌐 **HelpdeskEmailLog** · cabeceras CSV del export → `__('helpdeskemaillog::emaillog.csv.*')` (claves añadidas es/en). Export test verde. ~~hardcoded~~
- [x] 🌐 **HelpdeskChatFlow** · `docLabels` movido a `config('helpdeskchatflow.document_labels')` — unificadas las **3 copias** duplicadas (Engine + NodeExecutor + TestSimulator) en una sola fuente editable. 21 tests verdes. ~~3 copias hardcoded~~
- [x] 🌐 **HelpdeskSocial** · creado `resources/lang/en/helpdesksocial.php` (40 claves, espejo exacto de es). ~~sin lang/en~~
- [x] 🧹 **HelpdeskSocial** · `forgetCachedPermissions()` al inicio del `HelpdeskSocialPermissionsSeeder`. ~~sin reset de caché~~
- [x] 🧹 **HelpdeskSocial** · guard `Module::find($this->name)?->isDisabled()` al inicio de `boot()` (consistente con los demás satélites). ~~sin guard~~
- [x] 🧹 **HelpdeskChatFlow** · extraer helper `postBotMessage()` (12+ `ConversationItem::create` duplicados). · **M**
- [x] 🧹 **HelpdeskLivechat** · excepción del stack React ya documentada en el README del módulo; añadida referencia cruzada de 1 línea en el `CLAUDE.md` raíz junto a la regla "No Livewire/Inertia". ~~sin documentar~~
- [x] 🧹 **HelpdeskLivechat** · `trim()` a ambos lados de la comparación de email en `emailTranscript` (el email del cliente guardado con espacios daba 403 falso). Test de regresión (whitespace). ~~sin trim~~
- [x] 🧹 **HelpdeskCampaigns** · `duplicate()` ahora clona las variantes A/B (se perdían) y resetea `impressions_count`/`clicks_count` a 0 en campaña y variantes, todo en `DB::transaction()`. Test de regresión. ~~no clonaba variantes ni reseteaba~~
- [ ] 🧹 Código muerto: **Compliance** `TYPE_EXPORT` + permiso `manage`; **Analytics** permiso `export`; **Agents** flag `requires_approval`; **Integration** columna `credentials`. · **S** (decidir: implementar o retirar)

### Etapa 4 — Cobertura de tests

- [ ] 🧪 **Helpdesk core** · `AutomationEngine`, `WorkflowEngine`, `ConversationMessageService`, GDPR, routing, business-hours. · **L**
- [ ] 🧪 **HelpdeskAgents** · `EmbeddingService`, `KnowledgeRetrievalService`, `ToolExecutionService` (db-tool), 3 proveedores IA. · **M**
- [ ] 🧪 **HelpdeskTickets** · deduplicar tests SLA + regresión de `priority_id`. · **M**
- [ ] 🧪 **HelpdeskSla** · `SlaBreachesController`/policy + factory de `ConversationSlaBreach`. · **M**
- [ ] 🧪 **HelpdeskEmailLog** · comportamiento del reenvío (cabeceras/tracking/nueva fila). · **M**
- [ ] 🧪 **HelpdeskHelpcenter** · `HelpcenterWidgetController`/`Service` (conteo cruzado). · **S**
- [ ] 🧪 **HelpdeskCompliance** · path de fallo del job (`status='failed'`). · **S**
- [ ] 🧪 **HelpdeskCampaigns** · acciones del panel disparan eventos (`Event::fake`). · **S**
- [ ] 🧪 **HelpdeskAnalytics** · end-to-end de `AnalyticsController::data()`. · **S**
- [ ] 🧪 **HelpdeskTranslate** · rutas manuales respetan el toggle. · **S**
- [ ] 🧪 **HelpdeskLivechat** · queue-position + presencia (online/offline/cola). · **M**
- [ ] 🧪 **HelpdeskIntegration** · driver custom (`isNative()===false`) de punta a punta. · **M**
- [ ] 🧪 **HelpdeskChatFlow** · end-to-end de `buildAbComparison` con `analytics()`. · **S**

### Etapa 5 — Nuevas implementaciones (features de valor)

Iniciativas más grandes; priorizar según negocio. Detalle completo en la Parte B de cada módulo.

- [ ] ✨ **Helpdesk core** · Adjuntos como modelo de primera clase · **L**
- [ ] ✨ **Helpdesk core** · Búsqueda full-text dentro del hilo · **M**
- [ ] ✨ **Helpdesk core** · Scoring automático de calidad de conversación · **M**
- [ ] ✨ **Helpdesk core** · Dashboard de entregas de webhook · **S**
- [ ] ✨ **HelpdeskAgents** · Dashboard de coste/presupuesto LLM · **M**
- [ ] ✨ **HelpdeskAgents** · Versionado/rollback de flows · **M**
- [ ] ✨ **HelpdeskAgents** · Red de handoff humano (sentiment/N turnos) · **M**
- [ ] ✨ **HelpdeskIntegration** · Circuit breaker por proveedor · **M**
- [ ] ✨ **HelpdeskIntegration** · "Probar conexión" por proveedor · **S**
- [ ] ✨ **HelpdeskIntegration** · Resync masivo en background · **M**
- [ ] ✨ **HelpdeskTickets** · Motor SLA único (reusando HelpdeskSla) · **L**
- [ ] ✨ **HelpdeskSla** · Recalculo de SLA al reabrir + notificación real de breach · **M**
- [ ] ✨ **HelpdeskSla** · Export CSV + dashboard por agente/grupo · **M/L**
- [ ] ✨ **HelpdeskEmailLog** · Reenvío por Mailable original + ingesta de webhooks de bounce/apertura · **L**
- [ ] ✨ **HelpdeskLivechat** · Tiempo estimado de espera + panel de salud del widget · **M**
- [ ] ✨ **HelpdeskChatFlow** · Lint en vivo en el editor + métrica de coste IA por flow · **M**
- [ ] ✨ **HelpdeskHelpcenter** · Artículos relacionados por embeddings + panel de feedback negativo + versionado de artículos · **M/L**
- [ ] ✨ **HelpdeskErp/Prestashop** · Panel de salud de integración en Settings · **M**
- [ ] ✨ **HelpdeskContacts** · Evento `ContactsMerged` + actividad en vivo por broadcast · **M**
- [ ] ✨ **HelpdeskCampaigns** · UI de variantes A/B + reporte exportable + historial de webhooks · **L**
- [ ] ✨ **HelpdeskAnalytics** · API Sanctum + export + digest por email · **M**
- [ ] ✨ **HelpdeskSocial** · Terminar/retirar push + WhatsApp como 2º canal + alerta de Crisis Mode · **L**
- [ ] ✨ **HelpdeskCompliance** · Exportación GDPR (Art. 20) + reintento de cascadas + extender a Translate/Document · **L**
- [ ] ✨ **HelpdeskDocument** · Vista "documentos pendientes" transversal + evento en timeline + OCR · **L**
- [ ] ✨ **HelpdeskTranslate** · Navegador/purga de caché + preferencia de idioma del cliente + dashboard de coste · **M**

---

## PARTE B — Detalle por módulo (referencia de trabajo)

### 1. Helpdesk (core) — _maduro_
Núcleo omnicanal: conversaciones, clientes, canales, automatizaciones, SLA, GDPR, macros, portal, simulador (~470 archivos).

**Mejoras**
- [ ] 🐞 (PHPStan) `BroadcastService::dispatchBroadcast()` despacha `Modules\Helpdesk\Jobs\Campaigns\SendBroadcastMessageJob` que **no existe** (el dir `Jobs/Campaigns/` no existe; `SendBroadcastJob` es otro job, por-broadcast, incompatible) → fatal "class not found" al usar broadcasts. Feature **incompleta y dormida** (nadie llama a BroadcastService): crear el job por-destinatario o retirar el servicio. `BroadcastService.php:63` · **M**
- [x] 🐞 (PHPStan) `CampaignsControllerLegacy` (módulo base Campaign) hacía `Modules\Campaign\Events\CampaignUpdated::dispatch()` sin `\` inicial → PHP lo resolvía relativo al namespace actual → fatal "class not found". Corregidas 2 refs al nombre corto importado. `CampaignsControllerLegacy.php:79,512` · **S**
- [x] 🧹 (PHPStan) `Conversation::items()` tipada `HasMany<ConversationItem, $this>` → mejora inferencia de tipos en todo `->items()` del código. · **S**
- [x] 🔒 `merge()` sin autorizar la conversación destino. `ConversationsController.php:1184` · **S**
- [ ] 🧹 Controller de 2760 líneas: separar email/HSM, reacciones, forward, merge. · **L**
- [ ] ⚡ `downloadAttachment()` con scan `LIKE` no indexable. `:1520` · **M**
- [ ] 🧹 `HelpdeskServiceProvider` (504 líneas) → sub-providers. · **S**
- [ ] 👁 Estandarizar `Log::channel('helpdesk')`. · **S**
- [ ] 🌐 Mensajes JSON de merge a `__()`. `:1191,1197,1207` · **M**
- [ ] 🧪 Tests de Automation/Workflow/GDPR/routing/business-hours. · **L**

**Nuevas implementaciones**
- [ ] ✨ Adjuntos como modelo (`ConversationAttachment` path indexado). · **L**
- [ ] ✨ Búsqueda full-text dentro del hilo. · **M**
- [ ] ✨ Scoring automático de calidad de conversación. · **M**
- [ ] ✨ Dashboard de entregas de webhook (`WebhookDelivery`). · **S**

---

### 2. HelpdeskAgents — _maduro (IA)_
Agentes de IA: flujos LLM, KB con embeddings, tools, turnos.

**Mejoras**
- [x] 🐞 `AiAgentFlow` (modelo + controlador) seguía usando `trigger_type` tras la migración que renombró la columna a `trigger` (`2026_05_21_000600`) → `create()`/`scopeByTrigger()`/accessor rotos contra el esquema real ("Unknown column"). Latente porque el runtime de IA va off por defecto. Corregidas las 7 referencias + 2 tests de regresión. · **S**
- [x] 🐞 (PHPStan) `addTicketTag()` invocaba `$ticket->tags()->syncWithoutDetaching()` pero `tags` es columna JSON (cast array), no relación → fatal "undefined method Ticket::tags()". Corregido a append al array con dedup + 2 tests. · **S**
- [ ] 🐞 (PHPStan) `executeActionNode()` hace `$session->conversation->tickets()->latest()` pero **`Conversation::tickets()` no existe** (Ticket no tiene `conversation_id` ni vínculo). El path de acciones sobre ticket está **incompleto contra el esquema**; requiere decidir cómo resolver el ticket de una conversación (bridge/servicio) antes de cablearlo. `AiAgentFlowEngine.php:303` · **M**
- [x] 🧹 (PHPStan) Tipadas relaciones que causaban clústeres de `method.notFound`: `Conversation::items()` (ayuda a todo el código), `ChatFlowSession::conversation()/chatFlow()` (ChatFlow 125→82), `AiAgent::flows()`, `AiAgentSession::conversation()`. Docblocks-only, cero riesgo runtime; PHPStan ahora caza mal-usos de estos modelos. · **S**
- [x] 🔒 Tool `database` = SELECT arbitrario; permiso privilegiado + allowlist. `ToolExecutionService.php:140` · **M**
- [ ] 🧹 Acoplamiento duro a `Ticket` sin guard de módulo. `AiAgentFlowEngine.php:277` · **S**
- [x] ⚡ 100 mensajes por turno al LLM sin ventana/resumen. `:175` · **M**
- [ ] 🧹 Flag `requires_approval` es dato muerto. · **S**
- [ ] 🌐 Strings de error del flow en inglés hardcodeado. · **S**
- [ ] 🧪 Tests de Embedding/Knowledge/ToolExecution/proveedores. · **M**

**Nuevas implementaciones**
- [ ] ✨ Dashboard de coste/presupuesto LLM. · **M**
- [ ] ✨ Versionado/rollback de flows. · **M**
- [ ] ✨ Red de handoff humano (sentiment/N turnos). · **M**
- [ ] ✨ Workflow de aprobación de tools (conectar `requires_approval`). · **M**

---

### 3. HelpdeskIntegration — _nuevo_
Drivers pluggable a plataformas externas + gate OTP + audit log.

**Mejoras**
- [ ] 🧹 Columna `credentials` write-only para drivers nativos. · **M**
- [x] 🔒 Códigos OTP previos no invalidados al reemitir. `CustomerIdentityVerificationService.php:28` · **S**
- [ ] 👁 Activar provider sin módulo instalado → vacío en silencio; badge `isAvailable()`. · **S**
- [ ] 🧹 `search()` sin `authorize()` explícito (inconsistente). · **S**
- [ ] 🧪 Test de driver custom end-to-end. · **M**

**Nuevas implementaciones**
- [ ] ✨ Botón "probar conexión" por proveedor. · **S**
- [ ] ✨ Resync masivo en background. · **M**
- [ ] ✨ Circuit breaker por proveedor. · **M**
- [ ] ✨ Auto-descubrimiento de vínculos por webhook. · **L**

---

### 4. HelpdeskTickets — _maduro_
Tickets completos (agentes/managers/portal/widget/API) con SLA propio.

**Mejoras**
- [x] 🐞 (imports rotos, detector propio) Corregidos 13 `use` a clases inexistentes/mal-namespaced en el ecosistema helpdesk (fatales al usarse): `NotificationPreference` (×6 notifs de ticket, `App\Models\Notifications\`→`Modules\Notification\Models\`), factories `TicketMessage`/`TicketSlaPolicy` (`Modules\Helpdesk\`→`Modules\HelpdeskTickets\`), `HelpCenterArticle` en `AiAgentService` (→`HelpdeskHelpcenter`), evento `TicketUnassigned` **creado** (se despachaba sin existir → fatal al desasignar) + import, 3 tests con `AiAgentSettingsController`→`AgentSettingsController`. Todas cargan OK vía tinker.
- [x] ✨ **`TicketReplyMail` implementado** (era una clase inexistente usada en `TicketCommentsController::store` → responder un comentario externo fataba). Nuevo Mailable en `HelpdeskTickets\Mail\` espejando `TicketCreatedMail` (TracksEmailLog); call site corregido para renderizar vía `TicketMailRenderer` con la plantilla **existente** `helpdesk.ticket_reply` (misma que el path del listener TicketItem) y enviar a `$ticket->customer->email`. Verificado (carga, Mailable, TracksEmailLog); el test HTTP queda bloqueado por el drift de permisos del test-DB.
- [x] 🧹 Comando `tickets:sla-warnings` **consolidado**: duplicaba ~200 líneas de lógica SLA con `TicketSlaWarning`+`TicketSlaWarningMail` (inexistentes → fatal). Ahora delega en el job canónico `SendSlaWarnings` (mismo que corre programado). + test de delegación.
- [x] 🧹 `StoreTicketsRequest` (con 's') **retirada**: Form Request muerta (unrouted + `App\Permissions\V1\Abilities` inexistente); duplicado legacy de `StoreTicketRequest`.
- [ ] 🐞 Clases inexistentes en código **dormido** (decisión de producto: activar o retirar): `App\Models\ReturnCommunication` (`CleanupOldCommunications`, no scheduleado), `SideConversation{Created,MessageReceived}Notification` (`SideConversationService`, sin callers), `Jobs\Campaigns\SendBroadcastMessageJob` (`BroadcastService`, sin UI/callers). · **M**
- [ ] 🐞 Política SLA por `priority_id` inexistente. `SlaService.php:249` · **M**
- [ ] 🐞 Emails a vistas `helpdesk.emails.*` inexistentes → `failed_jobs`. · **M**
- [ ] 🐞 Doble modelo de mensajería `TicketItem`/`TicketMessage`. · **L**
- [ ] 🧹 Motor SLA duplicado y divergente (2 modelos de política). · **L**
- [ ] ⚡ Batch SLA con `updateQuietly()`. `:54` · **S**
- [x] 🔒 `store()` sin `authorize()` explícito. `:117` · **S**
- [ ] 🧹 Form Requests con sintaxis pipe. `StoreTicketRequest.php:26` · **S**
- [ ] 🧪 Tests SLA duplicados; sin regresión de `priority_id`. · **S/M**

**Nuevas implementaciones**
- [ ] ✨ Motor SLA único reutilizando HelpdeskSla. · **L**
- [ ] ✨ Panel de "mensajes huérfanos". · **M**
- [ ] ✨ Conectar fallos de notificación a HelpdeskEmailLog. · **M**
- [ ] ✨ Clarificar versionado de API (dos `TicketResource`). · **S**

---

### 5. HelpdeskSla — _MVP_
Motor SLA para conversaciones (independiente del de Tickets).

**Mejoras**
- [ ] 🐞 No maneja la reapertura de conversación. `ConversationSlaObserver.php:28` · **M**
- [ ] ⚡ Breach + flag sin `DB::transaction()`. `ConversationSlaService.php:121` · **S**
- [ ] 🧹 Guard de toggle solo en `send-warnings`, no en `check-breaches`. `:99` · **S**
- [ ] 🧹 Accessor legacy → `Attribute`. `:68` · **S**
- [ ] 👁 Sin logging estructurado. · **S**
- [ ] 🌐 `'Sin asunto'`/`'Sin cliente'` hardcodeados. `:43` · **S**
- [ ] 🧪 Sin tests de controller/policy; sin factory. · **M**

**Nuevas implementaciones**
- [ ] ✨ Recalculo de SLA al reabrir. · **M**
- [ ] ✨ Export CSV de incumplimientos (streaming). · **M**
- [ ] ✨ Dashboard de cumplimiento por agente/grupo. · **L**
- [ ] ✨ Notificación real de breach/warning. · **M**

---

### 6. HelpdeskEmailLog — _alineado_
Auditoría de emails transaccionales.

**Mejoras**
- [ ] 🐞👁 Reenvío no crea fila de log (sin `TracksEmailLog`). `ResendEmailLogJob.php:27` · **M**
- [x] 🔒 `bulkDestroy()` sin `authorize('deleteAny')`. `:108` · **S**
- [ ] 🧹 Accessors legacy → `Attribute`. `EmailLog.php:194` · **S**
- [ ] ⚡ `computeStats()` sin índice `(status, created_at)`. `:169` · **S**
- [x] 👁 `getEntityUrlAttribute()` traga excepción sin log. `:224` · **S**
- [ ] 🌐 Cabeceras CSV inline → `lang/es/emaillog.php`. `:143` · **S**
- [ ] 🧪 Sin cobertura del reenvío. · **M**

**Nuevas implementaciones**
- [ ] ✨ Reenvío por Mailable original (`mailable_class`). · **L**
- [ ] ✨ Ingesta de webhooks de bounce/queja/apertura (`external_id`). · **L**
- [ ] ✨ Dashboard de salud de email por módulo. · **M**
- [ ] ✨ Retención por módulo. · **M**

---

### 7. HelpdeskLivechat — _maduro (widget React)_
Chat en vivo embebible con WebRTC, livestream, pre-chat, transcripciones.

**Mejoras**
- [ ] ⚡ `queuePosition` usa `id` como FIFO. `:252` · **M**
- [x] ⚡ GeoIP síncrono en el primer heartbeat. `WidgetSessionService.php:171` · **M**
- [ ] 👁 `webrtc.ts` traga fallos de señalización. `:130` · **S**
- [ ] 🧹 Stack React sin documentar como excepción. · **S**
- [ ] 🧹 `emailTranscript` sin `trim()`. `:212` · **S**
- [ ] 🧪 Test queue-position + presencia. · **M**

**Nuevas implementaciones**
- [ ] ✨ Tiempo estimado de espera visible. · **M**
- [ ] ✨ Reconexión resiliente Echo/WS con buffer offline. · **M**
- [ ] ✨ Panel de salud del widget. · **M**
- [ ] ✨ Borrado GDPR bajo demanda del visitante. · **L**

---

### 8. HelpdeskChatFlow — _maduro_
Chatbot/flow builder multicanal con A/B, versionado, replay, analítica.

**Mejoras**
- [ ] ⚡ `analytics()` sin caché. `:345` · **M**
- [ ] 🌐 `docLabels` hardcodeado en español. `ChatFlowEngine.php:429` · **M**
- [x] 🧹 12+ `ConversationItem::create` duplicados → helper. · **M**
- [ ] 🐞 A/B no persiste el variant por conversación. `:662` · **S**
- [x] 🔒 `import()` sin límite de nodos/tamaño. · **S**
- [ ] 👁 Sin contador/alerta para flows rotos. · **S**
- [ ] 🧪 Sin test end-to-end de `buildAbComparison`. · **S**

**Nuevas implementaciones**
- [ ] ✨ Lint en vivo en el editor. · **M**
- [ ] ✨ Condiciones reutilizables entre flows. · **M**
- [ ] ✨ Métrica de coste de IA por flow. · **M**
- [ ] ✨ Export de drop-off a CSV/PDF. · **S**

---

### 9. HelpdeskHelpcenter — _maduro_
Base de conocimiento con traducción, votos, embeddings, sitemap, widget API.

**Mejoras**
- [ ] 🐞 Dos sistemas de feedback que se pisan (widget vs público). `HelpcenterWidgetService.php:147` · **M**
- [ ] ⚡ Caché del widget invalidada pero nunca consumida. `:17` · **M**
- [x] 🔒 `apiArticleFeedback` sin dedup por artículo. · **S**
- [ ] 🧹 `recordFeedback()` no filtra `is_published`/`active`. `:147` · **S**
- [ ] 🧹 `searchArticles()` LIKE sobre `content` sin FULLTEXT confirmado. · **S**
- [ ] 🧪 Sin tests de `HelpcenterWidgetController`/`Service`. · **S**

**Nuevas implementaciones**
- [ ] ✨ Unificar pipeline de feedback widget↔público. · **M**
- [ ] ✨ Artículos relacionados por embeddings (página pública). · **M**
- [ ] ✨ Panel de artículos con feedback negativo. · **S**
- [ ] ✨ Versionado/historial de artículos. · **L**

---

### 10. HelpdeskErp — _medio (Oracle)_
Puente al ERP Oracle: contexto comercial, búsqueda, timeline, auto-vinculación.

**Mejoras**
- [x] ⚡ Sin circuit breaker (15s por request en caídas). `ErpContextService.php:178` · **M**
- [x] 👁 `searchCustomers()` silencia errores sin log. `:85` · **S**
- [x] 🧹 Autorización inline en `health/search/warmCache`. · **S**
- [x] ⚡ `getOrderDetail()` sin caché HTTP. `:90` · **S**
- [x] ⚡ Sin `Http::retry()` con backoff. · **S**
- [ ] 👁 `Pulse::set()` sobrescribe por email (no series). `:580` · **S**
- [x] 🔒 `/health` y `/cache/warm` sin `audit.access`. `api.php:37` · **S**

**Nuevas implementaciones**
- [x] ✨ Circuit breaker para el manager ERP. · **M**
- [ ] ✨ Health-check programado con alerta. · **M**
- [ ] ✨ Panel de salud ERP en Settings. · **M**
- [x] ✨ Caché corta para `getOrderDetail()`. · **S**

---

### 11. HelpdeskPrestashop — _medio (PS)_
Puente al bridge PS: contexto, devoluciones, productos, webhooks, carritos asistidos.

**Mejoras**
- [x] 🔒🐞 `categories()` ability sin Policy → 403 para agentes. `ProductSearchController.php:190` · **S**
- [x] 🔒 `generateOrder()` sin idempotencia (pedidos duplicados). · **M**
- [ ] 🧹 `PsRecommendationController::store()` valida inline. `:31` · **S**
- [ ] 🧹 Validación duplicada nativo vs `ContactCartController`. · **M**
- [x] 🔒 Password de relleno con `uniqid()`. `:315` · **S**
- [ ] ⚡ Caché de categorías (1h) sin invalidación por evento. `:444` · **S**
- [ ] 🧹 `normalizeProduct()`/`mapOrder()` sin validar shape del payload. · **S**

**Nuevas implementaciones**
- [ ] ✨ Idempotencia en `generateOrder()`/`sendPaymentLink()`. · **M**
- [ ] ✨ Reutilizar Form Requests desde `ContactCartController`. · **S**
- [ ] ✨ Panel de salud PrestaShop en Settings. · **M**
- [ ] ✨ Invalidación de caché de catálogo por eventos. · **S**

---

### 12. HelpdeskContacts — _medio (CRM 360)_
Agregación por cliente + fusión de duplicados + import/export CSV.

**Mejoras**
- [ ] ⚡ Import CSV fila a fila sin transacción/batching. `ContactsController.php:88` · **M**
- [x] 🔒 Merge sin `audit.access` ni evento. `routes/web.php:51` · **S**
- [ ] 🧹 Caché ERP/PS no invalidada tras merge. · **S**
- [ ] 🧹 `bulkAction()` valida inline. `:208` · **S**
- [ ] 🧹 `ContactCartController` no reutiliza Form Requests de PS. · **M**
- [ ] 👁 `ban()`/`unban()` sin motivo ni actor. `:186` · **S**

**Nuevas implementaciones**
- [ ] ✨ Evento `ContactsMerged` con listeners. · **M**
- [ ] ✨ Import CSV a batch + cola. · **L**
- [ ] ✨ Trazabilidad de ban/unban/merge. · **M**
- [ ] ✨ Actividad en vivo por broadcast `ErpOrdersReady`. · **M**

---

### 13. HelpdeskCampaigns — _maduro_
Campañas on-site con targeting, A/B, tracking, aprobación, webhooks.

**Mejoras**
- [ ] 🐞 Eventos de ciclo de vida no se disparan desde el panel. `:164-215` · **M**
- [ ] 🐞 CTR por variante siempre 0. `:29` · **S**
- [ ] 🧹 `duplicate()` no clona variantes ni resetea contadores. `:421` · **S**
- [ ] ⚡ Listados con `withCount` pese a columnas denormalizadas. `:27` · **S**
- [x] 👁 Jobs con `->limit(100)`/min sin alerta al tope. · **S**
- [ ] 🧪 Sin test de eventos disparados desde el panel. · **S**

**Nuevas implementaciones**
- [ ] ✨ UI de gestión de variantes A/B + tabla de CTR. · **L**
- [ ] ✨ Reporte de rendimiento exportable. · **M**
- [ ] ✨ Historial de entregas de webhook. · **M**
- [ ] ✨ Notificación de decisión de aprobación. · **M**

---

### 14. HelpdeskAnalytics — _optimizado (read-only)_
Dashboard cross-canal, solo lectura, ya cacheado.

**Mejoras**
- [x] 🔒 Rango de fechas sin tope (DoS). `:16` · **S**
- [ ] 🐞 `customerSegments()` trunca a 500 sin aviso. `:198` · **S**
- [ ] 🧹 Permiso `export` sembrado sin uso. · **S**
- [ ] 🌐 `dashboard/index.blade.php` sin `__()`. · **S**
- [ ] 🧹 Sin API para BI externo. · **M**
- [ ] 🧪 Falta e2e de `data()`. · **S**

**Nuevas implementaciones**
- [ ] ✨ Endpoint API Sanctum. · **M**
- [ ] ✨ Export CSV/Excel. · **S**
- [ ] ✨ Digest por email. · **M**
- [ ] ✨ Tendencia de SLA/primera respuesta. · **M**

---

### 15. HelpdeskSocial — _desactivado_
Bandeja social (Meta/FB/IG, config WhatsApp). Grande (~150 archivos). **Off** en `modules_statuses.json`.

**Bloqueantes para activar**
- [x] 🔒 `assign()` sin autorización (P0). `SocialInboxController.php:130` · **S**
- [x] 🔒 `bulk()` autoriza solo permiso genérico. `:142` · **M**
- [ ] 🧹 Push notifications completamente stub. `SendWebPushNotificationJob.php:29` · **M**
- [ ] 🧹 Seeder sin `forgetCachedPermissions()`. · **S**
- [ ] 🧹 `boot()` sin guard `Module::isDisabled()`. · **S**
- [ ] 🌐 Falta `lang/en/`. · **S**

**Nuevas implementaciones**
- [ ] ✨ Terminar o retirar el push real (VAPID/minishlink). · **M**
- [ ] ✨ WhatsApp Business API como 2º canal. · **L**
- [ ] ✨ Superficie de alerta para Crisis Mode. · **M**
- [ ] ✨ Webhooks salientes de breach de SLA social. · **M**

---

### 16. HelpdeskCompliance — _MVP (GDPR)_
Orquestador asíncrono de la cascada GDPR.

**Mejoras**
- [x] 🔒 `moduleReady()` ignora los toggles de integración. `:102` · **S**
- [x] 👁 `failed()` no llama `AuditLogService::record()`. `:80` · **S**
- [ ] 🧪 Path de fallo sin test. · **S**
- [ ] 🧹 UI sin controles de paginación (solo primera página). `index.blade.php:37` · **S**
- [ ] 🧹 Sin filtro por `status`/fecha. · **S**
- [ ] 🧹 `TYPE_EXPORT` y permiso `manage` código muerto. · **S**
- [ ] 🧹 Sin snapshot de email/nombre → fila huérfana tras hard-delete. · **M**

**Nuevas implementaciones**
- [ ] ✨ Exportación real de datos (GDPR Art. 20). · **L**
- [ ] ✨ Reintento de cascadas fallidas desde la UI. · **M**
- [ ] ✨ Extender la cascada a Translate y Document. · **L**
- [ ] ✨ Dashboard de cumplimiento agregado. · **M**

---

### 17. HelpdeskDocument — _medio (KYC)_
Puente entre el módulo Document y el inbox (expedientes, importación, proxy de acciones).

**Mejoras**
- [x] 🔒 Un solo permiso `view` autoriza acciones destructivas. `routes/managers.php:39` · **M**
- [ ] ⚡ Query + escritura en cada carga del inbox. `right-panel.blade.php:97` · **M**
- [ ] ⚡ `LOWER()` anula el índice de email. `ConversationDocumentLinker.php:93` · **S**
- [ ] ⚡ `attachToDocument()` media pesada síncrona → encolar. `:218` · **M**
- [ ] 🧹 Read-modify-write de `metadata` sin lock. `:294` · **S**
- [ ] 🐞 Bug cross-módulo (`DocumentRequirement::langs()` FK). · **M**
- [x] 👁 `DocumentPanelPresenter` silencia `\Throwable` sin `report()`. · **S**

**Nuevas implementaciones**
- [ ] ✨ Vista "documentos pendientes" transversal por SLA. · **L**
- [ ] ✨ Evento en el timeline al cambiar estado de documento. · **M**
- [ ] ✨ OCR/auto-extracción al subir. · **L**
- [ ] ✨ Vinculación diferida de importaciones sin expediente. · **M**

---

### 18. HelpdeskTranslate — _medio (i18n)_
Traducción automática (DeepL + LibreTranslate) en el inbox.

**Mejoras**
- [x] 🔒 Toggle no protege los endpoints manuales. · **M**
- [x] 🔒 PII sin límite en `helpdesk_translation_cache` (sobrevive GDPR hard) → comando `helpdesktranslate:prune-cache` (retención `cache_retention_days`=90, poda por `last_used_at`) + schedule diario + 2 tests. Caché deduplicada por hash (sin `customer_id`), se acota por retención en vez de purga por cliente. · **M**
- [ ] 🧹 `TranslateRequest` acepta cualquier código sin `Rule::in`. `:21` · **S**
- [x] ⚡ Path manual síncrono sin circuit breaker (~25s). · **M**
- [ ] 🧹 Memoización `columnsExist()` sin invalidación (documentar). · **S**
- [ ] 🧪 Sin test de toggle en rutas manuales. · **S**

**Nuevas implementaciones**
- [ ] ✨ Gating de las rutas manuales (`integration.enabled:translate`). · **S**
- [ ] ✨ Navegador/purga selectiva de la caché. · **M**
- [ ] ✨ Preferencia de idioma explícita del cliente. · **M**
- [ ] ✨ Dashboard de uso/coste real. · **M**

---

## Cómo usar este documento

1. **Por etapas** (Parte A): ejecutar de arriba a abajo. Etapa 0 y 1 antes de merge/deploy; Etapa 2–4 en sprints; Etapa 5 según prioridad de producto.
2. **Por módulo** (Parte B): al abrir un módulo, revisar su bloque completo y marcar `- [x]` lo hecho.
3. Cada ítem de Etapa 0/1 debería llevar su **test de regresión** en el mismo PR.
4. Antes de cada PR: `vendor/bin/pint --dirty` y correr los tests del módulo con `--filter`.
