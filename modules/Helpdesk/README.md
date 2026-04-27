# Helpdesk

> Sistema completo de tickets de soporte con SLA, colas y auditoria

## Proposito

Modulo de helpdesk empresarial que cubre dos flujos principales: **tickets** (soporte estructurado con SLA, prioridades, grupos y auditoria) y **conversaciones** (mensajeria en tiempo real multicanal). Incluye portal de cliente, AI agents con flujos visuales, centro de ayuda, campanas, integraciones con WhatsApp/Facebook/Instagram y estadisticas de rendimiento.

## Arquitectura

El modulo usa tres providers: `HelpdeskServiceProvider`, `RouteServiceProvider` y `EventServiceProvider`. Las rutas estan separadas en archivos por rol de usuario y tipo de acceso.

### Policies

13 policies registradas en `HelpdeskServiceProvider`, siguiendo la convencion `{Entity}Policy`:

`ConversationPolicy`, `TicketPolicy`, `CustomerPolicy`, `GroupPolicy`, `PriorityPolicy`, `CannedReplyPolicy`, `AiAgentPolicy`, `CampaignPolicy`, `HelpCenterArticlePolicy`, `HelpCenterCategoryPolicy`, `TicketCannedReplyPolicy`, `TicketTemplatePolicy`, `CustomAttributePolicy`

Todas usan permisos Spatie con convencion `helpdesk.{entity}.{action}` (ver tabla de permisos).

### Notifications y Listeners

6 notifications con channels `database` + `broadcast` (la de StatusChanged envia `mail` al cliente):

| Notification | Canal(es) | Disparada por |
|---|---|---|
| `NewConversationNotification` | database, broadcast | `ConversationCreated` |
| `ConversationAssignedNotification` | database, broadcast | `ConversationAssigned` |
| `MessageReceivedNotification` | database, broadcast | `MessageReceived` |
| `MentionNotification` | database, broadcast | `MentionDetected` |
| `EscalationNotification` | database, broadcast | `ConversationEscalated` |
| `StatusChangedNotification` | database, broadcast (agents) / mail (customer) | `ConversationStatusChanged` |

6 listeners en `app/Listeners/Send*Notification.php`, todos con `ShouldQueue`, registrados en `EventServiceProvider`.

### Trait HasMessageThread

El trait `Concerns\HasMessageThread` es compartido entre el modelo `Conversation` (Helpdesk) y los modelos del modulo `HelpdeskTickets`. Provee la logica de hilo de mensajes reutilizable.

### Convencion de permisos

**Formato correcto**: `helpdesk.{entity}.{action}` — por ejemplo: `helpdesk.conversations.view`, `helpdesk.tickets.create`.

**Incorrecto**: `manager.helpdesk.X` (no usar este prefijo).

## Modelos principales

**Tickets**: `Ticket`, `TicketMessage`, `TicketStatus`, `TicketCategory`, `TicketGroup`, `SlaPolicy`, `TicketSlaBreach`, `TicketCannedReply`, `TicketTemplate`, `RecurringTicket`, `TicketTimeEntry`, `TicketAssignment`, `TicketWatcher`, `TicketNote`, `TicketComment`

**Conversaciones**: `Conversation`, `ConversationItem`, `ConversationStatus`, `ConversationTag`, `CannedReply`, `Customer`, `CustomerSession`, `PageVisit`

**AI**: `AiAgent`, `AiAgentFlow`, `AiAgentFlowNode`, `AiAgentKnowledgeBase`, `AiAgentTool`, `AiAgentSession`, `AiAgentSessionMessage`, `AiAgentTag`

**Otros**: `Campaign`, `CampaignTemplate`, `HelpCenterArticle`, `HelpCenterCategory`, `Priority`, `Group`, `CustomAttribute`

## Servicios

| Servicio | Responsabilidad |
|----------|----------------|
| `TicketService` | CRUD y ciclo de vida de tickets |
| `SlaService` | Calculo de brechas SLA con horario laboral |
| `AssignmentService` | Asignacion round-robin y manual |
| `EscalationService` | Escalado automatico por tiempo |
| `NotificationService` | Notificaciones a agentes y clientes |
| `AiAgentFlowEngine` | Ejecucion de flujos de AI agents |
| `WhatsAppBusinessService` | Integracion WhatsApp Business API |
| `FacebookMessengerService` | Integracion Facebook Messenger |
| `InstagramService` | Integracion Instagram DM |
| `OutboundMessageService` | Envio de mensajes por canal |
| `CannedReplyService` | Gestion de respuestas predefinidas |
| `PromptSanitizer` | Proteccion contra prompt injection |

## Rutas

| Archivo | Prefijo / Audiencia |
|---------|---------------------|
| `routes/managers.php` | `manager.helpdesk.*` — panel de administradores |
| `routes/agents.php` | `agent.helpdesk.*` — panel de agentes |
| `routes/portal.php` | `/helpdesk/portal/*` — portal publico de clientes |
| `routes/widget.php` | `/helpdesk/widget/*` — widget embebible |
| `routes/api.php` | `api/helpdesk/*` con Sanctum |
| `routes/webhooks.php` | `/helpdesk/webhooks/*` — WhatsApp, Facebook, Instagram |
| `routes/web.php` | Rutas generales y de configuracion |

## Permisos

| Permiso | Descripcion |
|---------|-------------|
| `helpdesk.view` / `helpdesk.manage` | Acceso general al modulo |
| `helpdesk.tickets.*` | CRUD de tickets (view/create/update/delete/manage) |
| `helpdesk.customers.*` | CRUD de clientes |
| `helpdesk.conversations.*` | CRUD de conversaciones |
| `helpdesk.metrics.view` / `.export` | Reportes y metricas |
| `helpdesk.cannedreplies.*` | Respuestas predefinidas |
| `helpdesk.aiagents.*` | Gestion de AI agents |
| `helpdesk.campaigns.*` | Campanas de mensajeria |
| `helpdesk.settings.view` / `.update` | Configuracion del modulo |

## Configuracion

- Archivo: `config/helpdesk.php`
- Variables env relevantes:

| Variable | Descripcion | Default |
|----------|-------------|---------|
| `HELPDESK_SLA_TIMEZONE` | Zona horaria para SLA | `app.timezone` |
| `HELPDESK_QUEUE` | Cola por defecto | `default` |
| `HELPDESK_NOTIFICATIONS_QUEUE` | Cola de notificaciones | `notifications` |
| `HELPDESK_HEAVY_QUEUE` | Cola de tareas pesadas | `helpdesk-heavy` |
| `HELPDESK_WEBHOOKS_QUEUE` | Cola de webhooks | `helpdesk-webhooks` |
| `HELPDESK_FROM_EMAIL` | Email remitente | `MAIL_FROM_ADDRESS` |
| `HELPDESK_PORTAL_ENABLED` | Habilitar portal de cliente | `true` |
| `HELPDESK_IMAP_ENABLED` | Ingesta de tickets por email | `false` |
| `HELPDESK_IMAP_SERVER` | Servidor IMAP | — |
| `HELPDESK_ESCALATION_ENABLED` | Escalado automatico | `true` |
| `WHATSAPP_ENABLED` | Integracion WhatsApp | `false` |
| `FACEBOOK_ENABLED` | Integracion Facebook | `false` |
| `INSTAGRAM_ENABLED` | Integracion Instagram | `false` |

## Dependencias

- **Core**: Si (usa `Setting` model para configuracion persistente)
- Otros: `laravel-imap` (ingesta email), Laravel Reverb/Pusher (tiempo real), servicios externos de mensajeria
