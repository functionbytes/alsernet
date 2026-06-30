# Auditoría — HelpdeskTickets

> Fecha: 2026-06-29 · Health score: 72/100 · Estado: solid-minor-issues

**Resumen:** Módulo de ticketing grande y bien probado, con fundamentos sólidos de seguridad, pero debilitado por lógica de ciclo de vida duplicada entre `Ticket::booted()` y `TicketObserver` que escribe doblemente el historial de auditoría, bugs de métricas por diffs con signo de Carbon 3, y accessors perezosos propensos a N+1. Diagnóstico: la base es robusta (cobertura de tests amplia, sanitización HTML, ownership del portal, agregados cacheados y parametrizados), y los problemas son de calidad/consistencia más que de exposición crítica. La prioridad real es consolidar el manejo del ciclo de vida del ticket antes de que la deuda de duplicación contamine reportes y SLA.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HT-01 | high | quality | app/Models/Ticket.php:125-211 | [AJUSTADO→high] | M | Lógica de ciclo de vida duplicada entre `Ticket::booted()` y `TicketObserver` |
| HT-02 | medium | quality | app/Models/Ticket.php:822,834,844 | [CONFIRMADO] | S | `diffInMinutes` con signo de Carbon 3 produce métricas negativas |
| HT-03 | medium | performance | app/Models/Ticket.php:248-280 | [CONFIRMADO] | M | Accessors status/category auto-cargan y disparan N+1 |
| HT-04 | medium | quality | app/Services/TicketUpdateService.php:16-93 | [CONFIRMADO] | S | `applyChanges` hace múltiples writes sin transacción |
| HT-05 | medium | conventions | app/Http/Controllers/Portal/CustomerPortalController.php:52,164,218,269,296 | [CONFIRMADO] | M | Form Requests del Portal existen pero se usa `$request->validate()` inline |
| HT-06 | medium | wiring | app/Services/HelpdeskTicketBridgeService.php:186-194 | [CONFIRMADO] | M | Join cross-conexión en dashboard falla con BD helpdesk separada |
| HT-07 | medium | quality | app/Models/Ticket.php:217-238 | [CONFIRMADO] | S | Race en generación de número: `lockForUpdate` fuera de transacción |
| HT-08 | low | security | app/Services/TicketCategoryValidationBuilder.php:44 | [CONFIRMADO] | S | Adjuntos públicos y de email entrante aceptan cualquier MIME |
| HT-09 | low | security | app/Jobs/Helpdesks/FetchTicketEmailsJob.php:184-189 | [CONFIRMADO] | S | Threading por número en subject sin verificar remitente |
| HT-10 | low | security | app/Http/Requests/StoreTicketRequest.php:33 | [CONFIRMADO] | S | `assignee_id` aceptado sin validación `exists` |
| HT-11 | low | wiring | app/Http/Controllers/Managers/BulkTicketsController.php:44-61 | [CONFIRMADO] | M | Bulk close/reopen solo cambia `closed_at` y omite status + eventos |
| HT-12 | low | quality | app/Http/Controllers/Managers/TicketsCrudController.php:314-353 | [CONFIRMADO] | S | Método huérfano `TicketsCrudController::details()` |
| HT-13 | low | quality | app/Http/Requests/BaseTicketRequest.php:9-35 | [CONFIRMADO] | S | Código JSON:API muerto en `BaseTicketRequest` |
| HT-14 | low | quality | app/Services/TicketService.php:12 | [CONFIRMADO] | M | Modelos SLA duplicados (`SlaPolicy` vs `TicketSlaPolicy`) |
| HT-15 | low | ux | resources/views/managers/ticket-categories/index.blade.php:242 | [CONFIRMADO] | S | Clase base Tabler `ti` + inline style en lista de categorías |
| HT-16 | low | conventions | resources/views/managers/tickets/show.blade.php:6 | [CONFIRMADO] | M | `style=` inline en blades de manager/agent (no email) |
| HT-17 | low | conventions | app/Jobs/CheckSlaBreaches.php:1-40 | [CONFIRMADO] | S | Varios jobs omiten la propiedad `$backoff` |
| HT-18 | low | security | app/Http/Controllers/Managers/TicketMessagingController.php:140-143 | [CONFIRMADO] | S | `smartReplies` sin autorización por recurso |

## Hallazgos detallados

### HIGH

#### HT-01 · [AJUSTADO→high] Lógica de ciclo de vida duplicada entre `Ticket::booted()` y `TicketObserver`
- **Archivo:** `modules/HelpdeskTickets/app/Models/Ticket.php:125-211` (+ `TicketObserver.php`, `HelpdeskTicketsEventServiceProvider.php:64`)
- **Evidencia:** Confirmado real, aunque el mecanismo exacto para cambios de estado difiere de la descripción original. Existen tres duplicaciones genuinas:
  1. **Campos genéricos logueados DOS VECES**: el closure `updated` de `Ticket::booted()` (Ticket.php:200-208, rama `else` que llama `logFieldChange`) y `TicketObserver::updated` (TicketObserver.php:49-61), cuya skip-list solo excluye timestamps/`status_id`/`assignee_id`, dejando todos los demás campos (priority, subject, category_id, etc.) doblemente registrados.
  2. **`calculateSlaDueDates()` invocado DOS VECES en creación** con política SLA: el closure `created` de `booted()` (Ticket.php:144) y `TicketObserver::created` (TicketObserver.php:28) disparan ambos para el mismo `Ticket::create()`.
  3. **Cambios de estado generan dos filas `TicketHistory`**: `booted() updated` ejecuta `logStatusChange` sincrónicamente (Ticket.php:181), y luego `TicketUpdateService::applyChanges` (línea 41) hace `broadcast(new TicketStatusChanged(...))` que dispara el listener encolado `RecordTicketHistory` (HelpdeskTicketsEventServiceProvider.php:64), escribiendo una segunda fila con `action='status_changed'`.
  - **Corrección al hallazgo original:** la afirmación de que el observer también loguea status es INCORRECTA — `TicketObserver::updated` salta `status_id` explícitamente (línea 47, con comentario). El doble `cache-forget` en `saved`/`deleted` se confirma pero es benigno. La duplicación `creating`/`creating` es idempotente (ambas comprueban `if (!$ticket->ticket_number)`) y no es un bug real.
- **Impacto:** Rastro de auditoría contaminado en cada guardado de campo genérico; doble escritura de SLA en creación; filas de historial de estado duplicadas cuando se procesa vía `TicketUpdateService`. Historial confuso para agentes y reportes poco fiables.
- **Recomendación:** Elegir UN solo mecanismo. Mover todo el manejo de ciclo de vida a `TicketObserver` y eliminar los closures de `Ticket::booted()` (conservar solo `generateTicketNumber` si hace falta). Garantizar que el historial de status/assignee se produzca una sola vez (observer o listener `RecordTicketHistory`, no ambos), y que `calculateSlaDueDates()` se llame una sola vez.
- **Severidad:** HIGH confirmada tras verificación (esfuerzo: M).

### MEDIUM

#### HT-02 · [CONFIRMADO] `diffInMinutes` con signo de Carbon 3 produce métricas negativas
- **Archivo:** `modules/HelpdeskTickets/app/Models/Ticket.php:822,834,844`
- **Evidencia:** `getTimeToFirstResponse()`: `$this->first_response_at->diffInMinutes($this->created_at)`; `getDuration()`: `$end->diffInMinutes($this->created_at)`; `getTimeToResolution()`: `$this->resolved_at->diffInMinutes($this->created_at)`. En Carbon 3 (Laravel 12) `diffIn*` es con signo por defecto, así que `posterior->diff(anterior)` devuelve valores negativos.
- **Impacto:** Métricas de tiempo de resolución/respuesta y duración mostradas en reportes y dashboards pueden ser negativas o incorrectas.
- **Recomendación:** Usar diffs absolutos: pasar `true` como segundo argumento o invertir operandos, p.ej. `$this->created_at->diffInMinutes($this->first_response_at)` o `->diffInMinutes($end, true)`. Auditar todos los call sites de `diffIn*` del módulo.
- **Esfuerzo:** S

#### HT-03 · [CONFIRMADO] Accessors status/category auto-cargan y disparan N+1
- **Archivo:** `modules/HelpdeskTickets/app/Models/Ticket.php:248-280`
- **Evidencia:** `getCategoryAttribute()` y `getStatusAttribute()` llaman `$this->load('category')`/`$this->load('status')` al acceder cuando la relación no está cargada. Cualquier Blade/loop que itere tickets sin eager load dispara una query por fila, y estos accessors anulan optimizaciones de `whenLoaded()`/column-select.
- **Impacto:** N+1 oculto en listados/partials cuando se omite el eager load; más difícil razonar sobre conteo de queries.
- **Recomendación:** Eliminar estos accessors override y depender de relaciones estándar + eager loading explícito (`with(['status','category'])`) en controladores/resources.
- **Esfuerzo:** M

#### HT-04 · [CONFIRMADO] `applyChanges` hace múltiples writes sin transacción
- **Archivo:** `modules/HelpdeskTickets/app/Services/TicketUpdateService.php:16-93`
- **Evidencia:** `applyChanges` realiza varios writes por llamada (update de status + fresh + `items()->create`, update de category + item, `assignTo`, más un `$ticket->update($remaining)` final) sin envoltura `DB::transaction`.
- **Impacto:** Un fallo a mitad de método deja el ticket parcialmente actualizado (p.ej. status cambiado pero historial/pausa-SLA no aplicados).
- **Recomendación:** Envolver el cuerpo de `applyChanges` en `DB::transaction()`. Evitar también los round-trips extra de `$ticket->fresh()->status`.
- **Esfuerzo:** S

#### HT-05 · [CONFIRMADO] Form Requests del Portal existen pero se usa validación inline
- **Archivo:** `modules/HelpdeskTickets/app/Http/Controllers/Portal/CustomerPortalController.php:52,164,218,269,296`
- **Evidencia:** `Portal/ReplyTicketRequest`, `StoreTicketRequest`, `RateTicketRequest`, `UpdateAccountRequest`, `PortalLoginRequest` existen bajo `app/Http/Requests/Portal` pero `CustomerPortalController` valida inline. También se usa `validate()` inline en `Agents/`, `Settings/` y `BulkTicketsController` pese a la regla de Form Requests.
- **Impacto:** Lógica de validación duplicada/divergente, `messages()`/`attributes()` en español bypasseados, y clases Form Request ya escritas quedan como código muerto.
- **Recomendación:** Cablear los Form Requests del Portal en las firmas del controlador (y migrar otras llamadas `validate()` inline a Form Requests donde ya exista la clase).
- **Esfuerzo:** M

#### HT-06 · [CONFIRMADO] Join cross-conexión en dashboard falla con BD helpdesk separada
- **Archivo:** `modules/HelpdeskTickets/app/Services/HelpdeskTicketBridgeService.php:186-194`
- **Evidencia:** `getDashboardData()` ejecuta `User::...->join('helpdesk_tickets as t', 't.assignee_id','=','users.id')`. `User` está en la conexión por defecto; `helpdesk_tickets` en la conexión `helpdesk`. En otros sitios el código califica explícitamente la tabla users (`TicketCannedReply.user(): ->from("{database}.users")`) precisamente porque difieren. `config/database.php` solo hace que `DB_DATABASE_HELPDESK` use `DB_DATABASE` por defecto, así que el join sin calificar rompe cuando las dos BD difieren.
- **Impacto:** El dashboard de inbox/agente lanza error (o devuelve datos erróneos) en despliegues donde helpdesk corre en su propio esquema/servidor.
- **Recomendación:** Calificar la tabla del join con el nombre de la BD helpdesk (o correr el agregado en la conexión helpdesk y resolver nombres de agente por separado), replicando el patrón de `TicketCannedReply.user()`.
- **Esfuerzo:** M

#### HT-07 · [CONFIRMADO] Race en generación de número: `lockForUpdate` fuera de transacción
- **Archivo:** `modules/HelpdeskTickets/app/Models/Ticket.php:217-238`
- **Evidencia:** `generateTicketNumber()` usa `->lockForUpdate()` pero se invoca desde `static::creating` sin transacción envolvente en `CustomerPortalController::storeTicket` (~228) y `FetchTicketEmailsJob::findOrCreateTicket` (Job:212). `lockForUpdate` no tiene efecto sin una transacción activa.
- **Impacto:** Envíos concurrentes del portal o emails entrantes simultáneos pueden generar números `TCK-YYYY-#####` duplicados.
- **Recomendación:** Envolver la creación de ticket en esos paths en `DB::transaction()` (como ya hacen `TicketService::createTicket` y `TicketsCrudController::store`), o generar el número atómicamente vía un contador dedicado.
- **Esfuerzo:** S

### LOW

#### HT-08 · [CONFIRMADO] Adjuntos públicos y de email entrante aceptan cualquier MIME
- **Archivo:** `modules/HelpdeskTickets/app/Services/TicketCategoryValidationBuilder.php:44`
- **Evidencia:** Los campos de archivo del formulario público validan solo `['file','max:10240']` sin regla `mimes`/extensiones; `FetchTicketEmailsJob::saveAttachment` también guarda cualquier adjunto sin filtrar. Mitigado: los archivos van al disco privado `local` y `TicketAttachmentDownloadController` usa `Storage::download()` (attachment forzado), por lo que no hay ejecución inline/XSS.
- **Impacto:** Usuarios no autenticados pueden almacenar tipos de archivo arbitrarios (llenado de disco / staging de malware), aunque no se sirven por web.
- **Recomendación:** Añadir whitelist de `mimes`/extensiones (reusar `helpdesk.attachments.allowed_mime_types`) y un tope de tamaño por archivo a las reglas del formulario público y al manejo de adjuntos de email entrante.
- **Esfuerzo:** S

#### HT-09 · [CONFIRMADO] Threading por número en subject sin verificar remitente
- **Archivo:** `modules/HelpdeskTickets/app/Jobs/Helpdesks/FetchTicketEmailsJob.php:184-189`
- **Evidencia:** `findOrCreateTicket()` matchea un ticket por `#(TCK-YYYY-#####)` en el Subject y lo devuelve sin importar si la dirección From coincide con el cliente del ticket.
- **Impacto:** Un tercero que adivine/conozca un número de ticket puede inyectar un mensaje "de cliente" en el ticket de otra persona mediante un subject manipulado.
- **Recomendación:** Tras matchear por número, verificar que el From coincida con el cliente/CC del ticket antes de anexar; si no, crear un ticket nuevo o poner en cuarentena.
- **Esfuerzo:** S

#### HT-10 · [CONFIRMADO] `assignee_id` aceptado sin validación `exists`
- **Archivo:** `modules/HelpdeskTickets/app/Http/Requests/StoreTicketRequest.php:33`
- **Evidencia:** `'assignee_id' => 'nullable|integer'` (también `UpdateTicketRequest.php:38` `'sometimes|nullable|integer'`) sin `exists:users,id`, a diferencia de otras FK que sí usan `exists`.
- **Impacto:** Los tickets pueden asignarse a IDs de usuario inexistentes, dejando referencias rotas de assignee en UI/notificaciones.
- **Recomendación:** Añadir `exists:users,id` (en la conexión por defecto) a `assignee_id` en ambos requests.
- **Esfuerzo:** S

#### HT-11 · [CONFIRMADO] Bulk close/reopen solo cambia `closed_at` y omite status + eventos
- **Archivo:** `modules/HelpdeskTickets/app/Http/Controllers/Managers/BulkTicketsController.php:44-61`
- **Evidencia:** `'close'` pone `closed_at=now()` pero deja `status_id` apuntando a un status abierto; `'reopen'` limpia `closed_at` sin restaurar un status abierto. Todas las acciones usan `update()`/`delete()` del query builder, así que los eventos de modelo, `TicketHistory`, recálculo de SLA y broadcasts nunca se disparan.
- **Impacto:** Tickets cerrados en bloque permanecen en status "abierto" según la lógica `status.is_open` (filtrado/reporting inconsistente) y sin efectos de auditoría/SLA.
- **Recomendación:** Asignar `status_id` al status cerrado/abierto apropiado durante las transiciones en bloque y registrar una entrada de historial mínima (o documentar el bypass intencional).
- **Esfuerzo:** M

#### HT-12 · [CONFIRMADO] Método huérfano `TicketsCrudController::details()`
- **Archivo:** `modules/HelpdeskTickets/app/Http/Controllers/Managers/TicketsCrudController.php:314-353`
- **Evidencia:** Existe una acción JSON pública `details()` pero no hay ruta en ningún `routes/*.php` ni referencia Blade/JS en `resources/` ni `public/`.
- **Impacto:** Código muerto; ruido de mantenimiento y falsa sensación de un endpoint disponible.
- **Recomendación:** Eliminar el método (o registrar una ruta si realmente se necesita).
- **Esfuerzo:** S

#### HT-13 · [CONFIRMADO] Código JSON:API muerto en `BaseTicketRequest`
- **Archivo:** `modules/HelpdeskTickets/app/Http/Requests/BaseTicketRequest.php:9-35`
- **Evidencia:** `mappedAttributes()` mapea `data.attributes.*` / `data.relationships.*` y `messages()` referencia estados 'A, C, H, X' — una convención JSON:API/legacy no usada en el flujo de requests actual.
- **Impacto:** Código legacy engañoso; el override de `messages()` podría mostrar texto de validación irrelevante.
- **Recomendación:** Eliminar `mappedAttributes()`/`messages()` no usados de `BaseTicketRequest`.
- **Esfuerzo:** S

#### HT-14 · [CONFIRMADO] Modelos SLA duplicados (`SlaPolicy` vs `TicketSlaPolicy`)
- **Archivo:** `modules/HelpdeskTickets/app/Services/TicketService.php:12`
- **Evidencia:** `TicketService.php:12` y `SlaService.php:10` importan ambos `Modules\HelpdeskTickets\Models\SlaPolicy` con `// TODO: migrate to TicketSlaPolicy`, mientras el resto del módulo (policies, requests, controllers) usa `TicketSlaPolicy`.
- **Impacto:** Dos modelos solapados para el mismo concepto invitan a drift y confusión.
- **Recomendación:** Completar la migración a `TicketSlaPolicy` y eliminar `SlaPolicy`, o documentar por qué existen ambos.
- **Esfuerzo:** M

#### HT-15 · [CONFIRMADO] Clase base Tabler `ti` + inline style en lista de categorías
- **Archivo:** `modules/HelpdeskTickets/resources/views/managers/ticket-categories/index.blade.php:242`
- **Evidencia:** `<i class="ti {{ $category->icon }}" style="color: {{ $category->color }}; font-size: 20px;"></i>` — `ti` es la clase base de Tabler (prohibida; no cargada) y usa inline style.
- **Impacto:** El icono se renderiza mal (fuente Tabler ausente) y viola las reglas FA6-only + no-inline-style.
- **Recomendación:** Renderizar con Font Awesome (p.ej. `<i class="{{ $category->icon }}">`) y mover color/tamaño a una clase utility/CSS.
- **Esfuerzo:** S

#### HT-16 · [CONFIRMADO] `style=` inline en blades de manager/agent (no email)
- **Archivo:** `modules/HelpdeskTickets/resources/views/managers/tickets/show.blade.php:6`
- **Evidencia:** grep muestra `style=""` inline en ~15 vistas no-email (`managers/tickets/show`, `edit`, `partials/_table`, `sidebar`; `ticket-views/statuses create/edit`; `agents/dashboard`, `tickets/index`, `edit`, etc.), contra la regla de no-inline-style del proyecto (plantillas de email exceptuadas).
- **Impacto:** Drift de estilos y theming más difícil; deuda menor de convención.
- **Recomendación:** Migrar inline styles en vistas del panel a utilities de Bootstrap o clases CSS del módulo.
- **Esfuerzo:** M

#### HT-17 · [CONFIRMADO] Varios jobs omiten la propiedad `$backoff`
- **Archivo:** `modules/HelpdeskTickets/app/Jobs/CheckSlaBreaches.php:1-40`
- **Evidencia:** `AutoAssignUnassignedTickets`, `CheckSlaBreaches`, `EscalateTicketsJob`, `ProcessRecurringTicketsJob` y `SendSlaWarnings` definen `$tries`/`$timeout`/`failed()` pero no `$backoff`, que la regla de jobs requiere.
- **Impacto:** Los jobs fallidos reintentan inmediatamente sin backoff; brecha menor de robustez.
- **Recomendación:** Añadir un valor `$backoff` a estos jobs por consistencia con `jobs.md`.
- **Esfuerzo:** S

#### HT-18 · [CONFIRMADO] `smartReplies` sin autorización por recurso
- **Archivo:** `modules/HelpdeskTickets/app/Http/Controllers/Managers/TicketMessagingController.php:140-143`
- **Evidencia:** `smartReplies(Ticket $ticket, TicketAiService $ai)` no tiene `$this->authorize('view',$ticket)` (`storeMessage`/`bulkReply` sí lo tienen). Mitigado por el middleware del grupo de rutas managers `role:super-admin|super-settings`.
- **Impacto:** Depende únicamente del gate de rol a nivel de ruta en vez de la `TicketPolicy`; riesgo bajo dado el gating actual.
- **Recomendación:** Añadir `$this->authorize('view', $ticket)` por consistencia defense-in-depth.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **HT-01 (high) — Consolidar ciclo de vida del ticket.** Mover todo a `TicketObserver`, eliminar closures de `booted()`, garantizar historial/SLA single-write. Desbloquea la fiabilidad de reportes y elimina las tres duplicaciones reales.
2. **HT-02 (medium) — Corregir diffs con signo de Carbon 3.** Cambio mínimo de alto impacto en métricas; auditar todos los `diffIn*`.
3. **HT-03 (medium) — Eliminar accessors auto-cargadores.** Restaurar relaciones estándar + eager loading explícito.
4. **HT-06 (medium) — Calificar tabla en join cross-conexión.** Previene roturas en despliegues con BD helpdesk separada.
5. **HT-04 + HT-07 (medium) — Transacciones.** Envolver `applyChanges` y los paths de creación portal/email en `DB::transaction()`.
6. **HT-05 (medium) — Cablear Form Requests del Portal.**
7. **LOW de seguridad (HT-08, HT-09, HT-10, HT-18):** whitelist de MIME, verificación de remitente en threading, `exists` en `assignee_id`, authorize en `smartReplies`.
8. **LOW de calidad/convención (HT-11 a HT-17):** limpieza de código muerto, modelos SLA, iconos/estilos, `$backoff`.

## Quick wins

- Eliminar el método huérfano `TicketsCrudController::details()` (HT-12).
- Envolver `TicketUpdateService::applyChanges` en `DB::transaction` (HT-04).
- Reemplazar la clase base Tabler `ti` en `ticket-categories/index.blade.php:242` por Font Awesome y quitar el inline style (HT-15).
- Añadir `exists:users,id` a `assignee_id` en `Store/UpdateTicketRequest` (HT-10).
- Eliminar el código JSON:API muerto `mappedAttributes()`/`messages()` de `BaseTicketRequest` (HT-13).
- Corregir los `diffInMinutes` con signo (HT-02).

## Fortalezas

- **Cobertura de tests automatizados fuerte:** 58 archivos de test (~442 métodos) entre Feature (controllers, policies, portal, API, settings) y Unit (services, models, listeners, jobs); `tests/` en minúscula (PSR-4 safe).
- **HTML de email entrante saneado** vía HTMLPurifier (`clean_html`) con whitelist estricta de tags/atributos; la vista de manager escapa con `e()`+`nl2br` — no se encontró superficie de XSS almacenado.
- **Portal del cliente con ownership real** (scoping `customer_id`) en cada lectura/respuesta/valoración de ticket, comprueba `banned_at`, y protege el enlace de valoración por email con middleware firmado.
- **Agregados de dashboard cacheados** (`Cache::remember` con TTLs sanos) y todo el SQL crudo es totalmente estático o parametrizado con bindings — sin vectores de inyección SQL.
- **Policies registradas vía `Gate::policy`** en el ServiceProvider; todos los listeners de eventos son class-based (`event:cache` safe); uploads de manager validados con la regla magic-byte `ValidMimeMagicBytes`.

## Cobertura de la auditoría

Solo análisis estático (BD de test bloqueada). Lectura profunda: ambos ServiceProviders, los 5 archivos de rutas, 12 controllers (Portal, PublicTicketForm, ConversationTicketBridge, Api/Tickets, TicketsCrud, TicketMessaging, BulkTickets, TicketAttachmentDownload, TicketGeneralSettings), modelos Ticket + TicketCannedReply, TicketUpdateService, AutomationEngine, TicketAiService, TicketCategoryValidationBuilder, HelpdeskTicketBridgeService (parcial), TicketObserver, FetchTicketEmailsJob, Form Requests clave, TicketResource, helper `clean_html`. Grep de los 47 controllers para authz/inline-validate/raw-SQL, los 8 jobs para props de queue, los 18 listeners para `ShouldQueue`, blades para iconos/estilos/select2, modelos para `appends`.

**NO leído en profundidad:** cuerpos de MacroExecutor, SlaService, AssignmentService, EscalationService, MentionService, TicketNotificationService; ~35 controllers restantes; ~30 modelos restantes; cuerpos individuales de jobs/listeners; los 72 blades (muestreados); migraciones (solo comparado `fillable`). Ningún hallazgo fue validado en runtime.

## Descartados en verificación

Ningún hallazgo fue refutado durante la verificación. El único veredicto emitido (HT-01) ajustó el mecanismo descrito pero mantuvo la severidad HIGH; las correcciones puntuales al hallazgo original (el observer NO loguea status; las duplicaciones `creating` y double cache-forget son idempotentes/benignas) quedan documentadas dentro de la evidencia de HT-01.
