# Auditoría core Helpdesk — Canales & Mensajería (in/out)
> Fecha: 2026-06-29 · Health score: 62/100 · Estado: solid-minor-issues

**Resumen:** La verificación de firmas de webhook es correcta y *fail-closed* y la ingesta entrante está encolada, pero el lado saliente bloquea el hilo de la petición contra las APIs de Meta, filtra el token de WhatsApp dentro del payload de la cola, y el *threading* de email/social carece de garantías de propiedad e idempotencia. Diagnóstico: el subsistema entrante está sólido (firma verificada sobre el cuerpo crudo con `hash_equals`, ingesta en jobs encolados, descarga de media fuera del *hot path*), mientras que el saliente y el *threading* concentran los 3 hallazgos de severidad alta: fuga de credencial de larga vida hacia Redis/Horizon (CHAN-01), inyección de mensajes entre clientes vía etiqueta de asunto sin validar propietario (CHAN-03) y llamadas síncronas a Graph dentro del hilo HTTP que pueden agotar los workers de PHP-FPM (CHAN-05). El resto son brechas de defensa en profundidad, idempotencia no atómica, deriva entre rutas duplicadas y código muerto.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| CHAN-01 | Alta | security | ProcessSocialWebhookJob.php:213-218 | [CONFIRMADO] | S | Token de acceso de WhatsApp serializado en el payload del job (fuga a Redis/Horizon) |
| CHAN-03 | Alta | security | EmailInboundService.php:34-49 | [CONFIRMADO] | S | Inyección de mensajes entre clientes vía etiqueta de hilo en el asunto sin validar |
| CHAN-05 | Alta | performance | ConversationMessagesController.php:106-140 | [CONFIRMADO] | M | Llamadas síncronas a Graph de Meta/WhatsApp dentro del hilo HTTP |
| CHAN-02 | Media | performance | ProcessSocialWebhookJob.php:185-200 | Sin verificar | M | Idempotencia entrante no atómica y sin índice único (mensajes duplicados en reintentos) |
| CHAN-04 | Media | security | ImapPullService.php:97-106 | Sin verificar | M | Reply-by-email confía en la cabecera From (falsificable) para la identidad del agente |
| CHAN-06 | Media | security | DownloadConversationAttachmentsJob.php:106-148 | Sin verificar | M | Descarga de media sin guard SSRF ni tope de tamaño (OOM) / SVG servido inline |
| CHAN-07 | Media | quality | WhatsAppMessageProcessor.php:22-112 | Sin verificar | L | Dos implementaciones divergentes de persistencia entrante (simulador vs producción) |
| CHAN-08 | Media | quality | ConversationMessagesController.php:47-198 | Sin verificar | M | Lógica duplicada de "store + send + broadcast" en controller y service |
| CHAN-09 | Media | wiring | WhatsAppHsmService.php:18-58 | Sin verificar | S | HSM de WhatsApp "mock-succeeds" si no está configurado e ignora el idioma pedido |
| CHAN-10 | Media | ux | OutboundMessageService.php:22-48 | Sin verificar | M | Fallos de envío saliente silenciados; sin estado de fallo ni ventana de 24h |
| CHAN-11 | Baja | wiring | HsmTemplatesController.php:19-78 | Sin verificar | S | Endpoint de plantillas HSM muerto con datos placeholder hardcodeados |
| CHAN-12 | Baja | quality | ProcessSocialWebhookJob.php:508-743 | Sin verificar | S | Métodos privados muertos en ProcessSocialWebhookJob |
| CHAN-13 | Baja | security | EmailInboundController.php:46-92 | Sin verificar | S | Sin chequeo de frescura/replay en webhooks de email entrante |
| CHAN-14 | Baja | quality | EmailInboundController.php:64-75 | Sin verificar | M | "Verificación de firma" SendGrid entrante es una comparación de secreto compartido |

## Hallazgos detallados

### Severidad ALTA

#### CHAN-01 — Token de acceso de WhatsApp serializado en el payload del job (fuga a Redis/Horizon) · [CONFIRMADO]
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Jobs/ProcessSocialWebhookJob.php:213-218`
- **Evidencia:** `processWhatsApp` despacha `DownloadConversationAttachmentsJob::dispatch($item->id, [$pendingAttachment], 'whatsapp', config('helpdesk.integrations.whatsapp.access_token'))`; el token se convierte en la propiedad `public readonly $bearerToken` del job (`DownloadConversationAttachmentsJob.php:39-46`) y se serializa en el almacén de la cola. Verificado en `DownloadConversationAttachmentsJob.php:43` — `public readonly ?string $bearerToken = null` es propiedad pública de un job `ShouldQueue`, por lo que el serializador de cola de Laravel la escribe literalmente en el payload de Redis y Horizon la renderiza en la UI de detalle del job. `DownloadConversationAttachmentsJob.php:176` ya hace fallback a `config(...)` cuando `$this->bearerToken` es null, por lo que el 4º argumento es completamente redundante.
- **Impacto:** El token de acceso de WhatsApp/Meta de larga vida queda persistido en el payload de la cola Redis y se muestra en la UI de detalle de Horizon, ampliando la exposición de una credencial de alto valor a cualquiera con acceso a la cola/Horizon.
- **Recomendación:** No pasar el token como argumento del job. `DownloadConversationAttachmentsJob::resolveWhatsAppMediaUrl` ya hace fallback a `config('helpdesk.integrations.whatsapp.access_token')`; eliminar el 4º argumento del `dispatch` y leer el token desde config dentro del job en tiempo de ejecución.
- **Esfuerzo:** S

#### CHAN-03 — Inyección de mensajes entre clientes vía etiqueta de hilo en el asunto sin validar · [CONFIRMADO]
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/EmailInboundService.php:34-49`
- **Evidencia:** `resolveConversation()` hace *threading* con `preg_match('/\[CONV-(\d+)\]/', $subject)` y carga la conversación por id solo con un chequeo de estado `is_open` — sin match de `customer_id`. El mismo patrón existe en `ImapPullService::resolveConversation` (`app/Services/Email/ImapPullService.php:123-134`, `/\[#(\d+)\]/`). El mensaje del remitente entrante se añade entonces a esa conversación como `ConversationItem`. Verificado en ambos archivos: `EmailInboundService.php:37-45` recibe `$customerId` como segundo parámetro pero nunca lo compara contra `conversation->customer_id`; `ImapPullService.php:123-134` es aún más débil — consulta por id SIN chequeo de estado abierto y SIN match de customer_id, por lo que incluso una conversación cerrada puede ser inyectada. El alcance de CHAN-03 está, si acaso, subestimado.
- **Impacto:** Cualquier email entrante a la dirección de soporte con el id de conversación de otro cliente en el asunto (p. ej. `Re: [CONV-500]`) se añade a esa conversación víctima, permitiendo inyección de mensajes entre clientes/agentes e ingeniería social.
- **Recomendación:** Al hacer *threading* por etiqueta de asunto, exigir que el `customer_id` de la conversación coincida con el id del remitente resuelto (o que el email del remitente pertenezca a esa conversación); en caso contrario, crear una nueva conversación. Aplicar en `EmailInboundService` e `ImapPullService`.
- **Esfuerzo:** S

#### CHAN-05 — Llamadas síncronas a Graph de Meta/WhatsApp dentro del hilo HTTP · [CONFIRMADO]
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationMessagesController.php:106-140`
- **Evidencia:** `store()` llama a `$this->outbound->sendReply()` y `sendAttachment()` de forma síncrona por mensaje y por adjunto; `OutboundMessageService` delega en `WhatsAppBusinessService::client()` (timeout 15s + retry(2,500)) y `MetaGraphChannelDriver::send()` (timeout 15s + retry(2)). Mismo patrón síncrono en `ConversationMessageService::store` (69-102), `broadcastTyping->setTyping` (297-299, llamada de 8s en un endpoint de typing de alta frecuencia) y `markConversationRead->markSeen` (238-246). Verificadas las tres rutas: `ConversationMessagesController.php:112,117-123` (N adjuntos → hasta 45s de bloqueo, `MetaGraphChannelDriver.php:128-129`, `WhatsAppBusinessService.php:434-435`); typing síncrono en cada toggle (`MetaGraphChannelDriver.php:80`, timeout 8s); `markSeen()` síncrono en línea 245. Las tres tienen try/catch (no causan un 500), pero retienen el worker FPM durante todo el timeout en caso de fallo, haciendo del agotamiento de workers el riesgo principal bajo carga.
- **Impacto:** Una respuesta de agente con N adjuntos puede bloquear la petición decenas de segundos (15s × ~3 intentos por llamada) y el endpoint de typing emite una llamada Graph bloqueante en cada toggle; bajo carga esto agota los workers de PHP-FPM. El envío de campañas ya usa jobs (`SendBroadcastJob`), por lo que el patrón existe.
- **Recomendación:** Despachar las llamadas salientes de texto/adjunto/typing/seen a un job encolado (reusar la cola helpdesk-webhooks/notifications) y correlacionar el id externo devuelto de forma asíncrona; como mínimo, encolar los adjuntos y hacer *fire-and-forget* del typing.
- **Esfuerzo:** M

### Severidad MEDIA

#### CHAN-02 — Idempotencia entrante no atómica y sin índice único (mensajes duplicados en reintentos)
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Jobs/ProcessSocialWebhookJob.php:185-200`
- **Evidencia:** `processWhatsApp/Facebook/Instagram` llaman a `isDuplicate()` (un chequeo `where(external_id)->exists()`, línea 449-459) y luego `ConversationItem::create()` fuera de cualquier transacción, con `$tries=3` y sin middleware `WithoutOverlapping` (`middleware()` retorna `[]` explícitamente, línea 52-57). La migración de `conversation_items` (`2025_12_29_020916`) indexa `conversation_id/type/created_at` pero NO tiene índice único en `external_id`. La ruta de email, en cambio, usa `WithoutOverlapping` (`ProcessEmailInboundJob.php:34-39`).
- **Impacto:** Meta re-entrega webhooks cuando el ACK es lento; workers concurrentes (o jobs reintentados) pueden ambos pasar el chequeo `exists()` e insertar `ConversationItems` duplicados para el mismo `wamid/mid`, produciendo burbujas de hilo duplicadas y automatizaciones dobles.
- **Recomendación:** Añadir índice único en `external_id` (o compuesto `conversation_id+external_id`) y confiar en una violación de unicidad capturada, y/o añadir `WithoutOverlapping` keyed por `external_id` al job social como se hace para email.
- **Esfuerzo:** M

#### CHAN-04 — Reply-by-email confía en la cabecera From (falsificable) para la identidad del agente
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/Email/ImapPullService.php:97-106`
- **Evidencia:** `processMessage()` resuelve el agente puramente por `User::where('email', $fromEmail)` y, si el asunto contiene `[#NNN]`, llama a `addAgentReply()` que almacena un item `is_internal=false` autorado como ese `user_id` — sin verificación SPF/DKIM/DMARC de la dirección From.
- **Impacto:** El From del email es trivialmente falsificable; un atacante que conozca la dirección de un agente y un id de conversación puede inyectar una respuesta de "agente" forjada en una conversación arbitraria vía el buzón monitorizado.
- **Recomendación:** Verificar `authentication-results` (SPF/DKIM pass) en los mensajes recogidos antes de confiar en el From para atribución de agente, o restringir el reply-by-email a un token de respuesta firmado embebido en el asunto/Reply-To saliente en lugar de hacer match del From crudo.
- **Esfuerzo:** M

#### CHAN-06 — Descarga de media sin guard SSRF ni tope de tamaño (OOM) / SVG servido inline
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Jobs/DownloadConversationAttachmentsJob.php:106-148`
- **Evidencia:** `downloadOne()` hace `Http::timeout(45)->get($url)` sobre una `original_url` suministrada por webhook sin chequeo `OutboundUrlGuard::isSafe()` (la clase guard existe y se usa en otros sitios) y carga toda la respuesta vía `$response->body()` sin max-content-length, luego hace `strlen()`. `extensionFromMime` mapea `image/svg+xml -> .svg` y almacena en el disco `public` servido same-origin. El `downloadMedia` inline en `ProcessSocialWebhookJob.php:642` tiene las mismas brechas.
- **Impacto:** Brecha de defensa en profundidad: una URL de media apuntando a IPs internas (p. ej. en no-prod donde la firma se omite, o un futuro canal no-Meta) se descarga sin guard; un fichero muy grande puede causar OOM en el worker; un SVG entrante almacenado y abierto same-origin puede ejecutar script.
- **Recomendación:** Pasar `original_url` por `OutboundUrlGuard::isSafe()` antes de descargar, forzar un tamaño máximo de descarga (stream + cap de Content-Length), y almacenar SVG/HTML con un content-type no ejecutable o forzar descarga (`Content-Disposition: attachment`).
- **Esfuerzo:** M

#### CHAN-07 — Dos implementaciones divergentes de persistencia entrante (simulador vs producción)
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/Webhooks/WhatsAppMessageProcessor.php:22-112`
- **Evidencia:** `WhatsAppMessageProcessor/FacebookMessageProcessor/InstagramMessageProcessor` solo se referencian desde las rutas del simulador (`HelpdeskSimulatorController`, `PublicSimulatorService`, `SimulateInboundWebhookCommand`) — grep no muestra ningún caller de webhook en producción. Los handlers POST de producción usan la lógica inline de `ProcessSocialWebhookJob`. Las dos ya difieren: el processor deduplica GLOBALMENTE (`where(external_id)`, línea 107-112) y fija `phone+whatsapp_phone`, mientras que el job deduplica POR-conversación y fija solo `whatsapp_phone`.
- **Impacto:** Los simuladores de agente/público ejercitan una ruta de código distinta a los webhooks reales, por lo que la confianza basada en el simulador y cualquier test a través de él no validan el comportamiento de producción, y las dos rutas derivan en silencio.
- **Recomendación:** Extraer un único servicio compartido (p. ej. las clases `MessageProcessor` existentes) y hacer que `ProcessSocialWebhookJob` delegue en él, para que simulador y producción compartan normalización/dedup/persistencia idénticas.
- **Esfuerzo:** L

#### CHAN-08 — Lógica duplicada de "store + send + broadcast" en controller y service
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/ConversationMessagesController.php:47-198`
- **Evidencia:** `ConversationMessagesController::store` y `ConversationMessageService::store` implementan ambos: construir `attachment_urls`, crear el item, llamar `outbound->sendReply/sendAttachment`, fijar id externo/outbound, actualizar timestamps, broadcast. Difieren en comportamiento (el service elimina los `@handles` para cuerpos externos y maneja `scheduled_at`/mentions; el controller añade link previews) y cada uno reimplementa `absoluteUrl()`/`mediaTypeFromMime()`.
- **Impacto:** Dos rutas salientes casi idénticas derivan (p. ej. *mention stripping* aplicado en una pero no en la otra), duplicando la superficie de bugs y el eventual refactor de encolado.
- **Recomendación:** Consolidar en `ConversationMessageService::store` y hacer que el controller delegue en él; eliminar los helpers privados duplicados.
- **Esfuerzo:** M

#### CHAN-09 — HSM de WhatsApp "mock-succeeds" si no está configurado e ignora el idioma pedido
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/WhatsAppHsmService.php:18-58`
- **Evidencia:** Cuando faltan token de acceso/phone id, `send()` retorna `['mocked'=>true,'id'=>'wa-mock-...']` y `sendHsm()` (`ConversationsController.php:808-841`) registra el item y responde éxito (201). El idioma está hardcodeado `'language'=>['code'=>'es']` (línea 56) aunque `SendHsmRequest` valida un `'language' in:es,en,pt` que nunca se pasa.
- **Impacto:** En un entorno de producción mal configurado se le dice al agente que la plantilla fue enviada cuando nada salió del sistema; y las plantillas aprobadas en idiomas no españoles fallan en Meta porque el código de idioma siempre es `'es'`.
- **Recomendación:** Tratar las credenciales faltantes como un fallo duro fuera de local (throw/retornar un error claro), y pasar el idioma validado a `WhatsAppHsmService` en lugar de hardcodear `'es'`.
- **Esfuerzo:** S

#### CHAN-10 — Fallos de envío saliente silenciados; sin estado de fallo ni ventana de 24h
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Services/OutboundMessageService.php:22-48`
- **Evidencia:** `sendReply` retorna null en canal deshabilitado, circuit breaker abierto, o error de la API de Meta (`WhatsAppBusinessService::send` retorna null en `!successful`, solo logueando). Los callers (`ConversationMessagesController::store` 128-131, `ConversationMessageService` 96-101) solo persisten un id externo cuando es no-null y nunca marcan el item como fallido; un retorno null deja el mensaje mostrado como enviado. No hay detección de la ventana de servicio al cliente de 24h de WhatsApp, así que las respuestas tras 24h fallan en Meta pero se muestran como entregadas.
- **Impacto:** Los agentes creen que una respuesta al cliente fue entregada cuando fue rechazada (canal deshabilitado, circuito abierto, o fuera de la ventana de 24h), sin indicación en UI ni aviso para usar una plantilla HSM.
- **Recomendación:** Distinguir 'no aplicable' de 'envío fallido' (p. ej. throw en error de API o retornar un objeto de estado), persistir un estado failed/needs-HSM en el item, y mostrarlo en el hilo; detectar la ventana de 24h y dirigir al agente a HSM.
- **Esfuerzo:** M

### Severidad BAJA

#### CHAN-11 — Endpoint de plantillas HSM muerto con datos placeholder hardcodeados
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/Managers/HsmTemplatesController.php:19-78`
- **Evidencia:** `HsmTemplatesController::index` (ruta `manager.helpdesk.hsm-templates.index`, `/panel/helpdesk/api/hsm-templates`) retorna 5 plantillas hardcodeadas. El frontend (`newconv.blade.php:188`, `new-conv-wizard.blade.php:181`) solo llama a `/panel/helpdesk/hsm-templates -> ConversationsController::hsmTemplates`, que lee correctamente la tabla sincronizada `WhatsAppTemplate`. Grep no encuentra caller de `/api/hsm-templates`.
- **Impacto:** Endpoint sin uso anunciando plantillas 'APPROVED' fabricadas que no existen en WhatsApp Business, una trampa de mantenimiento/footgun.
- **Recomendación:** Eliminar `HsmTemplatesController` y su ruta, o reapuntarlo a la tabla `WhatsAppTemplate` si se pretende un segundo consumidor.
- **Esfuerzo:** S

#### CHAN-12 — Métodos privados muertos en ProcessSocialWebhookJob
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Jobs/ProcessSocialWebhookJob.php:508-743`
- **Evidencia:** `resolveWhatsAppMediaUrl` (508), `labelForType` (621), `downloadMedia` (642) y `extensionFromMime` (692) ya no son alcanzados: la resolución/descarga de media se movió a `DownloadConversationAttachmentsJob`, y los cuerpos se construyen con `buildWhatsAppBody/resolveBodyAndAttachments` sin labels. Permanecen como copias duplicadas de la lógica del job de descarga.
- **Impacto:** ~230 líneas de código muerto y duplicado (con sus propias descargas Http sin guard) inflan el job e invitan a la divergencia.
- **Recomendación:** Eliminar los métodos sin uso; conservar la única implementación en `DownloadConversationAttachmentsJob`.
- **Esfuerzo:** S

#### CHAN-13 — Sin chequeo de frescura/replay en webhooks de email entrante
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/EmailInboundController.php:46-92`
- **Evidencia:** `verifyMailgun` valida `HMAC(timestamp.token)` pero nunca comprueba que el timestamp sea reciente; `verifySendgrid/verifyPostmark` no tienen ventana de nonce/timestamp. La idempotencia aguas abajo depende del dedup por `message_id` (`EmailInboundService::isDuplicate`), que un cuerpo reenviado con un `message_id` fresco eludiría.
- **Impacto:** Una petición de webhook válida capturada puede ser reenviada; para Mailgun una firma rancia-pero-válida se acepta hasta que el token se reuse en otro sitio.
- **Recomendación:** Rechazar peticiones Mailgun cuyo timestamp sea mayor a unos minutos y cachear/seen-check el token de Mailgun; para los otros confiar en el dedup por `message_id` existente pero documentarlo.
- **Esfuerzo:** S

#### CHAN-14 — "Verificación de firma" SendGrid entrante es una comparación de secreto compartido
- **Archivo:línea:** `/Users/developerts/Herd/system/src/modules/Helpdesk/app/Http/Controllers/EmailInboundController.php:64-75`
- **Evidencia:** `verifySendgrid` hace `hash_equals($secret, $request->header('X-Sendgrid-Signature', $request->input('signature','')))` — i.e. compara un secreto estático contra un valor de cabecera. SendGrid Inbound Parse no provee tal cabecera; su Event Webhook usa firmas ECDSA de clave pública, no igualdad de secreto compartido.
- **Impacto:** Verificación engañosa: es solo tan fuerte como un token estático compartido en una cabecera (efectivamente un bearer secret), no una firma real por payload, y no interoperará con la firma real de SendGrid.
- **Recomendación:** O implementar la verificación de firma ECDSA de SendGrid (clave pública + Ed/ECDSA sobre timestamp+payload) o renombrar el mecanismo a un chequeo de token compartido y exigirlo vía cabecera sobre TLS; alinear el naming de config en consecuencia.
- **Esfuerzo:** M

## Plan de ataque priorizado

1. **CHAN-01 (Alta, S)** — Quick win y la corrección más rápida: eliminar el 4º argumento del `dispatch` en `ProcessSocialWebhookJob.php:213-218`; el job ya hace fallback a config. Elimina la credencial de Redis/Horizon de inmediato.
2. **CHAN-03 (Alta, S)** — Añadir un guard de dos líneas en `EmailInboundService` e `ImapPullService` que exija coincidencia de `customer_id` antes de hacer *threading* por etiqueta de asunto. Cierra la inyección entre clientes.
3. **CHAN-05 (Alta, M)** — Extraer las llamadas salientes (texto/adjunto/typing/seen) a un job encolado reusando el patrón de `SendBroadcastJob`. Elimina el riesgo de agotamiento de workers FPM.
4. **CHAN-02 (Media, M)** — Índice único en `external_id` + manejo de violación de unicidad / `WithoutOverlapping` en el job social.
5. **CHAN-06 (Media, M)** — `OutboundUrlGuard::isSafe()` + cap de tamaño + `Content-Disposition: attachment` para SVG/HTML.
6. **CHAN-10 / CHAN-09 (Media)** — Estado de fallo saliente + detección de ventana de 24h; fallo duro de HSM sin credenciales y paso del idioma validado.
7. **CHAN-04 (Media, M)** — Verificación SPF/DKIM o token firmado para reply-by-email.
8. **CHAN-07 / CHAN-08 (Media)** — Consolidación de rutas duplicadas (entrante simulador/prod y saliente controller/service).
9. **Bajas (CHAN-11/12/13/14)** — Limpieza de código muerto y endurecimiento de verificación de email entrante.

## Quick wins

- **CHAN-01:** Eliminar el 4º argumento de `DownloadConversationAttachmentsJob::dispatch` y leer el token de WhatsApp desde config dentro del job (ya hace fallback) — elimina el secreto de Redis/Horizon.
- **CHAN-02:** Añadir un índice único en `helpdesk_conversation_items.external_id` (o compuesto `conversation_id+external_id`) para hacer la idempotencia atómica.
- **CHAN-11 / CHAN-12:** Borrar el `HsmTemplatesController` muerto con endpoint hardcodeado (sin caller JS) y los métodos sin uso `downloadMedia/resolveWhatsAppMediaUrl/labelForType` en `ProcessSocialWebhookJob`.

## Fortalezas

- La verificación de firma de webhook (`X-Hub-Signature-256`) se computa sobre el cuerpo crudo con `hash_equals` y falla CERRADO en entornos no-local (`WhatsAppWebhookRequest`/`FacebookWebhookRequest`/`InstagramWebhookRequest` + `EmailInboundController`), no fail-open.
- La ingesta entrante se delega a jobs encolados (`ProcessSocialWebhookJob`, `ProcessEmailInboundJob`, `DownloadConversationAttachmentsJob`) por lo que el endpoint público de webhook retorna rápido; la descarga de media se mueve explícitamente fuera del *hot path*.
- Los webhooks de automatización/workflow salientes SÍ aplican un guard SSRF (`OutboundUrlGuard` en `SendWebhookAction`) y firman los payloads con HMAC (`DispatchWebhookJob`, secreto oculto en el modelo `Webhook`).
- La ruta de envío de WhatsApp tiene un `CircuitBreaker`, reintenta solo en 5xx, y tiene timeouts acotados; el render de cuerpos de mensaje es XSS-safe (`ConversationItem::getBodyHtmlAttribute` escapa vía `e()` antes de linkificar, la preview de conversación usa `e()`).
- Cobertura de tests de ingesta razonable: tests de webhook WhatsApp/Facebook/Instagram, `WebhookSignatureTest`, `EmailInboundSignatureTest`, `ChannelDriverTest`, `WhatsAppHsmServiceTest`.

## Cobertura de la auditoría

Leídos en su totalidad: controllers de webhook (WhatsApp/Facebook/Instagram) y sus FormRequests; `WhatsAppBusinessService`, `FacebookMessengerService`, `InstagramService`, `MetaGraphChannelDriver`, `OutboundMessageService`, `WhatsAppHsmService`; `ProcessSocialWebhookJob`, `ProcessEmailInboundJob`, `DownloadConversationAttachmentsJob`, `SyncWhatsAppTemplatesJob`; `EmailInboundController` + `EmailInboundService` + `ImapPullService` + `SmtpSendService`; `HsmTemplatesController`, `WhatsAppTemplatesController`, `SendHsmRequest`; `ConversationMessagesController` + `ConversationMessageService` (rutas store salientes) y `ConversationsController::sendHsm/hsmTemplates`; `OutboundUrlGuard`; `routes/webhooks.php`; bloques `config/helpdesk.php` integration/email_inbound; migración conversation_items; `ConversationItem::getBodyHtmlAttribute` y Blade de thread/preview para XSS; definición de rate-limiter; y los ficheros de test del subsistema. Verificado que `Services/Webhooks/*MessageProcessor` son solo de simulador.

NO ejecutado (BD de test bloqueada) — el análisis es estático. Los tres hallazgos de severidad alta (CHAN-01, CHAN-03, CHAN-05) fueron verificados estáticamente en las líneas reclamadas sin falsos positivos; las demás (CHAN-02, CHAN-04, CHAN-06–14) están reportadas con evidencia estática pero sin verificación independiente adicional.

No auditados en profundidad: internals de `SendBroadcastJob`/drip campaigns, el JS de chrome/widget, ni `HelpdeskSocial` (módulo separado). Hallazgos previos conocidos (colas huérfanas de Horizon, fragmentación de key OpenAI, ruta ConversationSummary, god `ConversationsController`, SSRF de link-preview, DELETE de conversation-items) no se re-reportaron salvo donde este subsistema añade detalle nuevo.

## Descartados en verificación

Ninguno. Los tres hallazgos verificados (CHAN-01, CHAN-03, CHAN-05) fueron CONFIRMADOS en las líneas reclamadas sin falsos positivos. En el caso de CHAN-03, la verificación reveló que `ImapPullService::resolveConversation` es más débil de lo descrito (no chequea estado abierto ni `customer_id`), por lo que el alcance está, si acaso, subestimado.
