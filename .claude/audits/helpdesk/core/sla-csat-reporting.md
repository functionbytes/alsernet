# Auditoría core Helpdesk — SLA, CSAT & reporting/dashboards

> Fecha: 2026-06-29 · Health score: 57/100 · Estado: needs-work

**Resumen:** El CRUD de settings y las exportaciones CSV están limpios y bien construidos, pero el flujo público de encuesta CSAT está roto por nombres de vista incorrectos (nunca se recoge ningún dato de satisfacción), el informe de clientes en riesgo tiene un N+1 más una regla de diff con signo de Carbon 3 que está muerta, y los endpoints de analítica recalculan sin caché en cada carga sobre una columna `closed_at` sin índice. Diagnóstico: la fontanería de recolección de datos (CSAT) y la precisión de métricas (healthScore, business hours) son los puntos débiles; la capa de exportación/validación de settings es ejemplar. Tres defectos confirmados sin atenuantes definen las prioridades.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| CSAT--01 | Crítica | wiring | app/Http/Controllers/CsatController.php:25,29,32 | [CONFIRMADO] | S | Flujo público CSAT referencia vistas inexistentes (500 en cada magic-link) |
| AR--01 | Alta | performance | app/Http/Controllers/Managers/AtRiskCustomersReportController.php:74-101 | [CONFIRMADO] | M | Informe at-risk calcula healthScore() (4 queries) por cliente dentro del map (N+1 antes de take(50)) |
| AR--02 | Alta | quality | app/Services/CustomerInsightsService.php:46 | [CONFIRMADO] | S | Diff con signo de Carbon 3 deshabilita la penalización por inactividad (siempre false) |
| RPT--CACHE | Media | performance | app/Http/Controllers/Managers/CsatReportController.php:47-59 | [CONFIRMADO] | M | Endpoints de informe recalculan agregaciones pesadas en cada request sin caché |
| IDX--01 | Media | performance | database/migrations/2025_12_29_020915_create_helpdesk_conversations_table.php:32-36 | [CONFIRMADO] | S | Faltan índices en conversations.closed_at y csat_ratings.agent_id |
| LIVE--01 | Media | wiring | app/Http/Controllers/Managers/LiveDashboardController.php:44-51,91-94 | [CONFIRMADO] | M | Dashboard en vivo lee tablas jobs/sessions de BD pero el stack usa Redis/Horizon |
| BH--01 | Media | quality | app/Services/BusinessHoursService.php:21-40 | [CONFIRMADO] | M | BusinessHoursService elige el día en timezone de app, no en timezone del negocio |
| VAL--01 | Media | conventions | app/Http/Controllers/Managers/CsatReportController.php:34-37 | [CONFIRMADO] | S | Controladores de informe validan inline en vez de usar DateRangeReportRequest; taxonomía de permisos inconsistente |
| BH--02 | Baja | wiring | app/Http/Controllers/Managers/Settings/BusinessHoursController.php:51-67 | [CONFIRMADO] | S | UpdateBusinessHoursRequest existe pero el controlador valida inline (Form Request inerte) |
| LIVE--02 | Baja | performance | app/Http/Controllers/Managers/LiveDashboardController.php:57,64,76 | [CONFIRMADO] | S | whereDate() sobre columnas datetime impide uso de índice en métricas en vivo |
| EXP--01 | Baja | quality | app/Services/Exports/CsatExporter.php:34-35 | [CONFIRMADO] | S | Export CSAT filtra por created_at mientras el reporting usa answered_at |
| TEST--01 | Baja | tests | tests/Feature/SlaPoliciesControllerTest.php:1 | [CONFIRMADO] | M | El subsistema tiene cobertura de tests automatizados prácticamente nula |

## Hallazgos detallados

### CSAT--01 · [CONFIRMADO] · Crítica · El flujo público de encuesta CSAT referencia vistas inexistentes (500 en cada clic al magic-link)

**Archivo:** `app/Http/Controllers/CsatController.php:25,29,32`

**Evidencia:** `show()` retorna `view('helpdesk::public.csat-thanks')`, `view('helpdesk::public.csat-expired')` y `view('helpdesk::public.csat-form')`. Verificado en código: las líneas 25/29/32 usan exactamente esos nombres con guion. Los ficheros reales están en `resources/views/public/csat/{thanks,expired,show}.blade.php`, que resuelven a `helpdesk::public.csat.thanks` / `.expired` / `.show` (separados por punto, no por guion). `find` confirma que solo existen `csat/show|thanks|expired`. Además `csat-form` no tiene fichero alguno — la vista del formulario es `show.blade.php`.

**Impacto:** Cada cliente que pulsa el enlace CSAT enviado por `CsatService` lanza `View [public.csat-form] not found` (500). No es posible enviar ninguna valoración de satisfacción web/widget, por lo que `CsatService`, `CsatRating`, el informe CSAT, la columna CSAT de rendimiento de agentes, `csat_avg` del live dashboard y la serie CSAT de trends quedan todas vacías. Todo el subsistema CSAT es inerte en producción.

**Recomendación:** Renombrar a `view('helpdesk::public.csat.show')`, `'...csat.thanks'`, `'...csat.expired'`. Añadir un feature test que golpee `GET helpdesk/csat/{token}` para los estados no respondida/respondida/expirada (lo habría detectado).

### AR--01 · [CONFIRMADO] · Alta · El informe at-risk calcula healthScore() (4 queries) por cliente dentro del map (N+1 antes de take(50))

**Archivo:** `app/Http/Controllers/Managers/AtRiskCustomersReportController.php:74-101`

**Evidencia:** Verificado en código: `$rows` (uno por cliente con tag de sentimiento negativo en los últimos 90 días — sin límite previo) se carga eficientemente con un único `whereIn` (líneas 69-72), pero el `->map()` de la línea 74 llama a `$this->insights->healthScore($customer)` para CADA fila, y solo `->take(50)` después (línea 101). `CustomerInsightsService::healthScore()` (líneas 25-57) ejecuta 4 queries separadas (media CSAT, conteo de cerradas, última conversación, exists de sentimiento negativo).

**Impacto:** Para N clientes en riesgo el endpoint dispara ~4*N queries (p. ej. 300 clientes → ~1200 queries) en un informe AJAX para managers, sin tope antes del scoring. Página lenta y presión sobre la BD que crece con el volumen de sentimiento negativo.

**Recomendación:** Ordenar primero por `negativeCount`, recortar a 50, y ENTONCES calcular `healthScore` solo para las filas supervivientes; o vectorizar healthScore en batch (el `HealthScoreBatchService` de HelpdeskAnalytics ya lo hace — reutilizarlo). Además, cachear el resultado unos minutos.

### AR--02 · [CONFIRMADO] · Alta · El diff con signo de Carbon 3 deshabilita la penalización por inactividad en healthScore() (siempre false)

**Archivo:** `app/Services/CustomerInsightsService.php:46`

**Evidencia:** Confirmado con ejecución en vivo. Línea 46: `if ($lastConv && now()->diffInMonths($lastConv) > 6) { $score -= 20; }`. `$lastConv` siempre está en el pasado, y Carbon 3 (3.11.3 confirmado en composer.lock) devuelve un float CON SIGNO en `diffInMonths`. Tinker para `now()->diffInMonths(now()->subMonths(8))` retorna `-8.0` (negativo). La condición `> 6` nunca puede ser cierta.

**Impacto:** La regla "-20 por >6 meses inactivo" nunca se dispara, así que los health scores son sistemáticamente demasiado altos y el ranking at-risk (que ordena por healthScore asc como desempate) y los insights de Cliente-360 son inexactos. Corrupción silenciosa de métricas.

**Recomendación:** Usar diff absoluto: `$lastConv->diffInMonths(now(), true) > 6`, o `now()->diffInMonths($lastConv, true) > 6`. Auditar otras llamadas `diffIn*` (p. ej. `SlaBreachesReportController.php:122` es seguro por el guard `isPast()` pero trunca un float a int).

### RPT--CACHE · [CONFIRMADO] · Media · Los endpoints de informe recalculan agregaciones pesadas en cada request sin caché ni serie temporal

**Archivo:** `app/Http/Controllers/Managers/CsatReportController.php:47-59`

**Evidencia:** `CsatReport::data()` ejecuta 7 agregaciones agrupadas por llamada; `TrendsReportController::data()` ejecuta 4 `GROUP BY DATE(...)` sobre 30 días; `HeatmapReportController::index()` agrupa por `DAYOFWEEK/HOUR` sobre 30 días; `AgentPerformanceController::index()` ejecuta 4 agregaciones cross-table en cada carga. Solo `DashboardController` cachea (`helpdesk:dashboard:conv_stats`, 300s). Ningún controlador de informe usa `Cache::remember`.

**Impacto:** Cada refresco de informe re-escanea las tablas conversations/items/csat; con crecimiento se vuelven lentos y repetidos (los gráficos suelen hacer polling). Contrasta con el dashboard, que ya cachea el mismo tipo de datos.

**Recomendación:** Envolver cada payload de informe en `Cache::remember` keyado por el rango de fechas (TTL 5-15 min), o precomputar rollups diarios vía comando programado (tabla de serie temporal) como hace HelpdeskAnalytics.

### IDX--01 · [CONFIRMADO] · Media · Faltan índices en conversations.closed_at y csat_ratings.agent_id para las queries de analítica

**Archivo:** `database/migrations/2025_12_29_020915_create_helpdesk_conversations_table.php:32-36`

**Evidencia:** `conversations` indexa `created_at`, `customer_id`, `status_id`, `assignee_id`, `inbox+created_at` — pero NO `closed_at`. `closed_at` se filtra/agrupa en AgentPerformance (`whereBetween closed_at`), TrendsReport (`whereBetween` + `GROUP BY DATE(closed_at)`), LiveDashboard (`whereDate closed_at` x2) y el `conv_stats` cacheado. Aparte, `helpdesk_csat_ratings` (migración `2026_05_01_500003`) indexa `answered_at` + `survey_token` pero no `agent_id`, que `CsatReport.getByAgent` agrupa y AgentPerformance hace `whereIn`.

**Impacto:** Las queries de rango/agrupación sobre `closed_at` y `agent_id` hacen full table scans; el coste crece linealmente con el volumen de conversaciones/CSAT en toda la analítica de managers.

**Recomendación:** Añadir un índice sobre `helpdesk_conversations.closed_at` (o compuesto `[closed_at, assignee_id]`) y sobre `helpdesk_csat_ratings.agent_id` (o `[agent_id, answered_at]`) en una nueva migración.

### LIVE--01 · [CONFIRMADO] · Media · El dashboard en vivo lee tablas jobs/sessions de BD pero el proyecto corre Redis queue/Horizon

**Archivo:** `app/Http/Controllers/Managers/LiveDashboardController.php:44-51,91-94`

**Evidencia:** `queuePendingJobs()` retorna `DB::table('jobs')->count()` y `agentsOnline()` lee `DB::table('sessions')`. El stack es Redis cache/queue con Horizon (según config del proyecto), así que la tabla `jobs` de BD está vacía/irrelevante y, si las sesiones usan Redis, la tabla `sessions` también.

**Impacto:** `queue_pending_jobs` reporta 0 (o un número obsoleto) y `agents_online` puede ser siempre 0, así que dos KPIs en vivo engañan silenciosamente a los operadores.

**Recomendación:** Obtener la profundidad de cola desde Horizon/Redis (p. ej. `LLEN` de la cola Redis o métricas de Horizon) y derivar agentes-online del heartbeat existente de `AgentPresence` (la presencia ya está implementada) en vez de la tabla `sessions`.

### BH--01 · [CONFIRMADO] · Media · BusinessHoursService elige el día de la semana en timezone de app, no en timezone del negocio

**Archivo:** `app/Services/BusinessHoursService.php:21-40`

**Evidencia:** La fila del día se obtiene con `where('day_of_week', now()->dayOfWeek)` usando la timezone por defecto de la app, pero la comparación open/close luego re-parsea `opens_at`/`closes_at` en `$hour->timezone` (por defecto Europe/Madrid). Cerca de medianoche el día seleccionado puede diferir del día en la timezone del negocio. Además solo se toma `->first()` (sin soporte para múltiples turnos/inboxes) y los rangos nocturnos donde `closes_at < opens_at` rompen el check `between()`.

**Impacto:** Alrededor de medianoche y para horarios nocturnos, `isOpenNow()` (consumido por `ProcessSocialWebhookJob.php:387` para gating de auto-respuesta) devuelve el estado abierto/cerrado equivocado, enviando o suprimiendo respuestas automáticas incorrectamente.

**Recomendación:** Calcular el día de la semana en la timezone del negocio (`Carbon::now($tz)->dayOfWeek`) antes del lookup, y tratar `closes_at <= opens_at` como ventana que cruza medianoche.

### VAL--01 · [CONFIRMADO] · Media · Los controladores de informe usan validación inline en vez del DateRangeReportRequest existente; taxonomía de permisos inconsistente

**Archivo:** `app/Http/Controllers/Managers/CsatReportController.php:34-37`

**Evidencia:** `CsatReportController::data()` y `SlaBreachesReportController::data()` (línea 52) llaman a `$request->validate([...])` inline, violando la regla de Form Request del proyecto, mientras un `DateRangeReportRequest` ya existe a propósito. Ese Form Request autoriza con `helpdesk.metrics.view` mientras los controladores de informe gatean con `helpdesk.reports.view` (dos permisos paralelos para la misma superficie).

**Impacto:** La validación inline duplica reglas y se salta el patrón estándar `authorize()`/mensajes en español; el split `metrics.view` vs `reports.view` arriesga que un admin conceda uno y no el otro y pierda acceso silenciosamente.

**Recomendación:** Type-hint `DateRangeReportRequest` en los métodos `data()` (alinear sus nombres de parámetro `from`/`to` con los controladores, actualmente `date_from`/`date_to`) y consolidar en un único permiso de informes.

### BH--02 · [CONFIRMADO] · Baja · UpdateBusinessHoursRequest existe pero BusinessHoursController valida inline (Form Request inerte)

**Archivo:** `app/Http/Controllers/Managers/Settings/BusinessHoursController.php:51-67`

**Evidencia:** `update(Request $request)` llama a `$request->validate([...])` con exactamente las mismas reglas ya definidas en `app/Http/Requests/Managers/Settings/UpdateBusinessHoursRequest.php`, que nunca se referencia en ningún sitio.

**Impacto:** Reglas duplicadas y un Form Request sin usar y nunca ejercitado; su `authorize()` interno (`helpdesk.settings.update`) es código muerto (el permiso se aplica vía middleware del constructor).

**Recomendación:** Type-hint `UpdateBusinessHoursRequest` en `update()` y borrar el bloque `validate()` inline.

### LIVE--02 · [CONFIRMADO] · Baja · whereDate() sobre columnas datetime impide uso de índice en métricas en vivo

**Archivo:** `app/Http/Controllers/Managers/LiveDashboardController.php:57,64,76`

**Evidencia:** `messagesToday` (`whereDate created_at`), `avgFirstResponseSeconds` (`whereDate closed_at`), `csatAvgToday` (`whereDate answered_at`) envuelven la columna en `DATE()`, lo que deshabilita los índices de `created_at`/`answered_at`. El endpoint hace polling (`throttle:30,1`).

**Impacto:** Cada poll hace scans que anulan el índice; menor hoy pero se compone con el índice ausente de `closed_at` y la tasa de 30/min.

**Recomendación:** Usar filtro de rango: `whereBetween($col, [today()->startOfDay(), today()->endOfDay()])` para que se use el índice.

### EXP--01 · [CONFIRMADO] · Baja · El export CSAT filtra por created_at (fecha de envío) mientras el reporting CSAT usa answered_at

**Archivo:** `app/Services/Exports/CsatExporter.php:34-35`

**Evidencia:** `rows()` filtra `date_from`/`date_to` contra `created_at`, pero el informe CSAT (`CsatReportController`) y los dashboards agregan sobre `answered_at`. La columna `Fecha` exportada también cae a `created_at` cuando `answered_at` es null, mezclando encuestas enviadas-pero-no-respondidas en un export con rango de fechas.

**Impacto:** Un manager que exporta CSAT para un rango de fechas obtiene encuestas keyadas por fecha de envío (incluyendo no respondidas), que no reconciliarán con los números del informe en pantalla.

**Recomendación:** Filtrar el export por `answered_at` (y opcionalmente `whereNotNull('answered_at')`) para igualar la semántica del informe, o exponer un toggle de base de fecha.

### TEST--01 · [CONFIRMADO] · Baja · El subsistema tiene cobertura de tests automatizados prácticamente nula

**Archivo:** `tests/Feature/SlaPoliciesControllerTest.php:1`

**Evidencia:** El único test del subsistema es `SlaPoliciesControllerTest` (CRUD de settings). No existen tests para `CsatService`, `CsatController` (la vista rota crítica se habría detectado), `ExportController`/exporters, ningún controlador de informe, `BusinessHoursService` ni LiveDashboard (grep de `tests/` por Csat/Report/Export/BusinessHours/Dashboard solo devuelve un test no relacionado de webhook de WhatsApp).

**Impacto:** Regresiones como CSAT--01 se publican sin detectar; los bugs de precisión de métricas (AR--02, BH--01) no tienen guardarraíl.

**Recomendación:** Añadir feature tests para el flujo público CSAT show/submit, cada endpoint `data()` de informe (auth + forma del happy path), auth/headers del export CSV, y tests unitarios para el scoring de `healthScore()` y los límites de timezone de `BusinessHoursService`.

## Plan de ataque priorizado

1. **CSAT--01 (Crítica, S)** — Renombrar las 3 llamadas `view()` en `CsatController` a `helpdesk::public.csat.show/thanks/expired`. Desbloquea toda la recolección CSAT; sin esto, todo el reporting CSAT está vacío. Acompañar con feature test del magic-link.
2. **AR--02 (Alta, S)** — Corregir el diff con signo de Carbon 3 a `diffInMonths(..., true)`. Restaura la precisión del health score y del ranking at-risk. Cambio de una línea.
3. **AR--01 (Alta, M)** — Recortar a 50 por `negativeCount` antes de calcular `healthScore` (o reutilizar `HealthScoreBatchService`). Elimina el N+1 de ~4*N queries.
4. **IDX--01 (Media, S)** — Migración con índices en `conversations.closed_at` y `csat_ratings.agent_id`. Base barata para toda la analítica.
5. **RPT--CACHE (Media, M)** — `Cache::remember` por rango de fechas en cada `data()` de informe (5-15 min).
6. **LIVE--01 (Media, M)** — Reapuntar profundidad de cola a Horizon/Redis y agentes-online a `AgentPresence`.
7. **BH--01 (Media, M)** — Calcular weekday en timezone del negocio y manejar ventanas nocturnas.
8. **VAL--01 (Media, S)** + **BH--02 (Baja, S)** — Migrar a Form Requests existentes y consolidar permiso de informes.
9. **LIVE--02 / EXP--01 (Baja, S)** — `whereBetween` en lugar de `whereDate`; alinear export CSAT a `answered_at`.
10. **TEST--01 (Baja, M)** — Cobertura de tests del subsistema.

## Quick wins

- Renombrar las 3 llamadas `view()` en `CsatController` a `helpdesk::public.csat.show/thanks/expired` (CSAT--01).
- Cablear `BusinessHoursController::update()` al `UpdateBusinessHoursRequest` ya existente en vez de `validate()` inline (BH--02).
- Cambiar `CsatReportController` al `DateRangeReportRequest` existente (VAL--01).
- Corregir AR--02 con `diffInMonths(..., true)` (una línea).
- Añadir índices `closed_at` / `agent_id` en una migración (IDX--01).

## Fortalezas

- `CsvStreamExporter` usa `League\Csv` `EscapeFormula` para neutralizar inyección CSV/fórmula y hace streaming vía generadores `lazy(200)` con BOM UTF-8 — seguro en memoria y frente a inyección.
- Todos los Form Requests de export tienen `authorize()` real (`helpdesk.exports.create`), reglas en sintaxis array, mensajes/atributos en español, y `date_to` con `after_or_equal date_from`.
- `SlaPoliciesController` es ejemplar: middleware de permiso por acción, Form Requests Store/Update dedicados, `$request->boolean()` para checkboxes, tipos de retorno explícitos.
- Los listeners encolados (`NotifySlackOnSlaBreached`, `EnrollCustomerDripOnCsat`) implementan `ShouldQueue` con `tries`/`backoff`/`failed()` y colas nombradas; eventos registrados vía mapa `$listen` basado en clases (sin closures).
- El comando legacy `CheckSlaBreaches` correctamente aborta cuando el módulo HelpdeskSla está habilitado, evitando el viejo doble-dispatch hardcodeado de 15 min.

## Cobertura de la auditoría

Leídos por completo: `BusinessHoursService`, `CheckSlaBreaches`, `CsatService`, `CustomerInsightsService` (healthScore/lifetimeMetrics), los 4 Exporters + `CsvStreamExporter`, controladores Dashboard/LiveDashboard/CsatReport/SlaBreachesReport/AtRiskCustomersReport/TrendsReport/HeatmapReport/AgentPerformance/Export/CsatController/Settings (BusinessHours, SlaPolicies), modelos `CsatRating`/`SlaPolicy`, eventos `CsatRatingAnswered`/`SlaBreached`, listeners `NotifySlackOnSlaBreached`/`EnrollCustomerDripOnCsat`, los Form Requests relevantes, migraciones (índices csat/sla/conversations/conversation_items), rutas (managers/public/settings), wiring de EventServiceProvider, y los blades del subsistema.

Verificado que los permisos están seeded (`helpdesk.reports.view`, `helpdesk.exports.create`, `helpdesk.metrics.view`, `helpdesk.sla-policies.*`, `access_helpdesk`). Carbon 3.11.3 confirmado en composer.lock; AR--02 confirmado con ejecución en vivo de tinker (`-8.0` para fecha de 8 meses atrás). NO se ejecutaron tests (BD bloqueada). Módulos HelpdeskSla/HelpdeskTickets fuera de alcance (solo se revisaron sus puntos de integración).

## Descartados en verificación

Ningún hallazgo fue refutado durante la verificación. Los tres hallazgos de mayor severidad (CSAT--01, AR--01, AR--02) son defectos reales sin factores atenuantes: CSAT--01 es un error de wiring que hace que todo el flujo de encuesta público devuelva 500 en cada request; AR--01 es un patrón genuino de N*4 queries por fila antes del slice `take(50)`; AR--02 es una regresión de diff con signo de Carbon 3 confirmada por ejecución en vivo (`-8.0` devuelto para una fecha de 8 meses, la condición nunca se cumple).
