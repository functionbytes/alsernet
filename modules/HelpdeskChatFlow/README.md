# HelpdeskChatFlow

Constructor visual de chatbots/flujos conversacionales para el helpdesk multicanal (WhatsApp, Instagram, Facebook, web). Alias: `chatflow`. Editor React (`@xyflow`) en `resources/js/ChatFlowEditor.tsx`.

## Conceptos

- **ChatFlow**: árbol de nodos con un `trigger_type` (`conversation_start`, `keyword`, `manual`, `no_agent`, `intent`) y `trigger_conditions` (settings de comportamiento). El motor ejecuta `published_nodes` (la versión publicada); el editor trabaja sobre `nodes` (borrador).
- **ChatFlowSession**: ejecución del flow en una conversación (estados: `active`, `transferred`, `completed`, `abandoned`, `failed`).
- **Entrega multicanal**: el bot crea un `ConversationItem`; `ConversationItemObserver` → `DeliverBotMessageJob` → `BotMessageDispatcher` → `OutboundMessageService` (WhatsApp/FB/IG) o broadcast (web). Las opciones se estandarizan como lista numerada "1, 2, 3"; los botones nativos solo se usan si caben en el límite del canal.

## Tipos de nodo

`start`, `message`, `quick_replies`, `collect_input`, `identify_customer` (con verificación OTP por defecto), `request_documents`, `branches`/`branchItem`, `action`, `delay`, `add_tag`, `set_attribute`, `go_to_step`, `ai_response` (RAG), `ai_agent` (tool-calling), `order_lookup` (ERP/PrestaShop), `http_request`, `csat`, `business_hours`, `rich_message` (1 tarjeta o carrusel `cards[]`), `send_file` (adjunto nativo), `document_link` (enlace al portal de HelpdeskDocument: `{{doc_upload_url}}`, `{{doc_missing}}`), `create_ticket` (abre un ticket vía `TicketServiceContract`; `{{created_ticket_number}}`), `transfer`, `close`, `end`.

## Arquitectura del motor

| Pieza | Responsabilidad |
|---|---|
| `ChatFlowEngine` | Ciclo de vida de la sesión: arranque (lock por conversación), respuesta del cliente (idioma, sentimiento, escape a humano, validación, reintentos), bucle de nodos y cierre. |
| `ChatFlowNodeExecutor` | Ejecuta un nodo: resuelve los de navegación (`start`, `branchItem`, `delay`, `go_to_step`) y delega el resto en su handler; registra `ChatFlowExecution`. |
| `Services/Nodes/*NodeHandler` | Un handler por familia: `Messaging` (mensajes y preguntas), `Ai`, `Integration` (pedidos, HTTP, horario), `RichContent` (tarjetas, ficheros, documentos), `Conversation` (etiquetas, atributos, transferir, cerrar, ticket). |
| `NodeHandlerRegistry` | Mapa tipo → handler a partir de los servicios con la tag `NodeHandlerRegistry::TAG`. Si dos handlers declaran el mismo tipo, falla al construir el registro. |
| `Services/Input/*` | Respuestas del cliente en nodos de espera complejos: identificación + OTP, subida de documentos, CSAT. Devuelven el nodo por el que seguir; el motor continúa. |
| `ChatFlowScheduler` | Timeouts de nodos de espera y reanudación de `delay` (jobs en cola). |

**Añadir un tipo de nodo desde otro módulo**: implementa `Services\Nodes\NodeHandler` y regístralo en el `register()` de tu ServiceProvider con `$this->app->tag([MiHandler::class], NodeHandlerRegistry::TAG);`. `ChatFlow::nodeTypes()` (validación de guardado/importación) lo incluye automáticamente. El simulador lo muestra como «no simulable» salvo que se le añada soporte.

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

**Panel de pruebas del editor** (`ChatFlowTestSimulator`): lo que ve el cliente se genera con los **mismos handlers de producción**, ejecutados sobre una sesión y conversación en memoria (`Services/Simulation/*`), así que los textos coinciden con los reales. Los nodos de IA y `http_request` se ejecutan de verdad (con su coste; las rutas están limitadas por throttle); la consulta de pedidos usa datos de demostración, y tickets, etiquetas, atributos y transferencias se simulan y se anotan en el chat sin ejecutarse.

**Escenarios de prueba**: cada flow puede tener casos de regresión (`test-cases`). Publicar ejecuta todos contra el borrador y **bloquea la publicación si alguno falla** (se puede forzar con `skip_tests=1`, queda en el log). En CI: `php artisan chatflow:test-cases --all`.

**Versiones**: cada publicación guarda un snapshot; `versions/{id}/diff` compara una versión con el borrador actual u otra versión (nodos añadidos, eliminados y campos cambiados).

## Comandos

| Comando | Descripción |
|---|---|
| `chatflow:expire-sessions` | Expira sesiones inactivas (programado /5 min). |
| `chatflow:prune-sessions` | Borra sesiones finalizadas más antiguas que `session_retention_days` (programado diario 03:45). |
| `chatflow:outbound` | Lanza un flow proactivo manualmente. |
| `chatflow:poll-abandoned-carts` | Lanza el flow de carrito abandonado (programado /15 min). |
| `chatflow:test-cases {flow?} {--all}` | Ejecuta los escenarios de prueba; sale con error si alguno falla (para CI). |

## Analítica

Por flow y ventana (7/30/90/365 días o todo): resumen, abandono por nodo, métricas de IA, CSAT (con evolución semanal), comparativa A/B, latencia y fallos por nodo, y alerta de nodos `http_request` con muchos fallos (`analytics.http_failure_alert_rate` / `_min`). Cacheado 15 min e invalidado al completar una sesión. El CSAT usa la columna virtual indexada `csat_score_value`.

## Reúso de servicios del Helpdesk

Wrappers opcionales (bind con `class_exists`): voz/Whisper (`AiClient`), multilingüe (`CachedTranslator`), sentiment (`SentimentService`), RAG/tool-calling (`EmbeddingsService`), ERP/PrestaShop (`ChatFlowOrderLookup`), HSM (`WhatsAppHsmService`).

## Tests

PHPUnit. Los tests extienden `Modules\HelpdeskChatFlow\Tests\TestCase`, que arranca la aplicación con el módulo **activado** aunque esté apagado en `modules_statuses.json` (apunta el activador a una copia temporal; el fichero real no se toca). Los tests con BD se saltan con `requireHelpdeskDb()` si faltan las tablas.

```bash
docker exec webadmin-app sh -c 'cd /var/www && php vendor/bin/phpunit modules/HelpdeskChatFlow/tests'
```
