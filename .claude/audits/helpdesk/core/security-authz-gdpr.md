# Auditoría core Helpdesk — Seguridad, autorización & GDPR/Compliance

> Fecha: 2026-06-29 · Health score: 66/100 · Estado: needs-work

**Resumen:** Las bases son sólidas (9 policies con aislamiento por inbox real, 117 Form Requests todos con `authorize()` y permisos reales, 2FA con secretos cifrados + recovery hasheados, webhook secrets cifrados, cascada GDPR por evento y purga programada que sí se ejecuta), pero el aislamiento por inbox se rompe sistemáticamente en los endpoints de **búsqueda y listado** (las policies existen pero las queries no las aplican) provocando fuga de PII cross-inbox, y la configuración (`settings`) está gateada solo por `role:super-admin|super-settings`, dejando muertos ~30 permisos granulares.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Esfuerzo | Título |
|----|-----|-----------|---------------|----------|--------|
| SEC-01 | high | IDOR / fuga PII | GlobalSearchService.php:62-128, GlobalSearchController.php:22-67, CustomersController.php:30-57,183-187 | m | Búsqueda y listado de clientes/conversaciones sin aislamiento por inbox |
| SEC-02 | high | IDOR / authz | ConversationParticipantsController.php:15-61 | s | Gestión de participantes con permiso global, sin policy por conversación |
| SEC-03 | medium | Authz granular | RouteServiceProvider.php:33-36 | m | `settings` gateado solo por rol; ~30 permisos granulares muertos/incoherentes |
| SEC-04 | medium | GDPR | GdprDeletionService.php:46-92 | m | Soft-delete deja PII sin redactar (CSAT/NPS comment, subject, ban_reason) |
| SEC-05 | medium | ReDoS / DoS | RoutingRule.php:45 + StoreRoutingRuleRequest.php:18 | s | Regex de routing sin validar → ReDoS en pipeline de entrada |
| SEC-06 | medium | Permiso inexistente | SearchCannedRepliesRequest.php:11, UploadAttachmentRequest.php:11, Api/IndexSearchApiRequest.php:11 | s | `authorize()` con permisos que no existen → authz rota (fail-closed) |
| SEC-07 | low | Authz fail-open | UpdateConversationRequest.php:11-13 | s | Condición duplicada con fallback `?? true` (autoriza si user es null) |
| SEC-08 | low | Fuga PII local | GdprExportService.php:94-145 | s | Export GDPR a `/tmp` con nombre predecible y dir 0755 |
| SEC-09 | low | Retención PII | AuditLogService.php:21-30 + GdprDeletionService | s | `ip_address` en audit_logs nunca se anonimiza tras borrado del cliente |

## Hallazgos detallados

### SEC-01 (high) — Búsqueda y listado de clientes/conversaciones sin aislamiento por inbox

**Evidencia.** El modelo de aislamiento está bien diseñado en las policies — `ConversationPolicy::canAccessInbox()` (ConversationPolicy.php:57-66) y `CustomerPolicy::sharesInboxWith()` (CustomerPolicy.php:60-78) restringen el acceso de un agente a los inboxes que tiene asignados vía `AgentInboxCapacity`. Pero esas policies **solo se invocan en las acciones unitarias** (`show`/`update`/...). Los endpoints de búsqueda y listado las saltan:

- `GlobalSearchService::paginateConversations()/searchConversations()/searchMessages()/searchCustomers()` (GlobalSearchService.php:62-128) construyen las queries sin ningún `whereIn('inbox_id', ...)`. Devuelven `customer.email`, `customer.name`, `subject`, snippet del `body` de mensajes — de **cualquier inbox**.
- `GlobalSearchController::__invoke()` (GlobalSearchController.php:14-74) — sin `authorize()` (solo el `can:helpdesk.view` del grupo en RouteServiceProvider.php:65) y sin filtro de inbox; expone `email` y `phone` del cliente (líneas 25-33).
- `CustomersController::search()` (CustomersController.php:177-199) — `authorize('viewAny', Customer::class)` (permiso global) + `Customer::query()->search($q)` sin scope de inbox → devuelve `name/email/phone` de todos los clientes.
- `CustomersController::index()` (CustomersController.php:28-50) — lista paginada de **todos** los clientes sin scope; y en la línea 57 `$selected = Customer::find($request->integer('selected'))` carga el detalle de **cualquier** cliente por id **sin** llamar a `authorize('view', $selected)` → IDOR directo que evita `sharesInboxWith()`.

**Impacto.** Un agente con el permiso base `helpdesk.view` + `helpdesk.customers.view` (que tienen todos los agentes) puede leer PII de clientes (email, teléfono) y contenido de conversaciones/mensajes de inboxes a los que **no** está asignado, vía la barra de búsqueda global, la búsqueda de clientes o el parámetro `?selected=`. Rompe por completo el aislamiento por inbox que las policies prometen. Confirma y profundiza el tema transversal INBO-01/02/03 y CUST-01/10.

**Recomendación.** Añadir scoping de inbox a todas las queries de búsqueda/listado replicando la lógica de las policies: en `GlobalSearchService` aceptar los `inboxIds` del agente (null = `helpdesk.manage`) y aplicar `->whereIn('inbox_id', $inboxIds)` a conversaciones/mensajes y `->whereHas('conversations', fn($q)=>$q->whereIn('inbox_id',$inboxIds))` a clientes. En `CustomersController::index/search` aplicar el mismo `when(! manage, ...)`. En la línea 57 sustituir `Customer::find()` por `authorize('view', $selected)` tras cargarlo. Centralizar en un scope `scopeForAgent(User)` en los modelos `Conversation`/`Customer`. Esfuerzo: m.

### SEC-02 (high) — Gestión de participantes con permiso global, sin policy por conversación

**Evidencia.** `ConversationParticipantsController` (ConversationParticipantsController.php:12-17) se protege solo con middleware de permiso **global**: `can:helpdesk.conversations.view` (index) y `can:helpdesk.conversations.update` (store/destroy). No hay ningún `$this->authorize('view'/'update', $conversation)` por modelo, pese a que `{conversation}` es route-model-bound (RouteServiceProvider.php:95-101, grupo `['web','auth']` sin rol ni `helpdesk.view`).

- `index()` (líneas 19-32) devuelve `id/firstname/lastname/email` de los participantes de **cualquier** conversación.
- `store()` (líneas 34-54) hace `syncWithoutDetaching($data['user_ids'])` validando solo `exists:users,id` → permite añadir **cualquier** usuario interno como participante de **cualquier** conversación.
- `destroy()` (líneas 56-61) hace `detach($userId)` sobre cualquier conversación.

**Impacto.** IDOR de escritura cross-inbox: un agente puede enumerar emails de agentes internos y manipular la lista de participantes (añadir/quitar) de conversaciones de inboxes ajenos. La `ConversationPolicy` existe pero aquí no se usa.

**Recomendación.** Inyectar `$this->authorize('update', $conversation)` en `store`/`destroy` y `$this->authorize('view', $conversation)` en `index`; validar que `user_ids` sean agentes con acceso al inbox de la conversación. Esfuerzo: s.

### SEC-03 (medium) — `settings` gateado solo por rol; permisos granulares muertos

**Evidencia.** `RouteServiceProvider.php:33-36` carga `routes/settings.php` con `['web','auth','role:super-admin|super-settings']`. Es el **único** gating: ninguno de los Settings controllers añade `$this->authorize()` por permiso (grep: 0 coincidencias en la mayoría). Sin embargo, los ~30 Form Requests de settings declaran permisos granulares — `helpdesk.settings.update`, `helpdesk.brands.manage`, `helpdesk.companies.manage`, `helpdesk.skills.manage`, `helpdesk.webhooks.*`, etc. (ver UpdateBusinessHoursRequest, StoreInboxRequest, StoreBrandRequest, ...). Estos permisos:

1. **No pueden delegar** nada: cualquier no-super queda bloqueado en la ruta, así que el permiso granular jamás concede acceso.
2. Son **incoherentes**: un `super-settings` que NO tenga, p.ej., `helpdesk.brands.manage` pasaría la ruta pero sería rechazado por el `authorize()` del Form Request — comportamiento dependiente de qué permisos arrastre el rol.
3. Conceden **todo o nada**: cualquier `super-settings` accede a operaciones sensibles (webhooks con secretos, integraciones, broadcasts) sin granularidad.

**Impacto.** Modelo de permisos de settings inconsistente y no delegable; privilegio efectivo = pertenencia al rol, no permiso. Dificulta el principio de mínimo privilegio.

**Recomendación.** Decidir una estrategia única: o bien sustituir el `role:` por `can:helpdesk.settings.view` a nivel de grupo y dejar que cada Form Request aplique el permiso granular (recomendado), o bien eliminar los permisos granulares muertos para no inducir a error. Asegurar que el rol `super-settings` tenga todos los `helpdesk.*.manage` que sus Form Requests exigen. Esfuerzo: m.

### SEC-04 (medium) — GDPR soft-delete deja PII sin redactar durante la ventana de 90 días

**Evidencia.** `GdprDeletionService::softDelete()` (GdprDeletionService.php:46-92) anonimiza el `Customer` (email/phone/whatsapp/psid/instagram/avatar/internal_notes/custom_attributes/portal_*) y redacta `body`/`html_body`/`attachment_urls` de los `ConversationItem`. Pero **no** toca:

- `helpdesk_csat_ratings.comment` y `helpdesk_nps_ratings.comment` (texto libre del cliente — migraciones `*_csat_ratings_table.php:19` y `*_nps_ratings_table.php:18`). Solo se borran en `hardDelete()` (líneas 117-125).
- `conversations.subject` (`create_helpdesk_conversations_table.php:16`) — en email es el asunto, frecuentemente con PII; no se redacta en soft-delete.
- `customers.ban_reason` (fillable, Customer.php:39) — texto libre, no se limpia.

**Impacto.** Tras una "anonimización" GDPR, queda PII recuperable durante 90 días (comentarios de encuestas, asuntos de email, motivo de baneo). Incumple la expectativa de borrado que el propio panel comunica ("PII del cliente anonimizada", GdprController.php:65).

**Recomendación.** En `softDelete()` añadir: redacción de `comment` en csat/nps por `customer_id`, `subject => '[Asunto eliminado]'` en las conversaciones del cliente, y `ban_reason => null`. Esfuerzo: m.

### SEC-05 (medium) — Regex de routing sin validar → ReDoS en el pipeline de entrada

**Evidencia.** `RoutingRule::matches()` (RoutingRule.php:45) ejecuta `(bool) @preg_match($this->keyword, $text)` usando como patrón completo el `keyword` configurado, contra el `body` del mensaje entrante (RoutingRuleService.php:33). `StoreRoutingRuleRequest` (StoreRoutingRuleRequest.php:18) valida `keyword` solo como `string|max:255` — sin comprobar que, cuando `match_type=regex`, sea un patrón válido y sin backtracking catastrófico.

**Impacto.** Un usuario con acceso a settings (solo gateado por rol, ver SEC-03) puede guardar un patrón tipo `/(a+)+$/` que se evalúa contra **cada** mensaje entrante → bloqueo del worker / DoS de la cola de entrada. El `@` además silencia patrones inválidos, ocultando el fallo.

**Recomendación.** En el Form Request validar `match_type=regex` con una regla `closure` que haga `@preg_match($value, '') !== false` (patrón válido) y rechazar metacaracteres de riesgo / limitar longitud; idealmente ejecutar `matches()` con `preg_match` bajo `pcre.backtrack_limit` reducido y tratar el timeout como no-match. Esfuerzo: s.

### SEC-06 (medium) — `authorize()` con permisos inexistentes → autorización rota

**Evidencia.** Tres Form Requests referencian permisos que **no existen** en `PermissionsSeeder.php` (verificado por grep):

- `SearchCannedRepliesRequest.php:11` → `helpdesk.cannedreplies.view` (el real es `helpdesk.canned-replies.view`, con guion).
- `UploadAttachmentRequest.php:11` → `manager.helpdesk.conversations.update` (prefijo `manager.` espurio; fuera de la convención `{alias}.{action}`).
- `Api/IndexSearchApiRequest.php:11` → `helpdesk.tickets.view` (permiso de otro módulo, ausente del core).

Como `can()` sobre un permiso no registrado devuelve `false`, estos endpoints quedan **denegados para todos** salvo super-admin (que pasa vía `Gate::before`). Fallan cerrados (no es escalada), pero rompen funcionalidad y enmascaran la intención de authz.

**Impacto.** Búsqueda de canned replies, subida de adjuntos y búsqueda API quedan inutilizables para agentes normales; el bug es silencioso.

**Recomendación.** Corregir a `helpdesk.canned-replies.view` y `helpdesk.conversations.update`; para la API de búsqueda usar un permiso del core (`helpdesk.conversations.view`) o declarar el permiso si la dependencia con Tickets es intencional. Esfuerzo: s.

### SEC-07 (low) — Condición duplicada con fallback `?? true` en `UpdateConversationRequest`

**Evidencia.** `UpdateConversationRequest.php:11-13`:
```php
return $this->user()?->can('helpdesk.conversations.update')
    ?? $this->user()?->can('helpdesk.conversations.update')
    ?? true;
```
La condición se repite y el fallback final es `?? true`: si `$this->user()` es null, devuelve **true** (autoriza). Mitigado porque la ruta exige `auth`, por lo que `user()` no será null en la práctica.

**Impacto.** Patrón fail-open frágil; ante un cambio futuro de middleware quedaría como bypass de autorización. Además duplica la comprobación sin sentido.

**Recomendación.** Reemplazar por `return $this->user()?->can('helpdesk.conversations.update') ?? false;`. Esfuerzo: s.

### SEC-08 (low) — Export GDPR a `/tmp` con nombre predecible

**Evidencia.** `GdprExportService::exportToZip()` (GdprExportService.php:94-145) escribe el JSON con toda la PII y los adjuntos en `sys_get_temp_dir().'/gdpr_export_'.$id.'_'.time()` (dir creado con `0755`, línea 95) y el ZIP final en `sys_get_temp_dir().'/gdpr_customer_'.$id.'.zip'` (línea 125, **nombre predecible sin aleatoriedad**). El borrado del ZIP depende de `deleteFileAfterSend(true)` (GdprController.php:42).

**Impacto.** En hosting compartido, otro usuario local podría leer el export (PII completa) durante la ventana entre creación y envío, al ser la ruta predecible y el directorio legible por terceros.

**Recomendación.** Usar un sufijo aleatorio (`Str::random()`) en el nombre del ZIP y `chmod(0700)` en el dir temporal; o usar `Storage::disk('local')` en una carpeta privada y limpiar en `finally`. Esfuerzo: s.

### SEC-09 (low) — `ip_address` en audit_logs nunca se anonimiza tras el borrado del cliente

**Evidencia.** `AuditLogService::log()` (AuditLogService.php:21-30) persiste `ip_address` y `user_agent`. `GdprDeletionService` no purga ni anonimiza los `AuditLog` del cliente (ni en soft ni en hard delete); `GdprExportService` los **exporta** (GdprExportService.php:43-48) pero quedan retenidos indefinidamente con la IP (PII).

**Impacto.** Retención de PII (IP) más allá del borrado del cliente. La retención del rastro de auditoría puede tener base legal, pero la IP en claro es discutible.

**Recomendación.** Definir política de retención del audit log y, en hard delete, anonimizar `ip_address` de las entradas del cliente borrado (o truncar la IP). Esfuerzo: s.

## Plan de ataque priorizado

1. **SEC-01 + SEC-02 (high, IDOR/PII):** añadir scoping por inbox a búsqueda/listado y `authorize()` por modelo en participantes y en el `?selected=`. Es el riesgo de confidencialidad real y confirma el tema transversal. Crear un `scopeForAgent()` reutilizable.
2. **SEC-06 (medium, fix rápido):** corregir los 3 permisos inexistentes — desbloquea funcionalidad y limpia la authz.
3. **SEC-04 (medium, GDPR):** completar la redacción del soft-delete (csat/nps comment, subject, ban_reason) antes de confiar en el mensaje "anonimizado".
4. **SEC-03 (medium, diseño):** unificar el gating de settings (permiso granular vs rol).
5. **SEC-05 (medium):** validar el regex de routing.
6. **SEC-07/08/09 (low):** endurecer el fallback de authorize, el temp de export y la retención de IP.

## Quick wins

- SEC-06: cambiar `helpdesk.cannedreplies.view` → `helpdesk.canned-replies.view` y `manager.helpdesk.conversations.update` → `helpdesk.conversations.update`.
- SEC-07: reemplazar el `?? true` por `?? false` en UpdateConversationRequest.php:13.
- SEC-01 (parcial): en CustomersController.php:57 sustituir `Customer::find(...)` por carga + `authorize('view', $selected)`.
- SEC-08: añadir `Str::random()` al nombre del ZIP de export.

## Fortalezas

- **Policies con aislamiento por inbox bien diseñadas** (ConversationPolicy.php:57-66, CustomerPolicy.php:60-78) y **realmente invocadas** en las acciones unitarias de `ConversationsController`/`CustomersController` (decenas de `$this->authorize('view'/'update', $model)`), incluido `BulkConversationsController.php:36` que valida `can('update', $conversation)` por cada id.
- **117/117 Form Requests con `authorize()`**; los `return true` se limitan a endpoints públicos legítimos (webhooks con `signatureIsValid()`, portal con sesión, 2FA, CSAT/encuestas públicas, simulador con flag).
- **Webhooks con firma HMAC** validada en `authorize()` (FacebookWebhookRequest/InstagramWebhookRequest/WhatsAppWebhookRequest) y **secreto cifrado** en reposo (Webhook.php:47-51, `$hidden`).
- **2FA robusto:** secreto y recovery codes cifrados con `Crypt`, recovery codes hasheados con `Hash::make` y consumidos de un solo uso (TwoFactorService.php:26-32,124-154); middleware `Require2FA` auto-exime sus propias rutas.
- **Sin XSS en el hilo:** el render del thread usa el accessor `getBodyHtmlAttribute()` que escapa el `body` plano con `nl2br(e())` y construye los enlaces con `e()` (ConversationItem.php:368-395); el `html_body` crudo solo se muestra en un iframe `sandbox="allow-same-origin"` (sin `allow-scripts`) y el help-center usa `clean()` (HTMLPurifier). Los `html_body` salientes se generan con `nl2br(e())`.
- **GDPR operativo de verdad:** servicio de borrado con cascada por evento `CustomerGdprDeleted` (listener en HelpdeskCompliance), captura de `conversationIds` antes del hard-delete, y comando `helpdesk:purge-old-gdpr-deletes` **realmente programado** a las 04:00 con `onOneServer()` (HelpdeskServiceProvider.php:341-343), cumpliendo la promesa de los 90 días.
- **AuditLogService** registra acciones GDPR (export/delete) con usuario/IP y nunca rompe el flujo principal.

## Cobertura de la auditoría

- **Policies (9/9):** Conversation, Customer, ConversationStatus, ConversationTag, ConversationView, Group, Webhook, CustomAttribute, CannedReply — leídas; registro verificado en HelpdeskServiceProvider.php:349-368. Conclusión: solo Conversation y Customer aplican ownership/inbox; el resto son chequeos de permiso plano (aceptable para entidades de configuración global).
- **Form Requests (117/117):** extraídos y analizados todos los cuerpos de `authorize()` por script; muestreo en profundidad de los representativos. Verificación cruzada de cada permiso contra `PermissionsSeeder.php`.
- **Middleware (2/2):** Require2FA y PortalAuth leídos completos.
- **Compliance:** GdprDeletionService, GdprExportService, TwoFactorService, PiiMaskingService, AuditLogService, GdprController, comando PurgeOldGdprDeletes — leídos completos.
- **Controllers Managers:** mapa completo de `authorize()`; lectura profunda de los que carecen de chequeo por modelo (GlobalSearch, Search+GlobalSearchService, ConversationParticipants, BulkConversations, CannedReplies, ConversationViews, CustomersController).
- **Routing/authz a nivel de provider:** RouteServiceProvider.php (grupos managers/settings/inbox/portal/api) y routes/managers.php revisados para el gating efectivo.
- **Tema transversal IDOR/inbox:** confirmado en SEC-01 (search/index/selected) y SEC-02 (participantes). **ReDoS** confirmado en SEC-05 (RoutingRule). No se evaluó el prompt-injection de IA (fuera de este subsistema; pertenece al de AI/automatización).
- **No ejecutado:** ningún test (BD de test bloqueada); todo el análisis es estático (Read/Grep/Glob/Bash).
