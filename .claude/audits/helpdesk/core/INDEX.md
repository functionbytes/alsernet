# Auditoría profunda — Core Helpdesk (2026-06-29)

Este índice complementa la auditoría por muestreo de [`../Helpdesk.md`](../Helpdesk.md) con una **cobertura sistemática por subsistema**: cada uno de los **9 subsistemas** del core fue auditado en profundidad (lectura completa, no muestreo) y verificado estáticamente línea a línea (la BD de test está bloqueada, ver nota de cobertura en cada reporte). Total: **~122 hallazgos**, **4 críticos** y **17 altos** confirmados.

## Subsistemas (peor primero)

| Subsistema | Health | Estado | #Crit | #High | Total | Reporte |
|------------|:------:|--------|:-----:|:-----:|:-----:|---------|
| IA & Automatización | 46 | half-wired | 1 | 2 | 11 | [ai-automation.md](./ai-automation.md) |
| Jobs, eventos & scheduler | 47 | half-wired | 2 | 4 | 18 | [jobs-events-scheduler.md](./jobs-events-scheduler.md) |
| SLA, CSAT & reporting/dashboards | 57 | needs-work | 1 | 2 | 12 | [sla-csat-reporting.md](./sla-csat-reporting.md) |
| Asignación, agentes & presencia | 58 | half-wired | 0 | 2 | 15 | [agents-routing.md](./agents-routing.md) |
| Inbox & Conversaciones | 61 | needs-work | 0 | 1 | 15 | [inbox-conversations.md](./inbox-conversations.md) |
| Canales & Mensajería (in/out) | 62 | solid-minor-issues | 0 | 3 | 14 | [channels-messaging.md](./channels-messaging.md) |
| Seguridad, authz & GDPR/Compliance | 66 | needs-work | 0 | 2 | 9 | [security-authz-gdpr.md](./security-authz-gdpr.md) |
| Customer 360 & CRM | 67 | solid-minor-issues | 0 | 1 | 14 | [customer-360.md](./customer-360.md) |
| Modelos & capa de datos | 73 | solid-minor-issues | 0 | 0 | 14 | [models-data.md](./models-data.md) |
| **TOTAL** | **~59 (media)** | — | **4** | **17** | **~122** | — |

> **Subsistemas recuperados (jobs/eventos y seguridad)** — añadidos tras reintento:
> - **Jobs/eventos/scheduler (47, 2 críticos):** `JOBS-01` 4 colas huérfanas sin supervisor Horizon (`helpdesk`, `helpdesk-events`, `notifications-high`, `helpdesk-broadcasts`) → ~18 jobs/listeners nunca procesados en prod; `JOBS-02` `helpdesk:purge-old-gdpr-deletes` programado pero **el comando no está registrado** → error diario del scheduler y la purga GDPR de 90 días **nunca corre**. Causa raíz: `supervisor-helpdesk` vigila nombres (`helpdesk-scheduled/-heavy/-ai/chatflow`) que ningún job del core usa.
> - **Seguridad/authz/GDPR (66, 2 altos):** `SEC-01` fuga de PII cross-inbox en búsqueda/listado (`GlobalSearchService`, `CustomersController::search/index`, e IDOR directo por `?selected=` sin `authorize`); `SEC-02` participantes con permiso global sin policy por modelo; `SEC-03` settings gateado solo por rol → ~30 permisos granulares muertos. Fortalezas: 117/117 Form Requests con `authorize()` real, webhooks HMAC + secreto cifrado, 2FA cifrado, sin XSS en el hilo.
>
> **⚠️ Reconciliación GDPR:** el reporte de seguridad listó la purga GDPR como "programada" (está en el scheduler), pero el de jobs verificó que **el comando no está registrado en `commands()`** → en la práctica **falla y no purga**. Prevalece el de jobs (`JOBS-02`): la purga GDPR está rota.

## Atacar primero (orden recomendado dentro del core)

El orden prioriza **dato corrupto/inerte que invalida features completas** y **brechas de seguridad de aislamiento**, equilibrando con el esfuerzo (quick wins primero dentro de igual severidad).

1. **CSAT--01 (Crítica, S)** — Renombrar las 3 vistas en `CsatController` (`public.csat.show/thanks/expired`). Una corrección casi de una línea desbloquea TODA la recolección CSAT (hoy cada magic-link devuelve 500; informe CSAT, columna de agentes, live dashboard y trends están vacíos).
2. **AI-AUTO-01 (Crítica, M)** — Bind singleton + poblar `AutomationActionRegistry`. Sin esto, automatizaciones y macros marcan "matched" pero **no ejecutan ninguna acción**. Bloquea todo el valor del subsistema de automatización.
3. **CHAN-01 (Alta, S)** — Eliminar el 4º argumento del `dispatch` que serializa el token de WhatsApp en Redis/Horizon. Quick win de seguridad; el job ya hace fallback a config.
4. **Clúster de aislamiento por inbox (Alta/Media, S cada uno)** — Cerrar los IDOR cross-inbox de un tirón: `CustomerInsightsController::show` (CUST-01), `react()` (INBO-01), `delete()` policy (INBO-02), participants add/remove (INBO-03), y el threading de email entre clientes (CHAN-03). Mismo patrón, parches pequeños.
5. **INBO-06 (Alta, M)** — Cierre/reapertura masivos deben fijar `status_id` (no solo `closed_at`) y disparar eventos de cierre/CSAT + notificaciones de assign. Hoy dejan estado inconsistente en todo filtro, contador y observer de SLA.
6. **AI-SEC-01 + ReDoS de regex (Alta/Media, M)** — `PromptSanitizer` para los 5 servicios LLM, y validar/limitar las regex almacenadas que corren sobre input de cliente (mismo bug en 3 subsistemas: AI-RR-01 / AGEN-12 / MODE-13).
7. **CHAN-05 (Alta, M)** — Mover los envíos salientes (texto/adjunto/typing/seen) a un job encolado para evitar agotamiento de workers FPM bajo carga.
8. **AGEN-01 (Alta, M)** — Conectar disponibilidad/turnos/capacidad/presencia al enrutamiento automático (hoy se asigna a agentes offline/de vacaciones/por encima de límite). Reusar el patrón de `Group::getNextAgent`.
9. **AR--02 (Alta, S) + AR--01/CUST-02 (Media, M)** — Corregir el diff con signo de Carbon 3 (`diffInMonths(..., true)`) que deshabilita la penalización por inactividad, y eliminar el N+1 de `healthScore()` por cliente (mismo bug reportado en sla y customer-360).
10. **AI-WIRE-02 / INBO-04 (Alta/Media, S)** — Eliminar el stub `aiSuggestions` hardcodeado + su ruta y apuntar el front a `ai/suggest-replies` (mismo defecto visto desde IA e Inbox).
11. **AGEN-02 (Alta, S)** — Unificar el filtro de roles de agente (incluir `administrative`); hoy el panel de ajustes sale casi vacío.
12. **IDX--01 + RPT--CACHE + LIVE--01/AGEN-06 (Media)** — Índices en `closed_at`/`agent_id`, caché de informes, y reapuntar las métricas de cola/online a Horizon/Redis/AgentPresence (la tabla `jobs`/`sessions` está vacía bajo Redis).
13. **Limpieza (Media/Baja)** — Consolidar rutas store duplicadas (controller vs service), `$hidden` en canales, cableado multi-brand/empresa, y la deuda transversal de tests.

## Temas transversales del core

Patrones que se repiten entre subsistemas (la repetición eleva su prioridad real por encima de su severidad individual):

1. **Aislamiento por inbox roto (IDOR / fuga PII)** — `helpdesk.*.view`/`update` se trata como permiso global sin volver a verificar la policy por modelo (`sharesInboxWith`/`canAccessInbox`). Afectados: INBO-01 (react), INBO-02 (delete policy), INBO-03 (participants), CUST-01 (insights), CUST-10 (API). El subsistema *tiene* el mecanismo; el problema es que endpoints concretos lo saltan.
2. **Features a medio cablear / código muerto que aparenta entregado** — La fachada existe pero el runtime está inerte. Afectados: AI-AUTO-01 (registry vacío), CSAT--01 (vistas rotas), AI-WIRE-02/INBO-04 (aiSuggestions stub), INBO-05 (SideConversations), AGEN-01/AGEN-04/AGEN-08 (routing ignora disponibilidad, AgentCalendar huérfano, skills hardcodeados), CHAN-11/CHAN-12 (HSM templates + métodos muertos), MODE-01/03/04 (is_public, company, brand sin cablear). Es la causa raíz del estado "half-wired" de 3 subsistemas.
3. **Input no confiable → LLM/regex sin defensa (prompt-injection + ReDoS)** — Texto crudo del cliente entra a prompts LLM (solo `strip_tags`) y a `preg_match` de patrones de admin sin validar. Afectados: AI-SEC-01 (5 servicios LLM), AI-RR-01 + AGEN-12 + MODE-13 (la **misma** `RoutingRule::matches()` regex reportada en 3 subsistemas), AI-QUAL-02 (args de tool sin validar). Riesgo de exfiltración de prompt/KB y de bloqueo del worker de cola.
4. **N+1 y búsquedas no-sargables (LIKE leading-wildcard)** — `healthScore()` por cliente (AR--01 = CUST-02), `getLatestMessage()` sin eager-load de `lastMessage` (INBO-09, CUST-13, MODE-08, AGEN-05), y `LIKE '%term%'` sobre columnas no indexadas (INBO-07/08 attachments+búsqueda, CUST-08 customer search).
5. **Validación inline en vez de Form Request (deriva de convención)** — INBO-03, AI-QUAL-01, AGEN-13, VAL--01, BH--02 (algunos con el Form Request ya escrito pero nunca type-hinteado).
6. **Rutas store/send duplicadas (controller vs service) que derivan** — INBO-13, CHAN-07 (simulador vs prod), CHAN-08; el `ConversationMessageService::store` no es transaccional. Ya manifestado como el bug de SLA de `first_response_at` (INBO-11).
7. **Métricas en vivo leen tablas de BD bajo stack Redis/Horizon** — AGEN-06 = LIVE--01 (mismo bug): `queue_pending_jobs` y `agents_online` cuentan `jobs`/`sessions` vacías y muestran 0.
8. **Exposición de secretos** — CHAN-01 (token WhatsApp en cola), MODE-05 (`$hidden` ausente en canales Api/Whatsapp), AGEN-11 (presencia por canal público).
9. **Cobertura de tests casi nula en la lógica delicada** — AI-TEST-01, AGEN-15, TEST--01, CUST-11, MODE-11. Varios de los bugs críticos/altos (registry vacío, vistas CSAT, branch stub) habrían sido atrapados por un test mínimo.

## Quick wins del core

Correcciones de alto valor y esfuerzo S (la mayoría 1-5 líneas o una migración):

- **CSAT--01** — Renombrar las 3 `view()` de `CsatController` a `helpdesk::public.csat.{show,thanks,expired}`.
- **CHAN-01** — Borrar el 4º argumento de `DownloadConversationAttachmentsJob::dispatch` (token sale de Redis/Horizon).
- **CUST-01** — `$this->authorize('view', $customer)` al inicio de `CustomerInsightsController::show`.
- **INBO-01** — `$this->authorize('view', $item->conversation)` en `ConversationItemsController::react()` (o eliminar el endpoint duplicado).
- **INBO-02** — Añadir `&& $this->canAccessInbox(...)` a `ConversationPolicy::delete()`.
- **AR--02** — `diffInMonths(..., true)` para reactivar la penalización por inactividad del health score.
- **AI-WIRE-02 / INBO-04** — Borrar `aiSuggestions()` + su ruta; apuntar el modal a `ai/suggest-replies`.
- **MODE-05** — `protected $hidden` en `Channels\Api` y `Channels\Whatsapp`.
- **AGEN-02** — Incluir `administrative` en el filtro de roles de `AgentSettingsController`.
- **AGEN-03** — Registrar y programar `CleanupAgentPresence` (`->everyMinute()`).
- **IDX--01** — Migración con índices en `conversations.closed_at` y `csat_ratings.agent_id`.
- **CHAN-02** — Índice único en `helpdesk_conversation_items.external_id` (idempotencia atómica).
- **MODE-01** — Quitar `is_public` de `Conversation::$fillable` (columna inexistente).
- **CUST-06** — Añadir prefijo de conexión `helpdesk.` a la regla unique del API store de cliente.

## Resumen de salud

- **Salud media del core:** ~58/100. Ningún subsistema es "production-ready" sin trabajo; el rango va de **46 (IA & Automatización)** a **73 (Modelos & capa de datos)**.
- **Criticos confirmados: 2** — `CSAT--01` (todo el flujo CSAT inerte) y `AI-AUTO-01` (automatizaciones/macros 100% inertes). Ambos son bugs de *wiring*, no de arquitectura: bajo esfuerzo, impacto desproporcionado.
- **Altos confirmados: 11** — Concentrados en seguridad de aislamiento (IDOR cross-inbox), enrutamiento desconectado de la capacidad, envíos salientes bloqueantes, fuga de credenciales, y precisión de métricas.
- **Patrón dominante:** "half-wired" — la superficie de UI/endpoints/modelos está construida pero piezas centrales de runtime no están conectadas. Los subsistemas más débiles (IA, SLA/CSAT, Agentes) lo son por features inertes, no por código defectuoso.
- **Punto fuerte transversal:** la capa de modelos (73), la verificación de firmas de webhook (fail-closed), la ingesta entrante encolada, el guard SSRF centralizado, los exports CSV (anti-inyección + streaming) y el escape XSS del thread están genuinamente sólidos.
- **Mayor riesgo latente:** la **deuda de tests** (transversal): varios críticos/altos habrían sido atrapados por tests mínimos, y no hay red de regresión para las correcciones propuestas.
