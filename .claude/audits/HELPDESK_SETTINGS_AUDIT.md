<!-- Generado por auditoría multi-agente (workflow helpdesk-settings-audit) el 2026-06-13. 15 grupos / ~52 paneles / 11 módulos. Hallazgos críticos T0.1/CC-1, T0.2, T0.4, T0.11 verificados contra código. -->

# Plan de mejora — Settings de Helpdesk

## 1. Resumen ejecutivo

**Inventario de paneles auditados:** se auditaron **~52 paneles** repartidos en **11 módulos** (Helpdesk core, HelpdeskTickets, HelpdeskLivechat, HelpdeskAgents, HelpdeskSocial, HelpdeskTranslate, HelpdeskEmailLog). Distribución por estado:

| Estado | Nº paneles | Detalle |
|---|---|---|
| **broken** | 18 | layout-shell, statuses, custom-fields, team-groups/member-edit, skills, routing-rules, sla-policies, macros (core), canned-replies, email, email-accounts, notifications, webhooks, inboxes, whatsapp-templates, status-page, gdpr, TicketGeneralSettings, schedule, social-general |
| **partial** | 14 | nav-sidebar, attributes, views, team-members, business-hours, automation-rules, uploading, drip-campaigns, surveys, macros (tickets), automations (tickets), livechat, ai-agent-settings |
| **working** | 14 | SettingsController-uploading, tags, workflows, lookup, features, integrations, slack-integrations, banners, brands, companies, audits, profile/tokens, pre-chat-forms, translate-settings, email-log-settings |
| **stub** | 1 | ai-agents (directorio vacío) |
| **missing** | 1 | ai (panel de configuración inexistente con enlace muerto en nav) |

**Problemas críticos más graves (afectan a múltiples paneles o rompen flujos centrales):**

1. **BUG SISTÉMICO DE PREFIJO DE CLAVE** (email, notifications, uploading + TicketGeneral): `Setting::set(GROUP.'.'.key)` guarda claves prefijadas pero los controllers hacen `array_merge(DEFAULTS_planas, Setting::allAsArray())` → las vistas SIEMPRE leen el default, nunca el valor guardado. El usuario "guarda" y al recargar ve vacío, re-guarda sobre defaults y **corrompe la config real**. `FeaturesSettingsController` es el patrón de referencia correcto.
2. **PERMISOS ROTOS POR INCONSISTENCIA singular/plural y gates divergentes**: `status-page` exige `helpdesk.status.manage` (no sembrado; existe `statuses.manage`), `gdpr` llama `CustomerPolicy::manage()` (no existe), `social-general` usa `helpdesksocial.manage-rules` (renombrado por migración a `rules.manage`), y varios Form Requests (attributes, webhooks, schedule) autorizan con un gate distinto al middleware del controller → **paneles enteros devuelven 403** o usuarios con el permiso correcto reciben 403 al guardar.
3. **RUTAS ROTAS Y MÉTODOS FANTASMA**: `WebhooksController::index(Request)` sin `use Illuminate\Http\Request` → 500 en runtime; `routing-rules.toggle`, `inboxes.channels/channelList`, `statuses.toggle` apuntan a métodos/rutas inexistentes → RouteNotFoundException/500; `schedule` renderiza `helpdesk::` en vez de `helpdeskagents::` → View not found; el enlace `settings.helpdesk.ai` del sidebar es muerto.
4. **MODAL DE BORRADO ROTO (IDs)** (custom-fields, skills, broadcasts, drip-campaigns): el JS apunta a `#deleteModal/#deleteForm/#deleteItemName` mientras `core::components.delete` define `#delete-modal/#delete-form` → **el botón Eliminar no hace nada** en 4+ paneles.
5. **CAMPOS FANTASMA / DATA BINDING ROTO** (statuses `is_open`, views `sort_by`/`is_shared`, macros `visibility→is_shared`, sla `*_minutes` vs `*_time_hours`, ai-agent `settings` vs `parameters`, livechat `$settings` vs `$backups`, TicketGeneral `$backups` vs `$settings`): formularios envían campos que el controller/BD ignoran → datos perdidos silenciosamente; al editar se pisan valores existentes.
6. **SECRETOS EXPUESTOS EN HTML** (webhooks `secret`, inboxes credenciales/tokens): se vuelcan en `value=` de inputs → visibles en "ver código fuente"/DOM. El modelo `Webhook` no cifra ni oculta `secret`.
7. **PANELES STORE ROTOS POR ESQUEMA** (sla-policies: `uid` UNIQUE NOT NULL nunca seteado → SQL integrity violation; canned-replies: columna legacy `content` NOT NULL sin default → INSERT falla en strict mode) → **no se puede crear ningún registro**.
8. **SOLAPAMIENTO ARQUITECTÓNICO** (HelpdeskTickets macros/automations comparten tablas físicas con el core Helpdesk sin discriminador) y **DUPLICACIÓN de config social** (HelpdeskSocial vs core social-integrations) → dos pantallas listando las mismas filas con vocabularios incompatibles.

**Veredicto general:** el sistema de settings está **funcionalmente deteriorado**. La maquetación y las convenciones de UI están relativamente maduras (cards, FA6, dropdowns), pero hay una **desconexión generalizada entre formularios, controllers y esquema de BD** que indica que el grupo **nunca se probó end-to-end** (cobertura de tests ~0% salvo schedule y email-log). Más del 60% de los paneles tienen al menos un bug crítico o de alta severidad. Los settings **no son confiables en producción** hasta completar la FASE 0.

---

## 2. Problemas transversales (cross-cutting)

### CC-1. Bug sistémico de prefijo de clave en `Setting`
- **Descripción:** `Setting::set(GROUP.'.'.key)` persiste claves como `email.smtp_host`, pero los controllers hacen `array_merge(DEFAULTS [planas], Setting::allAsArray(group) [prefijadas])`. Las vistas leen claves planas que siempre resuelven al DEFAULT.
- **Paneles afectados:** email, notifications, uploading (Helpdesk), general-tickets (HelpdeskTickets). `FeaturesSettingsController` lo corrige (líneas 115-122) y es el patrón de referencia.
- **Impacto:** CRÍTICO. Configuración guardada nunca visible; riesgo de corrupción al re-guardar sobre defaults.
- **Corrección global:** crear un helper compartido `Setting::allAsFlatArray($group)` (o un trait `ReadsGroupedSettings`) que devuelva claves SIN prefijo, y usarlo en los 4 controllers. Test: guardar valor → recargar index → assert que aparece.

### CC-2. Inconsistencia de permisos (middleware vs Form Request, singular/plural)
- **Descripción:** el middleware `can:` del controller y el `authorize()` del Form Request chequean gates distintos; nombres singular/plural divergentes; policies sin método.
- **Paneles afectados:** attributes (`helpdesk.attributes.*` vs `helpdesk.settings.update`), webhooks (`helpdesk.webhooks.*` vs `helpdesk.settings.update`), schedule (`helpdesk.schedule.*` vs `helpdesk.settings.update`), status-page (`helpdesk.status.manage` no sembrado), gdpr (`CustomerPolicy::manage` inexistente), social-general (`manage-rules` renombrado), macros/automations tickets (`helpdesk.tickets.settings` global).
- **Impacto:** CRÍTICO/ALTO. 403 a usuarios legítimos o paneles enteros inaccesibles.
- **Corrección global:** auditar cada panel y unificar el gate entre middleware y Form Request usando la convención `{alias}.{action}`. Sembrar permisos faltantes en los PermissionsSeeder. Añadir un **test que itere todos los paneles y verifique coherencia de permiso**.

### CC-3. Color de marca incorrecto y `<style>` inline / `text-transform:uppercase`
- **Descripción:** azul `#5D87FF`/`#3E5BDB`/`#0d6efd` y rojos off-brand `#7b0000` en vez del verde `#90bb13`; bloques `<style>` de hasta 300 líneas; `text-transform:uppercase` en headings; `style=""` inline con colores/medidas hardcodeadas.
- **Paneles afectados:** layout-shell, member-edit/groups, livechat (~140 líneas CSS), ai-agent-settings (bg-info-subtle azul), translate-settings (#7b0000), social-integrations (#e1306c), custom-fields (#6f42c1), y `style=""` puntual en casi todas las vistas (swatches de color, avatares, anchos de columna).
- **Impacto:** MEDIO. Marca inconsistente; deuda de mantenimiento; viola `blade-views.md`.
- **Corrección global:** crear hojas CSS por módulo (publicadas en `public/modules/...`), migrar bloques `<style>` y `style=""` a clases utilitarias Bootstrap o clases del módulo (`.color-dot`, `.avatar-initials`, `.brand-swatch{--swatch}`). Eliminar todo `text-transform:uppercase` y sustituir hex por `#90bb13`.

### CC-4. Modal de borrado roto por IDs incorrectos
- **Descripción:** JS usa `#deleteModal/#deleteForm/#deleteItemName`; el componente `core::components.delete` define `#delete-modal/#delete-form`.
- **Paneles afectados:** custom-fields, skills, broadcasts, drip-campaigns.
- **Impacto:** ALTO. El botón Eliminar no abre nada → no se puede borrar desde la UI.
- **Corrección global:** estandarizar el snippet JS de borrado a los IDs reales (`$('#delete-form').attr('action',url); $('#delete-modal .modal-title').text(...); new bootstrap.Modal('#delete-modal').show()`) en todos los paneles. Idealmente extraer a un helper JS compartido `helpdeskDelete(url,name)`.

### CC-5. Booleanos como checkbox en vez de `<select>`
- **Descripción:** la convención del proyecto exige `<select>` para booleanos en forms; la mayoría usa checkbox/form-switch.
- **Paneles afectados:** casi todos (statuses, attributes, macros, canned-replies, sla, automation-rules, workflows, business-hours, group-create/edit, drip, surveys, email, notifications, livechat, translate, email-log, brands, social, ai-agent, schedule...).
- **Impacto:** BAJO (cosmético/convención). Algunos paneles tipo dashboard de toggles masivos (features 39 toggles, livechat >15) son excepción razonable.
- **Corrección global:** convertir booleanos puntuales a `<select>`; documentar como excepción los dashboards de toggles masivos. Baja prioridad, batch único.

### CC-6. Validación inline en vez de Form Request existente
- **Descripción:** controllers usan `$request->validate()` inline pese a existir Form Requests dedicados (perdiendo además la autorización Spatie del Form Request).
- **Paneles afectados:** business-hours, routing-rules, automation-rules, banners, surveys, status-page, gdpr, profile/tokens, integrations, notifications, social-general, views (bulkAction).
- **Impacto:** MEDIO. Viola `controllers.md`/`form-requests.md`; bypass de autorización centralizada.
- **Corrección global:** inyectar el Form Request existente (o crear el que falte) y eliminar la validación inline.

### CC-7. Navegación de settings desconectada de las rutas reales
- **Descripción:** sidebar lista 23 ítems pero `routes/settings.php` define ~32 grupos → 15+ paneles huérfanos solo accesibles por URL; enlace muerto `settings.helpdesk.ai`; ítem `tickets.general` con esquema `manager.*` que cae a `#` si HelpdeskTickets está deshabilitado; sidebar plano sin agrupación ni iconos.
- **Paneles afectados:** brands, companies, status-page, gdpr, banners, surveys, broadcasts, drip-campaigns, whatsapp-templates, custom-fields, routing-rules, skills, agent-settings, email-accounts, slack-integrations, workflows + nav-sidebar.
- **Impacto:** ALTO. Funcionalidad inalcanzable; enlaces muertos.
- **Corrección global:** reagrupar el sidebar en secciones temáticas (Canales, Automatización, Equipo, Personalización, Integraciones, Avanzado), añadir iconos FA6, enlazar paneles funcionales con su permiso, eliminar el enlace `ai` muerto, y **test que itere cada ítem del sidebar y asserte `Route::has()===true`** + test que verifique que cada `index` de panel tiene ítem (o whitelist).

### CC-8. Ausencia total de tests
- **Descripción:** cobertura ~0% (salvo schedule "good" y email-log "good"; tags y macros-tickets "partial" triviales).
- **Paneles afectados:** ~48 de 52.
- **Impacto:** ALTO. Ningún bug de este informe habría sido cazado por CI.
- **Corrección global:** FASE 5 — un `*ControllerTest` por panel (index 200, store/update persistencia, 422, 403). Usar `DatabaseTransactions` (no `RefreshDatabase`, por la nota de memoria sobre conexión `helpdesk`).

### CC-9. Doble fuente de verdad y persistencia frágil
- **Descripción:** Cache de 365 días + BD con claves distintas (uploading, tickets-general); secret regenerado; `firstOrCreate` con efectos secundarios en GET.
- **Paneles afectados:** uploading, tickets-general, livechat (secret_key en index).
- **Corrección global:** BD como única fuente de verdad; cache solo lectura con invalidación explícita; mover generación de secrets a seeder/comando.

### CC-10. Secretos sin cifrar/expuestos
- **Descripción:** credenciales en `value=` de inputs; `Webhook::secret` y email passwords sin cifrar.
- **Paneles afectados:** webhooks, inboxes, email. `slack-integrations` es el patrón correcto (cifrado + `$hidden` + placeholder "Cambiar").
- **Corrección global:** adoptar el patrón slack en todo el grupo: mutator `Crypt::encryptString`, `$hidden`, placeholder enmascarado, conservar valor si el campo va vacío.

### CC-11. Falta `bulk-action` en listados
- **Descripción:** la convención `routes.md` exige `bulk-action` en todo index; casi ninguno lo tiene.
- **Impacto:** BAJO. Opcional/convención.
- **Corrección global:** añadir ruta+método+checkboxes donde aporte valor (activar/desactivar/eliminar masivo). Baja prioridad.

### CC-12. JSON crudo en lugar de builder visual
- **Descripción:** condiciones/acciones de macros y automations (core y tickets) se editan como `<textarea>` JSON a mano → inusable para managers, 422 genéricos.
- **Paneles afectados:** macros-tickets, automations-tickets, workflows (nodes), macros-core (parcial).
- **Corrección global:** reutilizar el patrón de builder dinámico jQuery (repeater de filas que serializa a hidden JSON) que ya tiene macros-core.

---

## 3. Plan dividido por tareas

### FASE 0 — Correcciones críticas y de seguridad

| ID | Título | Paneles/Archivos | Tipo | Sev | Esf | Agente | Criterio de aceptación (test) |
|---|---|---|---|---|---|---|---|
| **T0.1** | Helper `allAsFlatArray` + fix prefijo en index() | EmailSettingsController, NotificationSettingsController, SettingsController@uploadingIndex, TicketGeneralSettingsController; Core/Setting | CORREGIR | crítico | M | backend | Test: guardar `email_from_name` → GET index → response contiene el valor guardado (no el default) en email/notifications/uploading/tickets-general |
| **T0.2** | Importar `Illuminate\Http\Request` en WebhooksController | WebhooksController.php | CORREGIR | crítico | S | backend | Test feature: GET `settings.helpdesk.webhooks.index` → 200 (hoy 500) |
| **T0.3** | Sembrar `helpdesk.status.manage` o cambiar middleware a `statuses.manage` | StatusPageController, PermissionsSeeder | CORREGIR | crítico | S | security | Test: usuario con permiso correcto accede a status-page (200); sin permiso 403 |
| **T0.4** | Añadir `CustomerPolicy::manage()` | CustomerPolicy.php | CORREGIR | crítico | S | security | Test: manager con `helpdesk.customers.manage` accede a panel GDPR/export/delete; sin él 403 |
| **T0.5** | Fix permiso social `manage-rules`→`rules.manage` + usar Form Request | SocialModuleSettingsController | CORREGIR | crítico | S | security | Test: update con permiso `rules.manage` persiste; sin permiso 403 |
| **T0.6** | Generar `uid` + mapear `*_time_hours` en SlaPolicies store/update | SlaPoliciesController, SlaPolicy modelo (creating), _form | CORREGIR | crítico | M | backend | Test: `test_manager_can_create_sla_policy` persiste uid + horas; 422 sin name |
| **T0.7** | Migración: `content` nullable en `helpdesk_canned_replies` | nueva migración + CannedReply | CORREGIR | crítico | S | database | Test: `test_manager_can_create_canned_reply` → assertDatabaseHas (hoy SQL error en strict) |
| **T0.8** | Fix `statuses` persistencia `is_open`/`is_closed` + ruta toggle | StatusesController, Store/Update requests, ConversationStatus, settings.php | CORREGIR | crítico | M | backend | Test: crear estado con is_open=false → persiste; invocar toggle → 200 (hoy 500) |
| **T0.9** | Fix data binding macros `visibility→is_shared` + `existingActions` en edit + `usage_count` | MacrosController (core), edit.blade, _form | CORREGIR | crítico | M | backend | Test: store macro personal → is_shared=false; edit carga acciones existentes; persiste tras update |
| **T0.10** | Fix `$inboxCounts` indefinida + métodos `channels()/channelList()` + URL test | InboxesController, index.blade, form.blade, settings.php | CORREGIR | crítico | M | backend | Test: GET inboxes.index → 200 con conteos; rutas channels/test → no 500 |
| **T0.11** | Fix vista schedule namespace `helpdesk::`→`helpdeskagents::` | ScheduleController.php:31 | CORREGIR | crítico | S | backend | Test (con conexión helpdesk activa): GET schedule.index → 200 (hoy View not found) |
| **T0.12** | Fix ai-agent data binding `settings`→`parameters` + API key segura | settings-tab.blade.php | CORREGIR | crítico | M | backend | Test: editar agente existente → parámetros guardados se muestran (no defaults) |
| **T0.13** | Fix livechat `$settings`→`$backups` (3 líneas) + exponer `website_token` | livechat index.blade, LivechatSettingsController extractSettings | CORREGIR | alto | S | frontend | Test: update toggles archivos/emoji → reaparecen marcados; snippet install muestra token real |
| **T0.14** | Cifrar/ocultar secretos en webhooks e inboxes (patrón slack) | Webhook modelo, _form.blade; Inbox form.blade | CORREGIR | alto | M | security | Test: GET edit webhook/inbox → response NO contiene el secreto en texto plano; secret cifrado en BD |
| **T0.15** | Unificar gates Form Request vs middleware (attributes, webhooks, schedule) | Store/Update requests de los 3 paneles | CORREGIR | alto | M | security | Test: usuario con permiso del panel guarda sin recibir 403 del Form Request |
| **T0.16** | Implementar comando `helpdesk:sync-wa-templates` (o Job) + manejo error | WhatsAppTemplatesController, nuevo Job/Command | CORREGIR | crítico | M | backend | Test: pulsar Sincronizar → Queue::assertPushed (no CommandNotFoundException) |
| **T0.17** | Registrar ruta `routing-rules.toggle` + usar Form Requests | settings.php, RoutingRulesController | CORREGIR | crítico | S | backend | Test: GET routing-rules.index → 200 (hoy RouteNotFoundException); toggle → JSON 200 |
| **T0.18** | Fix data binding TicketGeneral `$backups` + respuesta redirect/AJAX | TicketGeneralSettingsController, general/index.blade | CORREGIR | crítico | M | backend | Test: guardar valor → reaparece en GET; submit no muestra JSON crudo |
| **T0.19** | Fix `views` columnas `is_shared`→`is_public` + decidir `sort_by/sort_direction` | ViewsController, views/index.blade, Store request | CORREGIR | alto | M | backend | Test: vista pública muestra "Compartida"; ordenación persiste o se elimina (sin dead-code) |
| **T0.20** | Fix `team` `assignment_limit`→`max_concurrent_conversations` + `working_hours` top-level + restringir rol asignable | TeamController, member-edit.blade, UpdateTeamMemberRequest | CORREGIR | crítico | M | security | Test: límite y horario tecleados persisten; no se puede asignar rol admin desde settings |
| **T0.21** | Eliminar enlace muerto `settings.helpdesk.ai` del sidebar | HelpdeskServiceProvider:181 | CORREGIR | alto | S | backend | Test: ningún ítem del sidebar settings resuelve a `#` por ruta inexistente |
| **T0.22** | Migrar `imap_open` a webklex/php-imap o sockets crudos | EmailSettingsController checkImapConnection, ImapPullService | CORREGIR | alto | L | backend | Test: test-imap con host inválido → status:false (no fatal por ext-imap ausente en PHP 8.4) |
| **T0.23** | Reparar IDs modal borrado (custom-fields, skills, broadcasts, drip) | 4 index.blade | CORREGIR | alto | S | frontend | Test E2E/JS o assert presencia de IDs correctos; click Eliminar abre modal |
| **T0.24** | Fix canal fantasma `widget`→`web` (broadcasts, drip) + scheduled_at en broadcasts store | broadcasts/drip forms+filtros, BroadcastsController store | CORREGIR | alto | M | backend | Test: store broadcast con scheduled_at → status='scheduled'; drip con canal default no da 422 |
| **T0.25** | Fix `attributes` toggle no funcional (onchange/AJAX) | attributes _table.blade, index.blade | CORREGIR | alto | S | frontend | Test: cambiar switch → dispara submit/AJAX y persiste is_active |
| **T0.26** | Escapar `data-properties` (XSS) en audits + `{{ }}` en macros-tickets edit | audits/index.blade, macros-tickets edit.blade | CORREGIR | medio | S | security | Test: propiedades con comillas/HTML no rompen atributo ni ejecutan script |

### FASE 1 — Unificación de UI/marca

| ID | Título | Paneles/Archivos | Tipo | Sev | Esf | Agente | Criterio de aceptación |
|---|---|---|---|---|---|---|---|
| **T1.1** | Decidir y eliminar `settings/layout.blade.php` huérfano | modules/Helpdesk/.../settings/layout.blade.php | CORREGIR | alto | S | frontend | grep `helpdesk::settings.layout` = 0; archivo eliminado; smoke test render de 3 paneles 200 |
| **T1.2** | Crear hojas CSS por módulo y migrar `<style>` inline | livechat, translate, member-edit, group-edit, ai-agent | MEJORAR | medio | L | frontend | grep `<style>` en vistas settings = 0; visual sin regresión |
| **T1.3** | Eliminar todos los `style=""` inline → clases utilitarias | ~todas las vistas settings (swatches, avatares, anchos) | MEJORAR | medio | L | frontend | grep `style="` en vistas settings = 0 (salvo `--swatch` data-driven documentado) |
| **T1.4** | Reemplazar colores off-brand por `#90bb13` | layout, livechat #7b0000, ai-agent bg-info azul, social #e1306c, custom-fields #6f42c1 | MEJORAR | medio | M | frontend | grep de hex azules/#7b0000 en settings = 0; capturas con verde de marca |
| **T1.5** | Quitar `text-transform:uppercase` de headings | layout, livechat, translate, member-edit | MEJORAR | bajo | S | frontend | grep `uppercase` en CSS/clases de settings = 0 |
| **T1.6** | Booleanos puntuales → `<select>` (excluir dashboards masivos) | statuses, macros, canned-replies, sla, drip, surveys, brands, schedule, etc. | MEJORAR | bajo | M | frontend | Form Requests ajustados a `in:0,1`; selects renderizan valor correcto |
| **T1.7** | Corregir clase `form-control -0`→`border-0` | banners, surveys, routing-rules index | CORREGIR | bajo | S | frontend | grep `-0 ps-0` = 0 |
| **T1.8** | Quitar `text-danger` en Eliminar / unificar dropdown acciones | groups.blade, profile/tokens | MEJORAR | bajo | S | frontend | dropdown items sin `text-danger`, sin iconos en items |
| **T1.9** | select2 sin `theme:'bootstrap-5'` + multiple en agent-settings/schedule | agent-settings _form, schedule oncall | MEJORAR | bajo | S | frontend | select2 inicializado sin theme bootstrap-5; UX de multiselección |

### FASE 2 — Reestructuración de navegación / arquitectura de información

| ID | Título | Paneles/Archivos | Tipo | Sev | Esf | Agente | Criterio de aceptación |
|---|---|---|---|---|---|---|---|
| **T2.1** | Reagrupar sidebar settings en secciones temáticas + iconos FA6 | HelpdeskServiceProvider::registerSettingsSidebar, nav.blade | MEJORAR | medio | M | backend | Sidebar con 6 secciones; cada ítem con icono; render 200 |
| **T2.2** | Enlazar paneles huérfanos (brands, companies, status-page, custom-fields, routing-rules, surveys, banners, broadcasts, drip, whatsapp, skills, agent-settings, slack, webhooks) | registerSettingsSidebar | IMPLEMENTAR | alto | M | backend | **Test: iterar items sidebar → `Route::has()===true`** + test cada index tiene ítem o whitelist |
| **T2.3** | Eliminar conscientemente rutas/vistas no usadas (status legacy, email-accounts huérfano, ai/ai-agents stub) | settings.php, vistas | CORREGIR | medio | M | backend | Decisión documentada por panel; sin rutas a métodos inexistentes |
| **T2.4** | Redirect raíz `settings.helpdesk.index` a panel propio (no legacy tickets) | settings.php:260 | CORREGIR | medio | S | backend | Test: GET settings.helpdesk.index → 302 a integrations (sin doble redirect) |
| **T2.5** | Envolver ítem tickets.general en `helpdesk_tickets_enabled()` | HelpdeskServiceProvider, nav | CORREGIR | medio | S | backend | Test: con HelpdeskTickets off el ítem no aparece (no cae a `#`) |
| **T2.6** | Limpiar middleware de métodos inexistentes (webhooks show/test, social connect/disconnect) | WebhooksController, SocialIntegrationsController | CORREGIR | medio | S | backend | grep de métodos en middleware sin ruta = 0 |

### FASE 3 — Completar paneles partial/stub e implementar missing_features de mayor valor

| ID | Título | Paneles/Archivos | Tipo | Sev | Esf | Agente | Criterio de aceptación |
|---|---|---|---|---|---|---|---|
| **T3.1** | Implementar persistencia real en social-general (settings store) | SocialModuleSettingsController | IMPLEMENTAR | crítico | M | backend | Test: update persiste y index lo refleja (hoy no-op) |
| **T3.2** | Decidir/implementar panel `ai` settings o eliminar stub | settings/ai/, settings/ai-agents/, nueva ruta/controller | IMPLEMENTAR | alto | L | backend | Panel funcional con test, o directorios eliminados y sin enlace |
| **T3.3** | Resolver solapamiento macros/automations tickets vs core (ADR + discriminador o consolidación) | Automation, Macro modelos, migraciones, ADR | IMPLEMENTAR | crítico | L | backend+database | Test: cada index lista solo sus filas; ADR documentado |
| **T3.4** | Completar EmailAccounts (controller+rutas CRUD) o eliminar vistas huérfanas | EmailAccountsController, settings.php, vistas | IMPLEMENTAR | alto | L | api | Test CRUD multi-cuenta + test conexión, o vistas eliminadas |
| **T3.5** | Builder visual para macros/automations (reusar patrón jQuery) | macros/automations forms (core+tickets) | MEJORAR | alto | L | frontend | Repeater serializa a JSON hidden; sin textarea crudo; test store con payload válido |
| **T3.6** | Implementar render real de respuestas en surveys | surveys/responses.blade | IMPLEMENTAR | medio | M | frontend | Muestra answers↔questions + agregados CSAT; test responses 200 con contenido |
| **T3.7** | Test real de conexión email en inboxes + social-integrations (API real) | InboxesController::test, SocialIntegrationsController | IMPLEMENTAR | medio | M | backend | Test con Http::fake: success/fail según API real (no solo config) |
| **T3.8** | Añadir campos faltantes ai-agent (presence_penalty, organization_id, version, base_url) + test conexión con base_url | settings-tab.blade, JS test | IMPLEMENTAR | medio | M | frontend | Campos condicionales por provider; test conexión envía base_url para local |
| **T3.9** | Encolar sync WhatsApp templates (Job) + feedback | WhatsAppTemplatesController, SyncWhatsAppTemplatesJob | MEJORAR | medio | M | backend | Test: Queue::assertPushed; toastr "en curso" |
| **T3.10** | Exponer enroll drip + cablear disparadores automáticos (listeners) | DripCampaignsController, listeners | IMPLEMENTAR | medio | M | backend | Test: enroll encola ProcessDripStepJob; listener dispara según trigger_type |

### FASE 4 — Mejoras de funcionalidad por panel (resto)

| ID | Título | Paneles/Archivos | Tipo | Sev | Esf | Agente | Criterio de aceptación |
|---|---|---|---|---|---|---|---|
| **T4.1** | Renombrar dominio "tickets"→"conversación" en taxonomía | statuses, tags, views vistas | MEJORAR | bajo | S | frontend | Títulos coinciden con entidad ConversationX |
| **T4.2** | Cifrar passwords SMTP/IMAP (Crypt) + email de prueba real | EmailSettingsController, modelo | MEJORAR | medio | M | security | Test: password cifrado en BD; envío de prueba real |
| **T4.3** | Unificar semántica disponibilidad `is_available` vs `accepts_conversations` | team, agent-settings | CORREGIR | medio | M | backend | Test: misma métrica entre paneles |
| **T4.4** | Unificar enum `assignment_mode` (load_balanced/manual) en vista/stats/modelo | groups.blade, TeamController, Group | CORREGIR | alto | S | backend | Test: badges y stats correctos para load_balanced/manual |
| **T4.5** | Validación SLA/closes_at, required_if horas, regex routing, exists team | business-hours, routing-rules requests | MEJORAR | medio | M | backend | Tests 422 para cierre<apertura, team inexistente, regex inválida |
| **T4.6** | Resolver colisión tabla `helpdesk_conversation_views` (saved vs read-state) | ConversationView, migración | CORREGIR | alto | L | database | Test: 2 saved views por usuario sin colisión unique |
| **T4.7** | Lookup macros: búsqueda server-side + roles agente en config | LookupController, _form JS | MEJORAR | medio | M | backend | Test: solo roles agente; paginación/búsqueda |
| **T4.8** | Scoping personal vs global (macros, canned-replies, macros-tickets) | controllers index | MEJORAR | medio | S | backend | Test: personal ajeno no visible salvo manage |
| **T4.9** | Unificar persistencia (eliminar cache 365d) uploading/tickets-general | SettingsController, TicketGeneral | MEJORAR | medio | M | backend | Test: index muestra valor BD tras cache:clear |
| **T4.10** | Test LibreTranslate en translate-settings (no solo DeepL) | TranslateSettingsController::test | MEJORAR | medio | M | backend | Test: rama provider libretranslate con Http::fake |
| **T4.11** | Botón "Purgar ahora"/dry-run email-log + fix `clearPrefixCache` | EmailLogSettingsController | MEJORAR | bajo | S | backend | Test: dry-run devuelve conteo; clearPrefixCache eliminado/corregido |
| **T4.12** | API tokens: selección de abilities (no `*` por defecto) + Form Request | ApiTokensController, tokens.blade | MEJORAR | medio | M | security | Test: token creado con scope mínimo; aislamiento por usuario |
| **T4.13** | Validación semántica JSON conditions/actions (campos/operadores/IDs) | Store/Update requests macros/automations/workflows | MEJORAR | medio | M | backend | Test 422: type/op fuera de catálogo o ID inexistente |
| **T4.14** | Migrar `fetch()` → jQuery `$.ajax` + toastr success | email, social-integrations, ai-agent, translate | MEJORAR | bajo | M | frontend | grep `fetch(` en settings = 0; feedback toastr |
| **T4.15** | Verificar consumo de flags (notify_*, daily_digest, feature_*, virus_scan) | notifications, features, uploading | CORREGIR | medio | M | backend | Auditar wiring; reportar/eliminar toggles sin efecto |
| **T4.16** | Edición (update) de shifts/vacations/oncall en schedule | ScheduleController, vistas | IMPLEMENTAR | medio | M | backend | Test: update turno persiste |
| **T4.17** | `bulk-action` en listados de mayor uso | statuses, brands, companies, inboxes, webhooks, slack, etc. | IMPLEMENTAR | bajo | M | backend | Test: bulk delete/activate con payload `{action,ids}` |
| **T4.18** | `distinct` en fields.*.key pre-chat; autogenerar `order` custom-fields | StorePreChatFormRequest, CustomField | MEJORAR | bajo | S | backend | Test 422 claves duplicadas; order autoincrement |
| **T4.19** | Verificación de uso antes de borrar (custom-fields) + SoftDeletes | CustomFieldsController/modelo | MEJORAR | medio | S | backend | Test: no borra campo en uso |

### FASE 5 — Cobertura de tests (feature tests por panel)

> Regla: PHPUnit + `DatabaseTransactions` (no `RefreshDatabase`), factories, seeding de permisos en `setUp()`, asegurar conexión `helpdesk` activa en el entorno de test.

| ID | Título | Paneles | Tipo | Sev | Esf | Agente | Criterio |
|---|---|---|---|---|---|---|---|
| **T5.1** | Tests shell + navegación | layout, nav-sidebar, SettingsController | TESTEAR | alto | M | testing | Itera sidebar→Route::has; render 3 paneles 200 |
| **T5.2** | Tests taxonomía | statuses, tags, attributes, custom-fields, views | TESTEAR | alto | L | testing | CRUD + 403/422 + reorder + toggle por panel |
| **T5.3** | Tests equipo/agenda | team, agent-settings, skills, business-hours, routing-rules | TESTEAR | alto | L | testing | CRUD + 403/422 + persistencia límite/horario |
| **T5.4** | Tests SLA/reglas | sla-policies, automation-rules, workflows | TESTEAR | alto | M | testing | store persiste uid+horas; toggle; 422 JSON |
| **T5.5** | Tests respuestas | macros, canned-replies, lookup | TESTEAR | alto | M | testing | visibility persiste; canned create; lookup solo agentes |
| **T5.6** | Tests email/notif/uploading/features | los 5 paneles | TESTEAR | alto | M | testing | index muestra valor guardado (caza bug prefijo); 403/422 |
| **T5.7** | Tests canales/integraciones | inboxes, slack, webhooks, integrations, social-integrations | TESTEAR | alto | L | testing | CRUD + secret cifrado/no expuesto + test conexión |
| **T5.8** | Tests marketing | broadcasts, drip, banners, surveys, whatsapp-templates | TESTEAR | medio | L | testing | store/dispatch (Queue::fake) + 403/422 |
| **T5.9** | Tests marcas/compliance/tokens | brands, companies, status-page, audits, gdpr, profile/tokens | TESTEAR | medio | L | testing | CRUD + 403 (caza permisos rotos) + export GDPR |
| **T5.10** | Tests HelpdeskTickets settings | general, macros, automations | TESTEAR | alto | M | testing | index carga valores; CRUD + 403/422 |
| **T5.11** | Tests Livechat/Social/AI-agent | livechat, pre-chat-forms, social-general, ai-agent-settings | TESTEAR | alto | M | testing | persistencia visible + 403/422 |

---

## 4. Tabla de paneles

| Panel | Módulo | Estado | Sev máx | Fase principal |
|---|---|---|---|---|
| layout (shell) | Helpdesk | broken | high | F1 (T1.1) |
| nav-sidebar (NavService) | Helpdesk | partial | critical | F2 (T2.1/T2.2) |
| SettingsController/uploading | Helpdesk | working | medium | F0 (T0.1) / F4 |
| statuses | Helpdesk | broken | critical | F0 (T0.8) |
| tags | Helpdesk | working | low | F4/F5 |
| attributes | Helpdesk | partial | high | F0 (T0.15/T0.25) |
| custom-fields | Helpdesk | broken | critical | F0 (T0.23) |
| views | Helpdesk | partial | high | F0 (T0.19) |
| team (members) | Helpdesk | partial | high | F0 (T0.20)/F4.4 |
| team (groups/member-edit) | Helpdesk | broken | critical | F0 (T0.20) |
| agent-settings | Helpdesk | working | medium | F4.3 |
| skills | Helpdesk | broken | critical | F0 (T0.23) |
| business-hours | Helpdesk | partial | medium | F0 (CC-6)/F4.5 |
| routing-rules | Helpdesk | broken | critical | F0 (T0.17) |
| sla-policies | Helpdesk | broken | critical | F0 (T0.6) |
| automation-rules | Helpdesk | partial | high | F0 (CC-6)/F4.13 |
| workflows | Helpdesk | working | medium | F4.13/F5 |
| macros (core) | Helpdesk | broken | critical | F0 (T0.9) |
| canned-replies | Helpdesk | broken | critical | F0 (T0.7) |
| lookup | Helpdesk | working | medium | F4.7 |
| email | Helpdesk | broken | critical | F0 (T0.1/T0.22) |
| email-accounts | Helpdesk | broken | critical | F3 (T3.4) |
| notifications | Helpdesk | broken | critical | F0 (T0.1)/F4.15 |
| features | Helpdesk | working | medium | F4.15/F5 |
| inboxes | Helpdesk | broken | critical | F0 (T0.10/T0.14) |
| integrations | Helpdesk | working | medium | F4 (CC-6) |
| social-integrations | Helpdesk | partial | medium | F3.7/F4.14 |
| slack-integrations | Helpdesk | working | low | F4/F5 |
| webhooks | Helpdesk | broken | critical | F0 (T0.2/T0.14/T0.15) |
| broadcasts | Helpdesk | partial | high | F0 (T0.23/T0.24) |
| drip-campaigns | Helpdesk | partial | high | F0 (T0.23/T0.24)/T3.10 |
| banners | Helpdesk | working | medium | F4 (CC-6)/F1.7 |
| surveys | Helpdesk | partial | medium | F3.6/CC-6 |
| whatsapp-templates | Helpdesk | broken | critical | F0 (T0.16) |
| brands | Helpdesk | working | medium | F2.2/F5 |
| companies | Helpdesk | working | medium | F2.2/F5 |
| status-page | Helpdesk | broken | critical | F0 (T0.3) |
| audits | Helpdesk | working | medium | F0 (T0.26)/F5 |
| gdpr | Helpdesk | broken | critical | F0 (T0.4) |
| ai (settings) | Helpdesk | missing | high | F2.3/F3.2 |
| ai-agents | Helpdesk | stub | medium | F2.3/F3.2 |
| profile/tokens | Helpdesk | working | medium | F4.12 |
| general (tickets) | HelpdeskTickets | broken | critical | F0 (T0.18) |
| macros (tickets) | HelpdeskTickets | partial | critical | F0 (T0.26)/T3.3 |
| automations (tickets) | HelpdeskTickets | partial | critical | T3.3 |
| livechat | HelpdeskLivechat | partial | high | F0 (T0.13)/F1.2 |
| pre-chat-forms | HelpdeskLivechat | working | low | F4.18/F5 |
| schedule | HelpdeskAgents | broken | critical | F0 (T0.11) |
| ai-agent-settings | HelpdeskAgents | partial | critical | F0 (T0.12)/T3.8 |
| social-general | HelpdeskSocial | broken | critical | F0 (T0.5)/T3.1 |
| translate-settings | HelpdeskTranslate | working | medium | F4.10/F5 |
| email-log-settings | HelpdeskEmailLog | working | low | F4.11/F5 |

---

## 5. Orden de ejecución recomendado y dependencias

### Secuencia macro
```
FASE 0 (bloqueante) → FASE 2 (nav) ┐
                    → FASE 1 (UI)  ┘→ FASE 3 → FASE 4 → FASE 5 (continua, por panel ya tocado)
```

**FASE 0 es bloqueante:** ningún panel es confiable hasta resolver bugs críticos. Dentro de F0, priorizar por nº de paneles afectados/exposición:

1. **Bloque A (paralelizable, agente backend):** T0.1 (prefijo, desbloquea 4 paneles), T0.2 (webhooks 500), T0.16 (whatsapp), T0.17 (routing-rules), T0.11 (schedule), T0.18 (tickets-general). Sin dependencias entre sí (archivos distintos).
2. **Bloque B (paralelizable, agente security):** T0.3, T0.4, T0.5, T0.15, T0.20, T0.21, T0.14. Tocan permisos/seguridad; T0.15 y T0.20 dependen de conocer el catálogo de permisos (hacer antes una pasada de CC-2).
3. **Bloque C (paralelizable, agente backend/database):** T0.6+T0.7 (esquema SLA/canned — database primero, luego backend), T0.8, T0.9, T0.10, T0.12, T0.19, T0.24.
4. **Bloque D (paralelizable, agente frontend):** T0.13, T0.23, T0.25, T0.26. Independientes de los demás bloques.

> **Dependencia clave:** T0.7 (migración canned) y T0.6 (esquema SLA) deben ir **antes** de sus tests F5.5/F5.4. T0.1 debe ir **antes** de F5.6 (los tests de email/notif/uploading validan precisamente el fix de prefijo).

**FASE 2 (nav)** puede arrancar en paralelo con F1, pero **T2.2 depende de T0.21** (eliminar enlace ai muerto) y de que los paneles enlazados ya no devuelvan 500 (depende de Bloques A–C de F0). El test de coherencia de sidebar (T2.2) **debe correr al final de F0+F2**.

**FASE 1 (UI/marca)** es independiente de la lógica; puede ejecutarse en paralelo a F0 por un agente frontend dedicado, EXCEPTO T1.1 (eliminar layout huérfano) que debe confirmarse tras decidir el shell canónico (relacionado con T2.x). T1.2/T1.3/T1.4 son batch grandes paralelizables por módulo.

**FASE 3** depende de F0 (paneles estables). T3.3 (solapamiento tickets/core) es la decisión arquitectónica más pesada → **requiere ADR primero** y bloquea el builder visual T3.5 para esos paneles. T3.1 (persistencia social) habilita los tests F5.11. T3.4 (email-accounts) es independiente.

**FASE 4** es la cola larga; cada tarea es autónoma y paralelizable por panel. Priorizar T4.4 (enum assignment_mode, alto impacto visible), T4.6 (colisión tabla views, riesgo de datos) y T4.15 (verificar que los toggles tienen efecto real, para no mantener stubs).

**FASE 5** no es una fase final aislada: **cada tarea de F0–F4 termina con su test** (requisito del usuario). Las tareas T5.x agrupan la cobertura sistemática por grupo y se ejecutan **inmediatamente después** del bloque funcional correspondiente, no al final. Un agente `testing` puede ir cerrando T5.x conforme los agentes backend/frontend liberan cada panel.

### Paralelización máxima sugerida (4 agentes simultáneos)
- **Agente backend** → F0 Bloque A + C → F3 lógica → F4 backend.
- **Agente security** → F0 Bloque B + auditoría CC-2 → F4.12/F4.2.
- **Agente frontend** → F0 Bloque D → F1 completa → F3.5/F3.6/F3.8 → F4.14.
- **Agente testing** → arranca tras primer panel cerrado; F5.x en cadena, validando cada fix.

**Hito de "settings confiables":** completar FASE 0 + T2.2 + tests F5.1/F5.2/F5.6/F5.7 (los grupos con más bugs críticos). A partir de ahí el sistema es usable en producción y el resto (F1 estética, F4 mejoras, F3 features) es incremental sin bloqueo.
