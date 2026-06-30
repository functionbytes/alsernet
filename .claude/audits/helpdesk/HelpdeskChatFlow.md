# Auditoría — HelpdeskChatFlow

> Fecha: 2026-06-29 · Health score: 82/100 · Estado: solid-minor-issues

**Resumen:** Módulo de chatbot/flow multicanal maduro y bien diseñado, con buena higiene de seguridad (guardas SSRF, locking por conversación, salida escapada) y cobertura de tests sólida. No se detectaron hallazgos critical ni high. Los problemas principales son: trabajo pesado (IA/HTTP) ejecutado de forma síncrona dentro de un observer de modelo, un job huérfano/muerto, un editor en React/TSX que viola la convención jQuery-only del proyecto, y un par de brechas menores de IDOR/manejo de rutas. Todos los hallazgos se confirmaron tal cual durante la verificación; ninguno fue refutado ni reajustado de severidad.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| CF-1 | medium | performance | modules/HelpdeskChatFlow/app/Observers/ConversationItemObserver.php:49 | [CONFIRMADO] | M | Trabajo pesado IA/HTTP corre de forma síncrona dentro de `ConversationItemObserver::created` |
| CF-3 | medium | conventions | modules/HelpdeskChatFlow/resources/js/ChatFlowEditor.tsx:1 | [CONFIRMADO] | L | Editor de flujos implementado en React/TSX, viola la convención jQuery-only |
| CF-4 | medium | security | modules/HelpdeskChatFlow/app/Http/Controllers/ChatFlowTestController.php:59 | [CONFIRMADO] | S | Input de usuario sin sanitizar usado en ruta/nombre de archivo al subir en el simulador |
| CF-2 | low | wiring | modules/HelpdeskChatFlow/app/Jobs/ExecuteChatFlowNodeJob.php:11 | [CONFIRMADO] | S | `ExecuteChatFlowNodeJob` es código muerto (nunca se despacha) |
| CF-5 | low | security | modules/HelpdeskChatFlow/app/Http/Controllers/ChatFlowTestCasesController.php:44 | [CONFIRMADO] | S | Rutas de test-case anidadas no verifican que el caso pertenezca al flujo |
| CF-6 | low | conventions | modules/HelpdeskChatFlow/app/Providers/HelpdeskChatFlowServiceProvider.php:199 | [CONFIRMADO] | M | Rutas protegidas por rol mientras el resto del módulo usa permisos `chatflow.*` |
| CF-7 | low | quality | modules/HelpdeskChatFlow/app/Services/ChatFlowTestSimulator.php:175 | [CONFIRMADO] | L | Gran duplicación de lógica entre `ChatFlowEngine` y `ChatFlowTestSimulator` |
| CF-8 | low | security | modules/HelpdeskChatFlow/app/Services/ChatFlowAiResponder.php:123 | [CONFIRMADO] | S | Los nodos IA carecen de mitigación explícita de prompt-injection |
| CF-9 | low | conventions | modules/HelpdeskChatFlow/database/migrations/2026_06_17_000002_create_helpdesk_chat_flow_sessions_table.php:18 | [CONFIRMADO] | S | Tablas cross-connection usan FKs solo-índice (sin `constrained()`/cascade) |

## Hallazgos detallados

### Medium

#### CF-1 · [CONFIRMADO] Trabajo pesado IA/HTTP corre de forma síncrona dentro de `ConversationItemObserver::created`
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Observers/ConversationItemObserver.php:49`
- **Evidencia:** En cada `ConversationItem` entrante del cliente, el observer llama `$this->engine->processMessage(...)` de forma inline. `processMessage` avanza el flujo y puede invocar OpenAI (`ChatFlowAiResponder`, timeout 30s + 2 reintentos), nodos genéricos `http_request` y lookups a ERP/PS — todo bloqueando el request/webhook que creó el item.
- **Impacto:** Los handlers de webhook de canales entrantes (WhatsApp/Messenger) pueden bloquearse decenas de segundos, arriesgando timeouts/reintentos del webhook y procesamiento duplicado bajo carga.
- **Recomendación:** Despachar el `ExecuteChatFlowNodeJob` existente (cola `helpdesk-events`) desde el observer en lugar de llamar `processMessage` síncronamente; la clase del job ya existe para exactamente esto.
- **Esfuerzo:** M

#### CF-3 · [CONFIRMADO] Editor de flujos implementado en React/TSX, viola la convención jQuery-only
- **Archivo:línea:** `modules/HelpdeskChatFlow/resources/js/ChatFlowEditor.tsx:1`
- **Evidencia:** `resources/js/ChatFlowEditor.tsx` + `chatflow-editor.tsx` montados en `#chatflow-editor-root` en `editor.blade.php`. Las reglas del proyecto (`javascript.md`, `blade-views.md`, `CLAUDE.md`) obligan a jQuery+AJAX y prohíben explícitamente React/Livewire/Inertia/Alpine.
- **Impacto:** Diverge del stack de frontend mandatorio; eleva el coste de mantenimiento y es inconsistente con el enfoque de UI de los demás módulos. Puede ser una excepción deliberada para el canvas de grafo de nodos, pero no está documentada como tal.
- **Recomendación:** Documentar esto como excepción aprobada (canvas builder) en `CLAUDE.md`, o migrar al stack estándar. Como mínimo, marcarlo para que sea una decisión consciente.
- **Esfuerzo:** L

#### CF-4 · [CONFIRMADO] Input de usuario sin sanitizar usado en ruta/nombre de archivo al subir en el simulador
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Http/Controllers/ChatFlowTestController.php:59`
- **Evidencia:** `$file->storeAs('chatflow-test/'.$validated['session_key'], $validated['doc_key'].'_'.time().'.'.$ext)`. `SimulatorUploadRequest` valida `session_key`/`doc_key` solo como `['required','string']` — sin restricción de charset, por lo que un `'../'` (traversal) en la ruta/nombre es posible.
- **Impacto:** Un usuario autenticado con `chatflow.update` (supervisor) podría escribir archivos fuera del directorio previsto en el disco local. Radio de impacto limitado (rol de confianza, sin exposición pública), pero es una primitiva de escritura de ruta arbitraria.
- **Recomendación:** Sanitizar con `basename()`/`Str::slug()` o validar `session_key`/`doc_key` con regex (p. ej. `['regex:/^[A-Za-z0-9_-]+$/']`) antes de usarlos en la ruta.
- **Esfuerzo:** S

### Low

#### CF-2 · [CONFIRMADO] `ExecuteChatFlowNodeJob` es código muerto (nunca se despacha)
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Jobs/ExecuteChatFlowNodeJob.php:11`
- **Evidencia:** Un grep de `ExecuteChatFlowNodeJob` en `app/` devuelve solo su propia definición y la línea de log de `failed()`; no hay `::dispatch` en ningún sitio. El observer en cambio procesa mensajes inline (ver CF-1).
- **Impacto:** Superficie de código engañosa; el camino asíncrono previsto está sin cablear. Directamente ligado a CF-1.
- **Recomendación:** O cablearlo desde el observer (resuelve CF-1) o eliminarlo.
- **Esfuerzo:** S

#### CF-5 · [CONFIRMADO] Rutas de test-case anidadas no verifican que el caso pertenezca al flujo
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Http/Controllers/ChatFlowTestCasesController.php:44`
- **Evidencia:** `run()` y `destroy()` bindean `{chatFlow}` y `{testCase}` de forma independiente pero nunca verifican `$testCase->chat_flow_id === $chatFlow->id`. En contraste, `ChatFlowsController::replaySession` sí hace `abort_unless($session->chat_flow_id === $chatFlow->id, 404)`.
- **Impacto:** IDs no coincidentes se aceptan; impacto real mínimo porque la Policy concede a cualquier titular de `chatflow.*` acceso a todos los flujos (no hay modelo de ownership), pero es una brecha inconsistente de autorización/scoping.
- **Recomendación:** Añadir `abort_unless($testCase->chat_flow_id === $chatFlow->id, 404)` (y lo mismo en el target de `store`) para defensa en profundidad y 404 correctos.
- **Esfuerzo:** S

#### CF-6 · [CONFIRMADO] Rutas protegidas por rol mientras el resto del módulo usa permisos `chatflow.*`
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Providers/HelpdeskChatFlowServiceProvider.php:199`
- **Evidencia:** `registerRoutes()` aplica middleware `['web','auth','role:super-admin|super-settings']`, pero la Policy, los Form Requests y el sidebar todos verifican `chatflow.view/create/update/delete/manage`. `ChatFlowPermissionsSeeder` crea esos permisos pero nunca los asigna a ningún rol.
- **Impacto:** Doble protección: el acceso a rutas depende del rol, el controller/policy de permisos. Un usuario `super-settings` sin permisos `chatflow.*` pasa la guarda de ruta y luego recibe 403 en el controller; los permisos `chatflow.*` quedan efectivamente sin usar para entrada de ruta. Inconsistente con la convención de permisos `{alias}.{action}`.
- **Recomendación:** Elegir una sola guarda (preferir basada en permisos, p. ej. middleware `can:viewAny` o `'permission:chatflow.view'`) y asignar los permisos a los roles previstos en el seeder.
- **Esfuerzo:** M

#### CF-7 · [CONFIRMADO] Gran duplicación de lógica entre `ChatFlowEngine` y `ChatFlowTestSimulator`
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Services/ChatFlowTestSimulator.php:175`
- **Evidencia:** `ChatFlowEngine` (970 LOC) y `ChatFlowTestSimulator` (823 LOC) ambos reimplementan `executeNode`/`executeBranches`/evaluación de condiciones/manejo de elecciones numeradas. Solo los helpers de validación/escape de input se comparten vía traits.
- **Impacto:** Riesgo de deriva de comportamiento: el simulador y el motor de producción pueden divergir, socavando la feature de test-cases cuyo propósito entero es la paridad.
- **Recomendación:** Extraer la lógica compartida de ejecución de nodos/evaluación de ramas para que motor y simulador sean idénticos garantizados, o hacer que el simulador conduzca el motor contra una sesión en memoria.
- **Esfuerzo:** L

#### CF-8 · [CONFIRMADO] Los nodos IA carecen de mitigación explícita de prompt-injection
- **Archivo:línea:** `modules/HelpdeskChatFlow/app/Services/ChatFlowAiResponder.php:123`
- **Evidencia:** El texto libre del cliente se envía como mensaje `'user'` y los chunks de la KB se incrustan en la instrucción de sistema; no hay sanitación/guarda de input más allá de poner instrucciones en el rol de sistema y limitar el historial.
- **Impacto:** Un cliente podría intentar sobreescribir instrucciones (p. ej. "ignora las instrucciones previas"). Acotado por `max_tokens` y el system prompt, así que riesgo bajo, pero no existe capa defensiva.
- **Recomendación:** Aceptable tal cual por el riesgo bajo; opcionalmente añadir una reafirmación de instrucción/delimitador alrededor del input del cliente o un filtro ligero de injection si los nodos IA manejan acciones sensibles.
- **Esfuerzo:** S

#### CF-9 · [CONFIRMADO] Tablas cross-connection usan FKs solo-índice (sin `constrained()`/cascade)
- **Archivo:línea:** `modules/HelpdeskChatFlow/database/migrations/2026_06_17_000002_create_helpdesk_chat_flow_sessions_table.php:18`
- **Evidencia:** `chat_flow_id`/`conversation_id` declarados como `unsignedBigInteger()->index()` en lugar de `foreignId()->constrained()`. Desvía de la guía de FK en `migrations.md`.
- **Impacto:** Sin integridad referencial a nivel DB; aceptable porque las tablas viven en la conexión separada `helpdesk` y el padre usa `SoftDeletes`, pero vale la pena notarlo.
- **Recomendación:** Dejarlo tal cual dada la restricción cross-connection, o añadir constraints FK si todas las tablas comparten un esquema. Documentar la elección.
- **Esfuerzo:** S

## Plan de ataque priorizado

1. **Descargar el procesamiento de mensajes entrantes a la cola** (despachar `ExecuteChatFlowNodeJob` desde el observer) para que el trabajo IA/HTTP deje de bloquear los webhooks de canales — **CF-1/CF-2** (medium+low, resuelve dos hallazgos de un golpe).
2. **Sanitizar la ruta/nombre de archivo controlado por usuario** en la subida del simulador — **CF-4** (medium, esfuerzo S).
3. **Resolver la inconsistencia rol-vs-permiso** en las guardas y asignar los permisos `chatflow.*` a roles en el seeder — **CF-6** (low, pero alinea el módulo con la convención del proyecto).
4. Reducir deuda: extraer lógica compartida engine/simulador (**CF-7**) y decidir la excepción del editor React/TSX (**CF-3**) — ambos esfuerzo L, planificables.

## Quick wins

- Añadir `abort_unless($testCase->chat_flow_id === $chatFlow->id, 404)` en `ChatFlowTestCasesController::run/destroy` (**CF-5**).
- Restringir `session_key`/`doc_key` con regex en `SimulatorUploadRequest` para bloquear path traversal (**CF-4**).
- Eliminar o cablear `ExecuteChatFlowNodeJob` (**CF-2**).

## Fortalezas

- **Protección SSRF** (trait `GuardsAgainstSsrf`): valida scheme/host, bloquea rangos de IP privados/reservados y fija la conexión a la IP resuelta (`CURLOPT_RESOLVE`) para derrotar DNS rebinding, aplicado tanto al nodo `http_request` como a la descarga de nota de voz entrante, más `withoutRedirecting()`.
- **Seguridad de concurrencia:** `Cache::lock` por conversación en `ChatFlowEngine::start()` previene sesiones activas duplicadas por double-tap/race; documentado por qué un índice único en DB no es viable.
- **Capa de datos consciente del rendimiento:** buffering de escritura de contexto colapsa ~11 UPDATEs por mensaje en uno, árbol de nodos indexado por id (saltos O(1)), índices compuestos bien elegidos en cada tabla, queries de rango en lugar de `whereDate` para mantener los índices usables.
- **Higiene Blade:** sin iconos Tabler, sin `style=` inline, sin theme select2 bootstrap-5, el único `{!! !!}` es `nl2br(e(...))` (escapado), `data-props` usa `json_encode` escapado por Blade.
- **Convenciones fuertes:** Form Requests para toda la validación con `messages()`/`attributes()` en español, Policy registrada vía `Gate::policy`, método `casts()`, `$fillable` explícito, `SoftDeletes`, jobs en cola con `tries`/`timeout`/`backoff`/`failed()`, listener en cola basado en clase (seguro para `event:cache`).
- **Buena huella de tests:** 8 Feature + 17 Unit test files cubriendo motor, simulador, policy, observer, guarda SSRF, validadores y servicios.

## Cobertura de la auditoría

Leídos en su totalidad: `module.json`/`composer.json`, ServiceProvider, ambos archivos de rutas, los 3 controllers, todos los Form Requests, Policy, permissions seeder, los 5 modelos, los 3 jobs, observer, listener/launcher de eventos de negocio, servicio outbound, `BotMessageDispatcher`, `ChatFlowAiResponder`, `ChatFlowVoiceTranscriber`, traits `GuardsAgainstSsrf`/`ValidatesUserInput`, `ChatFlowHttpRequester`, config, todas las migraciones (revisión de índices), y secciones clave de `ChatFlowEngine` (start/processMessage/handleInput/takeOver/AB).

Muestreado (no línea por línea): resto de `ChatFlowEngine`, `ChatFlowNodeExecutor` (825 LOC, mapa de métodos + grep de patrones riesgosos), `ChatFlowTestSimulator` (823 LOC), y varios servicios menores (Localizer, Sentiment, HandoffSummary, OrderLookup, TemplateLibrary, AgentService, HsmDelivery, DocumentLink, ReplayService, TestRunner). Blades escaneados por grep para iconos `ti`/estilos inline/select2/salida sin escapar; `index.blade` y `editor.blade` leídos directamente.

No se leyó en profundidad el código fuente del editor React/TSX ni el bundle JS. Los tests no se ejecutaron (DB bloqueada según instrucciones); la cobertura se evaluó solo por inventario de archivos.

## Descartados en verificación

Ninguno. No hubo hallazgos refutados durante la verificación. Tampoco había hallazgos critical/high que verificar (`verify_note`: "Sin hallazgos critical/high que verificar"). Todos los hallazgos del cuerpo se confirmaron con su severidad original.
