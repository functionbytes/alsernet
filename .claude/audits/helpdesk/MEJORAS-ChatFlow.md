# Mejoras — HelpdeskChatFlow (2026-06-30)

> Módulo: `modules/HelpdeskChatFlow` (~13.4k LOC · 100 PHP · 7 blade · 1 editor React/TSX de 2.263 LOC).
> Stack: Laravel 12, conexión `helpdesk`, Reverb/Redis, Bootstrap 5.3 + jQuery (con una isla React en el editor),
> color `#90bb13`, Font Awesome 6. Entrega vía `BotMessageDispatcher → OutboundMessageService`; reúsa servicios
> del core Helpdesk (voz/multilingüe/sentiment/RAG/HSM) por wrappers opcionales.
>
> Este documento **eleva** los bugs ya catalogados (`HelpdeskChatFlow.md`, `MEJORAS.md`, `UX-PERFORMANCE.md`,
> `INDEX.md`) a iniciativas de mejora accionables. No repite los hallazgos como bugs sueltos: los reformula como
> oportunidades de producto/arquitectura y añade mejoras nuevas detectadas en la lectura del motor, el ejecutor de
> nodos, el observer, los servicios IA, el editor y los modelos.
>
> Convención de esfuerzo: `s` = horas/1 día · `m` = días · `l` = 1-2 semanas · `xl` = multi-semana.
> IDs `CFM-*` (ChatFlow Mejoras). Entre paréntesis se referencia el bug de origen cuando aplica (CF-1..CF-9).

---

## Resumen ejecutivo

HelpdeskChatFlow es uno de los módulos **más maduros** del subsistema: motor de flujo robusto (locking por
conversación, buffer de contexto que colapsa ~11 UPDATEs en uno, árbol de nodos indexado O(1) por run, manejo de
zombies/depth, escape/sentiment/timeout/handoff), SSRF endurecido, 25 tipos de nodo (incluido `ai_agent` con
tool-calling), versionado, import/export, simulador y test-cases de regresión, y buena cobertura de tests. No hay
hallazgos critical/high.

La oportunidad **no** es arreglar fallos graves, sino **subir el techo** en cuatro frentes:

1. **Velocidad**: el trabajo IA/HTTP de los mensajes entrantes corre **síncrono dentro de un observer global de
   `ConversationItem`** (bloquea webhooks decenas de segundos) y el observer se ejecuta en *cada* mensaje del
   helpdesk aunque no haya bot. El job asíncrono previsto existe pero está muerto e incompleto.
2. **Producto**: faltan piezas que ya tienen el 80% del andamiaje — separación draft/publicado del grafo,
   enrutado por intención (NLU) real en el trigger, comparación A/B en la analítica, operadores de condición ricos
   y un validador que detecte enlaces rotos/nodos inalcanzables.
3. **UX**: decidir formalmente la isla React del editor, avisar de "cambios sin publicar", bloqueo optimista
   anti-pisado y validación inline en el canvas.
4. **Seguridad/arquitectura**: cerrar el path-traversal del simulador, el scoping de test-cases, alinear el gating
   rol-vs-permiso, añadir un PromptSanitizer compartido y centralizar las 3+ rutas directas a OpenAI en el gateway
   LLM del core.

El mayor ROI temprano son varios **quick wins de horas** (seguridad + validador + gate del observer) y una
**iniciativa media de alto impacto**: mover el pipeline de mensajes a cola **bien hecho** (con attachments, rama
de arranque y orden por conversación).

---

## Top por ROI

Ranking por ROI; desempata mayor impacto y luego menor esfuerzo.

| # | ID | Oportunidad | Eje | Impacto | Esf. | ROI |
|---|----|-------------|-----|---------|------|-----|
| 1 | **CFM-P1** | Pipeline de mensajes entrantes a cola, completo (attachments + rama arranque + orden por conversación) (CF-1/CF-2) | Performance | high | m | high |
| 2 | **CFM-S1** | Sanear `session_key`/`doc_key` del upload del simulador (path traversal) (CF-4) | Seguridad | medium | s | high |
| 3 | **CFM-F4** | Validador más fuerte: `go_to_step` roto, nodos inalcanzables, variables `{{}}` inexistentes | Producto | medium | s | high |
| 4 | **CFM-P2** | Gate barato del observer global (salir sin 2-3 queries cuando no hay flows activos) | Performance | medium | s | high |
| 5 | **CFM-F1** | Separar grafo draft vs publicado + workflow de publicación | Producto | high | m | high |
| 6 | **CFM-F2** | Enrutado por intención (NLU/embeddings) en el trigger, no solo substring | Producto | high | m | high |
| 7 | **CFM-S4** | PromptSanitizer compartido + delimitar input de cliente en nodos IA (CF-8/SEC-02) | Seguridad | medium | m | high |
| 8 | **CFM-S2** | Scoping de test-cases anidados al flow (`abort_unless`) (CF-5) | Seguridad | low | s | high |
| 9 | **CFM-S3** | Alinear gating a permisos `chatflow.*` + asignarlos a roles en el seeder (CF-6) | Arquitectura | medium | m | medium |
| 10 | **CFM-S5** | Centralizar las 3+ llamadas directas a OpenAI en el gateway LLM del core (ARCH-02) | Arquitectura | medium | m | medium |

---

## Quick wins (esfuerzo s, ROI alto)

- **[s] CFM-S1 (CF-4)** — `SimulatorUploadRequest` valida `session_key`/`doc_key` como `['required','string']` y
  el controller los concatena en la ruta de `storeAs('chatflow-test/'.$session_key, $doc_key.'_'.time()...)`.
  Añadir `['regex:/^[A-Za-z0-9_-]+$/']` (o `basename()`/`Str::slug()` antes de usarlos). Evidencia:
  `app/Http/Requests/SimulatorUploadRequest.php:16-17`, `app/Http/Controllers/ChatFlowTestController.php:59`.
- **[s] CFM-S2 (CF-5)** — `ChatFlowTestCasesController::run/destroy` bindean `{chatFlow}` y `{testCase}` por
  separado sin comprobar pertenencia. Añadir `abort_unless($testCase->chat_flow_id === $chatFlow->id, 404)`
  (igual que ya hace `replaySession`). Evidencia: `app/Http/Controllers/ChatFlowTestCasesController.php:42-63`
  vs. `ChatFlowsController.php:310`.
- **[s] CFM-F4** — El validador detecta start dup/orphan/dead-end/else, **pero no** que un `go_to_step` apunte a
  un `target_node_id` existente, ni nodos **inalcanzables** desde `start`, ni variables `{{var}}` referenciadas
  pero nunca escritas. Son los errores que más rompen flujos en producción. Evidencia:
  `app/Services/ChatFlowValidator.php` (sin chequeo de `go_to_step`/reachability; el motor solo descubre el target
  roto en runtime → `ChatFlowEngine.php:730-743`).
- **[s] CFM-P2** — `ConversationItem::observe(...)` es **global**: en *cada* mensaje entrante del helpdesk el
  observer hace `getActiveSession()` y a veces la consulta `hasPriorCustomerMessage` aunque no exista ningún
  chatbot. Cachear en Redis (TTL corto) "¿hay flows `active` para este inbox/trigger?" y salir antes de tocar BD.
  Evidencia: `app/Providers/HelpdeskChatFlowServiceProvider.php:211`, `app/Observers/ConversationItemObserver.php:45-61`.
- **[s] CFM-S7** — `import()` solo exige `nodes` + un `start`; no valida tipos de nodo ni datos. Pasar el JSON
  importado por `ChatFlowValidator` (y rechazar/avisar) evita importar flujos que petan en runtime. Evidencia:
  `app/Http/Controllers/ChatFlowsController.php:251-273`.
- **[s] CFM-U3** — Mostrar los `warnings`/`errors` del validador **inline en el canvas** del editor antes de
  publicar (hoy solo aparecen como toast al pulsar publicar). Reusa `ChatFlowValidator` ya existente.
- **[s] CFM-Q1** — Estandarizar la cola: `DeliverBotMessageJob`/`HandleNodeTimeoutJob` usan `chatflow` pero el
  job muerto `ExecuteChatFlowNodeJob` usa `helpdesk-events`. Unificar en `chatflow` y **verificar que existe
  supervisor de Horizon** para esa cola (cruza con OBS-01: las colas del subsistema están huérfanas en
  `production`). Sin supervisor, mover a cola = silenciar el bot. Evidencia: `app/Jobs/*` (`onQueue('chatflow')`
  vs `onQueue('helpdesk-events')`).

---

## Iniciativas grandes (esfuerzo l/xl)

### CFM-F1 — Separar el grafo `draft` del `publicado` + workflow de publicación `[m→l]`
Hoy editar un flow **activo** cambia el runtime **al instante**: el editor guarda `nodes` directamente vía
`update($request->validated())`, y `versions` solo hace snapshot **al publicar**. No hay aislamiento entre lo que el
diseñador está tocando y lo que los clientes están ejecutando. Propuesta: columnas/relación `nodes_draft` vs
`nodes_published`; el motor lee `nodes_published`; "Publicar" valida + promueve draft→published + snapshot de
versión; indicador de "cambios sin publicar" en lista y editor. Es la mejora de producto de mayor valor/seguridad.
Evidencia: `ChatFlowsController.php:127-129` (update) y `:182-189` (publish).

### CFM-F2 — Enrutado por intención (NLU) en el disparador `[m]`
El `keyword` trigger es **substring** (`str_contains`); no hay enrutado semántico a nivel de conversación. Reusar
`EmbeddingsService` (ya inyectado en `ChatFlowAiResponder`/`ChatFlowAgentService`) o el `classifyIntent` ya escrito
para elegir el flow por intención. Desbloquea "un cliente escribe libre → cae en el flow correcto" sin listar
keywords a mano. Evidencia: `app/Services/ChatFlowTriggerResolver.php:29-41`, `ChatFlowAiResponder::classifyIntent`
(patrón reusable).

### CFM-S5 / ARCH-02 — Gateway LLM compartido `[m]`
Tres servicios + `classifyIntent` llaman `Http::post('https://api.openai.com/v1/chat/completions')` con
timeout/retry propios (30s/40s/15s). Centralizar en el `AiClient` del core (ya se inyecta en
`ChatFlowVoiceTranscriber`) da punto único para timeout/retry/coste/observabilidad **y** para aplicar el
PromptSanitizer (CFM-S4). Evidencia: `ChatFlowAiResponder.php:130`, `ChatFlowAgentService.php:101`,
`ChatFlowHandoffSummary.php` (las 3 leen `config('services.openai.key')` — la clave ya está unificada, falta el
gateway). Nota positiva: ARCH-01 (clave canónica) **ya está aplicado** en este módulo.

### CFM-S6 / CF-7 — Deduplicar `ChatFlowEngine` vs `ChatFlowTestSimulator` `[l]`
El simulador (823 LOC) reimplementa `runFrom`/`executeNode`/evaluación de ramas en paralelo al motor (970 LOC).
Riesgo de deriva que **socava el propósito de los test-cases** (paridad con producción). Hacer que el simulador
conduzca el motor real sobre una sesión en memoria (`tests/Support/InMemoryChatFlowSession.php` ya existe como
base), o extraer un núcleo de ejecución compartido. Evidencia: `ChatFlowTestSimulator.php:35-57` + su `executeNode`.

### CFM-U1 / CF-3 / FE-09 — Decisión sobre la isla React del editor `[l decisión]`
`ChatFlowEditor.tsx` (2.263 LOC, React 19 + `@xyflow/react` + axios) viola la regla jQuery-only. Recomendación:
**sancionarlo como excepción documentada** ("isla React acotada para el canvas de grafo") en `CLAUDE.md`, porque
reescribir un editor de nodos drag-and-drop en jQuery es alto coste/bajo valor; el resto del módulo (lista, test
panel, modales) ya es jQuery correcto. Lo que NO debe permitirse es que la isla crezca fuera del canvas. Evidencia:
`resources/js/ChatFlowEditor.tsx:1`, `resources/views/editor.blade.php:17-33`.

### CFM-F3 — A/B real: comparación en analítica + multivariante `[m]`
A/B es configurable (`ab_variant_id`/`ab_split`, binario 1 variante) y el editor lo expone, pero `analytics()`
**no compara variantes** — calcula métricas por flow aislado. Añadir vista comparativa A vs B (resolución/CSAT/
drop-off lado a lado) y soporte multivariante. Sin la comparación, el A/B no tiene valor accionable. Evidencia:
`ChatFlowEngine.php:661-669`, `ChatFlowEditor.tsx:2146-2150`, `ChatFlowsController::analytics` (sin split).

---

## Secciones por eje

### Eje 1 — Producto / Features

| ID | Oportunidad | Impacto | Esf. | ROI | Evidencia |
|----|-------------|---------|------|-----|-----------|
| CFM-F1 | Draft vs publicado + workflow de publicación | high | m | high | `ChatFlowsController.php:127-129,182-189` |
| CFM-F2 | Enrutado por intención (NLU/embeddings) en trigger | high | m | high | `ChatFlowTriggerResolver.php:29-41` |
| CFM-F4 | Validador: `go_to_step` roto, inalcanzables, variables | medium | s | high | `ChatFlowValidator.php` |
| CFM-F3 | A/B real (comparación analítica + multivariante) | medium | m | medium | `ChatFlowEngine.php:661-669` |
| CFM-F5 | Operadores de condición ricos + grupos OR | medium | m | medium | `ChatFlowEngine.php:802-823` |
| CFM-F6 | Catálogo de variables tipadas + autocompletado | medium | m | medium | contexto free-form (`setContextValue`) |
| CFM-F7 | Ampliar biblioteca de plantillas de flujo | low | m | medium | `ChatFlowTemplateLibrary.php` |

- **CFM-F5** — `evaluateBranchItem` solo soporta `= != > < contains` y todas las condiciones de un branchItem se
  combinan en **AND** (no hay OR). Añadir `in`, `is_empty`/`not_empty`, `starts_with`, `regex`, rango numérico y
  grupos OR ampliaría mucho el poder de ramificación sin tocar el resto del motor. Evidencia:
  `ChatFlowEngine.php:802-823`.
- **CFM-F6** — Las variables de contexto son strings libres descubiertas solo leyendo nodos. Un catálogo de
  variables (system: `customer_*`, `order_*`, `within_business_hours`, `csat_score`...; + las definidas por el
  diseñador) con autocompletado de `{{}}` en el editor reduce errores de interpolación silenciosos (hoy un
  `{{var}}` inexistente se queda literal, ver `interpolateContext` en `ChatFlowNodeExecutor.php:795-798`).
- **CFM-F2/CFM-F4** combinan especialmente bien con el validador: validar que las variables referenciadas en
  `branches`/mensajes existan en algún punto upstream del flujo.

### Eje 2 — Velocidad / Performance

| ID | Oportunidad | Impacto | Esf. | ROI | Evidencia |
|----|-------------|---------|------|-----|-----------|
| CFM-P1 | Pipeline de mensajes a cola, completo (CF-1/CF-2) | high | m | high | `ConversationItemObserver.php:49,64`; `ExecuteChatFlowNodeJob.php:21-26` |
| CFM-P2 | Gate barato del observer global | medium | s | high | `ServiceProvider.php:211`; `Observer.php:45-61` |
| CFM-P3 | Analítica acotada + agregada en SQL | medium | m | medium | `ChatFlowsController.php:358,412-418,445` |
| CFM-P4 | Índice de nodos por request / flow compilado | low | s | medium | `ChatFlowNodeExecutor.php:816`; `Engine.php:945,961` |
| CFM-P5 | Muestreo/asincronía del log de ejecuciones | low | m | low | `ChatFlowNodeExecutor.php:63-74` |

- **CFM-P1 (eleva CF-1+CF-2)** — `processMessage()` (y la rama `triggerFor`) corren **inline en el observer**, y
  pueden invocar OpenAI (`ai_response` 30s, `ai_agent` 40s, `classifyIntent` 15s), `order_lookup` (ERP/PS HTTP) y
  `http_request` — bloqueando el webhook del canal. El fix **no es "despachar el job tal cual"**: el job muerto
  `ExecuteChatFlowNodeJob` (a) **no lleva attachments** (constructor solo `sessionId+message`) y (b) **no cubre la
  rama de arranque** (`triggerFor` cuando no hay sesión activa). Hay que: extender el job para llevar `attachments`
  y un modo "trigger inicial", despacharlo desde el observer, y añadir `WithoutOverlapping` keyed por
  `conversation_id` para **preservar el orden** de mensajes entre workers. Evidencia: `ConversationItemObserver.php:49`
  y `:64`; `ExecuteChatFlowNodeJob.php:21-26`. Dependencia: supervisor de Horizon para la cola (CFM-Q1/OBS-01).
- **CFM-P2** — ver Quick wins. Saca 2-3 queries por mensaje del hot path de todo el inbox.
- **CFM-P3** — `buildCsatMetrics` hace `pluck('context')` de **todas** las sesiones y filtra en PHP;
  `buildDropOff`/`buildAiMetrics` hacen `pluck('id')` de todas las sesiones del flow sin **rango de fechas**. A
  volumen alto esto carga la tabla entera. Persistir `csat_score` en columna propia (ya se guarda en metadata de la
  conversación, ver `recordCsat`), acotar por fecha, y añadir índice `executions(node_id)` / `(session_id,
  node_type)` (hoy solo `(session_id)` y `(session_id,status)`). Evidencia: `ChatFlowsController.php:412-418`,
  `:358`, `:445`; migración `..._create_helpdesk_chat_flow_executions_table.php:18-29`.
- **CFM-P4** — `getFirstChildId`/`getNextNodeAfterInput` hacen `collect($flow->nodes)->filter(...)` **en cada
  llamada**; el bucle `runNodes` ya indexa una vez (`nodesById`) pero los helpers de input no. Pasar un índice
  `keyBy('id')` / por `parentId` a los helpers, o cachear el flow compilado (nodes ya parseados/indexados) por
  `chat_flow_id + updated_at`. Barato. Evidencia: `ChatFlowNodeExecutor.php:816-824`, `ChatFlowEngine.php:945-968`.
- **CFM-P5** — Cada nodo no-routing hace un `INSERT` en `helpdesk_chat_flow_executions` (con snapshot de contexto
  truncado a 500 chars). A tráfico alto es mucha escritura en la conexión `helpdesk`. Considerar muestreo
  configurable o escritura asíncrona/batch para flujos de alto volumen. Evidencia: `ChatFlowNodeExecutor.php:63-74`.

> Nota: el `started_at` index del índice de la lista **sí existe** (añadido en la migración del 23-jun); el
> contador "Sesiones hoy" no es full-scan. Lo retiramos como problema.

### Eje 3 — UX / UI

| ID | Oportunidad | Impacto | Esf. | ROI | Evidencia |
|----|-------------|---------|------|-----|-----------|
| CFM-U1 | Decidir/documentar la isla React (CF-3/FE-09) | medium | l(dec.) | medium | `ChatFlowEditor.tsx:1` |
| CFM-U2 | "Cambios sin publicar" + bloqueo optimista anti-pisado | medium | m | medium | `ChatFlowsController.php:127-129` |
| CFM-U3 | Validación inline en el canvas antes de publicar | low | s | high | `ChatFlowValidator.php` |
| CFM-U4 | Accesibilidad del chrome del editor + test panel | low | s | medium | `editor.blade.php:36-58` |

- **CFM-U2** — Dos diseñadores editando el mismo flow se pisan (last-write-wins): `update()` sobreescribe `nodes`
  sin comprobar `updated_at`. Añadir bloqueo optimista (enviar `updated_at` y rechazar si cambió) + indicador de
  "cambios sin publicar". Encaja con CFM-F1. Evidencia: `ChatFlowsController.php:127-129`.
- **CFM-U4** — El test panel y la barra superior tienen botones icon-only sin `aria-label`; el canvas en sí
  (React Flow) no es teclado-accesible por naturaleza, pero el chrome alrededor debería serlo. Evidencia:
  `editor.blade.php:40-46` (botones reiniciar/cerrar solo-icono). Higiene Blade del módulo es por lo demás buena
  (sin Tabler, sin `style=` inline salvo el `<style>` del panel, `escHtml` en el render jQuery).

### Eje 4 — Seguridad / Calidad / Arquitectura

| ID | Oportunidad | Impacto | Esf. | ROI | Evidencia |
|----|-------------|---------|------|-----|-----------|
| CFM-S1 | Sanear ruta/nombre del upload del simulador (CF-4) | medium | s | high | `SimulatorUploadRequest.php:16-17` |
| CFM-S2 | Scoping de test-cases anidados al flow (CF-5) | low | s | high | `ChatFlowTestCasesController.php:42-63` |
| CFM-S3 | Gating a permisos `chatflow.*` + roles en seeder (CF-6) | medium | m | medium | `ServiceProvider.php:199`; `ChatFlowPermissionsSeeder.php` |
| CFM-S4 | PromptSanitizer compartido en nodos IA (CF-8/SEC-02) | medium | m | high | `ChatFlowAiResponder.php:123-127`; `ChatFlowAgentService.php:46-48` |
| CFM-S5 | Gateway LLM compartido del core (ARCH-02) | medium | m | medium | 3 servicios con `Http::post(api.openai.com)` |
| CFM-S6 | Deduplicar engine vs simulator (CF-7) | low | l | medium | `ChatFlowTestSimulator.php` |
| CFM-S7 | Validar el import con `ChatFlowValidator` | low | s | medium | `ChatFlowsController.php:251-273` |
| CFM-S8 | Documentar FKs cross-connection (CF-9) | low | s | low | migración sessions:18-19 |

- **CFM-S3 (CF-6)** — `registerRoutes()` aplica `role:super-admin|super-settings`, pero la Policy, los Form
  Requests y el sidebar verifican `chatflow.view/create/update/delete/manage` — permisos que el seeder **crea pero
  nunca asigna a ningún rol**. Doble protección incoherente con la convención `{alias}.{action}`. Migrar el
  middleware a permiso (`can:viewAny`/`permission:chatflow.view`) y **asignar los permisos a los roles** en el
  seeder. Bonus de producto: hoy el endpoint `takeover` (que un supervisor usa desde el inbox para arrebatar una
  conversación al bot) está gated a super-admin/super-settings; con permisos, los supervisores reales podrían
  usarlo. Evidencia: `HelpdeskChatFlowServiceProvider.php:199`, `ChatFlowPermissionsSeeder.php:15-26`,
  `routes/managers.php:45-46`.
- **CFM-S4 (CF-8/SEC-02)** — El texto libre del cliente va como mensaje `user` y los chunks de KB se incrustan en
  el system prompt sin sanitización anti prompt-injection. Riesgo bajo (acotado por `max_tokens` + rol system) pero
  `ai_agent` **ejecuta tools**; conviene un PromptSanitizer compartido (promover el del core, SEC-02) + delimitar el
  input del cliente y reafirmar instrucciones. Nota positiva: los tools usan IDs de cliente del **contexto de
  sesión**, no del LLM, así que no hay escalada de ownership por args inventados. Evidencia:
  `ChatFlowAiResponder.php:123-127`, `ChatFlowAgentService.php:46-48,158-178`.
- **CFM-S8 (CF-9)** — `chat_flow_id`/`conversation_id` son `unsignedBigInteger()->index()` sin `constrained()`
  por la conexión cruzada `helpdesk`. Aceptable; solo **documentar** la decisión. Evidencia: migración
  `..._create_helpdesk_chat_flow_sessions_table.php:18-19`.

---

## Orden recomendado (olas)

### Ola 0 — Prerequisito (config-only, fuera del módulo pero bloqueante de CFM-P1)
- **CFM-Q1 / OBS-01** — Asegurar supervisor de Horizon para la cola `chatflow` (y unificar el job muerto a esa
  cola). Sin esto, mover el pipeline a cola = silenciar el bot.

### Ola 1 — Quick wins (esfuerzo s, ROI alto): cierra riesgo y deuda barata
- Seguridad: **CFM-S1** (CF-4 path traversal), **CFM-S2** (CF-5 scoping), **CFM-S7** (validar import).
- Producto/robustez: **CFM-F4** (validador go_to_step/inalcanzables/variables), **CFM-U3** (validación inline).
- Performance barata: **CFM-P2** (gate del observer), **CFM-P4** (índice de nodos por request).

### Ola 2 — Núcleo de alto impacto (esfuerzo m, ROI alto)
- Performance: **CFM-P1** (pipeline a cola completo; tras Ola 0) y **CFM-P3** (analítica acotada/SQL + índices).
- Producto: **CFM-F1** (draft/publicado) y **CFM-U2** (anti-pisado), que van juntas.
- Seguridad/arquitectura: **CFM-S3** (CF-6 permisos+roles), **CFM-S4** (PromptSanitizer), **CFM-S5** (gateway LLM).

### Ola 3 — Iniciativas grandes y decisiones (esfuerzo l/xl)
- Producto/IA: **CFM-F2** (enrutado por intención), **CFM-F3** (A/B con comparación), **CFM-F5** (operadores),
  **CFM-F6** (catálogo de variables).
- Arquitectura/calidad: **CFM-S6** (deduplicar engine/simulator, CF-7), **CFM-U1** (decisión isla React, CF-3),
  **CFM-P5** (log de ejecuciones), **CFM-F7** (plantillas), **CFM-S8** (documentar FKs, CF-9).

**Lógica de orden**: primero lo de horas que cierra riesgo (Ola 1), luego el cambio de mayor impacto operativo
(CFM-P1) y de producto (CFM-F1) más la plataforma compartida IA (CFM-S4/S5), y por último lo que requiere decisión
de arquitectura o reescritura amplia. Dependencias duras: CFM-Q1 → CFM-P1; CFM-S5 (gateway) facilita CFM-S4
(sanitizer aplicado en un punto); CFM-F1 ↔ CFM-U2; CFM-F4 habilita parte de CFM-F2/F6.
