# Auditoría — Helpdesk

> Fecha: 2026-06-29 · Health score: 73/100 · Estado: solid-minor-issues

**Resumen:** Núcleo omnicanal de helpdesk grande y en su mayoría bien arquitecturado (listeners encolados, webhooks con verificación de firma HMAC, policies registradas, Form Requests, consultas del portal con scope por cliente) lastrado por un controlador-dios de 2.762 líneas, una ruta cableada a un método inexistente, una SSRF en previsualización de enlaces accesible a agentes y una descarga HTTP síncrona en el hilo de la petición. Diagnóstico: arquitectura sólida con un único defecto de alto impacto (ruta rota) y una concentración de deuda técnica/seguridad media alrededor del controlador de conversaciones y del servicio de previsualización de enlaces. Sin hallazgos críticos.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HD-01 | high | wiring | modules/Helpdesk/routes/managers.php:234 | [CONFIRMADO] | S | DELETE /conversation-items/{item} apunta a un método de controlador inexistente |
| HD-02 | medium | security | modules/Helpdesk/app/Services/LinkPreviewService.php:41-51,80-87 | [CONFIRMADO] | M | SSRF: LinkPreviewService descarga URLs arbitrarias sin allowlist de host/IP |
| HD-03 | medium | performance | modules/Helpdesk/app/Observers/ConversationItemLinkPreviewObserver.php:36 | [CONFIRMADO] | M | Descarga HTTP de previsualización síncrona dentro del observer created() |
| HD-04 | medium | quality | modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1-2762 | [CONFIRMADO] | L | ConversationsController es un controlador-dios de 2.762 líneas con 50 acciones públicas |
| HD-05 | medium | wiring | modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1771-1797 | [CONFIRMADO] | M | aiSuggestions() devuelve texto hardcodeado pese a existir un servicio real de IA |
| HD-06 | medium | security | modules/Helpdesk/resources/views/helpdesk/inbox/partials/thread.blade.php:247 | [CONFIRMADO] | M | Render crudo `{!! $item->body_html !!}` — confirmar saneo de todo origen de html_body |
| HD-07 | medium | conventions | modules/Helpdesk/app/Http/Controllers/Portal/PortalDashboardController.php:47,85 | [CONFIRMADO] | M | `$request->validate()` inline en ~31 métodos pese a la regla de Form Request obligatoria |
| HD-08 | low | security | modules/Helpdesk/app/Providers/RouteServiceProvider.php:33-36 | [CONFIRMADO] | M | Settings dependen solo de role:super-admin\|super-settings, no de permisos granulares |
| HD-09 | low | security | modules/Helpdesk/app/Models/Customer.php:49,53 | [CONFIRMADO] | S | customer.portal_password es fillable/hidden pero sin cast 'hashed' en este módulo |
| HD-10 | low | performance | modules/Helpdesk/app/Services/LinkPreviewService.php:57-66 | [CONFIRMADO] | S | LinkPreviewService carga toda la respuesta HTTP en memoria antes de aplicar el cap |
| HD-11 | low | tests | modules/Helpdesk/tests | [CONFIRMADO] | L | Cobertura de tests escasa frente a la superficie del módulo |
| HD-12 | low | ux | modules/Helpdesk/resources/views | [CONFIRMADO] | M | Atributos `style="` inline en vistas Blade no-email |
| HD-13 | low | security | modules/Helpdesk/app/Services/AI/AiAgentService.php:160-171,104-140 | [CONFIRMADO] | M | Agente IA autónomo alimenta mensajes de cliente a un LLM con tool-calling (prompt-injection) |

## Hallazgos detallados

### HIGH

#### HD-01 — DELETE /conversation-items/{item} apunta a un método de controlador inexistente [CONFIRMADO]

- **Categoría:** wiring
- **Archivo:línea:** `modules/Helpdesk/routes/managers.php:234`
- **Evidencia:** La ruta `Route::delete('/conversation-items/{item}', [ConversationItemsController::class, 'destroy'])` está registrada, pero `ConversationItemsController` define únicamente `react()` (línea 17) y el privado `groupReactions()` (línea 56). No existe ningún método `destroy()` en la clase ni en su base `App\Http\Controllers\Controller` (verificado por grep). Cualquier petición DELETE a ese endpoint lanza `BadMethodCallException` / 500.
- **Impacto:** Cualquier cliente que llegue al endpoint de borrado recibe un 500 (método no encontrado en el dispatch). La acción de UI "eliminar mensaje" está muerta.
- **Recomendación:** Implementar `ConversationItemsController::destroy(ConversationItem $item)` con `$this->authorize` y soft-delete, o eliminar la ruta si el borrado se gestiona en otro sitio.
- **Verificación:** Confirmado. No mitigado, no es falso positivo.

### MEDIUM

#### HD-02 — SSRF: LinkPreviewService descarga URLs arbitrarias sin allowlist de host/IP [CONFIRMADO]

- **Categoría:** security
- **Archivo:línea:** `modules/Helpdesk/app/Services/LinkPreviewService.php:41-51, 80-87`
- **Evidencia:** `extractFirstUrl()` extrae la primera URL http(s) de un mensaje de agente y `preview()` ejecuta `Http::get($url)` con `allow_redirects` max 3 y sin validar que el host sea público. Se dispara desde `ConversationItemLinkPreviewObserver::created()` sobre ítems escritos por agentes.
- **Impacto:** Un agente autenticado (semi-confiable) puede forzar al servidor a consultar endpoints internos (p.ej. `http://169.254.169.254/` de metadata cloud, paneles admin internos) e inferir respuestas vía el título/preview cacheado — server-side request forgery / reconocimiento interno.
- **Recomendación:** Antes de descargar, resolver el host y rechazar rangos privados/loopback/link-local/metadata (y revalidar tras cada redirección). Considerar una allowlist o deshabilitar previews para hosts no públicos.

#### HD-03 — Descarga HTTP de previsualización síncrona dentro del observer created() [CONFIRMADO]

- **Categoría:** performance
- **Archivo:línea:** `modules/Helpdesk/app/Observers/ConversationItemLinkPreviewObserver.php:36`
- **Evidencia:** `$this->linkPreview->previewFromBody($item->body)` llama a `Http::timeout(6)` en línea dentro de `created()`; solo los resultados cacheados son rápidos. La primera vez que se ve una URL, la petición de envío del agente se bloquea hasta 6s.
- **Impacto:** Enviar un mensaje con una URL nueva puede colgar la petición del agente varios segundos; un host remoto lento degrada la responsividad del inbox.
- **Recomendación:** Mover el enriquecimiento de previsualización a un job encolado (p.ej. `GenerateLinkPreviewJob`) despachado desde el observer; emitir broadcast/actualizar metadata al completar.

#### HD-04 — ConversationsController es un controlador-dios de 2.762 líneas con 50 acciones públicas [CONFIRMADO]

- **Categoría:** quality
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1-2762`
- **Evidencia:** 50 métodos públicos que mezclan listado, agregados SQL crudos (`selectRaw`/`DB::raw`/`orderByRaw` en líneas 246, 364, 522, 2097-2099), render de email, manejo de adjuntos, stubs de IA, merge, snooze, macros, etc.
- **Impacto:** Difícil de testear, revisar y mantener; lógica de negocio embebida en el controlador viola la regla de controladores delgados del proyecto y concentra el riesgo.
- **Recomendación:** Extraer responsabilidades cohesivas (listado/filtrado, envío/preview de email, adjuntos, merge, reacciones/forwarding) a servicios y/o sub-controladores; mover los agregados crudos a objetos query/servicio.

#### HD-05 — aiSuggestions() devuelve texto hardcodeado pese a existir un servicio real de IA [CONFIRMADO]

- **Categoría:** wiring
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Managers/ConversationsController.php:1771-1797`
- **Evidencia:** El método está marcado como 'TODO: integrar OpenAI/Claude API' y devuelve tres cadenas estáticas en español; mientras tanto `AiController::suggestReplies` (ruta `ai/suggest-replies`), respaldado por `SuggestReplyService`, es la implementación real.
- **Impacto:** El endpoint `/conversations/{c}/ai-suggestions` presenta salida de IA falsa a los agentes — una funcionalidad duplicada a medio cablear.
- **Recomendación:** Delegar a `SuggestReplyService` (respetando `helpdesk.ai.enabled`) o eliminar el método stub y su ruta para evitar dos endpoints en competencia.

#### HD-06 — Render crudo `{!! $item->body_html !!}` — confirmar saneo de todo origen de html_body [CONFIRMADO]

- **Categoría:** security
- **Archivo:línea:** `modules/Helpdesk/resources/views/helpdesk/inbox/partials/thread.blade.php:247`
- **Evidencia:** Los cuerpos de mensaje se imprimen sin escapar. La mayoría de rutas de escritura son seguras (`nl2br(e(...))` en `ConversationMessagesController.php:88`, `ConversationMessageService.php:56`, `AiAgentService.php:231`), pero `ConversationsController.php:643/746` almacenan `html_body` desde `MailerTemplateRendererService::renderEmailTemplate()`, cuyas variables incluyen `CUSTOMER_NAME`/`CUSTOMER_EMAIL` controlados por el cliente, y ese renderer vive en otro módulo (saneo no verificable aquí).
- **Impacto:** Si algún origen de `html_body` (sustitución de variables de plantilla de email, o un canal que almacene HTML del cliente) no se escapa/purifica, se convierte en XSS almacenada que se ejecuta en el inbox del agente.
- **Recomendación:** Pasar todo `html_body` por un purificador HTML (`clean()`) en el momento de render, o garantizar el escape en cada ruta de escritura incluyendo la sustitución de variables de plantilla; añadir test de regresión con un nombre de cliente `<script>`.

#### HD-07 — `$request->validate()` inline en ~31 métodos pese a la regla de Form Request obligatoria [CONFIRMADO]

- **Categoría:** conventions
- **Archivo:línea:** `modules/Helpdesk/app/Http/Controllers/Portal/PortalDashboardController.php:47, 85`
- **Evidencia:** `PortalDashboardController` valida inline en :47 y :85 aunque ya existen (sin usar) `Portal/SendMessageRequest` y `Portal/UpdateProfileRequest`. El mismo patrón inline en `AiController.php:44`, `ConversationViewsController.php:14`, `ConversationParticipantsController.php:36` y ~25 controladores de Settings.
- **Impacto:** Viola la convención de Form Request del proyecto; la lógica de validación, los mensajes en español y la autorización quedan dispersos e inconsistentes; las clases de request prefabricadas son código muerto.
- **Recomendación:** Reemplazar `validate()` inline por las clases Form Request existentes/nuevas (con `authorize()`, `messages()`, `attributes()`); cablear las requests del portal sin usar en el controlador.

### LOW

#### HD-08 — Settings dependen solo de role:super-admin|super-settings, no de permisos granulares [CONFIRMADO]

- **Categoría:** security
- **Archivo:línea:** `modules/Helpdesk/app/Providers/RouteServiceProvider.php:33-36`
- **Evidencia:** El grupo de settings aplica solo `role:super-admin|super-settings`; ~30 controladores de Settings (Macros, Brands, SlaPolicies, Webhooks, Statuses, etc.) no contienen ningún `$this->authorize`/`can`, pese a que el sidebar declara permisos por entidad como `helpdesk.macros.view`, `helpdesk.webhooks.view` (`HelpdeskServiceProvider.php:236-271`).
- **Impacto:** Autorización de grano grueso: cualquier usuario super-settings puede gestionar todas las entidades de settings independientemente del permiso granular mostrado en navegación; los permisos granulares son decorativos para settings.
- **Recomendación:** Añadir middleware `can:` o llamadas `$this->authorize` alineadas con los permisos granulares anunciados, o documentar que settings está intencionadamente protegido solo por rol.

#### HD-09 — customer.portal_password es fillable/hidden pero sin cast 'hashed' en este módulo [CONFIRMADO]

- **Categoría:** security
- **Archivo:línea:** `modules/Helpdesk/app/Models/Customer.php:49, 53`
- **Evidencia:** `'portal_password'` aparece en `$fillable` (49) y `$hidden` (53); `casts()` no tiene `'portal_password' => 'hashed'` y no existe setter en Helpdesk (solo `GdprDeletionService` lo anula). El set/verificación vive en HelpdeskTickets.
- **Impacto:** Brecha de defensa en profundidad: si algún código de este módulo llegara a hacer mass-assign de `portal_password` se almacenaría en texto plano; la propiedad cross-módulo del hashing lo hace frágil.
- **Recomendación:** Añadir `'portal_password' => 'hashed'` a `casts()` (o un mutator de hashing) para que siempre se hashee independientemente de qué módulo escriba.

#### HD-10 — LinkPreviewService carga toda la respuesta HTTP en memoria antes de aplicar el cap [CONFIRMADO]

- **Categoría:** performance
- **Archivo:línea:** `modules/Helpdesk/app/Services/LinkPreviewService.php:57-66`
- **Evidencia:** `$fullBody = (string) $response->body()` materializa la respuesta completa; `MAX_BYTES` (2MB) solo limita el substring usado para el parseo, no la descarga.
- **Impacto:** Una página remota grande o maliciosa puede forzar al worker a bufferizar un cuerpo sobredimensionado en memoria (DoS / presión de memoria), al no aplicarse streaming/guarda de Content-Length.
- **Recomendación:** Streamear la respuesta y dejar de leer en `MAX_BYTES` (o aplicar guarda de Content-Length / cabecera Range) antes de bufferizar.

#### HD-11 — Cobertura de tests escasa frente a la superficie del módulo [CONFIRMADO]

- **Categoría:** tests
- **Archivo:línea:** `modules/Helpdesk/tests` (Feature/, Unit/)
- **Evidencia:** 37 archivos de test Feature + 10 Unit para un módulo con 88 controladores, 77 servicios y 917 archivos PHP; áreas de alto riesgo como el `ConversationsController` de 2.762 líneas, las previsualizaciones de enlaces y los flujos del agente IA tienen poca cobertura directa.
- **Impacto:** Las regresiones en los flujos centrales de inbox/conversación pueden pasar inadvertidas; la ruta de borrado rota (HD-01) es evidencia de un camino no testeado.
- **Recomendación:** Añadir tests Feature para el ciclo de vida de conversación (close/reopen/archive/merge/snooze), autorización en acciones destructivas y los endpoints de conversation-items; añadir tests Unit para la extracción de URL/guarda SSRF de `LinkPreviewService`.

#### HD-12 — Atributos `style="` inline en vistas Blade no-email [CONFIRMADO]

- **Categoría:** ux
- **Archivo:línea:** `modules/Helpdesk/resources/views` (66 ficheros coinciden con `style="`)
- **Evidencia:** grep encuentra `style="` en 66 ficheros blade; muchos son plantillas de email legítimas (p.ej. `emails/customer-outbound.blade.php`, donde los estilos inline son obligatorios), pero varios partials de inbox/customer también usan estilos inline, contra la regla de no-inline-style.
- **Impacto:** Deriva menor de mantenibilidad/consistencia respecto a la convención de utilidades Bootstrap; los emails son excepción legítima.
- **Recomendación:** Mover los estilos inline no-email a clases CSS; dejar las plantillas de email como están.

#### HD-13 — Agente IA autónomo alimenta mensajes de cliente a un LLM con tool-calling (prompt-injection) [CONFIRMADO]

- **Categoría:** security
- **Archivo:línea:** `modules/Helpdesk/app/Services/AI/AiAgentService.php:160-171, 104-140`
- **Evidencia:** `buildMessages()` pasa `strip_tags($item->body)` como contenido de rol user a `chatWithTools()`; el agente puede invocar funciones (p.ej. `escalate`). Mitigado: la funcionalidad está inerte salvo que `config helpdesk.ai.agent_enabled` (por defecto false), el contenido se delimita como rol user y se le quitan tags, el set de herramientas se limita a dos funciones y aplican palabras clave de escalado/caps de mensajes.
- **Impacto:** Si se habilita, un cliente podría redactar un mensaje para dirigir el uso de herramientas del modelo; el set actual de herramientas limita el radio de impacto a escalate/reply.
- **Recomendación:** Mantener las herramientas mínimas y sin efectos secundarios; añadir instrucciones explícitas para ignorar directivas embebidas y validar los argumentos de las herramientas en servidor antes de actuar.

## Plan de ataque priorizado

1. **HD-01 (high, S)** — Arreglar la ruta DELETE /conversation-items/{item} rota: implementar el método de controlador faltante o eliminar la ruta muerta. Impacto inmediato (500 en producción), esfuerzo mínimo.
2. **HD-02 (medium, M)** — Añadir filtrado SSRF de host/IP a `LinkPreviewService` (rechazo de rangos privados/loopback/link-local/metadata, revalidación tras redirecciones).
3. **HD-03 (medium, M)** — Mover la descarga de previsualización a un job encolado para sacarla del hilo de la petición (combinable con HD-02 y HD-10).
4. **HD-06 (medium, M)** — Garantizar saneo/purificado de todo origen de `html_body`; test de regresión con payload XSS.
5. **HD-05 (medium, M)** — Conectar `aiSuggestions()` a `SuggestReplyService` o eliminar el stub.
6. **HD-07 (medium, M)** — Migrar validaciones inline a Form Requests; cablear las requests del portal ya existentes.
7. **HD-04 (medium, L)** — Comenzar la descomposición del `ConversationsController` de 2.762 líneas en servicios/sub-controladores.
8. **Lows (HD-08..HD-13)** — Endurecer autorización granular de settings, cast `hashed` en portal_password, streaming en LinkPreview, cobertura de tests, limpieza de estilos inline y endurecimiento del agente IA.

## Quick wins

- **HD-01:** Añadir el método `ConversationItemsController::destroy()` faltante (o eliminar la ruta muerta) para que DELETE /conversation-items/{item} deje de devolver 500.
- **HD-07:** Cablear las `Portal/SendMessageRequest` y `Portal/UpdateProfileRequest` ya existentes en `PortalDashboardController` en lugar del `$request->validate()` inline.
- **HD-05:** Reemplazar las cadenas hardcodeadas en `ConversationsController::aiSuggestions()` por una llamada al `SuggestReplyService` real, o eliminar la ruta stub.
- **HD-09:** Añadir `'portal_password' => 'hashed'` a `casts()` (S).
- **HD-10:** Streamear la respuesta HTTP cortando en `MAX_BYTES` (S).

## Fortalezas

- Los webhooks entrantes (WhatsApp/Facebook/Instagram/email) verifican firmas HMAC y fallan cerrado fuera de local; los salientes van firmados con HMAC (`DispatchWebhookJob.php:206-208`, `WhatsAppWebhookRequest.php:20-45`, `EmailInboundController.php:35-66`).
- Los listeners de eventos que hacen trabajo pesado/externo (sentiment, agente IA, webhooks, broadcast, notificaciones) implementan todos `ShouldQueue`, manteniendo libres los hilos de petición.
- Policies registradas vía `Gate::policy` en el ServiceProvider y aplicadas consistentemente en acciones destructivas de Conversation/Customer (destroy/forceDelete/ban/merge llaman `$this->authorize`).
- Las consultas del portal de cliente están correctamente acotadas por `customer_id` (sin IDOR) y el middleware `PortalAuth` las protege (`PortalDashboardController.php:34-37,54-56`).
- Los modelos siguen convenciones: `$fillable` explícito, método `casts()` (no propiedad `$casts`), sin `$guarded=[]`; sin iconos Tabler, sin tema select2 bootstrap-5, sin mass-assignment vía `request->all()`.
- Los nombres de permiso siguen la convención lowercase `{alias}.{action}` (`helpdesk.conversations.update`, `helpdesk.exports.create`, `helpdesk.customers.merge`).

## Cobertura de la auditoría

Análisis estático únicamente (no se ejecutaron tests — la BD de test está bloqueada según las notas de entorno). Lectura profunda: `module.json`/`composer.json`, los 3 providers, los 6 ficheros de rutas, ~16 controladores (incluyendo el `ConversationsController` de 2.762 líneas, Portal, ConversationParticipants/Items, Export, EmailInbound), servicios clave (LinkPreview, AiClient, AiAgent, ConversationMessage), ambos observers, los 8 comandos de consola, EventServiceProvider, DispatchWebhookJob, request de webhook, y los modelos Conversation/Customer/CannedReply. Se hizo grep sobre los 88 controladores para cobertura de `authorize()`, sobre los 239 blades para iconos/estilos/select2/salida sin escapar, y sobre todo `app/` para SQL crudo, mass assignment, `env()`, `Str::random`, Http/SSRF, TODO/stub.

**No leído individualmente:** la mayoría de los ~30 controladores de Settings protegidos por rol, las 117 Form Requests, los 66 modelos, los 28 listeners y los subsistemas HelpCenter/Campaigns/Channels/Macros — solo muestreados. El saneo de `body_html` no pudo trazarse por completo porque `MailerTemplateRendererService` vive en otro módulo.

## Descartados en verificación

Ninguno. Ningún hallazgo fue refutado durante la verificación; todos los hallazgos del cuerpo quedan confirmados con su severidad original.
