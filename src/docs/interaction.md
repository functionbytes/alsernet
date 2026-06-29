# Documento de Arquitectura de Interacción: Módulos Helpdesk

## Resumen Ejecutivo

Este documento analiza la arquitectura actual de los módulos relacionados con atención al cliente en el proyecto Alsernet (Inoqualab), identifica problemas críticos de interoperabilidad y propone un plan de integración pragmático.

**Módulos analizados:**
1. `Helpdesk` — Conversaciones multicanal (core)
2. `HelpdeskAgents` — Agentes de IA
3. `HelpdeskTickets` — Tickets de soporte
4. `HelpdeskCampaigns` — Campañas proactivas
5. `Attention` — Sistema PQRSF

> **Nota**: El módulo `Chat` no se incluye en este análisis porque opera como sistema independiente fuera del ecosistema Helpdesk.

---

## 1. Arquitectura Actual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ECOSISTEMA HELPDESK                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐                 │
│   │  Helpdesk    │◄───│HelpdeskAgents│    │HelpdeskTickets│                │
│   │  (Core)      │    │   (IA)       │    │  (Tickets)   │                │
│   │              │    │  Requiere HD │    │  Requiere HD │                │
│   │ • Conversaciones│  │              │    │              │                │
│   │ • Clientes     │   └──────────────┘    └──────────────┘                │
│   │ • Canales      │                                                        │
│   │ • Webhooks     │    ┌──────────────┐                                    │
│   │ • Automatizaciones│  │HelpdeskCampaigns│                                │
│   │ • SLA           │   │  (Campañas)  │                                    │
│   │                 │   │  Requiere HD │                                    │
│   └──────────────┘    └──────────────┘                                    │
│                                                                             │
│   ┌──────────────┐                                                         │
│   │   Attention  │                                                         │
│   │   (PQRSF)    │                                                         │
│   │              │                                                         │
│   │ • Peticiones │                                                         │
│   │ • Quejas       │                                                         │
│   │ • Reclamos     │                                                         │
│   │ • Sugerencias  │                                                         │
│   │ • Felicitaciones│                                      │
│   └──────────────┘                                                         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Análisis por Módulo

### 2.1 Helpdesk (Módulo Core)

**Propósito:** Sistema central de conversaciones multicanal.

**Modelos principales:**
| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `Conversation` | `helpdesk_conversations` | Conversación con cliente |
| `ConversationItem` | `helpdesk_conversation_items` | Mensajes dentro de conversación |
| `Customer` | `helpdesk_customers` | Cliente unificado |
| `Inbox` | `helpdesk_inboxes` | Bandeja de entrada por canal |
| `Channel\Facebook` | `helpdesk_channel_facebooks` | Página de Facebook conectada |
| `Channel\Instagram` | `helpdesk_channel_instagrams` | Cuenta de Instagram conectada |
| `Channel\Whatsapp` | `helpdesk_channel_whatsapps` | Número WhatsApp Business |
| `AutomationRule` | `helpdesk_automations` | Reglas de automatización |
| `RoutingRule` | `helpdesk_routing_rules` | Reglas de enrutamiento |

**Canales soportados:** Facebook Messenger, Instagram DM, WhatsApp Business, Email, Web, API.

**Webhooks:** Controladores dedicados que validan firmas HMAC-SHA256 y despachan `ProcessSocialWebhookJob`.

**Eventos clave:**
- `ConversationCreated`
- `ConversationMessageCreated`
- `ConversationAssigned`
- `ConversationStatusChanged`
- `ConversationEscalated`
- `MessageReceived`

**Automatizaciones existentes:**
- Respuestas fuera de horario (`OffHoursResponse`)
- Reglas de enrutamiento por keywords (`RoutingRuleService`)
- Reglas de automatización genéricas (`AutomationRule`)

**Punto de integración con IA:** `HelpdeskAgents` a través de `AiAgentFlowEngine`.

---

### 2.2 HelpdeskAgents (Extensión de IA)

**Propósito:** Agentes de inteligencia artificial para automatización de respuestas.

**Dependencia:** Requiere `Helpdesk` (declarado en `module.json`).

**Modelos principales:**
| Modelo | Descripción |
|--------|-------------|
| `AiAgent` | Configuración de agente IA |
| `AiAgentFlow` | Flujo conversacional publicable |
| `AiAgentFlowNode` | Nodo individual dentro de un flujo |
| `AiAgentSession` | Sesión activa vinculada a una `Conversation` |
| `AiAgentKnowledgeBase` | Documentos de conocimiento |
| `AiAgentTool` | Herramientas ejecutables por el agente |
| `AgentShift` | Turnos de agentes humanos |
| `OncallRotation` | Rotación de guardia |

**Servicios clave:**
- `AiAgentFlowEngine` — Ejecuta flujos conversacionales nodo a nodo
- `PromptSanitizer` — Sanitiza input del usuario
- `AgentAvailabilityService` — Determina disponibilidad de agentes humanos

**Integración actual:** Se integra con `Helpdesk` vía modelos `Conversation` y `Customer`. No se integra con `Attention`, ni `HelpdeskTickets`.

---

### 2.3 HelpdeskTickets (Gestión de Tickets)

**Propósito:** Tickets tradicionales de soporte técnico con SLA.

**Dependencia:** Requiere `Helpdesk`.

**Modelos principales:**
| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `Ticket` | `helpdesk_tickets` | Ticket principal |
| `TicketItem` | `helpdesk_ticket_items` | Timeline del ticket |
| `TicketStatus` | `helpdesk_ticket_statuses` | Estados configurables |
| `TicketCategory` | `helpdesk_ticket_categories` | Categorías con formularios |
| `TicketSlaPolicy` | `helpdesk_ticket_sla_policies` | Políticas SLA |
| `Automation` | `helpdesk_automations` | Automatizaciones de tickets |
| `Macro` | `helpdesk_macros` | Macros de acciones |

**Eventos clave:** `TicketCreated`, `TicketAssigned`, `TicketStatusChanged`, `TicketClosed`, `SlaBreached`, `SlaWarning`.

**Jobs programados:**
- `CheckSlaBreaches` (cada 15 min)
- `SendSlaWarnings` (cada 30 min)
- `EscalateTicketsJob` (cada hora)

**Integración actual:** Depende de `Helpdesk` para `Customer` y `Group`. No hay integración automática conversación→ticket.

---

### 2.4 HelpdeskCampaigns (Campañas Proactivas)

**Propósito:** Campañas de mensajería proactiva (popups, banners, slide-ins).

**Dependencia:** Requiere `Helpdesk`.

**Modelos:** `Campaign`, `CampaignTemplate`, `CampaignImpression`.

**Integración actual:** Usa `Customer` y `CustomerSession` de `Helpdesk`. No tiene jobs, eventos, ni servicios propios.

---

### 2.5 Attention (Sistema PQRSF)

**Propósito:** Gestión de Peticiones, Quejas, Reclamos, Sugerencias, Felicitaciones.

**Dependencia:** NINGUNA. Independiente del ecosistema Helpdesk.

**Modelos principales:**
| Modelo | Tabla | Descripción |
|--------|-------|-------------|
| `Attention` | `attentions` | PQRSF principal |
| `AttentionType` | `attention_types` | Tipos (P/Q/R/S/F) |
| `AttentionCategory` | `attention_categories` | Categorías temáticas |
| `AttentionDepartment` | `attention_departments` | Departamentos |
| `AttentionRoutingRule` | `attention_routing_rules` | Reglas de enrutamiento |
| `AttentionSlaPolicy` | `attention_sla_policies` | Políticas SLA |

**Flujo:** Formulario público → Radicado → Enrutamiento → Gestión → Resolución → Encuesta → Cierre.

**Dependencias externas:** `Mailer` (plantillas de email), `Core` (settings), `Theme` (menús).

**Integración actual:** Ninguna con Helpdesk. Las PQRSF no generan conversaciones ni tickets automáticamente.

---

## 3. Matriz de Interoperabilidad

| | Helpdesk | HelpdeskAgents | HelpdeskTickets | HelpdeskCampaigns | Attention |
|---|---|---|---|---|---|
| **Helpdesk** | — | ◄ Requerido | ◄ Requerido | ◄ Requerido | ✕ Ninguna |
| **HelpdeskAgents** | ► Usa | — | ✕ Ninguna | ✕ Ninguna | ✕ Ninguna |
| **HelpdeskTickets** | ► Usa Customer | ✕ Ninguna | — | ✕ Ninguna | ✕ Ninguna |
| **HelpdeskCampaigns** | ► Usa Customer | ✕ Ninguna | ✕ Ninguna | — | ✕ Ninguna |
| **Attention** | ✕ Ninguna | ✕ Ninguna | ✕ Ninguna | ✕ Ninguna | — |

**Leyenda:**
- `◄ Requerido` — Declara dependencia en `module.json`
- `► Usa` — Importa clases del otro módulo
- `✕ Ninguna` — Sin integración

---

## 4. Problemas Críticos Identificados

### Problema 1: Sin Integración Attention ↔ Helpdesk 🔴 CRÍTICO

**Severidad:** Alta | **Impacto:** Experiencia del agente, trazabilidad

**Descripción:** Una PQRSF creada en `Attention` no genera automáticamente una conversación en `Helpdesk` ni un ticket en `HelpdeskTickets`.

**Consecuencias:**
- El agente debe gestionar la PQRSF en un panel y la comunicación con el cliente en otro
- No hay historial unificado de interacciones con el ciudadano
- Las respuestas por email de `Attention` no aparecen en `Helpdesk`

---

### Problema 2: HelpdeskTickets Aislado de Conversaciones 🟡 MEDIO

**Severidad:** Media | **Impacto:** Productividad del agente

**Descripción:** No existe flujo automático para convertir una conversación de `Helpdesk` en un ticket de `HelpdeskTickets`.

**Consecuencias:**
- Un cliente puede estar chateando en `Helpdesk` mientras su ticket se gestiona en `HelpdeskTickets` sin vinculación
- El agente no ve el contexto completo

---

### Problema 3: Sin Gestión de Comentarios en Publicaciones 🟡 MEDIO

**Severidad:** Media | **Impacto:** Cobertura de canales sociales

**Descripción:** `Helpdesk` solo gestiona mensajes directos (DM/Messenger). **No gestiona comentarios en publicaciones** de Facebook ni Instagram.

**Consecuencias:**
- Los comentarios de clientes en publicaciones quedan sin respuesta
- No hay forma de clasificar intenciones de comentarios
- No hay respuestas automáticas a comentarios

---

### Problema 4: Tokens OAuth sin Refresh Automático 🟡 MEDIO

**Severidad:** Media | **Impacto:** Disponibilidad del canal

**Descripción:** Los tokens de Meta se almacenan encriptados pero no hay job programado para refrescar tokens de larga duración antes de su expiración.

---

## 5. Recomendaciones de Integración

### Recomendación 1: Bridge Attention ↔ Helpdesk

**Acción:** Crear un servicio de bridge que sincronice PQRSF con conversaciones.

**Implementación:**
```php
class CreateConversationFromAttention
{
    public function handle(AttentionCreated $event): void
    {
        $attention = $event->attention;
        
        $customer = Customer::firstOrCreate(
            ['email' => $attention->email],
            ['name' => $attention->full_name]
        );
        
        $conversation = Conversation::create([
            'customer_id' => $customer->id,
            'channel' => 'attention',
            'subject' => "[{$attention->radicado}] {$attention->subject}",
            'external_id' => $attention->radicado,
        ]);
        
        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => $attention->description,
        ]);
    }
}
```

**Beneficios:**
- Agentes gestionan todo desde Helpdesk
- Historial unificado
- Se aprovechan automatizaciones y SLA de Helpdesk

---

### Recomendación 2: Conversación ↔ Ticket Link

**Acción:** Permitir convertir una conversación de `Helpdesk` en un ticket de `HelpdeskTickets` y mantener vinculación bidireccional.

**Implementación:**
```php
// En Conversation model
public function ticket(): ?BelongsTo
{
    return $this->belongsTo(Ticket::class, 'linked_ticket_id');
}

// En Ticket model
public function conversation(): ?BelongsTo
{
    return $this->belongsTo(Conversation::class, 'linked_conversation_id');
}
```

---

### Recomendación 3: Refresh Automático de Tokens Meta

**Acción:** Crear un job programado que refresque tokens de Meta antes de su expiración.

**Implementación:**
```php
class RefreshMetaTokensJob implements ShouldQueue
{
    public function handle(): void
    {
        Instagram::where('token_expires_at', '<', now()->addDays(7))
            ->each(function ($account) {
                // Refresh long-lived token via Graph API
            });
    }
}
```

---

## 6. Arquitectura Propuesta con HelpdeskSocial

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    ARQUITECTURA CON HELPDESCSOCIAL                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────┐           │
│   │                      Helpdesk (Core)                         │           │
│   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │           │
│   │  │Conversations│  │  Customers  │  │   Social Channels   │  │           │
│   │  │  (Unified)  │  │  (Unified)  │  │ FB/IG/WA/Email/Web  │  │           │
│   │  └─────────────┘  └─────────────┘  └─────────────────────┘  │           │
│   └──────────┬──────────────────────────────────────────────────┘           │
│              │                                                              │
│   ┌──────────┼──────────┬──────────────┬──────────────┐                    │
│   │          │          │              │              │                    │
│   ▼          ▼          ▼              ▼              ▼                    │
│ ┌──────┐ ┌──────┐ ┌──────────┐  ┌──────────┐  ┌──────────┐               │
│ │Agents│ │Tickets│ │ Campaigns│  │ Attention│  │  Social  │               │
│ │ (IA) │ │       │ │          │  │ (Bridge) │  │  (Nuevo) │               │
│ └──────┘ └──────┘ └──────────┘  └──────────┘  └──────────┘               │
│                                                                             │
│   ┌─────────────────────────────────────────────────────────────┐           │
│   │                    HelpdeskSocial (Nuevo)                    │           │
│   │  • Comentarios FB/IG      • Clasificación de intenciones     │           │
│   │  • Respuestas automáticas • Analítica social                 │           │
│   └─────────────────────────────────────────────────────────────┘           │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Diagrama de Flujo de Datos: HelpdeskSocial

```
Meta Graph API/Webhooks
        │
        ▼
┌───────────────┐
│ Webhook Handler│── Verifica HMAC-SHA256
└───────┬───────┘
        │
        ▼
┌───────────────┐
│  Parser/Filter │── Detecta: comment / mention / DM
└───────┬───────┘
        │
   ┌────┴────┬────────────┐
   │         │            │
   ▼         ▼            ▼
Comentario Mención     DM/Messenger
   │         │            │
   ▼         ▼            ▼
Classify   Classify    ProcessSocial
Intent     Intent      WebhookJob
   │         │         (Helpdesk core)
   ▼         ▼            │
SocialComment            Conversation
   │                        (Helpdesk)
   ▼
AutoResponder
   │
   ├─► Reply via Graph API
   ├─► Hide comment
   ├─► Mark spam
   └─► Escalate to human
```

---

## 8. Conclusiones

1. **Helpdesk tiene la arquitectura más madura** para ser el core unificado: eventos, jobs, AI integration, automatizaciones, SLA.

2. **Attention necesita un bridge** para integrarse con el ecosistema de atención. Su flujo de PQRSF es único y valioso, pero debe generar conversaciones en Helpdesk.

3. **HelpdeskSocial debe construirse sobre Helpdesk**, reutilizando `Conversation`, `Customer`, `Channel`, y los eventos existentes. Su diferenciador es la gestión de **comentarios públicos** (no solo DMs).

4. **HelpdeskTickets y HelpdeskAgents** ya están correctamente acoplados a Helpdesk y solo necesitan mejoras puntuales de integración (links conversación-ticket, triggers de agente IA).

---

## Apéndice A: Tablas de Base de Datos por Módulo

### Helpdesk
- `helpdesk_conversations`, `helpdesk_conversation_items`, `helpdesk_conversation_reads`
- `helpdesk_conversation_statuses`, `helpdesk_conversation_tags`
- `helpdesk_customers`, `helpdesk_customer_inboxes`
- `helpdesk_inboxes`
- `helpdesk_channel_facebooks`, `helpdesk_channel_instagrams`, `helpdesk_channel_whatsapps`
- `helpdesk_channel_webs`, `helpdesk_channel_emails`, `helpdesk_channel_apis`
- `helpdesk_automations`, `helpdesk_routing_rules`, `helpdesk_off_hours_responses`
- `helpdesk_groups`, `helpdesk_group_user`
- `helpdesk_canned_replies`, `helpdesk_macros`
- `helpdesk_sla_policies`, `helpdesk_ticket_sla_breaches`
- `helpdesk_webhooks`, `helpdesk_webhook_deliveries`
- `helpdesk_helpcenter_articles`, `helpdesk_helpcenter_categories`
- `helpdesk_ai_agents`, `helpdesk_ai_agent_flows`, `helpdesk_ai_agent_sessions`
- `helpdesk_settings`

### Attention
- `attentions`, `attention_types`, `attention_categories`
- `attention_departments`, `attention_department_user`
- `attention_sedes`, `attention_routing_rules`
- `attention_sla_policies`, `attention_sla_breaches`
- `attention_notes`, `attention_actions`, `attention_mails`
- `attention_satisfaction`, `colombian_holidays`

### HelpdeskTickets
- `helpdesk_tickets`, `helpdesk_ticket_items`, `helpdesk_ticket_messages`
- `helpdesk_ticket_statuses`, `helpdesk_ticket_categories`
- `helpdesk_ticket_groups`, `helpdesk_ticket_group_user`
- `helpdesk_ticket_canned_replies`, `helpdesk_macros`
- `helpdesk_ticket_sla_policies`, `helpdesk_ticket_sla_breaches`
- `helpdesk_ticket_histories`, `helpdesk_ticket_views`
- `helpdesk_ticket_notes`, `helpdesk_ticket_comments`
- `helpdesk_ticket_time_entries`, `helpdesk_ticket_attachments`
- `helpdesk_ticket_mails`, `helpdesk_ticket_reads`
- `helpdesk_automations`, `helpdesk_recurring_tickets`
- `helpdesk_ticket_templates`, `helpdesk_ticket_followups`
- `helpdesk_ticket_links`, `helpdesk_ticket_watchers`

### HelpdeskCampaigns
- `helpdesk_campaigns`, `helpdesk_campaign_templates`, `helpdesk_campaign_impressions`

### HelpdeskSocial (Nuevo)
- `helpdesk_social_accounts`, `helpdesk_social_comments`
- `helpdesk_social_rules`, `helpdesk_social_templates`
- `helpdesk_social_intents`, `helpdesk_social_metrics`

---

*Documento generado el 2026-05-01. Arquitecto: Asistente de Software Senior.*
