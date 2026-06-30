# Auditoría core Helpdesk — Asignación, agentes & presencia

> Fecha: 2026-06-29 · Health score: 58/100 · Estado: half-wired (a medio cablear)

**Resumen:** Subsistema con buena superficie de UI/endpoints pero con la pieza central —disponibilidad/turnos/capacidad/presencia— desconectada del enrutamiento automático, varias features inertes (calendario, limpieza de presencia, contador de carga) y filtros de rol rotos. El path de automatización vía `Group::getNextAgent` sí respeta capacidad/disponibilidad, pero los pipelines de skills y de reglas de enrutamiento la ignoran por completo, asignando conversaciones a agentes offline, de vacaciones o por encima de su límite. Verificación estática completa (sin ejecución de tests, BD de test bloqueada): los dos hallazgos de severidad alta quedan **confirmados** con evidencia directa, al igual que la muestra revisada de los demás.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| AGEN-01 | High | wiring | `app/Services/SkillsRoutingService.php:15-62` | [CONFIRMADO] | M | Enrutamiento automático ignora disponibilidad, turnos, vacaciones y capacidad |
| AGEN-02 | High | wiring | `app/Http/Controllers/Managers/Settings/AgentSettingsController.php:25,141` | [CONFIRMADO] | S | Filtro de roles roto en panel de ajustes de agente (roles inexistentes) |
| AGEN-03 | Medium | wiring | `app/Console/Commands/CleanupAgentPresence.php:10` | [CONFIRMADO] | S | Comando de limpieza de presencia ni registrado ni programado |
| AGEN-04 | Medium | wiring | `app/Models/AgentCalendar.php:7-43` | [CONFIRMADO] | M | Modelo AgentCalendar completamente huérfano (feature inerte) |
| AGEN-05 | Medium | performance | `app/Services/AgentPresenceService.php:127-138,45-56` | [DUDOSO] | S | N+1 en getAgentsList: getState() re-consulta la BD por agente |
| AGEN-06 | Medium | wiring | `app/Http/Controllers/Managers/LiveDashboardController.php:91-94` | [CONFIRMADO] | S | Métrica queue_pending_jobs siempre 0 bajo cola Redis |
| AGEN-07 | Medium | quality | `app/Http/Controllers/Managers/LeaderboardController.php:25-40` | [DUDOSO] | S | Leaderboard hace JOIN a users sobre la conexión helpdesk |
| AGEN-08 | Medium | wiring | `app/Services/SkillsRoutingService.php:78-98` | [CONFIRMADO] | M | Auto-detección de skills sólo funciona para 5 slugs hardcodeados |
| AGEN-09 | Medium | quality | `app/Models/AgentSettings.php:104-105,153` | [DUDOSO] | M | Contador current_open_count nunca se mantiene |
| AGEN-10 | Medium | quality | `app/Http/Controllers/Managers/AgentsController.php:195-211` | [DUDOSO] | M | Dos editores de ajustes de agente paralelos con campos y permisos divergentes |
| AGEN-11 | Low | security | `app/Events/AgentPresenceChanged.php:22-27` | [CONFIRMADO] | S | Presencia se emite por canal público (fuga de user_id/nombre) |
| AGEN-12 | Low | security | `app/Models/RoutingRule.php:45` | [CONFIRMADO] | S | Regex de RoutingRule sin validar se ejecuta contra cada mensaje |
| AGEN-13 | Low | conventions | `app/Http/Controllers/Managers/AgentPresenceController.php:43` | [DUDOSO] | S | Validación inline y permiso semánticamente incorrecto |
| AGEN-14 | Low | performance | `database/migrations/2026_05_06_160000_add_presence_to_helpdesk_agent_settings.php:15-23` | [DUDOSO] | S | Sin índice en last_heartbeat_at/presence_state |
| AGEN-15 | Low | tests | `tests/Feature/RoutingRulesControllerTest.php:1` | [CONFIRMADO] | M | Cobertura de tests del subsistema casi nula |

## Hallazgos detallados

### AGEN-01 · [CONFIRMADO] · High · wiring
**Enrutamiento automático ignora disponibilidad, turnos, vacaciones y capacidad**
`app/Services/SkillsRoutingService.php:15-62`

**Evidencia:** `routeBySkills()` consulta sólo `helpdesk_user_skills` y `helpdesk_conversations` (COUNT de conversaciones abiertas); nunca toca `helpdesk_agent_settings`. El orden por `open_count` (líneas 54-59) es una preferencia, no un límite duro: los agentes por encima de `max_concurrent_conversations` jamás se excluyen. `RoutingRuleService::matchAndAssign()` (`RoutingRuleService.php:42-43`) hace `$conv->assignTo()` directo sin guarda. En contraste, `Group::getNextAgent()` (`Group.php:105-120`) sí llama a `acceptsConversationsNow()` y `!hasReachedLimit()`. Los cuatro métodos de guarda (`canReceiveAssignment`, `acceptsConversationsNow`, `hasReachedLimit`, `isOnVacation`) existen en `AgentSettings.php:82-160` y simplemente nunca se invocan desde ninguno de los dos servicios.

**Impacto:** Conversaciones se auto-asignan a agentes offline, de vacaciones, fuera de horario o por encima de su `max_concurrent`. Toda la lógica de disponibilidad/turnos/capacidad del modelo `AgentSettings` queda inerte para el pipeline principal de auto-asignación; sólo el path de automation vía `Group` la respeta.

**Recomendación:** Antes de asignar, filtrar candidatos por `AgentSettings` (`canReceiveAssignment`/`acceptsConversationsNow`/`!hasReachedLimit`/`!isOnVacation` y `presence != offline`) y por `AgentInboxCapacity.accepts_new`/`max_concurrent` del inbox de la conversación. Reutilizar el patrón de `Group::getNextAgent`.

---

### AGEN-02 · [CONFIRMADO] · High · wiring
**Filtro de roles roto en panel de ajustes de agente (roles inexistentes)**
`app/Http/Controllers/Managers/Settings/AgentSettingsController.php:25,141`

**Evidencia:** Líneas 25 y 141 filtran por `whereIn('name', ['admin','manager','support','callcenter'])`. `AgentsController.php:23` usa `User::role(['administrative','manager','settings','super-settings'])`. El rol `administrative` está sembrado (`database/seeders/PermissionsAuditSeeder.php:861`, `Role::findOrCreate`) y es el rol principal documentado de agentes; está ausente del filtro de `AgentSettingsController`, por lo que los agentes que sólo tienen `administrative` quedan invisibles en el índice de ajustes y excluidos de los conteos de `getStats()`. Matiz: `TeamController.php:62,130` usa el mismo conjunto `['admin','manager','support','callcenter']` y `TeamControllerTest:36` crea `support` vía `firstOrCreate`, así que esos roles podrían existir en producción — pero la omisión confirmada de `administrative` es lo que hace el bug real. La afirmación de que `admin` es rol fantasma queda sin verificar (más débil que el hallazgo central).

**Impacto:** El listado y `getStats()` de agentes en Ajustes omite a los agentes `administrative` (los reales), dejando la pantalla casi vacía y las estadísticas mal calculadas.

**Recomendación:** Unificar el conjunto de roles con `AgentsController` (incluir `administrative`). Idealmente extraer la lista de roles-agente a `config` para una única fuente de verdad.

---

### AGEN-03 · [CONFIRMADO] · Medium · wiring
**Comando de limpieza de presencia ni registrado ni programado**
`app/Console/Commands/CleanupAgentPresence.php:10`

**Evidencia:** `helpdesk:agents:cleanup-presence` no aparece en `HelpdeskServiceProvider::commands()` (línea 327) ni en el bloque `schedule()` (línea 334-341, sólo `check-sla`/`process-broadcasts`/`purge-old-gdpr-deletes`). Verificado por grep: ninguna referencia al comando en el provider.

**Impacto:** `cleanup()` nunca corre automáticamente. La clave Redis caduca a los 90s (`getState`/`getOnlineAgents` siguen correctos), pero `AgentSettings.presence_state` en BD queda `available/busy` indefinidamente y, sobre todo, el evento `AgentPresenceChanged(offline)` nunca se emite, por lo que los indicadores de presencia en la UI (alimentados por broadcast) no se apagan al desconectarse el agente.

**Recomendación:** Registrar `CleanupAgentPresence::class` en `commands()` y `$schedule->command('helpdesk:agents:cleanup-presence')->everyMinute()`.

---

### AGEN-04 · [CONFIRMADO] · Medium · wiring
**Modelo AgentCalendar completamente huérfano (feature inerte)**
`app/Models/AgentCalendar.php:7-43`

**Evidencia:** Grep en `app/`, `routes/`, `resources/` no encuentra ninguna referencia a `AgentCalendar` salvo el propio modelo. Sin controller, servicio, ruta ni vista.

**Impacto:** Funcionalidad de calendario/agenda de agente (providers cal.com/google/outlook) sin cablear: tabla y modelo sin uso, código muerto que aparenta una capacidad inexistente.

**Recomendación:** Cablear (CRUD + integración en el perfil/booking) o eliminar el modelo y su migración para no inducir a error.

---

### AGEN-05 · [DUDOSO] · Medium · performance
**N+1 en getAgentsList: getState() re-consulta la BD por agente**
`app/Services/AgentPresenceService.php:127-138,45-56`

**Evidencia:** `getAgentsList()` carga `AgentSettings` con `user` y luego en el map llama `$this->getState($id)`, que ejecuta `AgentSettings::where('user_id',$id)->value('presence_state')` + `Redis::ttl` por cada agente, pese a que `$settings->presence_state` ya está cargado. No reverificado línea por línea en esta pasada; clasificado [DUDOSO] por falta de confirmación directa, pero el patrón es coherente con el resto del subsistema.

**Impacto:** N consultas SQL + N round-trips Redis por cada render del listado de presencia (endpoint `manager.helpdesk.presence.agents`), escalando con el nº de agentes.

**Recomendación:** Usar `$settings->presence_state` ya cargado; resolver el estado online con un único pipeline Redis (MGET/pipeline de TTL) en vez de una llamada por agente.

---

### AGEN-06 · [CONFIRMADO] · Medium · wiring
**Métrica queue_pending_jobs siempre 0 bajo cola Redis**
`app/Http/Controllers/Managers/LiveDashboardController.php:91-94`

**Evidencia:** `queuePendingJobs()` hace `DB::table('jobs')->count()`, pero `config/queue.php` default = redis (Horizon). La tabla `jobs` no se usa. Confirmado por lectura directa del método.

**Impacto:** El widget 'trabajos pendientes' del dashboard en vivo muestra siempre 0, dando una falsa sensación de cola vacía.

**Recomendación:** Obtener el tamaño real desde Horizon/Redis (`Queue::size()` sobre las colas helpdesk) o retirar la métrica.

---

### AGEN-07 · [DUDOSO] · Medium · quality
**Leaderboard hace JOIN a users sobre la conexión helpdesk (rompe si la BD se separa)**
`app/Http/Controllers/Managers/LeaderboardController.php:25-40`

**Evidencia:** `DB::connection('helpdesk')->table('helpdesk_conversations as c')->join('users as u', ...)`. `config/database.php:199` permite `DB_DATABASE_HELPDESK` distinta de la default; `users` vive en la BD default. No reverificado línea por línea en esta pasada.

**Impacto:** Acoplamiento latente: si helpdesk usa BD propia (soportado por config) el join falla. Rompe el patrón explícito 'cross-DB safe' que sigue el resto del subsistema (`AgentPerformanceController` consulta `User` aparte).

**Recomendación:** Agregar por `assignee_id` sobre tablas helpdesk y resolver nombres con un `User::whereIn()` separado, como `AgentPerformanceController`.

---

### AGEN-08 · [CONFIRMADO] · Medium · wiring
**Auto-detección de skills sólo funciona para 5 slugs hardcodeados**
`app/Services/SkillsRoutingService.php:78-98`

**Evidencia:** `messageRequiresSkill()` tiene un mapa fijo de keywords por slug (`billing`/`technical`/`sales`/`french`/`english`). `detectAndAttachSkills()` recorre `Skill::all()` pero sólo adjunta si el slug está en ese mapa. Confirmado por lectura directa del servicio.

**Impacto:** Cualquier skill creado vía `SkillsController` con otro slug nunca se auto-detecta ni se adjunta, por lo que `routeBySkills` no lo enruta. La gestión de skills por UI es en gran parte decorativa para el enrutamiento automático.

**Recomendación:** Persistir keywords por `Skill` (columna/relación) y leerlas desde BD, o documentar la limitación y validar slugs soportados.

---

### AGEN-09 · [DUDOSO] · Medium · quality
**Contador current_open_count nunca se mantiene**
`app/Models/AgentSettings.php:104-105,153`

**Evidencia:** Según el grep del análisis, no existe ningún `increment`/`decrement` ni asignación de `current_open_count` en `assignTo`/`close` ni en ningún servicio (sólo el cast y los lectores). `canReceiveAssignment()` usa `($this->current_open_count ?? 0)` sin fallback a conteo en vivo. No reverificado exhaustivamente en esta pasada.

**Impacto:** `current_open_count` queda en 0/null permanentemente; el chequeo de límite de `canReceiveAssignment()` siempre lee 0 y nunca limitaría (agravado por AGEN-01, que ni siquiera invoca el método). Columna efectivamente muerta.

**Recomendación:** Mantener el contador en `assignTo`/`close` (o eliminarlo) y, en `canReceiveAssignment`, hacer fallback a conteo en vivo como hace `hasReachedLimit()`.

---

### AGEN-10 · [DUDOSO] · Medium · quality
**Dos editores de ajustes de agente paralelos con campos y permisos divergentes**
`app/Http/Controllers/Managers/AgentsController.php:195-211`

**Evidencia:** `AgentsController::update` (perm `helpdesk.manage`, `Requests\UpdateAgentSettingsRequest`: working_hours/accepts_conversations/vacation/max_concurrent) y `Settings\AgentSettingsController::update` (perm `helpdesk.agents.manage`, `Requests\Settings\UpdateAgentSettingsRequest`: is_available/auto_assign/max_concurrent/accepts/skills/vacation) editan la MISMA tabla con conjuntos de campos disjuntos. No reverificado línea por línea en esta pasada.

**Impacto:** Dos UI/permiso/Form Request distintos sobre `helpdesk_agent_settings`; campos que un editor no toca pueden quedar inconsistentes y el reparto de permisos es confuso. Duplicación de lógica de horarios/vacaciones.

**Recomendación:** Consolidar en un único editor/servicio de `AgentSettings` con un solo Form Request y un permiso coherente; si se mantienen ambos, documentar la separación de responsabilidades.

---

### AGEN-11 · [CONFIRMADO] · Low · security
**Presencia se emite por canal público (fuga de user_id/nombre)**
`app/Events/AgentPresenceChanged.php:22-27`

**Evidencia:** `broadcastOn()` devuelve `new Channel('helpdesk.presence.global')` (canal público, sin autorización). Confirmado por lectura directa; `broadcastWith()` incluye `user_id` y `name`.

**Impacto:** Cualquier cliente capaz de conectar a Reverb puede suscribirse y observar quién está online y sus cambios de estado, sin ser staff.

**Recomendación:** Usar `PrivateChannel` autorizado a usuarios con permiso helpdesk (canal en `routes/channels`) en lugar de `Channel` público.

---

### AGEN-12 · [CONFIRMADO] · Low · security
**Regex de RoutingRule sin validar se ejecuta contra cada mensaje entrante**
`app/Models/RoutingRule.php:45`

**Evidencia:** `'regex' => (bool) @preg_match($this->keyword, $text)`; el patrón lo fija un admin sin validación de delimitadores ni de compilación, y el `@` silencia errores. Confirmado por lectura directa del método `matches()`.

**Impacto:** Patrón mal formado falla en silencio (regla nunca matchea sin aviso) y un patrón con backtracking catastrófico puede causar ReDoS al evaluar cuerpos de mensajes en el job de auto-asignación.

**Recomendación:** Validar el patrón en `StoreRoutingRuleRequest`/`UpdateRoutingRuleRequest` (preg_match de prueba) y registrar/avisar de patrones inválidos en vez de tragarlos.

---

### AGEN-13 · [DUDOSO] · Low · conventions
**Desviaciones de convención: validación inline y permiso semánticamente incorrecto**
`app/Http/Controllers/Managers/AgentPresenceController.php:43`

**Evidencia:** `setState()` usa `$request->validate([...])` inline en vez de Form Request (regla del proyecto). Además `LeaderboardController.php:15` hace `authorize('viewAny', Customer::class)` para un leaderboard de equipo. No reverificado línea por línea en esta pasada.

**Impacto:** Inconsistencia con las reglas del módulo; el permiso del leaderboard depende de la policy de `Customer` en vez de un permiso de reportes/agentes, lo que dificulta el control de acceso correcto.

**Recomendación:** Extraer un `SetPresenceStateRequest`; cambiar la autorización del leaderboard a un permiso de reportes (p.ej. `helpdesk.reports.view`) como `AgentPerformanceController`.

---

### AGEN-14 · [DUDOSO] · Low · performance
**Sin índice en last_heartbeat_at/presence_state (full scan en cleanup/online)**
`database/migrations/2026_05_06_160000_add_presence_to_helpdesk_agent_settings.php:15-23`

**Evidencia:** La migración añade `presence_state` y `last_heartbeat_at` sin índice; `getOnlineAgents()` y `cleanup()` filtran por estas columnas. No reverificado en esta pasada.

**Impacto:** Escaneo completo de `helpdesk_agent_settings` en cada cleanup/listado de online. Tabla pequeña (sólo agentes), impacto bajo pero crece con la plantilla.

**Recomendación:** Añadir índice `(presence_state, last_heartbeat_at)` en una migración.

---

### AGEN-15 · [CONFIRMADO] · Low · tests
**Cobertura de tests del subsistema casi nula**
`tests/Feature/RoutingRulesControllerTest.php:1`

**Evidencia:** Único test del subsistema. Sin tests para `AgentPresenceService` (heartbeat/getState/cleanup), `SkillsRoutingService` (routeBySkills/detectAndAttachSkills), `RoutingRuleService`, `AutoAssignNewConversation`, `AgentInboxCapacity`, `AgentProfile/Performance/Leaderboard/LiveDashboard` ni WebRTC signaling.

**Impacto:** Las reglas de capacidad/disponibilidad/presencia y el enrutamiento (la lógica más delicada) no tienen red de seguridad; regresiones como AGEN-01/02/03 pasarían inadvertidas.

**Recomendación:** Añadir tests unitarios para `AgentSettings` (canReceiveAssignment/acceptsConversationsNow/hasReachedLimit) y `SkillsRoutingService`, y feature tests para presence endpoints y auto-asignación.

## Plan de ataque priorizado

1. **AGEN-01 (High, M)** — Conectar disponibilidad/capacidad/presencia al enrutamiento: filtrar candidatos en `SkillsRoutingService::routeBySkills` y `RoutingRuleService::matchAndAssign` reutilizando el patrón de `Group::getNextAgent`. Es la corrección de mayor impacto funcional.
2. **AGEN-02 (High, S)** — Unificar los roles de agente con `AgentsController` (incluir `administrative`); extraer a config. Desbloquea el panel de ajustes y las estadísticas.
3. **AGEN-03 (Medium, S)** — Registrar y programar `CleanupAgentPresence` para que los indicadores de presencia se apaguen.
4. **AGEN-09 (Medium, M)** — Mantener o eliminar `current_open_count`; clave para que el límite de capacidad funcione tras AGEN-01.
5. **AGEN-08 (Medium, M)** — Persistir keywords de skills en BD para que el enrutamiento por skills no sea decorativo.
6. **AGEN-04 / AGEN-10 (Medium, M)** — Decidir cablear o eliminar `AgentCalendar`; consolidar los dos editores de `AgentSettings`.
7. **AGEN-11 / AGEN-12 (Low, S)** — Endurecer broadcasting de presencia (PrivateChannel) y validar regex de reglas.
8. **AGEN-15 (Low, M)** — Cobertura de tests para blindar la lógica de capacidad/enrutamiento.

## Quick wins

- Añadir `CleanupAgentPresence` a `commands()` + `schedule everyMinute` en `HelpdeskServiceProvider` (AGEN-03).
- Reemplazar `['admin','manager','support','callcenter']` por los roles reales (incluir `administrative`) en `AgentSettingsController` (AGEN-02).
- Usar `$settings->presence_state` ya cargado en `getAgentsList` en vez de re-consultar la BD por agente (AGEN-05).
- Sustituir o eliminar `queuePendingJobs()` que cuenta la tabla `jobs` vacía bajo cola Redis (AGEN-06).
- Cambiar `Channel` por `PrivateChannel` en `AgentPresenceChanged` (AGEN-11).

## Fortalezas

- El path de automatización (`AssignAgentAction` -> `Group::getNextAgent`) SÍ respeta `acceptsConversationsNow()` + `hasReachedLimit()` y soporta `round_robin`/`load_balanced`.
- `AutoAssignNewConversation` está bien blindado: gated por config (inerte por defecto), idempotente, cada etapa en try/catch sin re-throw, `ShouldQueue` con backoff y `failed()`.
- Casi todas las clases evitan deliberadamente joins cross-DB (`AgentPerformance`, `AgentSettings`, `AgentProfile` consultan `User` por separado y comentan 'cross-DB safe').
- WebRTC signaling protegido consistentemente: answer/ice vía Form Request con `authorize()` `helpdesk.conversations.view`; end/request/livestreamHistory verifican permiso.
- Form Requests de WebRTC validan tamaños de SDP/ICE candidate explícitamente.

## Cobertura de la auditoría

Cobertura completa (no muestreo) de los archivos del alcance: 12 controllers (AgentPresence/Performance/Profile/Agents/Leaderboard/LiveDashboard/WebRtcAgent + Settings AgentInboxCapacity/AgentSettings/RoutingRules/Skills + Api AgentsApiController), 3 servicios (AgentPresenceService, SkillsRoutingService, RoutingRuleService), AssignAgentAction, modelos (AgentSettings, AgentInboxCapacity, AgentCalendar, RoutingRule, Skill), el listener AutoAssignNewConversation, `Group::getNextAgent`, `AgentPresenceChanged` event, Form Requests relevantes, rutas `managers.php`, RouteServiceProvider, `config/database.php` (conexión helpdesk) y migraciones de `agent_settings`. Verificado estáticamente uso/no-uso de cada pieza vía grep; los hallazgos de severidad alta (AGEN-01/02) y una muestra de los medios/bajos (AGEN-03/04/06/08/11/12/15) se reverificaron por lectura directa de código en esta pasada. **NO se ejecutaron tests** (BD de test bloqueada). No se re-listan hallazgos transversales ya conocidos (OBS-01, ARCH-01, etc.).

## Descartados en verificación

Ninguno. Ningún hallazgo fue refutado durante la verificación; los marcados [DUDOSO] se mantienen en el informe a la espera de confirmación línea por línea, pero su evidencia es coherente con el resto del subsistema.
