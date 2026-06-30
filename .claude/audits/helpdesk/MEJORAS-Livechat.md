# Mejoras — HelpdeskLivechat (2026-06-30)

> Análisis estático (Read/Grep/Glob) del módulo `modules/HelpdeskLivechat` (~11.5k LOC PHP, widget
> **React/TSX** + Reverb). Complementa la auditoría de bugs (`HelpdeskLivechat.md`, health 77/100) y los
> roadmaps transversales (`MEJORAS.md`, `UX-PERFORMANCE.md`, `INDEX.md`): aquí los hallazgos se **elevan a
> mejoras accionables**, no se repiten como bugs.
>
> Convención de esfuerzo: `s` = horas/≤1 día · `m` = días · `l` = 1-2 semanas · `xl` = multi-semana.
> Color primario `#90bb13`. Stack widget: React 19 + react-router + zustand + laravel-echo/pusher + rrweb.

---

## Resumen ejecutivo

El módulo está **mejor construido de lo que sugiere su health score**: realtime por Reverb (sin polling)
con reconexión + resync, autorización REST por `X-Conversation-Token` en tiempo constante, HMAC opcional,
validación de origen, HTMLPurifier, anonimización de IP GDPR, rrweb cargado de forma diferida y buena suite
de tests. La oportunidad NO es arreglar fundamentos rotos, sino **cerrar tres brechas que limitan el valor**:

1. **El read-path en tiempo real está sin proteger.** El widget se suscribe a un canal *público y
   adivinable* (`helpdesk-widget-conversation.{id}` — sin el pubsub_token), mientras que todo el diseño de
   canal cripto-guardado (`.{id}.{pubsubToken}` en `channels.php`) quedó como **código muerto** que solo usa
   WebRTC. Un atacante puede escuchar en tiempo real las respuestas del agente y el "escribiendo" de
   cualquier chat enumerando IDs secuenciales. Es un IDOR de confidencialidad que la auditoría de bugs no
   detectó (se centró en el leak REST del `store`, HDLC-001).

2. **Funcionalidad construida pero no cableada.** El backend ya **emite el "escribiendo" del agente al
   widget**, pero el widget nunca lo escucha ni lo pinta. `queue_message` con `:number` está configurado
   pero la posición en cola **nunca se calcula ni se renderiza**. `ChatPageScreen` es una maqueta estática
   muerta con quick-replies hardcodeadas. La disponibilidad ("Online") se calcula **solo por horario**, sin
   detectar si hay algún agente realmente conectado. No hay IA conversacional (solo el puente de marketing
   de Engagement), pese a que existen HelpdeskAgents (runtime huérfano) y ChatFlow.

3. **Deuda de payload/arquitectura barata de pagar.** `getWidgetConfig()` envía claves camelCase
   *deprecadas duplicadas* en cada página; la config del widget se inyecta sin flags HEX; el host de Reverb
   del SPA puede apuntar a una dirección de bind; 0 policies y nombres de permiso fuera de convención.

La estrategia: **Ola 1 — quick wins** (agent-typing en el widget, UI de estado de conexión, hardening de
una línea, trim de payload), **Ola 2 — seguridad** (cerrar el IDOR de canal + reclaim de conversación),
**Ola 3 — producto** (presencia real, cola, IA/deflexión, canned, handoff).

---

## Top por ROI

Ranking por ROI; desempata impacto (high>medium) y luego menor esfuerzo.

| # | ID | Mejora | Eje | Impacto | Esf. | ROI | Evidencia |
|---|----|--------|-----|---------|------|-----|-----------|
| 1 | PROD-LC-03 | Mostrar "agente escribiendo" en el widget (el backend ya lo emite) | Producto/UX | high | s | **muy alto** | `widget/hooks/useConversationMessages.ts:172` (no escucha typing); core `Events/ConversationUserTyping.php` broadcastOn ya incluye el canal del widget |
| 2 | UX-LC-01 | UI de estado de conexión (reconectando/offline) | UX | high | s | **muy alto** | `widget/echo.ts:56-65` + `useConversationMessages.ts:308-321` (existe `isReconnecting`); `components/ConversationHeader.tsx:56` muestra "Online" estático |
| 3 | SEC-LC-01 | Cerrar el IDOR de escucha en tiempo real (canal público sin token) | Seguridad | high | m | **alto** | `useConversationMessages.ts:172` `echo.channel('helpdesk-widget-conversation.'+id)`; `Helpdesk/Events/MessageReceived.php:43`; `routes/channels.php:58` (canal con token sin usar para chat) |
| 4 | PROD-LC-01 | Presencia/disponibilidad real de agentes (no solo horario) | Producto | high | m | **alto** | `Models/Channels/Web.php:350,370-393` (`is_open` = horario); widget `widget-store.ts:147-171` `computeIsOpen` |
| 5 | QW-BUNDLE | Lote quick wins: trim camelCase dupes + JSON HEX + host Reverb conectable + reuse `widget_web` | Perf/Seg | high | s | **alto** | `Web.php:280-296`; `spa.blade.php:48,39`; `WidgetConversationController.php:195,302` |
| 6 | SEC-LC-02 | Hardening identidad: no filtrar token ni sobrescribir PII por `customer_id`/email no verificado (HDLC-001/002) | Seguridad | high | m | alto | `Services/Widget/WidgetConversationService.php:66-144` |
| 7 | PROD-LC-02 | Cablear IA (HelpdeskAgents/ChatFlow) + deflexión semántica al widget | Producto | high | l | alto | sin IA conversacional; solo puente Engagement `widget-entry.tsx:35` |
| 8 | PROD-LC-04 | Posición real en cola + experiencia de espera | Producto | medium | m | medio | `widget-store.ts:42,126` (`queue_message` nunca renderizado); `LivechatSettingsController.php:180` (`:number`) |
| 9 | PROD-LC-05 | Canned/quick replies reales (hoy maqueta muerta) | Producto | medium | m | medio | `widget/screens/ChatPageScreen.tsx:42-44` placeholders hardcodeados |
| 10 | SEC-LC-03 | Endurecer pre-chat-form (token en vez de email; tras middleware origen) (HDLC-003) | Seguridad | medium | s | medio | `Controllers/Api/PreChatFormApiController.php:97-116`; `routes/api.php` (solo `throttle:60,1`) |

---

## Quick wins (esfuerzo s, ROI alto) — empezar por aquí

- **[s] PROD-LC-03 — "Agente escribiendo" en el widget.** El backend YA difunde `ConversationUserTyping`
  al canal del widget (`Helpdesk/Events/ConversationUserTyping.php` broadcastOn → `Channel('helpdesk-widget-conversation.{id}')`),
  pero `useConversationMessages.ts:172-239` solo escucha `.message.received`/`.language.detected`. Añadir un
  listener `.UserTyping` + burbuja de typing. El visitante ya **envía** su typing (`screens/ConversationScreen.tsx:145`),
  así que la asimetría es puramente de front. Máximo ROI: feature percibida "gratis".
- **[s] UX-LC-01 — Estado de conexión visible.** `echo.ts` ya expone `onWsStateChange` y el hook ya marca
  `isReconnecting` y resincroniza al reconectar, pero la cabecera muestra "Online" fijo
  (`ConversationHeader.tsx:56-59`). Pintar banner "Reconectando…/Sin conexión" cuando el estado sea
  `disconnected|unavailable`. Genera confianza en el canal con infra ya existente.
- **[s] QW-BUNDLE — Lote de endurecimiento/payload de una pasada:**
  - **JSON HEX (HDLC-004):** `spa.blade.php:48` → `json_encode($widgetConfig, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)`.
  - **Host Reverb conectable (HDLC-006):** `spa.blade.php:39` usa `broadcasting...options.host` (puede ser
    bind `0.0.0.0`); reutilizar la lógica `connectableReverbHost()` de `WidgetScriptController` en
    `WidgetController::index()` y pasarla a la vista.
  - **Reuse `widget_web` (HDLC-007):** `WidgetConversationController::fileUploadEnabled()` (:302) y
    `emailTranscript()` (:195) re-ejecutan `whereHas('inbox')`; resolver `Web` desde `inbox.channel_id` o el
    atributo `widget_web` ya cacheado por `VerifyWidgetHmac`.
  - **Trim camelCase dupes (PERF-LC-02):** `Web::getWidgetConfig()` (`Web.php:280-296`) emite ~16 claves
    camelCase *deprecadas duplicadas* del snake_case; `widget-store.ts` ya lee snake_case → eliminarlas
    reduce el JSON inyectado en cada página y la respuesta de `/hd/api/settings`.
- **[s] QUAL — Limpieza de muertos:** eliminar la ruta/pantalla mock `ChatPageScreen` (`WidgetApp.tsx:46`,
  texto inglés hardcodeado + input no funcional) y el `livechat.secret_key` generado pero no usado
  (`LivechatSettingsController.php:157-161`; el HMAC usa `Web::hmac_token`).
- **[s] QUAL — Transcripción (HDLC-008):** render diferenciado agente (`{!! clean() !!}`) vs visitante
  (`e()+nl2br`) en `resources/views/emails/conversation-transcript.blade.php:73`.

---

## Iniciativas grandes (esfuerzo m/l)

### PROD-LC-02 — Cablear IA conversacional + deflexión semántica al widget `[l]`
- **Valor**: hoy el widget **no tiene IA conversacional** — solo el puente de marketing de Engagement
  (`widget-entry.tsx:35` `botMessage`, inyección de triggers en `spa.blade.php:75-101`). Existen
  HelpdeskAgents (runtime huérfano, RW-10 del roadmap) y ChatFlow. Cablear una conversación web entrante →
  agente/flow IA para primera respuesta y auto-resolución, con escalado a humano cuando proceda.
- **Alcance**: listener sobre `ConversationCreated`/mensaje entrante de canal `web` que despache el runtime
  IA tras feature flag; combinar con **deflexión semántica** (RW-02: `EmbeddingsService::search` en el
  paso pre-chat/nueva-conversación, hoy el widget consume solo el FULLTEXT de
  `/hd/api/helpcenter/search`). Cada ticket deflectado tiene ROI directo.
- **Dependencias**: ARCH-01 (clave OpenAI unificada), RW-10 (runtime), SEC-02 (PromptSanitizer). Evitar dos
  runtimes en competencia con el agente IA del core.

### PROD-LC-01 — Presencia/disponibilidad real de agentes `[m]`
- **Valor**: `is_open` se calcula **solo por horario** (`Web::isWithinBusinessHours` :370-393, y cliente
  `computeIsOpen` widget-store.ts:147-171). El visitante ve "Online" (`ConversationHeader.tsx:56`) aunque no
  haya **ningún agente conectado**, generando chats muertos; o ve "offline" en horario aunque haya agentes.
- **Alcance**: señal de agente-online (canal de presencia Reverb del panel, o `last_seen_at` por heartbeat
  del inbox) → alimentar `is_open`/offline-form/tiempo de espera esperado. Diferenciar "fuera de horario"
  de "sin agentes ahora".
- **Dependencias**: integra con RW-11 del roadmap (disponibilidad/turnos en el enrutado de asignación).

### PROD-LC-04 — Posición real en cola y experiencia de espera `[m]`
- **Valor**: `queue_message` con `:number` está configurado en 7 idiomas (`i18n/translations.ts`) y en
  settings (`LivechatSettingsController.php:180`) pero **la posición nunca se calcula ni se renderiza**
  (`widget-store.ts:42` definido, sin uso). Mostrar expectativa reduce abandono.
- **Alcance**: calcular posición (conversaciones `web` sin asignar por delante en el inbox) y
  difundir/poll-ear el valor; renderizar con sustitución de `:number` (el helper i18n ya soporta variables,
  `useLanguage.ts:89`).

### PROD-LC-05 / PROD-LC-06 — Canned replies + handoff visitante↔humano `[m]`
- **Canned/quick replies**: no existen; `ChatPageScreen.tsx:42-44` tiene chips muertos ("Track my order",
  "Contact support", "FAQs"). Cablear quick-reply chips configurables en la primera pantalla y, del lado
  agente, respuestas predefinidas.
- **Handoff**: solo hay auto-transfer servidor→cola (`ProcessAutoActionsCommand`). Falta botón visitante
  "hablar con una persona" y mensaje "te ha atendido X / transferido a Y" — **imprescindible** cuando la IA
  (PROD-LC-02) conteste primero.

### SEC-LC-01 — Unificar el realtime bajo el canal cripto-guardado `[m]`
- **Valor**: cierra el IDOR de escucha en tiempo real (ver eje Seguridad). Es cross-module (toca eventos
  del core), por eso es `m` y no `s`.
- **Alcance**: que `MessageReceived` y `ConversationUserTyping` del core difundan en
  `helpdesk-widget-conversation.{id}.{pubsubToken}` (canal ya autorizado en `channels.php:58`), y el widget
  se suscriba con token; retirar el canal sin token. Coordinar con otros consumidores del canal legacy.

---

## Eje 1 — Producto / Features

Funcionalidad de alto valor construida-pero-inerte o ausente.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| PROD-LC-03 | "Agente escribiendo" en el widget (backend ya lo emite) | high | s | muy alto |
| PROD-LC-01 | Presencia/disponibilidad real de agentes | high | m | alto |
| PROD-LC-02 | IA conversacional (Agents/ChatFlow) + deflexión semántica | high | l | alto |
| PROD-LC-04 | Posición real en cola + espera | medium | m | medio |
| PROD-LC-05 | Canned/quick replies reales | medium | m | medio |
| PROD-LC-06 | Handoff visitante↔humano / "talk to a human" | medium | m | medio |
| PROD-LC-07 | Reintento de envío fallido (cola optimista sin retry) | low | m | medio |
| PROD-LC-08 | Eliminar/implementar `ChatPageScreen` (maqueta muerta) | low | s | medio |
| PROD-LC-09 | Surface de CSAT/transcripción a HelpdeskAnalytics | low | s | bajo |

- **PROD-LC-07**: el envío es optimista (`ConversationScreen.tsx:180-296`) pero **no hay retry**: los fallos
  se descartan. Añadir affordance "Reintentar" + cola offline.
- **PROD-LC-09**: el CSAT se captura al cerrar (`WidgetConversationController::saveCsatRating` → `CsatRating`)
  pero no hay reporting del módulo; alimentar el dashboard de HelpdeskAnalytics.
- **Presentes y sólidas** (no tocar): pre-chat form, post-chat CSAT, file upload, emoji, help center +
  feedback de artículo, tickets (crear/listar), screen-share/live-view (rrweb diferido), sound
  notifications, i18n 7 idiomas, ErrorBoundary, envío optimista, read-receipts visitante→agente.

## Eje 2 — Velocidad / Performance

Buenos fundamentos (Reverb sin polling, rrweb diferido, geoip cacheado 24h, gate Redis de heartbeat). Lo
mejorable es payload y un par de hot-paths.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| PERF-LC-02 | Trim de claves camelCase deprecadas en `getWidgetConfig()` | medium | s | alto |
| PERF-LC-01 | Reusar `widget_web` cacheado en vez de `whereHas('inbox')` (HDLC-007) | medium | s | alto |
| PERF-LC-03 | N+1 dentro de `ProcessAutoActionsCommand` | medium | m | medio |
| PERF-LC-05 | Peso del bundle widget (main.js 409KB) | medium | m | medio |
| PERF-LC-04 | Carga del heartbeat (15s) — monitorizar | low | s | bajo |

- **PERF-LC-03**: `ProcessAutoActionsCommand` corre cada 5 min y, por conversación idle (hasta 500/canal),
  ejecuta `visitorIsWaiting()` (query, :132-142) + `close()`/`assignTo()`/`update()` individuales. Eager-load
  del último item o procesado por lotes. Bajo riesgo hoy, escala mal.
- **PERF-LC-05**: `public/build-helpdesklivechat/widget/main.js` = **409KB** (57KB CSS). React 19 +
  `react-router-dom` + echo + pusher + zustand. `react-router-dom` es pesado para un widget de chat;
  rrweb ya está diferido (bien, `livestream.ts:73`). Considerar: sustituir router por una máquina de estados
  mínima, code-split de pantallas (tickets/help/webrtc) por import dinámico, y verificar gzip/brotli en el
  asset servido.
- **PERF-LC-04**: `WidgetSessionService::heartbeat` tiene gate de cooldown Redis y geoip cacheado (bien);
  el default de 15s es razonable. Monitorizar volumen; subir el intervalo si la flota de visitantes crece.

## Eje 3 — UX / UI / Accesibilidad

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| UX-LC-01 | UI de estado de conexión (reconectando/offline) | high | s | muy alto |
| UX-LC-02 | Indicador "agente escribiendo" (faceta UX de PROD-LC-03) | high | s | muy alto |
| UX-LC-03 | A11y: focus-trap en el drawer + Esc/teclado en emoji picker | medium | s | medio |
| UX-LC-04 | Disponibilidad real en el banner offline (faceta de PROD-LC-01) | medium | m | medio |
| UX-LC-05 | Color dinámico vía CSS custom properties (consistencia) | low | s | bajo |

- **UX-LC-03**: ARIA básico presente (roles/aria-label en emoji picker, forms, recomendaciones) pero **sin
  focus-trap** en el drawer/overlays y el cierre del emoji picker es solo con ratón (sin Esc/teclado). Añadir
  focus-trap + Esc + restauración de foco; usar `<dialog>` donde aplique.
- **UX-LC-05**: el theming dinámico usa `style={{ backgroundColor: settings.primary_color }}` disperso
  (p.ej. `ChatPageScreen.tsx:13`). Centralizar en CSS custom properties para consistencia y permitir override.

## Eje 4 — Seguridad / Calidad / Arquitectura

La seguridad REST es buena (token por-conversación, HMAC, origen, HTMLPurifier). El gran hueco es el
**read-path en tiempo real** y la falta de policies/convención.

| ID | Oportunidad | Impacto | Esf. | ROI |
|----|-------------|---------|------|-----|
| SEC-LC-01 | IDOR de escucha en tiempo real (canal público sin token) | high | m | alto |
| SEC-LC-02 | Hijack de conversación + sobrescritura PII (HDLC-001/002) | high | m | alto |
| SEC-LC-03 | Endurecer pre-chat-form (token + middleware origen) (HDLC-003) | medium | s | medio |
| ARCH-LC-01 | Unificar y documentar la topología de canales | medium | s | medio |
| ARCH-LC-02 | Añadir policies + estandarizar permisos (HDLC-005) | medium | m | medio |
| ARCH-LC-03 | Adelgazar el god-model `Web` + poblar `config/config.php` | low | m | bajo |
| QUAL-LC-01 | Sentinela mágico `user_id => 0` en read-receipt | low | s | bajo |
| ARCH-LC-04 | Documentar la decisión React/TSX vs regla jQuery-only | low | s | bajo |

- **SEC-LC-01 (detalle)**: el widget hace `echo.channel('helpdesk-widget-conversation.'+conversationId)`
  (`useConversationMessages.ts:172`) — canal **público** (no `private`/`encrypted`) keyado por un id
  **secuencial**. El core difunde los mensajes del agente (`MessageReceived.php:43`) y el typing
  (`ConversationUserTyping.php`) en ese mismo canal sin token. El canal cripto-guardado
  `helpdesk-widget-conversation.{id}.{pubsubToken}` (autorizado en `channels.php:58`) **solo lo usa
  WebRtcSignal**. Resultado: cualquiera puede suscribirse a IDs secuenciales y leer en tiempo real cuerpos
  de respuesta del agente, nombres, link-previews y el "escribiendo" de chats activos, sin token. La
  auditoría de bugs cerró el leak REST (HDLC-001) pero **este vector websocket sigue abierto**. Fix en
  SEC-LC-01 (iniciativas grandes).
- **SEC-LC-02**: `WidgetConversationService::createConversation` (:66-144) confía en `customer_id`/`email`
  del request no verificado para *reclamar* conversaciones abiertas (y devuelve su `pubsub_token`) y para
  *sobrescribir* PII del customer. Requerir echo del `X-Conversation-Token` para reclamar; ignorar
  `customer_id`/`email` salvo HMAC válido; nunca actualizar PII desde input no autenticado.
- **ARCH-LC-01**: hay **5+ esquemas de canal** para una conversación —
  `helpdesk-widget-conversation.{id}` (público, mensajes/typing-agente), `.{id}.{pubsubToken}` (webrtc),
  `helpdesk.conversation.{id}` (privado, agentes), `helpdesk.conversation.{id}.typing` (privado, typing
  visitante), `conversations.{id}`/`conversation.{id}` (legacy). Consolidar y documentar el mapa; encadena
  con SEC-LC-01.
- **ARCH-LC-02**: **0 policies** en el módulo; los settings usan `can:helpdesk.livechat.settings.*`
  (4 segmentos, se desvía del alias `helpdesklivechat`) y `helpdesk.pre-chat.manage` cuelga de otro
  namespace (`HelpdeskLivechatPermissionsSeeder.php:14-18`). Estandarizar a `helpdesklivechat.*` (o
  documentar la excepción de la familia helpdesk) y registrar policies en el ServiceProvider.
- **ARCH-LC-03**: `Web` es un god-config de 100+ campos y `getWidgetConfig()` (:251-363) duplica claves
  deprecadas; `config/config.php` está **vacío** (solo `name`). Mover defaults/feature-flags a config; podar
  camelCase tras migrar el widget (ya lee snake_case).
- **ARCH-LC-04**: el widget es React/TSX, lo que contradice la regla `jQuery-only` del proyecto (FE-09 del
  roadmap). Es defendible como "isla React" embebible de terceros — **documentar la decisión** y acotar el
  patrón para que no se filtre al panel de agente.

---

## Orden recomendado (olas)

### Ola 1 — Quick wins (esfuerzo s, ROI muy alto/alto) — días
Aprovechar infra ya construida y endurecer en una pasada:
1. **PROD-LC-03 / UX-LC-02** — "agente escribiendo" en el widget (el core ya lo emite).
2. **UX-LC-01** — UI de estado de conexión (reconectando/offline).
3. **QW-BUNDLE** — JSON HEX (HDLC-004) + host Reverb conectable (HDLC-006) + reuse `widget_web` (HDLC-007) +
   trim camelCase dupes (PERF-LC-02).
4. **Limpieza de muertos** — eliminar `ChatPageScreen` mock (PROD-LC-08) y `livechat.secret_key`; fix de
   escapado de transcripción (HDLC-008).

### Ola 2 — Seguridad (esfuerzo s/m, ROI alto) — 1-2 semanas
5. **SEC-LC-01** — unificar el realtime bajo el canal cripto-guardado (cierra el IDOR de escucha).
6. **SEC-LC-02** — hardening de identidad/reclaim + no sobrescribir PII (HDLC-001/002).
7. **SEC-LC-03** — endurecer pre-chat-form (token + middleware origen) (HDLC-003).
8. **ARCH-LC-01 / ARCH-LC-02** — documentar topología de canales + policies y nombres de permiso.

### Ola 3 — Producto (esfuerzo m/l) — multi-semana
9. **PROD-LC-01** — presencia/disponibilidad real de agentes.
10. **PROD-LC-04** — posición real en cola + experiencia de espera.
11. **PROD-LC-05 / PROD-LC-06** — canned/quick replies + handoff visitante↔humano.
12. **PROD-LC-02** — IA conversacional (Agents/ChatFlow) + deflexión semántica (tras ARCH-01/RW-10/SEC-02).
13. **Pulido**: PERF-LC-03 (N+1 auto-actions), PERF-LC-05 (bundle), UX-LC-03 (a11y), PROD-LC-07 (retry),
    PROD-LC-09 (analytics), ARCH-LC-03 (god-model/config).

**Lógica**: la Ola 1 entrega features percibidas casi gratis sobre infra existente; la Ola 2 cierra el
riesgo de confidencialidad vivo (canal abierto) antes de añadir más superficie; la Ola 3 desbloquea el valor
de producto grande (presencia, cola, IA), que depende de plumbing transversal (IA del subsistema) y de la
decisión de negocio sobre handoff.
