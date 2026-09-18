# HelpdeskChatFlow

Constructor visual de chatbots/flujos conversacionales para el helpdesk multicanal (WhatsApp, Instagram, Facebook, web). Alias: `chatflow`. Editor React (`@xyflow`) en `resources/js/ChatFlowEditor.tsx`.

## Conceptos

- **ChatFlow**: árbol de nodos con un `trigger_type` (`conversation_start`, `keyword`, `manual`, `no_agent`) y `trigger_conditions` (settings de comportamiento).
- **ChatFlowSession**: ejecución del flow en una conversación (estados: `active`, `transferred`, `completed`, `abandoned`, `failed`).
- **Entrega multicanal**: el bot crea un `ConversationItem`; `ConversationItemObserver` → `DeliverBotMessageJob` → `BotMessageDispatcher` → `OutboundMessageService` (WhatsApp/FB/IG) o broadcast (web). Las opciones se estandarizan como lista numerada "1, 2, 3"; los botones nativos solo se usan si caben en el límite del canal.

## Tipos de nodo

`start`, `message`, `quick_replies`, `collect_input`, `identify_customer`, `request_documents`, `branches`/`branchItem`, `action`, `delay`, `add_tag`, `set_attribute`, `go_to_step`, `ai_response` (RAG), `ai_agent` (tool-calling), `order_lookup` (ERP/PrestaShop), `http_request`, `csat`, `business_hours`, `rich_message` (1 tarjeta o carrusel `cards[]`), `send_file` (adjunto nativo), `transfer`, `close`, `end`.

**Nodos de espera con timeout**: `collect_input`/`quick_replies`/`csat` aceptan `timeout_minutes` + `timeout_action` (`close`/`retry`/`transfer`) + `timeout_message`. Un `HandleNodeTimeoutJob` reacciona si el cliente se queda en silencio.

## Settings del flow (panel de ajustes)

Persisten en `trigger_conditions`: `multilingual`, `sentiment_escalation`, `escape_enabled`, `handoff_summary` (resumen IA al transferir), `ab_variant_id`/`ab_split` (A/B testing), `business_event` (disparador outbound).

## Bot ↔ inbox del agente

Mientras el bot atiende, la conversación se marca `metadata.handled_by_bot=true` y **no aparece en la bandeja del agente** (sí en el historial). En cada handoff (`transfer`, `end→agente`, escalado IA, escape, fallo de identificación) se libera (`releaseFromBot`) y entra al inbox, notificando al agente/grupo en tiempo real (`assignTo`/`assignToGroup`).

- Vista **«En bot»**: chip `?bot=1` en el inbox para supervisar las conversaciones que el bot atiende.
- **Tomar el control**: botón en la cabecera de la conversación → `POST panel/helpdesk/chatflows/takeover/{conversationId}` (`chatflow.takeover`) → para el bot y asigna al supervisor.

## Outbound proactivo

- **Manual**: `php artisan chatflow:outbound {flow} --conversation=ID --context='{...}'`.
- **Por evento de negocio**: configura un flow con `trigger_conditions.business_event` = `abandoned_cart` | `order_status` | `order_ready`. `LaunchOutboundFlowOnBusinessEvent` escucha los eventos PrestaShop/ERP (vía webhook) → `ChatFlowBusinessEventLauncher` resuelve cliente → canal WhatsApp → conversación → lanza el flow.
- **Polling** (fallback sin webhooks): `chatflow:poll-abandoned-carts` (programado cada 15 min) usa la caché de carritos de Remarketing.
- WhatsApp fuera de la ventana de 24h: el nodo `message` con `data.whatsapp_template` envía una plantilla HSM aprobada (`ChatFlowHsmDelivery`).

## Simulador

`/helpdesk/sim` (gated por `config('helpdesk.simulator_public_enabled')`, 404 en producción) reproduce los 4 canales con su formato nativo (carrusel→tarjetas, botones según límite, ✓✓), apariencia por canal, y **horarios simulables** (campo fecha/hora → `Carbon::setTestNow` durante la inyección).

## Comandos

| Comando | Descripción |
|---|---|
| `chatflow:expire-sessions` | Expira sesiones inactivas (programado /5 min). |
| `chatflow:outbound` | Lanza un flow proactivo manualmente. |
| `chatflow:poll-abandoned-carts` | Lanza el flow de carrito abandonado (programado /15 min). |

## Reúso de servicios del Helpdesk

Wrappers opcionales (bind con `class_exists`): voz/Whisper (`AiClient`), multilingüe (`CachedTranslator`), sentiment (`SentimentService`), RAG/tool-calling (`EmbeddingsService`), ERP/PrestaShop (`ChatFlowOrderLookup`), HSM (`WhatsAppHsmService`).

## Tests

PHPUnit. Los tests DB-backed se saltan con `requireHelpdeskDb()` (la DB de test del helpdesk no tiene las tablas). El resto cubre executor, engine, dispatcher, launcher, etc.
