# Auditoría — HelpdeskAgents

> Fecha: 2026-06-29 · Health score: 63/100 · Estado: half-wired

**Resumen:** Bloques de construcción de agentes IA bien diseñados (defensas SSRF sólidas, rate limiting, circuit breaker, sanitización de prompts, políticas y form-requests correctos) cuyo runtime central nunca se conecta al flujo de conversaciones de Helpdesk. El motor, la ejecución de herramientas y la recuperación de conocimiento son inalcanzables en producción; además hay un redirect de borrado roto, tests con nombres de ruta obsoletos y configuración que no llega al motor. La ingeniería es buena, pero la integración está a medio cablear.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HA-01 | high | wiring | app/Jobs/StartAiAgentSessionJob.php:16-61 | [CONFIRMADO] | L | Runtime de IA huérfano — nunca se invoca desde Helpdesk |
| HA-02 | high | wiring | app/Http/Controllers/Managers/AiAgentFlowsController.php:239 | [CONFIRMADO] | S | destroy() redirige a un nombre de ruta inexistente |
| HA-03 | high | tests | tests/Feature/AiTagsControllerTest.php:24-34 | [CONFIRMADO] | S | Tests feature aseveran nombres de ruta que no existen |
| HA-04 | medium | wiring | app/Services/AiAgentFlowEngine.php:310-315 | [CONFIRMADO] | S | El runtime lee params del agente desde backups; settings escriben en parameters |
| HA-05 | medium | performance | app/Http/Controllers/Managers/AiAgentFlowsController.php:24-28 | [CONFIRMADO] | S | Cache key obsoleta nunca invalidada en pantallas de flows |
| HA-06 | medium | conventions | app/Http/Controllers/Managers/AiAgentFlowsController.php:250-282 | [CONFIRMADO] | S | Endpoints de nodos usan validación inline; Form Requests dedicados huérfanos |
| HA-07 | medium | performance | app/Services/KnowledgeRetrievalService.php:36-61 | [CONFIRMADO] | M | La búsqueda por similitud carga todos los embeddings y puntúa en PHP |
| HA-08 | medium | wiring | app/Services/AgentAvailabilityService.php:12-42 | [CONFIRMADO] | M | La disponibilidad/turnos de agentes no tiene consumidor |
| HA-09 | low | security | app/Services/LlmConnectionTesterService.php:72-92 | [CONFIRMADO] | S | Test de conexión 'local' permite SSRF vía base_url arbitraria |
| HA-10 | low | quality | app/Http/Controllers/Managers/AiAgentFlowsController.php:97-110 | [CONFIRMADO] | S | store() sin guard de null en AiAgent::first() |
| HA-11 | low | security | app/Services/AiAgentFlowEngine.php:516-522 | [CONFIRMADO] | S | API key de Gemini enviada en el query string de la URL |
| HA-12 | low | conventions | app/Models/AiToolExecution.php:17 | [CONFIRMADO] | S | AiToolExecution usa $guarded = [] en vez de $fillable explícito |
| HA-13 | low | conventions | app/Http/Controllers/Managers/AiAgentFlowsController.php:20-314 | [CONFIRMADO] | S | Métodos del controlador sin return type declarations |
| HA-14 | low | ux | resources/views/managers/ai-agent/partials/tags-tab.blade.php:83 | [CONFIRMADO] | S | Estilo inline en el swatch de color de tag |

## Hallazgos detallados

### HIGH

#### HA-01 — Runtime de IA huérfano, nunca se invoca desde Helpdesk · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Jobs/StartAiAgentSessionJob.php:16-61`
- **Evidencia:** Un grep en todo el proyecto de `StartAiAgentSessionJob`, `AiAgentFlowEngine`, `ToolExecutionService` y `KnowledgeRetrievalService` fuera de este módulo devuelve cero coincidencias no-vendor. El único punto de entrada del runtime (`StartAiAgentSessionJob`) nunca se despacha, por lo que `AiAgentFlowEngine::startSession/processMessage`, `ToolExecutionService::execute()` y `KnowledgeRetrievalService::findRelevant()` son inalcanzables en producción. Los servicios se registran como singletons en `HelpdeskAgentsServiceProvider:53-57` pero ningún trigger externo los invoca. El `AiAgentKnowledgeBaseObserver` solo despacha `GenerateEmbeddingJob`. Ni el core de Helpdesk ni HelpdeskChatFlow (que tiene su propio motor) enganchan con este runtime.
- **Impacto:** El propósito declarado del módulo (agentes IA respondiendo en conversaciones de Helpdesk) no funciona; los admin pueden configurar flows/herramientas/conocimiento pero nada se ejecuta.
- **Recomendación:** Añadir un listener/observer sobre mensajes entrantes de conversación que despache `StartAiAgentSessionJob` para un agente activo, y que los nodos de prompt llamen a `KnowledgeRetrievalService` y los nodos de acción/herramienta a `ToolExecutionService`. Si es intencionadamente latente, protegerlo tras un feature flag y documentarlo.
- **Esfuerzo:** L

#### HA-02 — destroy() redirige a un nombre de ruta inexistente · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Http/Controllers/Managers/AiAgentFlowsController.php:239`
- **Evidencia:** `redirect()->route('manager.helpdesk.ai.flows.index')`, pero el ServiceProvider registra el prefijo de nombre `helpdesk.ai.` (`HelpdeskAgentsServiceProvider.php:121`), por lo que el nombre real es `helpdesk.ai.flows.index` (confirmado por `managers.php:47` y por `AiFlowsControllerTest.php:24` que asevera correctamente `Route::has('helpdesk.ai.flows.index')`). El prefijo `manager.` no existe en el registro de rutas.
- **Impacto:** Borrar cualquier flow lanza `RouteNotFoundException` (500) en lugar de redirigir.
- **Recomendación:** Cambiar a `route('helpdesk.ai.flows.index')`.
- **Esfuerzo:** S

#### HA-03 — Tests feature aseveran nombres de ruta que no existen · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/tests/Feature/AiTagsControllerTest.php:24-34`
- **Evidencia:** `AiTagsControllerTest.php:24-34`, `AiToolsControllerTest.php:24-34` y `AiKnowledgeControllerTest.php:24-34` aseveran `Route::has('manager.helpdesk.ai.{tags|tools|knowledge}.{index|store|destroy}')`. El ServiceProvider registra el prefijo `helpdesk.ai.` y `managers.php` define `tags.index`, `tools.index`, `knowledge.index` → los nombres reales son `helpdesk.ai.*`. El prefijo `manager.` es un artefacto obsoleto: `AiFlowsControllerTest.php:24` ya usa el corregido `helpdesk.ai.flows.index`, confirmando un rename incompleto.
- **Impacto:** Los tests de existencia de rutas fallan; la suite deja de validar el cableado real de rutas, enmascarando regresiones.
- **Recomendación:** Actualizar todas las aseveraciones `manager.helpdesk.ai.*` a `helpdesk.ai.*` para coincidir con el provider.
- **Esfuerzo:** S

> Nota de causa raíz compartida (HA-02/HA-03): un rename de prefijo `manager.helpdesk.ai.` → `helpdesk.ai.` se aplicó al ServiceProvider y a `AiFlowsControllerTest` pero no se propagó a `AiAgentFlowsController::destroy()` ni a los tres tests (Tags, Tools, Knowledge). Fix mecánico: `s/manager.helpdesk.ai./helpdesk.ai./` en cuatro archivos.

### MEDIUM

#### HA-04 — El runtime lee params del agente desde backups; settings escriben en parameters · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Services/AiAgentFlowEngine.php:310-315`
- **Evidencia:** `callAiProvider()` hace `$config = $agent->backups ?? []` y lee `temperature`/`max_tokens` de ahí. `AgentSettingsController::update()` guarda los valores ajustados en `$agent->parameters` (`AgentSettingsController.php:80`), nunca en `backups`. `AiAgent::getModelConfig()` lee `parameters ?? backups`, pero el motor lo bypasea.
- **Impacto:** `temperature`, `top_p` y `max_tokens` configurados por el admin se ignoran en runtime; el motor siempre cae a defaults (0.7 / 500 tokens).
- **Recomendación:** Leer la config vía `$agent->getModelConfig()` o `$agent->parameters` en `callAiProvider()`.
- **Esfuerzo:** S

#### HA-05 — Cache key obsoleta nunca invalidada en pantallas de flows · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Http/Controllers/Managers/AiAgentFlowsController.php:24-28`
- **Evidencia:** `index()` y `create()` cachean el agente bajo la key literal `'helpdesk:ai-agent:first'` (TTL 300s). El observer y el controlador de settings solo olvidan `'helpdesk:ai-agent:default'` (`InteractsWithDefaultAiAgent::DEFAULT_AGENT_CACHE_KEY`). La key `first` nunca se limpia al guardar/borrar.
- **Impacto:** Tras renombrar o borrar el agente, el index/create de flows puede mostrar un agente obsoleto o borrado hasta 5 minutos; además es inconsistente con `store()` que usa `AiAgent::first()` sin caché.
- **Recomendación:** Usar el `getDefaultAgent()`/`DEFAULT_AGENT_CACHE_KEY` compartido del trait para que `AiAgentObserver` lo invalide.
- **Esfuerzo:** S

#### HA-06 — Endpoints de nodos usan validación inline; Form Requests dedicados huérfanos · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Http/Controllers/Managers/AiAgentFlowsController.php:250-282`
- **Evidencia:** `storeNode()` y `updateNode()` llaman `$request->validate([...])` inline, violando la regla de Form Requests, mientras `StoreAiAgentFlowNodeRequest` y `UpdateAiAgentFlowNodeRequest` existen con `authorize()/rules()/messages()` correctos pero nunca se importan ni usan.
- **Impacto:** Ruta de validación/autorización inconsistente y código muerto; la creación de nodos carece de los mensajes en español definidos en los requests sin usar.
- **Recomendación:** Type-hint de `StoreAiAgentFlowNodeRequest`/`UpdateAiAgentFlowNodeRequest` en esos métodos y eliminar el `validate()` inline.
- **Esfuerzo:** S

#### HA-07 — La búsqueda por similitud carga todos los embeddings y puntúa en PHP · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Services/KnowledgeRetrievalService.php:36-61`
- **Evidencia:** `findBySimilarity()` recupera cada fila activa de conocimiento con embedding no nulo (`->get()`), hace `json_decode` de cada vector y calcula similitud coseno en PHP. La columna precalculada `vector_norm` (escrita por `GenerateEmbeddingJob`, migración `2026_05_21_000200`) nunca se usa para prefiltrar o acelerar.
- **Impacto:** Memoria y CPU escalan linealmente con el tamaño de la KB en cada consulta; sin límite/prefiltro en DB — se degrada con bases de conocimiento grandes.
- **Recomendación:** Prefiltrar candidatos (shortlist fulltext, o batch + chunk), usar el `vector_norm` almacenado, o mover a índice/extensión vectorial; limitar las filas traídas a memoria.
- **Esfuerzo:** M

#### HA-08 — La disponibilidad/turnos de agentes no tiene consumidor · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Services/AgentAvailabilityService.php:12-42`
- **Evidencia:** `AgentAvailabilityService` (`isAgentAvailableNow`/`availableAgents`) no se referencia fuera de su propio unit test; `OncallRotationResource` y `AgentShiftResource` existen pero no hay `api.php` ni consumidor. Turnos/vacaciones/on-call son CRUD puro sin efecto en la asignación de tickets/conversaciones.
- **Impacto:** Turnos, vacaciones y rotaciones on-call configurados no influyen en ninguna lógica de enrutado/asignación; el esfuerzo es inerte.
- **Recomendación:** Integrar `availableAgents()`/`isAgentAvailableNow()` en la ruta de asignación (o exponer los API Resources vía rutas), o marcar la feature como solo-configuración.
- **Esfuerzo:** M

### LOW

#### HA-09 — Test de conexión 'local' permite SSRF vía base_url arbitraria · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Services/LlmConnectionTesterService.php:72-92`
- **Evidencia:** `testLocal()` hace `Http::get($baseUrl.'/api/tags')` con `$baseUrl` de input validado (`TestAiAgentConnectionRequest` permite cualquier `url`, http incluido) y NO aplica el guard `validateApiUrl()`/IP-privada usado en `ToolExecutionService`.
- **Impacto:** Un admin (super-admin/super-settings) puede sondear servicios internos; radio de impacto limitado por estar protegido por rol y no devolver cuerpos verbatim, pero es una petición saliente sin guard a un host suministrado por el usuario.
- **Recomendación:** Reusar la validación IP-privada/host-allowlist (o restringir `base_url` a un allowlist configurado) antes de llamar a Ollama.
- **Esfuerzo:** S

#### HA-10 — store() sin guard de null en AiAgent::first() · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Http/Controllers/Managers/AiAgentFlowsController.php:97-110`
- **Evidencia:** `store()` hace `$agent = AiAgent::first();` y luego `$agent->flows()->create(...)` sin chequeo de null, a diferencia de `index()/create()` que redirigen cuando no hay agente.
- **Impacto:** Un POST directo sin agente configurado lanza un error fatal de método sobre null (500).
- **Recomendación:** Guardar contra null y redirigir a `helpdesk.ai.settings` con un error, espejando `create()`.
- **Esfuerzo:** S

#### HA-11 — API key de Gemini enviada en el query string de la URL · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Services/AiAgentFlowEngine.php:516-522`
- **Evidencia:** `callGemini()` hace POST a `'...:generateContent?key={$apiKey}'`, incrustando el secreto en la URL en vez de en un header.
- **Impacto:** La API key puede filtrarse a logs de petición, proxies o trazas APM.
- **Recomendación:** Enviar la key vía header `x-goog-api-key` (como ya hace `LlmConnectionTesterService::testGemini`).
- **Esfuerzo:** S

#### HA-12 — AiToolExecution usa $guarded = [] en vez de $fillable explícito · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Models/AiToolExecution.php:17`
- **Evidencia:** `protected $guarded = [];` viola la regla de modelos del proyecto (siempre definir `$fillable` explícito).
- **Impacto:** Mass-assignment sin restricción; riesgo práctico bajo porque el modelo se escribe vía inserts `DB::table`, pero rompe la convención.
- **Recomendación:** Reemplazar con un `$fillable` explícito listando las columnas de log.
- **Esfuerzo:** S

#### HA-13 — Métodos del controlador sin return type declarations · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/app/Http/Controllers/Managers/AiAgentFlowsController.php:20-314`
- **Evidencia:** `index/create/store/edit/update/publish/archive/duplicate/destroy/storeNode/updateNode/deleteNode/show` no declaran return types, a diferencia de `AgentSettingsController/ScheduleController` que usan `View/JsonResponse/RedirectResponse`.
- **Impacto:** Inconsistente con las reglas PHP del proyecto (return types explícitos) y análisis estático más débil.
- **Recomendación:** Añadir return types `View|RedirectResponse` / `JsonResponse` a todos los métodos.
- **Esfuerzo:** S

#### HA-14 — Estilo inline en el swatch de color de tag · [CONFIRMADO]
- **Archivo:** `modules/HelpdeskAgents/resources/views/managers/ai-agent/partials/tags-tab.blade.php:83`
- **Evidencia:** `style="background-color:{{ $tag->color ?? '#90bb13' }};"` usa un atributo de estilo inline, que las reglas de blade prohíben.
- **Impacto:** Violación menor de convención (color dinámico es una excepción común, pero la regla es no inline style).
- **Recomendación:** Fijar el color vía custom property CSS / data-attribute y una regla CSS pequeña, o aceptarlo como excepción documentada de valor dinámico.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **Decisión arquitectónica (HA-01):** cablear el runtime de IA en Helpdesk (despachar `StartAiAgentSessionJob` en mensajes entrantes) o documentarlo como dormido tras feature flag. Es el bloqueante de valor del módulo.
2. **Fix de rutas mecánico (HA-02, HA-03):** `s/manager.helpdesk.ai./helpdesk.ai./` en `AiAgentFlowsController::destroy()` y en los tres tests (Tags/Tools/Knowledge). Restaura el borrado de flows y la validez de la suite.
3. **Alinear fuente de configuración (HA-04):** leer params vía `getModelConfig()`/`parameters` para que la afinación del admin surta efecto.
4. **Coherencia de caché (HA-05):** unificar la key con `DEFAULT_AGENT_CACHE_KEY`.
5. **Consistencia de validación (HA-06):** usar los Form Requests existentes en los endpoints de nodos.
6. **Escalabilidad KB (HA-07):** prefiltrar/limitar la búsqueda de similitud.
7. **Integrar o desactivar disponibilidad (HA-08).**
8. **Endurecimiento y limpieza (HA-09 a HA-14):** SSRF en test local, guard null, key Gemini en header, `$fillable`, return types, estilo inline.

## Quick wins

- Corregir el nombre de ruta en `destroy()` (fix de una línea). [HA-02]
- Actualizar tests obsoletos `manager.helpdesk.ai.*` → `helpdesk.ai.*`. [HA-03]
- Cambiar `AiAgentFlowEngine::callAiProvider()` para leer `getModelConfig()`/`parameters` en vez de `backups`. [HA-04]
- Unificar la cache key del flows-controller con `InteractsWithDefaultAiAgent::DEFAULT_AGENT_CACHE_KEY` para que el observer la invalide. [HA-05]
- Enviar la key de Gemini en header `x-goog-api-key`. [HA-11]
- Reusar el guard IP-privada/allowlist en `testLocal()`. [HA-09]

## Fortalezas

- Defensas SSRF sólidas en `ToolExecutionService` (solo HTTPS, rechazo de IP privadas/reservadas vía `gethostbyname` + `FILTER_FLAG_NO_PRIV/RES_RANGE`, allowlist de host opcional, todos los tipos de herramienta desactivados por defecto en config).
- Llamadas LLM endurecidas: rate limiting por usuario/sesión/día (`RateLimiter`), `CircuitBreaker` por proveedor, timeouts + reintentos acotados, logging estructurado en el canal helpdesk.
- `PromptSanitizer` dedicado (truncado, stripping de caracteres de control, filtrado de patrones de inyección dirigido por config con logging en canal security) aplicado al input antes del dispatch al LLM.
- Form Requests consistentes y completos: chequeos reales de permiso Spatie en `authorize()`, `messages()` y `attributes()` en español en todo el módulo.
- Policies registradas vía `Gate::policy` en el ServiceProvider; `api_key` con cast `encrypted`; método `casts()` usado (sin propiedad `$casts`); blades libres de iconos Tabler / select2 bootstrap-5 / XSS de usuario sin escapar.
- Amplitud razonable de tests (~15 archivos / ~90 métodos) cubriendo servicios, modelos, excepciones y controladores.

## Cobertura de la auditoría

Análisis estático únicamente (DB de test bloqueada). Se leyeron todos los controladores, servicios, providers, rutas, policies, modelos clave, jobs, observers, el seeder de permisos y una muestra de form requests + blades. No se leyeron de forma exhaustiva todos los cuerpos de migración, todos los `rules()` de cada Form Request, las internas de factories ni el JavaScript del editor de flujos en `edit.blade.php`. Los hallazgos citan `archivo:línea` real; los comportamientos solo-runtime (p. ej. dispatch de middleware) se razonaron desde el código fuente, no se ejecutaron.

## Descartados en verificación

Ninguno. La verificación de los tres hallazgos high se confirmó con evidencia directa de archivo y no se detectaron falsos positivos. El resto de hallazgos (medium/low) se mantienen como [CONFIRMADO] al estar respaldados por evidencia `archivo:línea` y no haber sido refutados.
