# Auditoría core Helpdesk — Inbox & Conversaciones

> Fecha: 2026-06-29 · Health score: 61/100 · Estado: needs-work

**Resumen:** Inbox funcional y bien instrumentado (eager loading, caché, throttling, policies) socavado por un controlador-dios de 2762 líneas, varias brechas de cableado (URL de sugerencia IA rota, feature de side-conversations muerta, cierre masivo que nunca cambia el estado), agujeros de scoping de autorización por conversación, y un bug silencioso de corrección del primer tiempo de respuesta del SLA. El listado principal del inbox sí está protegido contra N+1 y el thread es seguro frente a stored-XSS; el riesgo se concentra en consistencia de datos (cierre masivo, merge) y en aislamiento por inbox (react, delete, participants).

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| INBO-06 | high | wiring | BulkConversationsController.php:53-66 | [CONFIRMADO] | M | Cierre/reapertura masivos nunca cambian status_id |
| INBO-01 | medium | security | ConversationItemsController.php:17-51 | [CONFIRMADO] | S | Falta autorización en react() (IDOR cross-inbox) |
| INBO-02 | medium | security | ConversationPolicy.php:39-42 | [CONFIRMADO] | S | delete() sin scoping por inbox |
| INBO-03 | medium | security | ConversationParticipantsController.php:34-61 | [CONFIRMADO] | S | Participants add/remove sin authz por conversación + validación inline |
| INBO-04 | medium | wiring | ai-suggest.blade.php:79 | [CONFIRMADO] | S | Modal IA llama ruta inexistente; controlador es stub |
| INBO-05 | medium | wiring | SideConversationService.php:1-189 | [CONFIRMADO] | M | Side Conversations es código muerto |
| INBO-07 | medium | performance | ConversationsController.php:1542-1545 | [CONFIRMADO] | M | downloadAttachment usa LIKE wildcard sobre columna JSON |
| INBO-08 | medium | performance | ConversationFilter.php:107-115 | [CONFIRMADO] | M | Búsqueda inbox usa LIKE leading-wildcard no indexado |
| INBO-11 | medium | quality | ConversationMessageService.php:214-221 | [CONFIRMADO] | S | Notas internas fijan first_response_at (corrupción SLA) |
| INBO-12 | medium | quality | ConversationsController.php:1221-1224 | [CONFIRMADO] | M | merge() solo reasigna items — tags/drafts/reads/participants huérfanos |
| INBO-09 | low | performance | ConversationsController.php:1181-1197 | [CONFIRMADO] | S | N+1 en mergeCandidates() |
| INBO-10 | low | performance | ConversationsController.php:310-324 | [CONFIRMADO] | S | Contadores sidebar: un COUNT por inbox |
| INBO-13 | low | quality | ConversationMessagesController.php:47-198 | [CONFIRMADO] | M | Dos implementaciones divergentes de envío + service no transaccional |
| INBO-14 | low | conventions | ConversationsController.php:1598-1681 | [CONFIRMADO] | M | DB::table() crudo para pin/mute/reads |
| INBO-15 | low | conventions | ConversationMessagesController.php:32-320 | [CONFIRMADO] | S | Sin return types + role check para delete |

## Hallazgos detallados

### INBO-06 · [CONFIRMADO · high] Cierre/reapertura masivos nunca cambian status_id

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/BulkConversationsController.php:53-66`
- **Evidencia:** `'close' => Conversation::whereIn('id',$ids)->whereNull('closed_at')->update(['closed_at'=>now()])`; `'reopen'` solo limpia `closed_at`. El cierre individual `Conversation::close()`/`reopen()` (Conversation.php:422-447) actualiza tanto `status_id` como `closed_at`. `scopeOpen`/`scopeClosed` usan `whereHas('status', is_open)`.
- **Impacto:** Cerrar en masa fija `closed_at` pero deja un estado ABIERTO, así que la conversación sigue apareciendo en filtros de estado, contadores y scopes de SLA. El cierre masivo además omite el evento `ConversationClosed` + dispatch de CSAT, y el assign masivo omite la notificación `ConversationAssigned` que dispara `Conversation::assignTo()`. Verificado en tres superficies: filtro por status_id (ConversationFilter.php:73-80), filtro is_open (ConversationFilter.php:66 / ConversationsController.php:278), y observer de HelpdeskSla (que comprueba `wasChanged('closed_at')` y nunca se dispara con `whereIn()->update()`). Mitigaciones parciales que evitan severidad crítica: DashboardController:22 y ConversationsController:240 usan `closed_at IS NULL` directo (count abierto correcto); ConversationResource usa `closed_at !== null` (API correcta).
- **Recomendación:** Resolver los `ConversationStatus` open/closed y actualizar `status_id` junto con `closed_at` (reusar `Conversation::close()/reopen()` por id dentro de la transacción, o fijar `status_id` explícito); disparar `ConversationClosed`/notificaciones de assign para paridad con las acciones individuales.

### INBO-01 · [CONFIRMADO · medium] Falta autorización en react() (IDOR cross-inbox)

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationItemsController.php:17-51`
- **Evidencia:** `react()` no tiene `$this->authorize()`/`assertItemAccess()`; la ruta `/conversation-items/{item}/react` (managers.php:231) solo añade throttle. El middleware de grupo es solo `['web','auth','can:helpdesk.view']`. Existe gemelo protegido: `/messages/{item}/react` → `reactToMessage()` que llama `assertItemAccess()`.
- **Impacto:** Cualquier usuario con `helpdesk.view` puede añadir/quitar metadata de reacción (y leer quién reaccionó) en CUALQUIER item de CUALQUIER inbox, saltándose el scoping AgentInboxCapacity aplicado en el resto.
- **Recomendación:** Añadir `$this->authorize('view', $item->conversation)` (o `assertItemAccess`) en `react()`; idealmente eliminar este endpoint y dejar solo el protegido `reactToMessage`.

### INBO-02 · [CONFIRMADO · medium] ConversationPolicy::delete() sin scoping por inbox

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Policies/ConversationPolicy.php:39-42`
- **Evidencia:** `delete()` retorna solo `hasPermissionTo('helpdesk.conversations.delete')` sin `canAccessInbox()`, a diferencia de `view()`/`update()` que ambas terminan con `canAccessInbox()`. `destroy`/`blockContact` usan `authorize('delete', conversation)`.
- **Impacto:** Un agente restringido a inboxes concretos pero con permiso de delete puede soft-deletear (y vía blockContact banear al cliente) conversaciones en inboxes que no puede ver, derrotando el modelo de aislamiento por inbox.
- **Recomendación:** Añadir `&& $this->canAccessInbox($user, $conversation)` a `delete()` (y revisar el uso de la habilidad delete en `blockContact`).

### INBO-03 · [CONFIRMADO · medium] Participants add/remove sin authz por conversación + validación inline

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationParticipantsController.php:34-61`
- **Evidencia:** `store()`/`destroy()` dependen solo del middleware de constructor `can:helpdesk.conversations.update` (permiso general, sin modelo); nunca llaman `$this->authorize('update', $conversation)`, así que `canAccessInbox()` nunca se evalúa. `store()` usa además `$request->validate([...])` inline en lugar de un Form Request.
- **Impacto:** Cualquier agente con el permiso general puede añadir o quitar observadores en conversaciones de inboxes a los que no está asignado (manipulación cross-inbox / exposición de la lista de agentes). La validación inline viola la convención de Form Request del proyecto.
- **Recomendación:** Llamar `$this->authorize('update', $conversation)` al inicio de `store()`/`destroy()`; extraer la validación a `StoreParticipantsRequest` con `authorize()` + mensajes en español.

### INBO-04 · [CONFIRMADO · medium] Modal IA llama ruta inexistente; controlador es stub

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/resources/views/helpdesk/inbox/partials/modals/ai-suggest.blade.php:79`
- **Evidencia:** El modal hace POST a `/panel/helpdesk/conversations/{id}/ai/suggestions`. Las rutas solo definen `ai/suggest-replies` (AiController::suggestReplies, real) y `ai-suggestions` (ConversationsController::aiSuggestions). Ninguna coincide con `/ai/suggestions` → 404, el modal siempre muestra error. `ConversationsController::aiSuggestions()` (línea 1771) retorna 3 strings en español hardcodeados con un comentario `TODO: integrar OpenAI/Claude` y es inalcanzable desde la UI.
- **Impacto:** El botón "sugerir respuesta IA" está roto permanentemente; queda un endpoint stub muerto cableado y con throttle, ocultando que la feature nunca llega al verdadero servicio AiController.
- **Recomendación:** Reapuntar el AJAX del modal a `route('manager.helpdesk.conversations.ai.suggest-replies')` y alinear la forma de respuesta; eliminar `aiSuggestions()` y su ruta `/ai-suggestions`.

### INBO-05 · [CONFIRMADO · medium] Side Conversations es código muerto

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/SideConversationService.php:1-189`
- **Evidencia:** Grep en todos los módulos encuentra `SideConversationService`/`handleIncomingReply`/`reply_token` referenciados solo por el propio servicio, el modelo SideConversation y la migración de creación de tabla — sin controlador, ruta, listener ni matcher de email entrante. El "called by the IMAP inbound handler" del docblock no tiene implementación en `src/modules`.
- **Impacto:** `createWithTeam`/`createWithExternal`/`handleIncomingReply` (incluyendo el threading por reply-token y las notificaciones SideConversation*) nunca pueden ejecutarse; superficie de capacidad que parece entregada pero está inerte.
- **Recomendación:** O bien cablearlo (controlador + rutas + matcher de reply-token en el pipeline de email) o eliminar el servicio/modelo/migración/notificaciones para recortar superficie muerta.

### INBO-07 · [CONFIRMADO · medium] downloadAttachment usa LIKE wildcard sobre columna JSON (full scan)

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1542-1545`
- **Evidencia:** `ConversationItem::query()->where('attachment_urls','like','%'.$relPath.'%')->with('conversation')->first()`; `attachment_urls` es columna JSON/text no indexada y el `%` inicial fuerza un scan secuencial de `helpdesk_conversation_items` en cada descarga.
- **Impacto:** Cada descarga de adjunto (lightbox + burbuja "descargar") escanea la tabla completa de items; el coste crece linealmente con el volumen de mensajes y está en una ruta caliente de cara al usuario.
- **Recomendación:** Persistir adjuntos en estructura normalizada/indexada (o columna índice de path) y buscar por path exacto, o acotar la búsqueda por `conversation_id` derivado del path de storage (`helpdesk/customers/{id}/conversations/{id}/...`).

### INBO-08 · [CONFIRMADO · medium] Búsqueda inbox usa LIKE leading-wildcard no indexado

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Filters/ConversationFilter.php:107-115`
- **Evidencia:** `applySearch()`: `where('subject','like','%'.$search.'%')->orWhereHas('customer', name like '%term%')`; `Conversation::scopeSearch()` (Conversation.php:357-363) duplica el patrón. Además `applyBotVisibility` salta el filtro `withoutActiveBot` cuando hay búsqueda, ampliando el conjunto escaneado.
- **Impacto:** Los wildcards iniciales hacen ambos predicados no-sargables; en tablas grandes de `helpdesk_conversations`/`customers` la búsqueda del inbox degrada a full scans (el bypass de bot agranda más el conjunto candidato).
- **Recomendación:** Adoptar índices FULLTEXT o el Laravel Scout ya instalado para búsqueda de conversación/cliente; al mínimo prefix-match donde la UX lo permita.

### INBO-11 · [CONFIRMADO · medium] Notas internas fijan first_response_at (corrupción SLA)

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/ConversationMessageService.php:214-221`
- **Evidencia:** `updateConversationTimestamps()` fija `first_response_at=now()` cuando está vacío, para CUALQUIER tipo de mensaje — y se llama incondicionalmente tras `store()` (la ruta del composer principal del inbox vía `ConversationsController::storeMessage`). El paralelo `ConversationMessagesController::store()` (línea 99) sí protege con `! $item->is_internal`.
- **Impacto:** Un agente que publica una nota interna como primer item marca silenciosamente el reloj de primer-respuesta del SLA como satisfecho, falseando métricas/reportes alimentados desde `first_response_at`.
- **Recomendación:** Fijar `first_response_at` solo cuando el item es no-interno (e idealmente respuesta de agente), espejando `ConversationMessagesController`; considerar centralizar en un único lugar.

### INBO-12 · [CONFIRMADO · medium] merge() solo reasigna items — tags/drafts/reads/participants huérfanos

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1221-1224`
- **Evidencia:** `DB::transaction(fn() => { $conversation->items()->update(['conversation_id'=>$target->id]); $conversation->delete(); })`; sin manejo de `helpdesk_conversation_tag_pivot`, drafts, reads, participants, skills ni side_conversations; los contadores agregados del cliente no se recalculan. `MergeTest` solo asegura items copiados + source soft-deleteado.
- **Impacto:** Fusionar pierde tags/participants/drafts de la conversación origen y deja read-state por usuario y side conversations apuntando a un padre soft-deleteado; datos desaparecen silenciosamente del thread fusionado.
- **Recomendación:** Dentro de la transacción, reasignar o fusionar todos los registros relacionados (tags vía `syncWithoutDetaching`, participants, drafts, reads, skills, side conversations) y recalcular contadores del cliente; ampliar `MergeTest` para cubrirlos.

### INBO-09 · [CONFIRMADO · low] N+1 en mergeCandidates()

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1181-1197`
- **Evidencia:** La query carga `['customer','status']` y luego `map()` llama `$c->getLatestMessage()?->body`; `lastMessage` no está eager-loaded, así que `getLatestMessage()` cae a la rama de query por fila (Conversation.php:504-516) — una query extra por cada uno de hasta 10 candidatos.
- **Impacto:** Hasta ~10 queries extra cada vez que se abre el selector de merge; menor pero evitable.
- **Recomendación:** Añadir `->with('lastMessage')` a la query de candidatos para que `getLatestMessage()` use la relación cargada.

### INBO-10 · [CONFIRMADO · low] Contadores sidebar: un COUNT por inbox

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:310-324`
- **Evidencia:** `$inboxes->map()` ejecuta `Conversation::where('inbox_id',$inbox->id)->count()` dentro del loop (una query por inbox). Cacheado 60s, pero el coste en frío escala con el número de inboxes activos.
- **Impacto:** El render en frío del inbox dispara N count queries (N = inboxes activos); insignificante para pocos inboxes, crece linealmente.
- **Recomendación:** Reemplazar por un único count agrupado: `Conversation::selectRaw('inbox_id, COUNT(*) c')->groupBy('inbox_id')` y mapear sobre la lista de inboxes.

### INBO-13 · [CONFIRMADO · low] Dos implementaciones divergentes de envío + service no transaccional

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationMessagesController.php:47-198`
- **Evidencia:** `ConversationMessagesController::store()` inline crea item, almacena adjuntos, link-preview y entrega outbound; `ConversationsController::storeMessage()` hace lo mismo vía `ConversationMessageService`. Divergen (p. ej. guard de `first_response_at` INBO-11; re-fetch duplicado de link_preview). `ConversationMessageService::store()` hace create + update(s) + close() sin envoltura `DB::transaction` pese a múltiples escrituras.
- **Impacto:** Drift de comportamiento entre las dos rutas de envío (ya manifestado como el bug de SLA) y riesgo de escritura parcial en el service ante fallo.
- **Recomendación:** Consolidar ambos controladores sobre `ConversationMessageService` y envolver su bloque multi-escritura en `DB::transaction()`.

### INBO-14 · [CONFIRMADO · low] DB::table() crudo para pin/mute/reads

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1598-1681`
- **Evidencia:** `togglePin()`/`toggleMute()` usan `DB::connection('helpdesk')->table('helpdesk_user_conversation_meta')->insert/update`; `BulkConversationsController::bulkMarkRead/Unread` y `Conversation::unreadCountForInbox()` igualmente atacan `helpdesk_conversation_reads` vía query builder. No existe modelo `UserConversationMeta`.
- **Impacto:** Salta Eloquent (casts, timestamps, eventos) y viola models.md ("avoid DB::; prefer Model::query()"); literales de tabla/conexión duplicados son propensos a error.
- **Recomendación:** Introducir modelos Eloquent `UserConversationMeta` y `ConversationRead` y usar `updateOrCreate`/relaciones.

### INBO-15 · [CONFIRMADO · low] Sin return types + role check para delete

- **Archivo:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationMessagesController.php:32-320`
- **Evidencia:** `index()`/`store()`/`markAsRead()`/`markConversationRead()`/`broadcastTyping()`/`destroy()`/`getCannedReplies()` no declaran return types (regla del proyecto: declaraciones explícitas). `destroy()` (línea 313) restringe el borrado con `auth()->user()->hasRole('admin')` en lugar de permiso Spatie/policy.
- **Impacto:** Drift de convención; el gating por string de rol es menos flexible/auditable que el esquema de permisos usado en otros lados y puede divergir del modelo `conversations.*`.
- **Recomendación:** Añadir `: JsonResponse` y reemplazar la rama `hasRole('admin')` por una comprobación de permiso/policy (p. ej. `helpdesk.conversations.manage`).

## Plan de ataque priorizado

1. **INBO-06 (high, M)** — Arreglar cierre/reapertura masivos para que fijen `status_id` y disparen eventos de cierre/CSAT + notificaciones de assign. Es el único hallazgo que deja estado inconsistente en todo filtro por estado y ruta de SLA del flujo diario.
2. **INBO-04 (medium, S)** — Reparar la feature de sugerencia IA: el modal llama una URL inexistente y el controlador cableado es un stub hardcodeado.
3. **INBO-01 / INBO-02 / INBO-03 (medium, S)** — Cerrar agujeros de scoping de autorización: authz en `react()`, scope por inbox en `delete()`, y authz por conversación en participants add/remove.
4. **INBO-11 (medium, S)** — Proteger `updateConversationTimestamps()` con `!is_internal` para que las notas internas dejen de fijar `first_response_at`.
5. **INBO-12 (medium, M)** — Hacer que `merge()` reasigne/fusione todos los registros relacionados, no solo items.
6. **INBO-07 / INBO-08 (medium, M)** — Eliminar los LIKE leading-wildcard de descargas y búsqueda (índice/Scout/FULLTEXT).
7. **INBO-05 (medium, M)** — Decidir: cablear o eliminar Side Conversations.
8. **Lows (INBO-09, 10, 13, 14, 15)** — Limpieza de N+1, contadores agrupados, consolidación de envío, modelos Eloquent y return types.

## Quick wins

- Añadir `$this->authorize('view', $item->conversation)` (o `assertItemAccess`) en `ConversationItemsController::react()` y deduplicar con `reactToMessage` (INBO-01).
- Apuntar `ai-suggest.blade.php` a la ruta real (`ai/suggest-replies`) y borrar el stub `aiSuggestions()` + su ruta `/ai-suggestions` (INBO-04).
- Eager-load `lastMessage` en `mergeCandidates()` para eliminar el N+1 de 10 queries (INBO-09).
- Proteger `ConversationMessageService::updateConversationTimestamps()` con `!is_internal` (INBO-11).
- Añadir `canAccessInbox()` a `ConversationPolicy::delete()` para cerrar la brecha de delete cross-inbox (INBO-02).

## Fortalezas

- El N+1 del listado principal del inbox está genuinamente mitigado: `lastMessage` HasOne vía `ofMany`, reads con eager-load acotado, y `withCount('incoming_messages_count')` alimentan `toInboxArray()`/`unreadCountForInbox()` (cubierto por `ConversationListNPlusOneTest`).
- Seguro frente a stored-XSS en el thread: el accessor `body_html` re-escapa el body plano con `e()` antes de linkify/mention-chip, y el preview de conv-item está pre-escapado en `toInboxArray()` — sin iconos Tabler.
- `ConversationPolicy` aplica scoping por inbox (AgentInboxCapacity) para view/update, y el controlador-dios añade `getUserInboxIds()`/`assertItemAccess()` para frenar acceso cross-inbox vía item ids en react/forward/info; `downloadAttachment` autoriza `view` contra la conversación dueña.
- Footprint de tests razonable para el subsistema (Merge, CloseReopenArchive, BulkActions, StoreMessage, Snooze, PinMuteBlock, N+1, más tests de modelo/unit).

## Cobertura de la auditoría

Leído completo: `ConversationsController` (2762 líneas), controladores Bulk/ConversationMessages/ConversationItems/ConversationParticipants/ConversationViews, modelos `Conversation` y `ConversationItem`, servicios ConversationMessageService/ConversationTagService/MentionParser/SideConversationService, `ConversationPolicy`, `ConversationFilter`, rutas (managers.php, api.php, RouteServiceProvider), y los blades del inbox escaneados por XSS/`{!! !!}`/Tabler/inline-style/select2. Verificado el cableado ruta↔método y el uso de URLs en frontend vía grep.

No leído en profundidad: cada partial del panel derecho/thread línea a línea, internals de conversations.js/.css, Services/Conversations/ActivityMessageService, y ejecución de tests con DB (test DB bloqueada — solo estático).

## Descartados en verificación

Ninguno. Todos los hallazgos reportados fueron confirmados en verificación (no hubo hallazgos refutados). INBO-06 fue confirmado explícitamente manteniendo severidad high.
