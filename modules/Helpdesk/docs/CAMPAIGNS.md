# Campaigns — Comunicación masiva

Sistema para enviar mensajes a múltiples clientes a la vez (broadcast) o secuencias automáticas (drip).

## Componentes

### Broadcast (envío único masivo)

Tabla: `helpdesk_broadcasts` + `helpdesk_broadcast_recipients`

```
helpdesk_broadcasts
├── name, channel, template_type (text|hsm), body, template_id, template_params
├── filters (json) — ej: {"tag_id": 5, "channel": "whatsapp", "last_contact_after": "2026-04-01"}
├── status (draft|scheduled|sending|sent|failed)
├── scheduled_at, sent_at, recipients_count, delivered_count, failed_count

helpdesk_broadcast_recipients
├── broadcast_id, customer_id, status (pending|sent|delivered|read|failed)
├── external_id (msg id de Meta), error, sent_at
```

**Flujo**:
1. Admin crea broadcast en `/panel/helpdesk/broadcasts/create` (wizard 4 pasos)
2. Step 2: muestra preview count con `BroadcastService::previewSegment(filters)`
3. Step 4: envía ahora o programa
4. `BroadcastService::dispatchBroadcast()`:
   - Crea recipients
   - Dispatch `SendBroadcastMessageJob` por cada uno con throttle 5/seg
5. Cada job llama a `OutboundMessageService::sendReply` o `WhatsAppHsmService::send`
6. Updatea recipient.status; cuando todos procesados, broadcast.status=sent

### Drip campaigns (secuencias automáticas)

Tablas: `helpdesk_drip_campaigns` + `helpdesk_drip_steps` + `helpdesk_drip_executions`

```
helpdesk_drip_campaigns
├── name, description, trigger_type, trigger_value, is_active

helpdesk_drip_steps
├── campaign_id, step_order, delay_minutes, channel (override), template_type, body

helpdesk_drip_executions
├── campaign_id, customer_id, current_step, status (active|paused|completed|cancelled)
├── started_at, completed_at
└── unique(campaign_id, customer_id) — un cliente solo entra una vez por drip
```

**Triggers soportados**:
- `tag_added` — cuando se asigna un tag a una conversación
- `conversation_closed` — al cerrar conversación
- `csat_low` — CSAT con rating <= 2
- `manual` — admin lo dispara desde UI

**Flujo**:
1. Admin define drip en `/panel/helpdesk/settings/drip-campaigns`
2. Listener detecta el trigger → `DripService::start(campaign, customer)`
3. `executeNextStep()` envía el step + agenda el siguiente con `dispatch(...)->delay(now()->addMinutes($nextStep->delay_minutes))`
4. Comando scheduled `helpdesk:process-pending-drips` cada 1 min recoge ejecuciones con next_step ready

## WhatsApp HSM (templates aprobadas)

Tabla: `helpdesk_whatsapp_templates`

```
├── external_id (name de Meta), display_name, language, category
├── status (approved|rejected|pending)
├── body_template (con {{1}} {{2}} placeholders)
├── header_type (text|image|document|video nullable)
```

**Importante**: Meta exige plantillas pre-aprobadas para enviar mensajes a usuarios fuera de la ventana de 24h después del último mensaje del cliente. Las plantillas se crean en Meta Business Manager y se aprueban manualmente (1-3 días).

**Sincronización**:
```bash
php artisan helpdesk:sync-wa-templates
```
Llama a `GET /{whatsapp_business_account_id}/message_templates` y guarda/actualiza locally.

**Servicio**: `Modules\Helpdesk\Services\Campaigns\WhatsAppHsmService::send($phone, $templateId, $lang, $params)`

## Throttling

Meta tiene rate limits estrictos:
- WhatsApp Business: 80 msg/seg por número (tier 1) — 1.000/seg (tier 4)
- Messenger: 250 msg/seg recomendado

Por defecto los jobs van a la cola `helpdesk-webhooks` con 3-8 procesos paralelos. Para broadcasts grandes, considera:

```php
// En BroadcastService::dispatchBroadcast
SendBroadcastMessageJob::dispatch(...)
    ->delay(now()->addSeconds(intdiv($i, 5))); // espacia 5/seg
```

## Permisos Spatie

- `helpdesk.broadcasts.manage` — crear/editar/enviar broadcasts
- `helpdesk.drip-campaigns.manage` — crear/editar drips
- `helpdesk.whatsapp-templates.view` — listar templates HSM

## Endpoints

| Endpoint | Auth | Descripción |
|---|---|---|
| `GET /panel/helpdesk/broadcasts` | web + permission | Lista broadcasts |
| `GET /panel/helpdesk/broadcasts/create` | web + permission | Wizard nuevo broadcast |
| `POST /panel/helpdesk/broadcasts/preview` | web + permission | Recibe filters, retorna count |
| `POST /panel/helpdesk/broadcasts/{id}/send` | web + permission | Lanza el broadcast |
| `GET /panel/helpdesk/settings/drip-campaigns` | web + permission | CRUD drips |
| `POST /panel/helpdesk/settings/drip-campaigns/{id}/start` | web + permission | Trigger manual |
| `GET /panel/helpdesk/settings/whatsapp-templates` | web + permission | Lista templates HSM |
| `POST /panel/helpdesk/settings/whatsapp-templates/sync` | web + permission | Refresh desde Meta |

## Ejemplos de filtros de segmentación

```json
{
  "tag_id": 5,
  "channel": "whatsapp",
  "last_contact_after": "2026-04-01",
  "csat_min": 4,
  "language": "es"
}
```

## Comandos scheduled

| Comando | Frecuencia | Descripción |
|---|---|---|
| `helpdesk:sync-wa-templates` | manual / weekly | Refresh templates desde Meta |
| `helpdesk:process-pending-drips` | cada 1 min | Ejecuta steps pendientes |

## Buenas prácticas

- **Evita spam**: marca `is_active=false` en drip campaigns que estás testeando
- **Test con segmento pequeño primero**: filter con `customer_id IN (...)` con 3-5 IDs antes del broadcast real
- **Usa HSM solo para clientes fuera de ventana 24h**: dentro de la ventana, usa `text` que es gratis
- **WhatsApp HSM marketing tiene costo**: ~$0.01-0.05 por mensaje según país
- **Messenger no permite HSM**: usa Quick Replies o texto + attachment

## Troubleshooting

**`(#100) Phone number is not a registered template recipient`** → cliente nunca te escribió. Solo puedes enviar HSM a números que han iniciado conversación contigo previamente o han opt-in.

**`Template name does not exist`** → corre `helpdesk:sync-wa-templates` y verifica que el template está `status=approved`.

**Drip no arranca** → verifica el listener correspondiente al `trigger_type`. Si es manual, asegúrate de invocar `DripService::start()` explícitamente.
