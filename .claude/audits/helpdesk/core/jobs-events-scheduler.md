# Auditoría core Helpdesk — Jobs, eventos, listeners & scheduler

> Fecha: 2026-06-29 · Health score: 47/100 · Estado: half-wired (lógica buena, cableado de colas roto)

**Resumen:** El *interior* de los jobs/listeners está bien hecho (todos `ShouldQueue`, con `$tries`/`$timeout`/`failed()`, guardas `fresh()`/`find()` y `WithoutOverlapping` en el inbound de email), pero el *cableado* entre las colas que se usan y los supervisores de Horizon está roto: **4 colas del core (`helpdesk`, `helpdesk-events`, `notifications-high`, `helpdesk-broadcasts`) no tienen ningún supervisor**, y precisamente concentran el grueso del trabajo asíncrono (logging de actividad, workflows, auto-asignación, agente IA, sentiment, auto-tag, mensajes programados, unsnooze, broadcasting al inbox, notificaciones de escalación/mención). Diagnóstico: en producción con Horizon+Redis esos jobs entran a Redis y **nadie los consume nunca** (no hay fallback `sync`: `QUEUE_CONNECTION=redis`). Además el cron `helpdesk:purge-old-gdpr-deletes` está programado pero el comando **no está registrado** → error diario y la purga GDPR prometida ("90 días") nunca se ejecuta; y dos crons (`check-sla`, `process-broadcasts`) corren sin `withoutOverlapping()`/`onOneServer()`. Prioridad absoluta: alinear nombres de cola con los supervisores (o crear los supervisores faltantes), registrar los comandos huérfanos y blindar el scheduler.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| JOBS-01 | Crítica | wiring | `config/horizon.php` ↔ Jobs/Listeners (`onQueue('helpdesk')`, `$queue='helpdesk-events'`) | [CONFIRMADO] | M | Colas `helpdesk` y `helpdesk-events` sin supervisor: ~18 jobs/listeners nunca se procesan en prod |
| JOBS-02 | Crítica | compliance/wiring | `Providers/HelpdeskServiceProvider.php:341` + `:327-331` | [CONFIRMADO] | S | `helpdesk:purge-old-gdpr-deletes` programado pero NO registrado → cron falla y la purga GDPR nunca corre |
| JOBS-03 | Alta | wiring | `Notifications/EscalationNotification.php:19`, `Notifications/MentionNotification.php:23`, `Listeners/SendEscalationNotification.php`, `SendMentionNotification.php` | [CONFIRMADO] | S | Cola `notifications-high` huérfana → escalaciones y menciones nunca se entregan |
| JOBS-04 | Alta | scheduler | `Providers/HelpdeskServiceProvider.php:335-336` | [CONFIRMADO] | S | `check-sla` (5 min) y `process-broadcasts` (1 min) sin `withoutOverlapping()`/`onOneServer()` → doble disparo multi-servidor |
| JOBS-05 | Alta | wiring | `Console/Commands/CleanupAgentPresence.php:10` (+ provider) | [CONFIRMADO] | S | `helpdesk:agents:cleanup-presence` ni registrado ni programado → agentes quedan "online" indefinidamente |
| JOBS-06 | Alta | performance | `Observers/ConversationItemLinkPreviewObserver.php:36` + `Services/LinkPreviewService.php:47` | [CONFIRMADO] | M | Observer hace HTTP síncrono (timeout 6 s, hasta 2 MB) en el hilo de la petición del agente |
| JOBS-07 | Media | bug/wiring | `Services/Campaigns/BroadcastService.php:7,63` | [CONFIRMADO] | M | `BroadcastService` referencia clase inexistente `SendBroadcastMessageJob` + cola `helpdesk-broadcasts` huérfana (código muerto, fatal latente) |
| JOBS-08 | Media | idempotencia | `Jobs/ProcessSocialWebhookJob.php:178-206,242-296,316-359` | [CONFIRMADO] | M | Creación multi-escritura (Customer+Conversation+Item) sin `DB::transaction()` → estado parcial al reintentar |
| JOBS-09 | Media | idempotencia | `Jobs/ProcessDripStepJob.php:54-69` | [CONFIRMADO] | M | Sin guarda de idempotencia: reintento tras envío reenvía el mismo paso (mensaje duplicado al cliente) |
| JOBS-10 | Media | performance | `Events/ConversationMessageCreated.php:12`, `ConversationItemCreated`, `AgentPresenceChanged`, `HelpdeskInboxUpdated` (`ShouldBroadcastNow`) | [CONFIRMADO] | M | 4 eventos difunden síncronos en peticiones web → latencia/bloqueo si Reverb está lento o caído |
| JOBS-11 | Media | wiring | `Observers/ConversationItemLinkPreviewObserver.php:46-48` | [CONFIRMADO] | S | Observer re-emite `MessageReceived` para todo item no interno (incl. agente y desde jobs) → sobre-notificación / workflows de más |
| JOBS-12 | Media | performance | `Jobs/SendBroadcastJob.php:20,33-85,142-144` | [CONFIRMADO] | M | Envía emails inline en bucle (`Mail::html`) con `tries=1` → un destinatario lento bloquea todo el lote (timeout 300 s) |
| JOBS-13 | Baja | conventions | Todos los `Listeners/*.php` encolados | [CONFIRMADO] | S | Listeners encolados sin `$timeout` (y la mayoría sin `$backoff`) |
| JOBS-14 | Baja | quality | `Console/Commands/CleanupOldCommunications.php:11,40` | [CONFIRMADO] | S | Comando mal ubicado (namespace `returns:`, `App\Models\ReturnCommunication`) + usa `confirm()` interactivo (no programable) |
| JOBS-15 | Baja | scheduler | `Console/Commands/FetchEmailTicketsCommand.php:13` (+ provider) | [CONFIRMADO] | S | `helpdesk:fetch-emails` registrado pero nunca programado → poll de email entrante no automatizado |
| JOBS-16 | Baja | wiring | `config/horizon.php` (`supervisor-helpdesk`) | [CONFIRMADO] | S | `supervisor-helpdesk` vigila `helpdesk-scheduled/-heavy/-ai/chatflow` que ningún job core usa (capacidad desperdiciada / nombres equivocados) |
| JOBS-17 | Baja | quality | `Listeners/LogToDatabase.php` | [CONFIRMADO] | S | Listener no registrado en el `EventServiceProvider` del módulo (registro poco claro; corre síncrono en cada log ≥warning) |
| JOBS-18 | Baja | idempotencia | `Jobs/DispatchWebhookJob.php:52-73` | [CONFIRMADO] | S | En reintentos duplica `WebhookDelivery` y duplica `success/failure_count` |

## Hallazgos detallados

### Crítica

#### JOBS-01 · Colas `helpdesk` y `helpdesk-events` sin supervisor de Horizon — [CONFIRMADO]
- **Evidencia:** `config/horizon.php` (entorno `production`) define supervisores para las colas `default, notifications, webhooks, helpdesk-webhooks, pagespeed, google-sync, reviews-sync, exports, reviews-replies, replies, emails, sla, helpdesk-scheduled, helpdesk-heavy, helpdesk-ai, chatflow, helpdesklivechat, broadcasts, drip, remarketing, remarketing-webhooks`. **No existe** ninguna entrada para `helpdesk` ni `helpdesk-events` (ni en `production` ni en `local`). Sin embargo, el core despacha a esas colas:
  - Cola **`helpdesk`** (`onQueue('helpdesk')` / `$queue='helpdesk'`):
    - `Jobs/RunWorkflowJob.php:24`, `Jobs/SendScheduledMessageJob.php:24`, `Jobs/SyncWhatsAppTemplatesJob.php:23`, `Jobs/UnsnoozeConversationJob.php:24`
    - `Listeners/AutoAssignNewConversation.php`, `TriggerWorkflowsOnConversationCreated.php`, `TriggerWorkflowsOnConversationClosed.php`, `TriggerWorkflowsOnMessageReceived.php`
  - Cola **`helpdesk-events`** (`$queue='helpdesk-events'`):
    - `Jobs/CreateActivityMessageJob.php:28`, `Notifications/ConversationAssignedNotification.php:17`
    - `Listeners/AnalyzeSentimentOnIncoming.php`, `AutoTagFirstMessage.php`, `BroadcastConversationMessage.php`, `HandleWithAiAgent.php`, `LogConversationCreated.php`, `LogConversationUpdated.php`, `LogActivityOnConversationAssigned.php`, `LogActivityOnConversationStatusChanged.php`, `LogActivityOnConversationTagAdded.php`, `LogActivityOnConversationUnsnoozed.php`, `LogActivityOnConversationUpdated.php`, `SendConversationAssignedNotification.php`, `Automation/RunAutomationsOnEventListener.php`
- **Impacto:** `config/queue.php:16` → `QUEUE_CONNECTION` por defecto `redis` (no hay fallback `sync`). En producción con Horizon, todo lo encolado a estas dos colas **entra a Redis y nunca se consume**: no se registra actividad en las conversaciones, los workflows no se ejecutan, la auto-asignación no ocurre, el agente IA y el análisis de sentiment no corren, el auto-tag no aplica, los mensajes programados no se envían, las conversaciones pospuestas no se reactivan, no se sincronizan plantillas de WhatsApp, y `BroadcastConversationMessage` (actualización del inbox en tiempo real) no se emite. Es una caída silenciosa masiva de funcionalidad asíncrona. Los jobs se acumulan en Redis hasta agotar memoria.
- **Causa raíz:** desalineación de nombres. `supervisor-helpdesk` vigila `helpdesk-scheduled/-heavy/-ai/chatflow` — nombres que **ningún job del core usa** (ver JOBS-16). Los jobs usan los nombres "lógicos" `helpdesk`/`helpdesk-events`.
- **Recomendación:** o bien (a) añadir `helpdesk` y `helpdesk-events` a la lista de colas de `supervisor-helpdesk` en `config/horizon.php` (y al `supervisor-local`), o bien (b) renombrar los `onQueue()/$queue` del core a las colas ya supervisadas (`helpdesk-scheduled` para schedulables, `helpdesk-ai` para IA/sentiment, etc.). La opción (a) es la de menor riesgo inmediato. Verificar después con `php artisan horizon:status` y `redis-cli LLEN queues:helpdesk`.
- **Esfuerzo:** M

#### JOBS-02 · `helpdesk:purge-old-gdpr-deletes` programado pero el comando no está registrado — [CONFIRMADO]
- **Evidencia:** `Providers/HelpdeskServiceProvider.php:341-343` programa `$schedule->command('helpdesk:purge-old-gdpr-deletes')->dailyAt('04:00')->onOneServer();`, pero el array `$this->commands([...])` (`:327-331`) solo registra `FetchEmailTicketsCommand, CheckSlaBreaches, ProcessScheduledBroadcasts, PurgeSimulatorConversationsCommand`. `PurgeOldGdprDeletes` (signature `helpdesk:purge-old-gdpr-deletes`, `Console/Commands/PurgeOldGdprDeletes.php:12`) **no aparece**. nwidart no auto-descubre comandos de módulo (`module.json` con `"files": []`; los demás se registran a mano), y no se referencia en `bootstrap/app.php`.
- **Impacto:** doble fallo de cumplimiento GDPR: (1) el scheduler lanza `Command "helpdesk:purge-old-gdpr-deletes" is not defined` cada día a las 04:00; (2) el hard-delete de clientes anonimizados >90 días **nunca se ejecuta** — incumple la promesa explícita al usuario documentada en el propio provider y en `docs/COMPLIANCE.md:57,173`. Los datos personales soft-deleted se retienen indefinidamente.
- **Recomendación:** añadir `PurgeOldGdprDeletes::class` (y `CleanupAgentPresence::class`, ver JOBS-05) al array `$this->commands([...])`. Mantener `onOneServer()`; añadir `withoutOverlapping()`.
- **Esfuerzo:** S

### Alta

#### JOBS-03 · Cola `notifications-high` huérfana — escalaciones y menciones nunca se entregan — [CONFIRMADO]
- **Evidencia:** `Notifications/EscalationNotification.php:19` y `Notifications/MentionNotification.php:23` hacen `$this->onQueue('notifications-high')`; los listeners `SendEscalationNotification` y `SendMentionNotification` están encolados (a su vez en `notifications-high` vía la notificación). `config/horizon.php` no tiene supervisor para `notifications-high` (solo `notifications`).
- **Impacto:** las notificaciones de escalación de conversación y de mención a agentes se encolan a una cola sin worker → nunca llegan al destinatario (database/broadcast/mail). Los agentes no se enteran de menciones ni escalaciones.
- **Recomendación:** añadir `notifications-high` a un supervisor (p. ej. `supervisor-default` junto a `notifications`), o cambiar el `onQueue` a `notifications`.
- **Esfuerzo:** S

#### JOBS-04 · `check-sla` y `process-broadcasts` sin `withoutOverlapping()`/`onOneServer()` — [CONFIRMADO]
- **Evidencia:** `Providers/HelpdeskServiceProvider.php:335-336`:
  ```php
  $schedule->command('helpdesk:check-sla')->everyFiveMinutes();
  $schedule->command('helpdesk:process-broadcasts')->everyMinute();
  ```
  Ninguno usa `withoutOverlapping()` ni `onOneServer()`. Contrasta con el patrón del proyecto en `bootstrap/app.php:77-81` (`tickets:check-sla-breaches`, `alerts:check`, etc. todos con `withoutOverlapping()`).
- **Impacto:** en despliegue multi-servidor, cada servidor corre el scheduler → doble disparo. `process-broadcasts` (cada minuto) marca `status='sending'` y despacha `SendBroadcastJob` sin transacción ni `withoutOverlapping`: dos ejecuciones solapadas pueden leer el mismo broadcast `scheduled` y despacharlo dos veces (envío duplicado a todos los destinatarios). `check-sla` puede emitir `SlaBreached` duplicado y reescribir `sla_warned_at` en ambos nodos.
- **Recomendación:** añadir `->withoutOverlapping()->onOneServer()` a ambos. Para `process-broadcasts`, además envolver el marcado `status='sending'` en `DB::transaction` con bloqueo (`lockForUpdate`).
- **Esfuerzo:** S

#### JOBS-05 · `helpdesk:agents:cleanup-presence` ni registrado ni programado — [CONFIRMADO]
- **Evidencia:** `Console/Commands/CleanupAgentPresence.php:10` define `helpdesk:agents:cleanup-presence` (marca offline a agentes sin heartbeat reciente vía `AgentPresenceService::cleanup()`). No está en `$this->commands([...])` ni en ningún `$schedule->command(...)` del repo (grep confirmado).
- **Impacto:** el comando es inaccesible (ni por cron ni manualmente sin registrarlo) → los agentes que pierden conexión sin emitir "offline" quedan marcados como "online" para siempre. Esto degrada el enrutamiento por presencia y los indicadores de disponibilidad.
- **Recomendación:** registrar el comando y programarlo `->everyMinute()->withoutOverlapping()` (o el intervalo del heartbeat).
- **Esfuerzo:** S

#### JOBS-06 · Observer de link-preview hace HTTP síncrono en el hilo de la petición — [CONFIRMADO]
- **Evidencia:** `Observers/ConversationItemLinkPreviewObserver.php:23-49` corre en el evento `created` de cada `ConversationItem` (registrado en `HelpdeskServiceProvider.php:155`). Para items de agente con URL, llama `LinkPreviewService::previewFromBody()` (`:36`), que en `Services/LinkPreviewService.php:47` hace `Http::timeout(self::FETCH_TIMEOUT_SECONDS)` con `FETCH_TIMEOUT_SECONDS = 6` (`:13`) y `MAX_BYTES = 2 MB` (`:15`). Aunque hay `Cache::remember` 24 h (`:45`), el **primer** mensaje con una URL nueva descarga la página remota de forma síncrona.
- **Impacto:** cuando un agente responde con una URL no cacheada, su petición HTTP se bloquea hasta 6 s (más descarga de 2 MB) antes de devolver respuesta. Peor: ocurre dentro del `created` del modelo, en la ruta de guardado. Es exactamente el anti-patrón "observer con IO pesado síncrono".
- **Recomendación:** mover la generación del preview a un job encolado (`GenerateLinkPreviewJob` en `helpdesk-events`/`helpdesk-webhooks`) que actualice `metadata.link_preview` y re-broadcastee. El observer debe limitarse a despachar el job.
- **Esfuerzo:** M

### Media

#### JOBS-07 · `BroadcastService` referencia clase inexistente + cola `helpdesk-broadcasts` huérfana — [CONFIRMADO]
- **Evidencia:** `Services/Campaigns/BroadcastService.php:7` importa `Modules\Helpdesk\Jobs\Campaigns\SendBroadcastMessageJob` y `:63` hace `SendBroadcastMessageJob::dispatch(...)->onQueue('helpdesk-broadcasts')`. La clase **no existe** en ningún módulo (`find`/`grep "class SendBroadcastMessageJob"` → 0 resultados; no hay subdirectorio `Jobs/Campaigns/`). Además `helpdesk-broadcasts` no tiene supervisor (el supervisor es para `broadcasts`, distinto). `BroadcastService` **no tiene ningún llamador** en el código (solo se referencia a sí mismo y en docs) — es código muerto hoy.
- **Impacto:** latente. Si se cablea `BroadcastService::send()` a un controlador (lo describe `docs/CAMPAIGNS.md`), lanzará `Error: Class "...SendBroadcastMessageJob" not found` en runtime; y aun creándola, despacharía a una cola sin worker. La ruta de broadcast que **sí** funciona es `ProcessScheduledBroadcasts → SendBroadcastJob` (cola `broadcasts`, supervisada).
- **Recomendación:** eliminar `BroadcastService` si está obsoleto, o crear `SendBroadcastMessageJob` y corregir la cola a `broadcasts`. Documentar cuál de las dos rutas de broadcast es la canónica.
- **Esfuerzo:** M

#### JOBS-08 · `ProcessSocialWebhookJob` hace escrituras múltiples sin transacción — [CONFIRMADO]
- **Evidencia:** en `processWhatsApp/processFacebook/processInstagram` (`:178-206`, `:242-296`, `:316-359`) se crean `Customer` (`firstOrCreate`), `Conversation` (`create` vía `findOrCreateConversation`) y `ConversationItem` (`create`) en escrituras separadas sin `DB::transaction()` (confirmado: ningún job del módulo usa `DB::transaction`).
- **Impacto:** si el job falla entre la creación de la conversación y la del item (p. ej. excepción al broadcastear o en una automatización), el reintento (`tries=3`) puede dejar/duplicar estado parcial. La idempotencia está parcialmente cubierta (`isDuplicate()` por `external_id` y `findOrCreateConversation` reusa conversaciones abiertas), pero `Customer`+`Conversation` no son atómicos respecto al item.
- **Recomendación:** envolver la creación Customer→Conversation→Item en `DB::transaction()`, dejando el `broadcast()` y los `DownloadConversationAttachmentsJob::dispatch()` **fuera** de la transacción (after-commit).
- **Esfuerzo:** M

#### JOBS-09 · `ProcessDripStepJob` sin guarda de idempotencia entre envío y avance — [CONFIRMADO]
- **Evidencia:** `Jobs/ProcessDripStepJob.php:54-69`: `sendStep(...)` envía el mensaje y **después** `execution->advance()`. No hay marca de "paso N ya enviado". Si el job se reintenta (`tries=3`) tras un `sendStep` exitoso pero antes/durante `advance()`, el mismo paso se reenvía.
- **Impacto:** el cliente recibe mensajes de drip duplicados ante cualquier reintento de cola.
- **Recomendación:** registrar el paso enviado (p. ej. columna/tabla `drip_step_sent` o `current_step` incrementado de forma idempotente con check) y/o usar `WithoutOverlapping` por `execution_id` con `dontRelease()`.
- **Esfuerzo:** M

#### JOBS-10 · Eventos `ShouldBroadcastNow` difunden de forma síncrona en peticiones web — [CONFIRMADO]
- **Evidencia:** `ConversationMessageCreated` (`:12`), `ConversationItemCreated`, `AgentPresenceChanged`, `HelpdeskInboxUpdated` implementan `ShouldBroadcastNow` (no `ShouldBroadcast`). `ShouldBroadcastNow` difunde **inline**, no por cola.
- **Impacto:** cuando estos eventos se emiten desde un controlador (respuesta de agente, cambio de presencia), la difusión a Reverb ocurre en el hilo de la petición; si Reverb está lento o caído, la petición se bloquea hasta el timeout HTTP. Combinado con JOBS-06, la ruta "agente envía mensaje" puede acumular: link-preview HTTP (6 s) + broadcast síncrono. (Desde jobs ya en cola — `ProcessSocialWebhookJob`, `TranscribeAudioJob`, `DownloadConversationAttachmentsJob` — es aceptable.)
- **Recomendación:** evaluar pasar a `ShouldBroadcast` (cola `broadcasts`/default, ya supervisadas) salvo donde la latencia <100 ms sea imprescindible; o aislar la difusión con timeout corto y tolerancia a fallo.
- **Esfuerzo:** M

#### JOBS-11 · El observer re-emite `MessageReceived` para todo item no interno — [CONFIRMADO]
- **Evidencia:** `Observers/ConversationItemLinkPreviewObserver.php:46-48` hace `broadcast(new MessageReceived($item->conversation, $item->fresh()))` para **cualquier** item con conversación que no sea interno (incluye items de agente y los creados dentro de jobs). `MessageReceived` dispara `SendMessageReceivedNotification` y `TriggerWorkflowsOnMessageReceived`.
- **Impacto:** se generan notificaciones y se disparan workflows también para respuestas salientes de agente y para items creados por jobs, no solo para mensajes entrantes del cliente. Sobre-notificación y disparos de workflow espurios (más carga en colas, ya de por sí huérfanas). El `->fresh()` añade un SELECT extra por item.
- **Recomendación:** acotar la emisión a items entrantes del cliente (sin `user_id`) o separar el evento de "actividad de widget" del de "mensaje recibido del cliente".
- **Esfuerzo:** S

#### JOBS-12 · `SendBroadcastJob` envía emails inline en bucle con `tries=1` — [CONFIRMADO]
- **Evidencia:** `Jobs/SendBroadcastJob.php:20` `tries=1`, `:33-85` itera todos los `BroadcastRecipient` en un solo job (`timeout=300`), y para email hace `Mail::html(...)` **síncrono** dentro del bucle (`:142-144`).
- **Impacto:** un único destinatario lento (API de Meta o SMTP) consume el presupuesto de 300 s y puede tumbar el lote entero; con `tries=1` no hay reintento del job. No hay throttling real por proveedor dentro del job (el throttle 5/s vive en el roto `BroadcastService`, no aquí).
- **Recomendación:** despachar un job por destinatario (o `Bus::batch`) en cola `broadcasts`/`emails`, con backoff y `tries>1`; o al menos encolar los emails vía `SendHelpdeskEmailJob` en lugar de `Mail::html` inline.
- **Esfuerzo:** M

### Baja

#### JOBS-13 · Listeners encolados sin `$timeout` (y la mayoría sin `$backoff`) — [CONFIRMADO]
- **Evidencia:** los 26 listeners encolados definen `$tries` pero **ninguno** define `$timeout`; solo `AutoAssignNewConversation`, `NotifySlackOnSlaBreached` y los `TriggerWorkflows*` definen `$backoff`. La regla del proyecto (`.claude/rules/jobs.md`, `events-listeners.md`) pide `$tries`+`$timeout`+`$backoff`.
- **Impacto:** los listeners heredan el `timeout` del supervisor (60–300 s según cola), inconsistente; sin backoff, los reintentos de listeners que llaman APIs externas (IA, sentiment, Slack) golpean inmediatamente.
- **Recomendación:** añadir `$timeout` y `$backoff` explícitos, especialmente a `HandleWithAiAgent`, `AnalyzeSentimentOnIncoming`, `NotifySlackOnSlaBreached`, `DispatchConversationWebhooks`.
- **Esfuerzo:** S

#### JOBS-14 · `CleanupOldCommunications` mal ubicado y no programable — [CONFIRMADO]
- **Evidencia:** `Console/Commands/CleanupOldCommunications.php:11` usa signature `returns:cleanup-communications` y `App\Models\ReturnCommunication` (modelo del módulo de devoluciones, no Helpdesk), y `:40` usa `$this->confirm(...)` (interactivo). No está registrado ni programado.
- **Impacto:** comando ajeno al dominio Helpdesk viviendo en el módulo equivocado; si se programara, `confirm()` colgaría el scheduler. Hoy es código muerto/mal ubicado.
- **Recomendación:** mover al módulo de devoluciones (o eliminar). Si debe programarse, sustituir `confirm()` por opción `--force`.
- **Esfuerzo:** S

#### JOBS-15 · `helpdesk:fetch-emails` registrado pero nunca programado — [CONFIRMADO]
- **Evidencia:** `FetchEmailTicketsCommand` (`helpdesk:fetch-emails`) sí está en `$this->commands([...])` pero no aparece en ningún `$schedule->command(...)`.
- **Impacto:** si la ingesta de email entrante depende de polling IMAP, no se ejecuta automáticamente. Puede ser intencional si el flujo entrante es por webhook (`ProcessEmailInboundJob`); conviene confirmarlo.
- **Recomendación:** si el polling es el canal previsto, programar `->everyMinute()->withoutOverlapping()`; si es webhook, documentar que el comando es solo manual/diagnóstico.
- **Esfuerzo:** S

#### JOBS-16 · `supervisor-helpdesk` vigila colas que ningún job core usa — [CONFIRMADO]
- **Evidencia:** `config/horizon.php` → `supervisor-helpdesk` `queue => ['helpdesk-scheduled','helpdesk-heavy','helpdesk-ai','chatflow']`. Ningún job/listener del core despacha a esos nombres (grep en `app/` → 0; el único `helpdesk-ai` es un `RateLimiter` HTTP, no una cola).
- **Impacto:** workers reservados a colas vacías mientras `helpdesk`/`helpdesk-events` (las que sí se usan) no tienen worker (raíz de JOBS-01). Capacidad desperdiciada y diagnóstico confuso.
- **Recomendación:** alinear nombres (resolver junto con JOBS-01): renombrar las colas del core a las supervisadas, o sustituir la lista del supervisor por `helpdesk`, `helpdesk-events`.
- **Esfuerzo:** S

#### JOBS-17 · `LogToDatabase` no está registrado en el `EventServiceProvider` del módulo — [CONFIRMADO]
- **Evidencia:** `Listeners/LogToDatabase.php` escucha `Illuminate\Log\Events\MessageLogged` y escribe en `Modules\Activity\Models\ApplicationLog`, pero no figura en el `$listen` de `Providers/EventServiceProvider.php` ni en ningún provider del módulo (grep → 0). Es el único listener no encolado y sin `failed()`.
- **Impacto:** o es código muerto, o se registra fuera del módulo (auto-discovery / app principal); registro poco claro. Corre **síncrono** en cada log ≥ warning, con un INSERT a BD y acceso a `request()` (que en jobs es un Request vacío).
- **Recomendación:** confirmar dónde se registra; documentarlo o eliminarlo. Si se mantiene, considerar batching/cola para no acoplar el logging a la BD en caliente.
- **Esfuerzo:** S

#### JOBS-18 · `DispatchWebhookJob` duplica entregas y contadores en reintentos — [CONFIRMADO]
- **Evidencia:** `Jobs/DispatchWebhookJob.php:52-73`: ante respuesta no-2xx hace `throw` para reintentar (`tries=3`), pero ya creó `WebhookDelivery` y ya hizo `increment('failure_count')`/`success_count`. Cada reintento crea otra fila de entrega y vuelve a incrementar.
- **Impacto:** infla métricas (`success_count`/`failure_count`) y duplica registros de auditoría de entrega. Es auditoría, no datos críticos, de ahí severidad baja.
- **Recomendación:** crear `WebhookDelivery` una sola vez (o `updateOrCreate` por intento `attempts()`), e incrementar contadores solo en el resultado final.
- **Esfuerzo:** S

## Colas huérfanas (mapa job→cola→¿supervisor?)

Conexión: `QUEUE_CONNECTION=redis` (sin fallback `sync`). "Supervisor" = existe en `config/horizon.php` (`production`/`local`).

| Cola | Productores (core Helpdesk) | ¿Supervisor? |
|------|------------------------------|--------------|
| **`helpdesk`** | RunWorkflowJob, SendScheduledMessageJob, SyncWhatsAppTemplatesJob, UnsnoozeConversationJob + listeners AutoAssignNewConversation, TriggerWorkflowsOn{Created,Closed,MessageReceived} | ❌ **HUÉRFANA** |
| **`helpdesk-events`** | CreateActivityMessageJob, ConversationAssignedNotification + 12 listeners (Log*, AnalyzeSentiment, AutoTag, BroadcastConversationMessage, HandleWithAiAgent, SendConversationAssignedNotification, RunAutomationsOnEventListener) | ❌ **HUÉRFANA** |
| **`notifications-high`** | EscalationNotification, MentionNotification + listeners SendEscalationNotification, SendMentionNotification | ❌ **HUÉRFANA** |
| **`helpdesk-broadcasts`** | BroadcastService → SendBroadcastMessageJob (clase inexistente, sin llamadores) | ❌ **HUÉRFANA** (+ clase ausente) |
| `helpdesk-webhooks` | ProcessSocialWebhookJob, ProcessEmailInboundJob, DownloadConversationAttachmentsJob, TranscribeAudioJob | ✅ supervisor-webhooks / -helpdesk-webhooks / -local-webhooks |
| `webhooks` | DispatchWebhookJob + listener DispatchConversationWebhooks | ✅ supervisor-webhooks / reviews-webhooks |
| `drip` | ProcessDripStepJob + listeners EnrollCustomerDrip* / EnrollCustomerToDripCampaigns | ✅ supervisor-drip |
| `broadcasts` | SendBroadcastJob (vía ProcessScheduledBroadcasts) | ✅ supervisor-broadcasts |
| `emails` | SendHelpdeskEmailJob, Mail CustomerOutboundEmail, ContinueConversationMail | ✅ reviews-notifications |
| `notifications` | NewConversation/MessageReceived/StatusChanged Notification + listeners NotifySlackOnSlaBreached, Send{NewConversation,MessageReceived,StatusChanged}Notification | ✅ supervisor-default / reviews-notifications |

Colas supervisadas pero **no usadas** por el core (raíz del desalineamiento): `helpdesk-scheduled`, `helpdesk-heavy`, `helpdesk-ai`, `chatflow` (`supervisor-helpdesk`). Eventos `ShouldBroadcast`/`ShouldBroadcastNow` se difunden por la cola `default` (supervisada) salvo los `Now` (síncronos, ver JOBS-10).

**Total core huérfanas: 4** (`helpdesk`, `helpdesk-events`, `notifications-high`, `helpdesk-broadcasts`).

## Plan de ataque priorizado

1. **JOBS-01 (Crítica):** añadir `helpdesk` y `helpdesk-events` a `supervisor-helpdesk` y `supervisor-local` en `config/horizon.php` (quick fix), o renombrar colas del core a las supervisadas. Verificar `LLEN` de Redis y reprocesar lo acumulado.
2. **JOBS-02 (Crítica):** registrar `PurgeOldGdprDeletes` (y `CleanupAgentPresence`) en `$this->commands([...])`; confirmar que el cron GDPR corre.
3. **JOBS-03 (Alta):** supervisar `notifications-high` (o reapuntar a `notifications`).
4. **JOBS-04 (Alta):** añadir `withoutOverlapping()->onOneServer()` a `check-sla` y `process-broadcasts`; transacción + lock en el marcado de broadcasts.
5. **JOBS-05 (Alta):** registrar y programar `helpdesk:agents:cleanup-presence`.
6. **JOBS-06 (Alta):** mover link-preview a un job encolado.
7. **JOBS-07..12 (Media):** decidir destino de `BroadcastService`; transacción en `ProcessSocialWebhookJob`; idempotencia en `ProcessDripStepJob`; revisar `ShouldBroadcastNow`; acotar re-emisión de `MessageReceived`; paralelizar `SendBroadcastJob`.
8. **JOBS-13..18 (Baja):** `$timeout`/`$backoff` en listeners; reubicar/limpiar `CleanupOldCommunications`; decidir polling de `fetch-emails`; limpiar colas supervisadas no usadas; aclarar `LogToDatabase`; deduplicar `WebhookDelivery`.

## Quick wins

- `config/horizon.php`: añadir `'helpdesk'`, `'helpdesk-events'`, `'notifications-high'` a un supervisor existente (resuelve JOBS-01 y JOBS-03 de golpe). **S, impacto crítico.**
- `HelpdeskServiceProvider.php`: añadir `PurgeOldGdprDeletes::class` y `CleanupAgentPresence::class` al `$this->commands([...])` (JOBS-02, JOBS-05). **S.**
- Añadir `->withoutOverlapping()->onOneServer()` a las 3 entradas del scheduler (JOBS-04, JOBS-02). **S.**
- Acotar `broadcast(new MessageReceived(...))` del observer a items entrantes (JOBS-11). **S.**

## Fortalezas

- **Disciplina de jobs:** los 13 jobs implementan `ShouldQueue`, con `$tries`, `$timeout`, `$backoff` y `failed()` con contexto de log; backoff escalonado (`[10,30,60]`) en los webhooks/inbound.
- **Idempotencia parcial bien pensada:** `find()/fresh()` con early-return en `CreateActivityMessageJob`, `SendScheduledMessageJob`, `UnsnoozeConversationJob`, `RunWorkflowJob`; `isDuplicate()` por `external_id` en webhooks sociales; `updateOrCreate` en `SyncWhatsAppTemplatesJob`.
- **Concurrencia:** `ProcessEmailInboundJob` usa `WithoutOverlapping(md5(from+message_id))->dontRelease()` — patrón correcto contra duplicados de email.
- **Diseño hot-path:** la descarga de adjuntos y la transcripción de audio se sacan del camino crítico a jobs (`DownloadConversationAttachmentsJob`, `TranscribeAudioJob`) con re-broadcast posterior.
- **Eventos/broadcasting:** uso correcto de `PrivateChannel` (`helpdesk.conversation.{id}`, `helpdesk.inbox`) para datos por conversación con Reverb; `EventServiceProvider` **sin closures** (compatible con `event:cache`).
- **Scheduler GDPR** usa `onOneServer()` (la intención de robustez existe; falta el registro del comando).
- `CheckSlaBreaches` cede correctamente a `HelpdeskSla` cuando el módulo está activo (evita doble disparo).

## Cobertura de la auditoría

- **Jobs (13/13):** revisados ShouldQueue, `$tries`/`$timeout`/`$backoff`, `failed()`, idempotencia, transacciones, nombre de cola.
- **Listeners (27 + 1 Automation):** revisados ShouldQueue, cola, `$tries`/`$timeout`/`$backoff`, `failed()`.
- **Eventos (23/23):** revisados `ShouldBroadcast`/`ShouldBroadcastNow`, `broadcastOn`/canal, idoneidad Reverb.
- **Notifications (6/6):** revisada cola (`onQueue`) y cruce con supervisores.
- **Observers (2/2):** `ConversationObserver`, `ConversationItemLinkPreviewObserver` (IO síncrono).
- **Commands (8/8):** revisadas signatures, registro en `$this->commands([...])` y programación.
- **Scheduler:** `HelpdeskServiceProvider::boot` (3 tareas) + cruce con `bootstrap/app.php`.
- **Horizon:** `config/horizon.php` (production + local) cruzado contra todas las colas productoras; `config/queue.php` (conexión default).
- **No verificado dinámicamente:** `php artisan` no disponible en este entorno (Docker/.env) — todos los hallazgos son estáticos (Read/Grep/Glob/Bash). No se ejecutaron tests (BD de test bloqueada).
