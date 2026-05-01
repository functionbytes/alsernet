# Helpdesk — Handoff para próxima sesión

> Documento de continuidad. Lee esto primero antes de tocar código.
> Última actualización: 2026-05-01 19:30
> Working directory: `/Users/developerts/Herd/system`

---

## 1. Contexto en 60 segundos

**Stack**: Laravel 12 + PHP 8.4 + MariaDB + Redis + Laravel Reverb (WebSocket) + Horizon (queues) + Bootstrap 5.3 + jQuery + Cloudflare tunnel.

**Módulo**: `modules/Helpdesk` (nwidart/laravel-modules). Conexión DB separada `helpdesk` para casi todas sus tablas.

**Producto**: helpdesk multi-canal nivel Intercom/Zendesk con:
- Mensajería real-time FB/IG/WhatsApp/Widget/Email
- AI assist (sugerencias, resumen, traducción, transcripción audio, sentimiento, auto-tag)
- Dashboard live, kanban, reports (heatmap, trends, agents)
- Workflow builder, macros, drip campaigns, broadcasts
- CSAT/NPS, audit log, GDPR, 2FA
- 117 tablas, 346 archivos PHP en el módulo, 136 migraciones

**Estado real**: ~60% UI completa, ~40% solo backend (controllers/vistas/rutas faltantes).

---

## 2. Documentos de referencia (LEE PRIMERO)

| Documento | Contenido |
|---|---|
| `modules/Helpdesk/docs/ROADMAP.md` | Roadmap completo de las 15 fases con estado ✅/📝 |
| `modules/Helpdesk/docs/SOCIAL-CHANNELS-SETUP.md` | Setup Meta + Cloudflare tunnel |
| `modules/Helpdesk/docs/AI-SETUP.md` | OpenAI + DeepL + costos |
| `modules/Helpdesk/docs/CAMPAIGNS.md` | Broadcasts + WhatsApp HSM + Drip |
| `modules/Helpdesk/docs/COMPLIANCE.md` | GDPR + 2FA + PII masking |
| `claude.md` (raíz) | Convenciones del proyecto |

---

## 3. Convenciones críticas (no negociables)

- **Bootstrap 5.3 + Font Awesome 6 + jQuery + AJAX**. NUNCA Livewire / Inertia / React / Tabler Icons.
- **Conexión DB**: tablas helpdesk usan `protected $connection = 'helpdesk'`. La tabla `users` y `sessions` usan default.
- **Migraciones**: con `Schema::connection($this->connection)->...`.
- **Permisos Spatie**: `helpdesk.{entity}.{action}` (lowercase).
- **Form Requests** SIEMPRE para validación (no inline `$request->validate`).
- **Routes**: prefix `panel/helpdesk/` para manager, `panel/settings/helpdesk/` para settings, `api/v1/helpdesk/` para REST API, `api/helpdesk/webhooks/` para webhooks Meta.
- **Color primario**: `#b10100` (rojo Alvarez).
- **Section titles**: capitalize primera palabra solo (`Información básica` no `Información Básica`).
- Después de cambios: `php artisan optimize:clear` (siempre) + `composer dump-autoload` (solo si nuevas clases) + `supervisorctl restart horizon:horizon_00` (si tocaste jobs/services).

---

## 4. Estado de implementación

### ✅ Funciona end-to-end (validado con Chrome DevTools)

**Manager**:
- `/panel/helpdesk` (root)
- `/panel/helpdesk/conversations` con typing, read receipts, reactions, attachments
- `/panel/helpdesk/conversations/kanban` con drag&drop SortableJS
- `/panel/helpdesk/customers`
- `/panel/helpdesk/dashboard/live` (auto-refresh 10s)
- `/panel/helpdesk/dashboard/live/metrics` (JSON)
- `/panel/helpdesk/reports/heatmap`, `/agents`, `/trends`

**Settings CRUDs**:
- `/panel/settings/helpdesk` `/livechat` `/ai` `/notifications` `/social-integrations`
- `/canned-replies` `/automation-rules` `/webhooks` `/macros` `/business-hours`
- `/banners` `/surveys` `/pre-chat-forms` `/status` `/audits`
- `/routing-rules` `/email-accounts`

**Públicos**:
- `/status` (status page)
- `/portal/login` (customer portal con magic link)
- `/helpcenter`
- `/helpdesk/health` (devuelve `status:ok` con todos los checks verdes)

**Features UX manager** (todas en `inbox-v4.js`):
- Dark mode toggle (storage `bv:theme:dark`)
- Modo concentración con SLA timer
- Sonido sintetizado AudioContext (storage `bv:sound:enabled`)
- Push notifications nativas (storage `bv:notif:enabled`)
- Badge favicon dinámico
- Atajos teclado (Ctrl+Enter / R / J/K / / / Esc / ?)
- Voice notes con MediaRecorder

**Broadcasting**:
- Echo conectado a Reverb (`system.test:8090`)
- Canal `private-helpdesk.inbox` (global) + `private-helpdesk.conversation.{id}` (por conv)
- Eventos `ConversationMessageCreated` y `SlaBreached` con `ShouldBroadcastNow`

### 📝 Backend creado pero SIN UI (pendiente próxima sesión)

| Feature | Modelo | Servicio | Migración | Controller admin | Vista | Ruta |
|---|---|---|---|---|---|---|
| **Agents settings (vacation, max_open)** | ✅ `AgentSettings` | ✅ `AgentAssignmentService` | ✅ | ❌ | ❌ | ❌ |
| **2FA TOTP** | ✅ (cols en users) | ✅ `TwoFactorService` | ✅ | ✅ `Compliance/TwoFactorController` | ❌ | ❌ |
| **GDPR export/delete** | ✅ | ✅ `GdprExportService` `GdprDeletionService` | — | ✅ `Compliance/GdprController` | ❌ | ❌ |
| **Companies** | ✅ `Company` | — | ✅ | ❌ | ❌ | ❌ |
| **Skills** | ✅ `Skill` (+ pivots) | ✅ `SkillsRoutingService` | ✅ | ❌ | ❌ | ❌ |
| **Brands** | ✅ `Brand` | — | ✅ | ❌ | ❌ | ❌ |
| **Custom Fields** | ✅ `CustomField` `CustomFieldValue` | — (trait `HasCustomFields`) | ✅ | ❌ | ❌ | ❌ |
| **Workflows visuales** | ✅ `Workflow` `WorkflowRun` | ✅ `WorkflowEngine` | ✅ | ❌ | ❌ | ❌ |
| **AI Agents autónomos** | ✅ `AiAgent` | ✅ `AI/AiAgentService` | ✅ | ❌ | ❌ | ❌ |
| **Drip Campaigns** | ✅ `DripCampaign` `DripStep` `DripExecution` | ✅ `DripService` | ✅ | ❌ | ❌ | ❌ |
| **Broadcasts** | ✅ `Broadcast` `BroadcastRecipient` | ✅ `Campaigns/BroadcastService` | ✅ | ❌ | ❌ | ❌ |
| **WhatsApp Templates** | ✅ `WhatsAppTemplate` | ✅ `WhatsAppHsmService` (existente) | ✅ | ❌ | ❌ | ❌ |
| **Slack integrations** | ✅ `SlackIntegration` | ✅ `SlackNotificationService` | ✅ | ❌ | ❌ | ❌ |
| **Outgoing webhooks** | ✅ `OutgoingWebhook` `OutgoingWebhookLog` | ✅ `OutgoingWebhookService` | ✅ | ❌ | ❌ | ❌ |
| **Side conversations** | ✅ `SideConversation` `SideConversationMessage` | ✅ `SideConversationService` | ✅ | ❌ (endpoint JSON sí, UI no) | ❌ | ❌ |
| **Live visitor tracking** | ✅ `WidgetSession` `WidgetPageView` | — | ✅ | ❌ | ❌ | ❌ |
| **Team leaderboard** | — (queries directas) | — | — | ❌ | ❌ | ❌ |
| **Customer journey/insights UI** | — (queries) | ✅ `CustomerInsightsService` | — | ✅ `CustomerInsightsController` (JSON only) | ❌ panel | parcial |
| **Knowledge Base UI completa** | ✅ `KbCategory` `KbArticle` (verificar) | parcial | ✅ | ⚠️ existente pero limitado | ⚠️ | ⚠️ |

---

## 5. Próxima sesión: cómo continuar

### Plan recomendado para completar el 40% restante

**Wave 1 (alta prioridad)**:
1. **Agent Settings UI** — vacation mode, max_open_conversations, auto_assign toggle por agente. Es muy usado.
2. **2FA setup wizard** — `/2fa/setup` con QR + recovery codes. Crítico para seguridad.
3. **Companies CRUD** — agrupar customers por empresa.
4. **Skills CRUD + asignar a agentes** — habilita skills-based routing.

**Wave 2 (media)**:
5. **Workflow visual builder UI** — el feature más diferenciador. Editor JSON simple OK como MVP.
6. **AI Agents UI** — crear/editar bot, ver métricas de resoluciones.
7. **Drip Campaigns UI** — wizard de pasos con delay.
8. **Broadcasts UI** — wizard de 4 pasos (segment → preview → body/template → send).

**Wave 3 (baja, completar el set)**:
9. **WhatsApp Templates UI** + comando sync.
10. **Slack integrations CRUD** + listeners de eventos.
11. **Outgoing webhooks CRUD** + tester.
12. **Custom Fields CRUD**.
13. **Brands CRUD** (multi-brand).
14. **Live Visitors page** con auto-refresh.
15. **Team Leaderboard** (solo queries + tabla).
16. **GDPR export/delete UI** wired al endpoint existente.

### Patrón a seguir para cada CRUD

```
1. Crear controller en modules/Helpdesk/app/Http/Controllers/Managers/Settings/{Name}Controller.php
   - extends App\Http\Controllers\Controller
   - constructor con $this->middleware('can:helpdesk.{name}.manage')
   - métodos: index, create, store, edit, update, destroy

2. Crear FormRequest en modules/Helpdesk/app/Http/Requests/Settings/Store{Name}Request.php
   - authorize() con permission check
   - rules() con array syntax
   - messages() y attributes() en español

3. Crear vistas en modules/Helpdesk/resources/views/settings/{name}/
   - index.blade.php (tabla con dropdown de acciones, paginación)
   - form.blade.php (parcial reutilizable create+edit)
   - create.blade.php @include form
   - edit.blade.php @include form

4. Registrar rutas en modules/Helpdesk/routes/settings.php:
   Route::prefix('{name}')->name('{name}.')->group(function () {
       Route::get('/', [Controller::class, 'index'])->name('index');
       Route::get('create', [Controller::class, 'create'])->name('create');
       Route::post('/', [Controller::class, 'store'])->name('store');
       Route::get('{model}/edit', [Controller::class, 'edit'])->name('edit');
       Route::put('{model}', [Controller::class, 'update'])->name('update');
       Route::delete('{model}', [Controller::class, 'destroy'])->name('destroy');
   });

5. Crear permission seeder si no existe (Spatie):
   helpdesk.{name}.view, helpdesk.{name}.manage

6. php artisan optimize:clear
7. Smoke test: curl https://system.test/panel/settings/helpdesk/{name} → 200
8. Actualizar ROADMAP.md moviendo de 📝 a ✅
```

### Ejemplo concreto: Agent Settings UI (50 minutos)

```bash
# 1. Crear controller
php artisan make:controller Modules/Helpdesk/app/Http/Controllers/Managers/Settings/AgentSettingsController

# 2. Métodos: index (lista users con AgentSetting + skills), edit, update
#    No hay create/destroy (un AgentSetting por user, autocreado al asignar primer rol)

# 3. Vista index: tabla de agentes con columnas:
#    [Avatar+Nombre] [Rol] [Disponible toggle] [Max open] [Vacaciones hasta] [Skills (chips)] [Editar]

# 4. Vista edit: form con:
#    is_available checkbox, max_open_conversations number, vacation_until date, skills multi-select

# 5. Ruta:
Route::prefix('agents')->name('agents.')->group(function () {
    Route::get('/', [AgentSettingsController::class, 'index'])->name('index');
    Route::get('{user}/edit', [AgentSettingsController::class, 'edit'])->name('edit');
    Route::put('{user}', [AgentSettingsController::class, 'update'])->name('update');
});
```

---

## 6. Configuración de entorno

### Variables `.env` necesarias para todo funcione

```env
# Core
APP_URL=https://system.test
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
HELPDESK_PUBLIC_URL=https://channels.functionbytes.com
HELPDESK_ATTACHMENTS_DISK=public

# Reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=system.test
REVERB_PORT=8090
REVERB_SCHEME=https
REVERB_LOCAL_CERT="/Users/developerts/Library/Application Support/Herd/config/valet/Certificates/system.test.crt"
REVERB_LOCAL_PK="/Users/developerts/Library/Application Support/Herd/config/valet/Certificates/system.test.key"

# Meta (Facebook + Instagram + WhatsApp comparten app)
FACEBOOK_APP_ID=847618268258098
FACEBOOK_APP_SECRET=515ddfe07232cdb5da57338edb99da6f
FACEBOOK_PAGE_ACCESS_TOKEN=EAAMC...
FACEBOOK_VERIFY_TOKEN=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0
INSTAGRAM_APP_ID=847618268258098
INSTAGRAM_APP_SECRET=515ddfe07232cdb5da57338edb99da6f
INSTAGRAM_BUSINESS_ACCOUNT_ID=17841405747428478
INSTAGRAM_ACCESS_TOKEN=EAAMC... (mismo que page token)
INSTAGRAM_VERIFY_TOKEN=823bc73b3e7ed39b47c838113d4b2fae0981d78fb43553c0

# AI (opcional, agregar cuando se quiera activar)
HELPDESK_AI_ENABLED=true
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
DEEPL_API_KEY=...
HELPDESK_AI_AGENT_ENABLED=false

# Compliance
HELPDESK_GDPR_RETENTION_DAYS=90
HELPDESK_REQUIRE_2FA=false
HELPDESK_REQUIRE_2FA_FOR_ADMINS=true

# Slack/Sentry (opcional)
SENTRY_LARAVEL_DSN=
```

### Servicios externos activos

- **Cloudflare tunnel**: `cloudflared tunnel run` (mapea `channels.functionbytes.com` → `system.test`)
- **Supervisor**: `supervisorctl status` muestra horizon, reverb, chat-broadcasts-worker, backups-worker
- **App Meta**: `Functionbytes` (`847618268258098`), modo Desarrollo activo. Agentes IG testers: `cristianesparza`, `functionbytes`
- **Webhook subscriptions**: `page` + `instagram` activas en Meta

---

## 7. Bugs conocidos / gotchas

1. **`composer dump-autoload`** después de crear nuevos modelos en módulos. Si no, "class not found" al instanciar.
2. **`php artisan route:list`** falla con `Class "SubscriberBulkController" does not exist` — bug de OTRO módulo (newsletter), no helpdesk. Usa `grep` en `routes/` para listar rutas hasta que se arregle.
3. **`ConversationRead`** es por conversación (`conversation_id`, `user_id`), NO por item. NO uses `conversation_item_id`.
4. **Modo concentración CSS** asume grid de 4 columnas en `.inbox-v4`. Si el grid cambia, ajustar `inbox-v4.js` línea ~4049.
5. **Reverb broadcasting**: usa `broadcast(new Event)` en lugar de `event(new Event)` cuando el evento es `ShouldBroadcastNow` y necesitas que llegue al canal SÍ o SÍ.
6. **Webhooks Meta**: si los mensajes no llegan, verificar:
   - App está en modo Desarrollo + cuenta IG agregada como Tester
   - Cuenta IG remitente NO es la dueña (echo se filtra)
   - `cloudflared tunnel run` activo
7. **Migraciones que ya corrieron pero no están en log**: si una migración falla parcialmente, la tabla se crea pero el log no se actualiza. Insertar manualmente:
   ```php
   DB::table('migrations')->insert(['migration' => '...', 'batch' => N]);
   ```

---

## 8. Comandos útiles

```bash
# Estado del sistema
php artisan optimize:clear
composer dump-autoload
supervisorctl status
supervisorctl restart horizon:horizon_00
tail -f storage/logs/horizon.log
tail -f storage/logs/reverb.log
tail -f storage/logs/laravel.log

# Health check
curl -sk https://system.test/helpdesk/health | python3 -m json.tool

# Borrar conversaciones de testing (mantiene users)
php artisan tinker --execute='
DB::connection("helpdesk")->statement("SET FOREIGN_KEY_CHECKS=0");
DB::connection("helpdesk")->table("helpdesk_conversation_items")->delete();
DB::connection("helpdesk")->table("helpdesk_conversations")->delete();
DB::connection("helpdesk")->table("helpdesk_customers")->where(function($q) {
    $q->whereNotNull("facebook_psid")->orWhereNotNull("instagram_id")->orWhereNotNull("whatsapp_phone");
})->delete();
DB::connection("helpdesk")->statement("ALTER TABLE helpdesk_conversations AUTO_INCREMENT = 1");
DB::connection("helpdesk")->statement("ALTER TABLE helpdesk_conversation_items AUTO_INCREMENT = 1");
DB::connection("helpdesk")->statement("SET FOREIGN_KEY_CHECKS=1");
'

# Validar canales Meta están suscritos
APP_TOKEN="847618268258098|515ddfe07232cdb5da57338edb99da6f"
curl -s "https://graph.facebook.com/v19.0/847618268258098/subscriptions?access_token=${APP_TOKEN}" | python3 -m json.tool

# Smoke test endpoints helpdesk
for url in /panel/helpdesk/conversations /panel/helpdesk/dashboard/live /panel/helpdesk/conversations/kanban /panel/settings/helpdesk /helpdesk/health /status /portal/login; do
    code=$(curl -sk -L -o /dev/null -w "%{http_code}" "https://system.test$url")
    echo "$code $url"
done

# Backup BD a Desktop
mysqldump -h localhost -u root -p'6cWRY1PUmiwYciQxJXkg' --single-transaction --quick --routines --triggers system > "/Users/developerts/Desktop/system_backup_$(date +%Y%m%d_%H%M%S).sql"
```

---

## 9. Tabla de archivos clave

| Archivo | Qué hace |
|---|---|
| `modules/Helpdesk/app/Jobs/ProcessSocialWebhookJob.php` | Procesa webhooks Meta (texto, attachments, status events) |
| `modules/Helpdesk/app/Jobs/DownloadConversationAttachmentsJob.php` | Descarga attachments en background |
| `modules/Helpdesk/app/Services/OutboundMessageService.php` | Envía agente→cliente: text, attachments, sender actions, quick replies |
| `modules/Helpdesk/app/Services/AI/*` | 7 servicios AI (suggest, summary, sentiment, auto-tag, translate, transcribe, agent) |
| `modules/Helpdesk/app/Events/ConversationMessageCreated.php` | Evento broadcast principal (canales `helpdesk.conversation.{id}` + `helpdesk.inbox`) |
| `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php` | Manager: thread, lista, attachments, send-email, send-hsm, snooze... (~1700 líneas) |
| `modules/Helpdesk/app/Http/Controllers/Managers/ConversationMessagesController.php` | Mensajes API: store, mark-read, typing, ai/suggest |
| `modules/Helpdesk/app/Http/Controllers/Managers/LiveDashboardController.php` | Dashboard live + métricas JSON |
| `modules/Helpdesk/app/Http/Controllers/HealthController.php` | `/helpdesk/health` |
| `public/vendor/helpdesk/inbox-v4.js` | JS principal del manager (~4200 líneas, IIFE) |
| `public/vendor/helpdesk/inbox-v4-dark.css` | Dark mode overrides |
| `public/vendor/helpdesk/voice-notes.js` | Grabación voz con MediaRecorder |
| `public/vendor/helpdesk/onboarding-tour.js` | Tour Shepherd.js primer login |
| `modules/Helpdesk/resources/views/managers/inbox/index.blade.php` | Layout principal manager |
| `modules/Helpdesk/resources/views/managers/inbox/partials/thread.blade.php` | Render thread + composer |
| `modules/Helpdesk/resources/views/managers/inbox/partials/right-panel.blade.php` | Panel derecho con tabs (General, Pedidos, Archivos, Tickets, Anteriores, Actividad) |
| `modules/Helpdesk/routes/managers.php` | Rutas manager (panel/helpdesk/*) |
| `modules/Helpdesk/routes/settings.php` | Rutas settings (panel/settings/helpdesk/*) |
| `modules/Helpdesk/routes/public.php` | Status, health, helpcenter, surveys públicos |
| `modules/Helpdesk/routes/portal.php` | Customer portal |
| `modules/Helpdesk/routes/api.php` | REST API v1 |
| `modules/Helpdesk/routes/widget.php` | API widget público |
| `modules/Helpdesk/routes/webhooks.php` | Webhooks Meta entrantes |
| `modules/Helpdesk/app/Providers/RouteServiceProvider.php` | Carga todos los archivos de rutas |
| `modules/Helpdesk/app/Providers/HelpdeskServiceProvider.php` | Comandos scheduled, listeners, etc. |

---

## 10. Test users y datos de demo

- Admin: `admin@sistema.test` (verifica password en seeder)
- Customer Instagram demo: external_sender_id `7466330370097736` (Cristian Esparza)
- Conversación demo: id=1 (Instagram, en estado Activo)

---

## 11. Si algo está roto al empezar

```bash
# 1. Verificar que servicios están UP
supervisorctl status

# 2. Tunnel
ps aux | grep cloudflared
# Si no corre: cloudflared tunnel run <tunnel-name>

# 3. Caches
php artisan optimize:clear

# 4. Migraciones
php artisan migrate

# 5. Health
curl -sk https://system.test/helpdesk/health | python3 -m json.tool
# Debe responder status:ok con todos los checks verdes

# 6. Manager
curl -sk -L -o /dev/null -w "%{http_code}\n" https://system.test/panel/helpdesk/conversations
# 200 (con sesión) o 302 a /login (sin sesión)
```

---

## 12. Últimos cambios (sesión 2026-05-01)

- ✅ Implementado todo el helpdesk de cero hasta nivel enterprise
- ✅ 27 features Fase 1-3 entregadas
- ✅ 30+ features Fase 4-10 (AI, workflow, customer 360, compliance, campaigns, voice notes, dark mode, kanban, PWA)
- ✅ Fase 11-15 backend creado (email, KB, workflows, custom fields, companies, skills, brands, surveys, status page, banners, REST API, webhooks salientes, Sentry, side conv, live tracking, AI agent autónomo)
- ✅ Validación E2E con Chrome DevTools de las features visibles
- ✅ 10 bugs encontrados y arreglados (ver sección 7)
- 📝 16 endpoints admin con backend completo pero falta UI

---

## 13. Próxima sesión: prompt sugerido para el siguiente Sonnet

```
Continúa el proyecto Helpdesk en /Users/developerts/Herd/system.

Lee modules/Helpdesk/docs/HANDOFF.md y modules/Helpdesk/docs/ROADMAP.md.

El sistema tiene 117 tablas y ~60% de UI completa. Los servicios y modelos
existen pero faltan los CRUDs admin de 16 features (ver sección 4 del HANDOFF).

Empieza por Wave 1 (alta prioridad):
1. Agent Settings UI
2. 2FA setup wizard
3. Companies CRUD
4. Skills CRUD

Sigue el patrón en sección 5 del HANDOFF. Cada CRUD: controller +
FormRequest + 4 vistas (index/create/edit/form partial) + 6 rutas + permiso
Spatie + smoke test + actualizar ROADMAP.

Convenciones (sección 3 del HANDOFF) son OBLIGATORIAS: Bootstrap 5.3,
Font Awesome 6, jQuery, conexión 'helpdesk' en migraciones, etc.

Después de cada CRUD: php artisan optimize:clear + smoke test + commit.
```
