# Auditoría — HelpdeskLivechat

> Fecha: 2026-06-29 · Health score: 77/100 · Estado: solid-minor-issues

**Resumen:** Módulo de widget de live-chat público cuidadosamente threat-modeled (canales protegidos por token, HMAC, validación de origen, HTMLPurifier, anonimización de IP y suite de tests robusta) cuyo diseño de control de acceso —por lo demás sólido— queda comprometido por un IDOR en la ruta de creación de conversaciones que permite filtrar el token por-conversación de un visitante. El resto de hallazgos son endurecimientos menores y deuda de calidad/rendimiento de bajo impacto.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HDLC-001 | high | security | modules/HelpdeskLivechat/app/Services/Widget/WidgetConversationService.php:66-144 | [CONFIRMADO] | M | Secuestro de conversación / fuga de token vía `customer_id` o `email` en `createConversation` |
| HDLC-002 | medium | security | modules/HelpdeskLivechat/app/Services/Widget/WidgetConversationService.php:66-74 | [DUDOSO] | S | Sobrescritura arbitraria de PII de cliente desde input no autenticado del widget |
| HDLC-003 | low | security | modules/HelpdeskLivechat/app/Http/Controllers/Api/PreChatFormApiController.php:97-116 | [DUDOSO] | S | Ownership del pre-chat-form depende de email adivinable y sin middleware origin/HMAC |
| HDLC-004 | low | security | modules/HelpdeskLivechat/resources/views/public/widget/spa.blade.php:48 | [DUDOSO] | S | Config del SPA inyectada con `json_encode` sin flags HEX |
| HDLC-005 | low | conventions | modules/HelpdeskLivechat/database/seeders/HelpdeskLivechatPermissionsSeeder.php:14-18 | [DUDOSO] | S | Nombres de permisos se desvían de la convención de alias del módulo |
| HDLC-006 | low | wiring | modules/HelpdeskLivechat/resources/views/public/widget/spa.blade.php:39 | [DUDOSO] | S | Host de Reverb del SPA usa config de bind-address en lugar del host conectable |
| HDLC-007 | low | performance | modules/HelpdeskLivechat/app/Http/Controllers/Api/WidgetConversationController.php:195-209,300-309 | [DUDOSO] | S | Lookups repetidos `whereHas('inbox')` por request del widget |
| HDLC-008 | low | quality | modules/HelpdeskLivechat/resources/views/emails/conversation-transcript.blade.php:73 | [DUDOSO] | S | Cuerpos de agente doble-escapados en email de transcripción |

## Hallazgos detallados

### HDLC-001 — Secuestro de conversación / fuga de token vía `customer_id` o `email` en `createConversation` · [CONFIRMADO]

- **Severidad:** high (confirmada, sin ajuste)
- **Categoría:** security
- **Archivo:línea:** `modules/HelpdeskLivechat/app/Services/Widget/WidgetConversationService.php:66-144`

**Evidencia.** `store()` (`StoreWidgetConversationRequest`, `authorize()=true`, `customer_id` `nullable|integer`, sin prueba de identidad por defecto) llama a `createConversation()`. Cuando se suministra `customer_id` ejecuta `Customer::find($id)` (línea 67); si se encuentra una conversación OPEN existente para ese cliente (líneas 97-102), devuelve `['pubsub_token' => $existingPubsubToken, 'customer' => email/name, 'conversation_id']` (líneas 133-143). Lo mismo ocurre vía `Customer::firstOrCreate(['email'=>$email])` (líneas 76-80) cuando se suministra el email de una víctima. El `pubsub_token` es exactamente el secreto sobre el que `VerifiesConversationToken`/`authorizeConversation` se apoyan para proteger `show`/`getMessages`/`sendMessage`/`close`.

**Confirmación de verificación.** Vulnerabilidad real y explotable en configuración por defecto. Ruta de ataque: `POST /hd/api/conversation` con `customer_id=<victim_id>` (o `email=<victim@email>`) + cualquier `website_token` público válido → `WidgetConversationService.php:67` hace `Customer::find($data['customer_id'])` sin prueba de ownership → líneas 97-102 localizan la conversación abierta de la víctima → línea 141 devuelve `existingPubsubToken` al atacante. El middleware por defecto es permisivo: `VerifyWidgetHmac.php:41` deja pasar cuando `enforce_identity_verification=false` (default por-inbox); `ValidateTrustedOrigin.php:39` deja pasar cuando no hay `trusted_domains` configurados (default global). La ruta por email es igualmente viable (`Customer::firstOrCreate(['email'=>$email])`). El rate limit `throttle:10,1` en `store` ralentiza pero no bloquea la enumeración (~600 intentos/hora/IP). Los tests de seguridad existentes (`WidgetConversationSecurityTest`, `ConversationOwnershipTest`) solo cubren que los endpoints downstream rechazan tokens ausentes/erróneos; ninguno ejercita el ataque de fuga-de-token en `store`.

**Impacto.** Con la config por defecto, un atacante no autenticado con alcance a la API del widget puede enumerar `customer_id` secuenciales (o suministrar un email conocido de la víctima) y recibir el `pubsub_token` por-conversación de un chat activo de la víctima, luego leer la transcripción completa, enviar mensajes suplantando al visitante y cerrar la conversación — derrotando la protección IDOR central del módulo.

**Recomendación.** Nunca devolver el `pubsub_token` de una conversación existente sin probar ownership. Para visitantes no verificados, requerir que el cliente reenvíe el header `X-Conversation-Token` existente para reclamar la conversación vía `customer_id`/`email`; de lo contrario, crear siempre una conversación nueva. Ignorar `customer_id` suministrado por el cliente salvo que `enforce_identity_verification` + HMAC válido estén presentes.

---

### HDLC-002 — Sobrescritura arbitraria de PII de cliente desde input no autenticado del widget · [DUDOSO]

- **Severidad:** medium
- **Categoría:** security
- **Archivo:línea:** `modules/HelpdeskLivechat/app/Services/Widget/WidgetConversationService.php:66-74`

**Evidencia.** `if (! empty($data['customer_id'])) { $customer = Customer::find($data['customer_id']); if ($customer && $email && $customer->email !== $email) { $customer->update(['email' => $email, 'name' => ...]); } }` — no verifica que el caller sea dueño de ese customer (solo se evita cuando el HMAC `enforce_identity_verification` está activo, off por defecto). Los `custom_attributes` también se hacen `array_merge` sobre el customer.

**Impacto.** Un visitante del widget puede cambiar el email/name de otro cliente (e inyectar `custom_attributes`) pasando un `customer_id` adivinado, corrompiendo datos del CRM y potencialmente redirigiendo contacto futuro.

**Recomendación.** Confiar en `customer_id` solo cuando la verificación de identidad HMAC haya pasado; de lo contrario, resolver el customer únicamente desde la conversación/sesión verificada, nunca desde input crudo del request.

**Nota de verificación:** Marcado [DUDOSO] — comparte la misma raíz que HDLC-001 (confianza en `customer_id` no verificado). No se ejecutó verificación independiente del vector de sobrescritura de PII de forma aislada; depende del mismo bypass de middleware por defecto. Confirmar manualmente el flujo de `update()` sobre el customer ajeno antes de tratarlo como vector separado de HDLC-001.

---

### HDLC-003 — Ownership del pre-chat-form depende de email adivinable y sin middleware origin/HMAC · [DUDOSO]

- **Severidad:** low
- **Categoría:** security
- **Archivo:línea:** `modules/HelpdeskLivechat/app/Http/Controllers/Api/PreChatFormApiController.php:97-116`

**Evidencia.** `ownsConversation()` devuelve `true` cuando el `customer_email` del request coincide con el email del customer de la conversación (case-insensitive) O el `customer_id` coincide. Las rutas en `routes/api.php` están montadas bajo `api/v1/helpdesk-livechat` solo con `throttle:60,1` (sin `ValidateTrustedOrigin` / `VerifyWidgetHmac`, a diferencia del grupo `hd/api` del widget). `submit()` entonces escribe metadata `pre_chat` y puede rellenar email/name/phone del customer.

**Impacto.** El email de un cliente identificado suele ser conocido/adivinable, por lo que el gate de ownership es débil; combinado con la ausencia de capa origin/HMAC permite manipular la metadata pre-chat de otra conversación y rellenar campos vacíos del customer.

**Recomendación.** Autorizar el submit de pre-chat con el `X-Conversation-Token` por-conversación (`VerifiesConversationToken`) en lugar de igualdad de email, y montar estas rutas detrás del mismo middleware de origen confiable.

**Nota de verificación:** [DUDOSO] — no verificado independientemente. Confirmar que el endpoint efectivamente carece del middleware origin/HMAC y que `submit()` puede sobrescribir campos del customer antes de priorizar el fix.

---

### HDLC-004 — Config del SPA inyectada con `json_encode` sin flags HEX · [DUDOSO]

- **Severidad:** low
- **Categoría:** security
- **Archivo:línea:** `modules/HelpdeskLivechat/resources/views/public/widget/spa.blade.php:48`

**Evidencia.** `<script>window.HELPDESK_WIDGET_CONFIG = {!! json_encode($widgetConfig) !!};</script>` — la config incluye strings editables por admin (`welcome_message`, `header_title`, etc.). Un valor que contenga `</script>` rompería el contexto del script.

**Impacto.** XSS almacenado/self-XSS limitado a settings controlados por admin, pero un valor malicioso o comprometido de admin se ejecuta en la página de cada visitante.

**Recomendación.** Usar `json_encode($widgetConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`.

**Nota de verificación:** [DUDOSO] — endurecimiento de defensa en profundidad; la explotabilidad requiere un admin malicioso/comprometido. Quick win de bajo riesgo independientemente de la verificación.

---

### HDLC-005 — Nombres de permisos se desvían de la convención de alias del módulo · [DUDOSO]

- **Severidad:** low
- **Categoría:** conventions
- **Archivo:línea:** `modules/HelpdeskLivechat/database/seeders/HelpdeskLivechatPermissionsSeeder.php:14-18`

**Evidencia.** Los permisos son `helpdesk.livechat.settings.view/update` y `helpdesk.pre-chat.manage` mientras que el alias del módulo es `helpdesklivechat`. La convención del proyecto es `{alias}.{action}` o `{alias}.{entity}.{action}`; estos usan una forma `helpdesk.livechat.*` de 4 segmentos y un namespace `helpdesk.*` separado.

**Impacto.** Taxonomía de permisos inconsistente en la familia helpdesk; más difícil de auditar/otorgar por alias. (Riesgo menor porque los nombres seedeados coinciden con el uso del middleware `can:*`.)

**Recomendación.** Estandarizar intencionalmente sobre el esquema documentado de la familia helpdesk, o migrar a `helpdesklivechat.settings.*` / `helpdesklivechat.pre-chat.manage`; documentar la excepción si se mantiene.

**Nota de verificación:** [DUDOSO] — los nombres seedeados coinciden con el uso del middleware, por lo que es deuda de convención, no un bug funcional. Verificar contra el resto de la familia helpdesk antes de migrar (puede ser una excepción intencional ya documentada).

---

### HDLC-006 — Host de Reverb del SPA usa config de bind-address en lugar del host conectable · [DUDOSO]

- **Severidad:** low
- **Categoría:** wiring
- **Archivo:línea:** `modules/HelpdeskLivechat/resources/views/public/widget/spa.blade.php:39`

**Evidencia.** `'reverbHost' => config('broadcasting.connections.reverb.options.host', request()->getHost())` — `WidgetScriptController::connectableReverbHost()` existe específicamente para reemplazar direcciones de bind `0.0.0.0`/`::` con el host del request, pero `spa.blade` no lo aplica.

**Impacto.** Cuando el host de Reverb está configurado como dirección de bind, el WebSocket del widget SPA de página completa apunta a un host inalcanzable y las actualizaciones en tiempo real fallan silenciosamente.

**Recomendación.** Calcular el host conectable en `WidgetController::index()` y pasarlo a la vista, reutilizando la misma lógica que `WidgetScriptController`.

**Nota de verificación:** [DUDOSO] — depende de cómo esté configurado el host de Reverb en cada entorno; en muchos despliegues el host ya es conectable y no se manifiesta. Verificar la config de Reverb del entorno objetivo.

---

### HDLC-007 — Lookups repetidos `whereHas('inbox')` por request del widget · [DUDOSO]

- **Severidad:** low
- **Categoría:** performance
- **Archivo:línea:** `modules/HelpdeskLivechat/app/Http/Controllers/Api/WidgetConversationController.php:195-209,300-309`

**Evidencia.** `fileUploadEnabled()` y `emailTranscript()` ejecutan cada uno `Web::query()->whereHas('inbox', fn($q)=>$q->where('id',$conversation->inbox_id))->first()` en cada llamada de send/transcript; `VerifyWidgetHmac` ya resolvió y cacheó un atributo de request `widget_web`, y la fila `Inbox` lleva `channel_id` directamente.

**Impacto.** Subquery correlacionada extra por envío-de-mensaje (throttle 30/min) y transcripción; menor pero evitable carga de DB en el hot path.

**Recomendación.** Resolver `Web` desde el `channel_id` del inbox (o el atributo cacheado `widget_web`) en lugar de una subquery `whereHas`.

**Nota de verificación:** [DUDOSO] — optimización válida pero impacto marginal; no se perfiló. Reutilizar el atributo `widget_web` cacheado es un quick win seguro.

---

### HDLC-008 — Cuerpos de agente doble-escapados en email de transcripción · [DUDOSO]

- **Severidad:** low
- **Categoría:** quality
- **Archivo:línea:** `modules/HelpdeskLivechat/resources/views/emails/conversation-transcript.blade.php:73`

**Evidencia.** `{!! nl2br(e($item->body)) !!}` escapa todos los cuerpos. Los cuerpos de visitante se almacenan ya sanitizados vía `clean()`/HTMLPurifier y los de agente pueden contener legítimamente HTML rico; el escape renderiza el HTML del agente como texto literal en la transcripción emailada.

**Impacto.** Cosmético/corrección: los mensajes formateados por agente se muestran como markup escapado en el email de transcripción. (Seguro, solo no fiel.)

**Recomendación.** Renderizar los cuerpos de agente (`user_id` presente) vía `{!! clean($item->body) !!}` y solo `e()`+`nl2br` el texto plano del visitante, reflejando la separación del renderizado in-app.

**Nota de verificación:** [DUDOSO] — corrección cosmética; el comportamiento actual es seguro (escapa de más, no de menos). Verificar que los cuerpos de agente efectivamente contienen HTML rico antes de cambiar a `clean()`.

---

## Plan de ataque priorizado

1. **HDLC-001 (high) — Cerrar el IDOR de secuestro de conversación.** Es el único hallazgo crítico de seguridad: bloquea la fuga del `pubsub_token`. Requerir echo de `X-Conversation-Token` para reclamar conversaciones existentes; ignorar `customer_id`/`email` no verificados. Añadir test que ejercite el ataque vía `store`. (Esfuerzo: M)
2. **HDLC-002 (medium) — Eliminar la sobrescritura de PII desde input no verificado.** Misma raíz que HDLC-001; resolver el customer solo desde conversación/sesión verificada. Abordar junto con HDLC-001. (Esfuerzo: S)
3. **HDLC-003 (low) — Endurecer el pre-chat-form.** Autorizar con `VerifiesConversationToken` y montar tras middleware de origen confiable. (Esfuerzo: S)
4. **HDLC-006 (low) — Host conectable de Reverb en el SPA** (afecta funcionalidad en tiempo real en ciertos entornos). (Esfuerzo: S)

## Quick wins

- **HDLC-004:** `json_encode($widgetConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)` en `spa.blade.php:48`.
- **HDLC-006:** Aplicar la lógica `connectableReverbHost()` en `spa.blade.php:39`.
- **HDLC-007:** Reutilizar el atributo de request `widget_web` (fijado por `VerifyWidgetHmac`) en `fileUploadEnabled()`/`emailTranscript()` en lugar de re-ejecutar lookups `whereHas('inbox')`.
- **HDLC-008:** Render diferenciado agente (`clean()`) vs visitante (`e()+nl2br`) en la transcripción.

## Fortalezas

- Autorización por `pubsub_token` por-conversación (`VerifiesConversationToken` + `hash_equals`) cierra el IDOR clásico en endpoints read/send/close/livestream/webrtc; los canales de broadcast usan comparación de token en tiempo constante y checks de permiso.
- Defensa en profundidad en la API del widget: rate limits por endpoint, middleware `ValidateTrustedOrigin` + `VerifyWidgetHmac`, re-verificación server-side de feature flags (subida de archivos, live view, screen share, transcripciones por email).
- Cuerpos de mensaje de visitante sanitizados con HTMLPurifier (`clean()`), allowlist de mime de adjuntos que excluye svg/exe/html, anonimización GDPR de IP en `WidgetSession`.
- Buena higiene Laravel: método `casts()`, `$fillable` explícito, return types, Form Requests con `messages()`/`attributes()` en español, jobs/listeners encolados con `tries`/`timeout`/`backoff`/`failed()`, listeners array-callable (seguros para `event:cache`), índices de migración correctos.
- Cobertura de tests sólida: 20 Feature + 5 Unit dirigidos a seguridad (IDOR, trusted origin, HMAC, tamaño de payload, transcripción), paginación y horarios de negocio.

## Cobertura de la auditoría

Revisadas todas las rutas (web-widget/widget/api/settings/channels), ServiceProvider, ambos middleware, el concern `VerifiesConversationToken`, los 5 controllers de API, ambos controllers de settings, los 3 controllers de Pages, ambos services, los 6 models, ambos jobs, el `EngagementBridgeListener`, `ProcessAutoActionsCommand`, el mailable, los Form Requests clave, los seeders de permisos/db y los índices de migración. Blades muestreados vía grep (icons/inline-style/select2/`{!! !!}`) y lectura de blades spa/settings/pre-chat.

**No leído en profundidad:** `CheckIntegrationHealthCommand`, `DemoController`, las 4 clases de Events, requests `WebRtc`/`MarkAsRead`/`UpdatePreChatForm` (skimmed), el bundle frontend React/TS del widget (fuera de alcance PHP) y los archivos de idioma. Tests no ejecutados por instrucciones (solo análisis estático); presencia de tests contada (20 Feature + 5 Unit).

## Descartados en verificación

Ninguno. Ningún hallazgo fue refutado durante la verificación. HDLC-001 quedó CONFIRMADO con cadena de explotación completa; el resto se mantiene como [DUDOSO] (pendiente de verificación independiente o de bajo impacto), pero ninguno se eliminó del cuerpo del reporte.
