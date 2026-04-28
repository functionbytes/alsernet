# Módulo Campaign

Email marketing self-hosted: campañas, listas, suscriptores, segmentos, plantillas, automatizaciones y tracking. Usa el módulo hermano `CampaignSendingServers` para gestionar los servidores de envío (SES, SendGrid, Mailgun, SparkPost, ElasticEmail, Brevo, SMTP, Sendmail).

NO es un sistema SaaS — los recursos son globales y el acceso se controla por permisos del módulo `Role`.

## Quick start

```bash
# 1. Bootstrap (idempotente)
php artisan campaign:install

# 2. Worker para la cola
php artisan queue:work --queue=default,batch,webhooks

# 3. Cron del sistema (CRÍTICO — sin esto las campañas programadas no arrancan)
* * * * * cd /path/to/system && php artisan schedule:run >> /dev/null 2>&1
```

Tras `campaign:install` tienes:

- Tablas migradas (`campaigns`, `campaign_maillists`, `campaign_subscribers`, ... + `campaign_sending_servers`).
- Permisos `campaigns.*` y `campaign_sending_servers.*` sincronizados (si el comando `permissions:sync` está disponible).
- Un `SendingServerSendmail` "Sendmail local" listo para tests locales.
- Una `MailList` "Default" creada.
- Schedules registrados en el provider:
  - `campaign:execute-scheduled` — every minute
  - `campaign:dispatch-automations` — every 5 minutes
  - `campaign-sending-servers:verify-tracking-domains` — hourly
  - `campaign-sending-servers:verify-senders` — hourly
  - `campaign-sending-servers:verify-dkim` — hourly (si lo registras)
  - `campaign-sending-servers:run-handlers` — every 5 minutes (bounces + feedback)

## Arquitectura

```
modules/
├── CampaignSendingServers/  ← infraestructura de envío
│   ├── Models/
│   │   ├── SendingServer (abstract) + 13 proveedores
│   │   ├── SendingDomain    (DKIM)
│   │   ├── TrackingDomain   (CNAME para tracking pixels/clicks)
│   │   ├── BounceHandler / FeedbackLoopHandler   (IMAP)
│   │   ├── BounceLog / FeedbackLog
│   │   └── Blacklist
│   ├── Library/
│   │   ├── RouletteWheel    (selección ponderada de servidores)
│   │   ├── RateTracker      (cuotas por servidor)
│   │   ├── DnsResolver, DkimGenerator, ImapMailbox
│   │   └── Everification/{NeverBounce,ZeroBounce}
│   └── Console/
│       ├── verify-tracking-domains, verify-senders, verify-dkim
│       └── run-handlers
│
└── Campaign/                ← email marketing core
    ├── Models/
    │   ├── Campaign (slim) + BaseCampaign (state machine)
    │   ├── CampaignMaillist (slim) / CampaignSubscriber
    │   ├── CampaignSegment / CampaignSegmentCondition
    │   ├── CampaignField / CampaignFieldOption
    │   ├── CampaignLink / CampaignWebhook
    │   ├── CampaignTrackingLog + Open/Click/Unsubscribe/Feedback Log
    │   ├── Automation/{Automation,AutomationElement,AutoTrigger,TriggerSession}
    │   ├── Layout / Template / TemplateCategory
    │   └── JobMonitor       (track de jobs/batches por campaña)
    ├── Library/
    │   ├── BaseCampaign     (máquina de estados)
    │   ├── HtmlHandler/     (pipeline procesamiento HTML)
    │   ├── Automation/{Action,Trigger,Wait,Evaluate,Operate,Send}
    │   ├── Tool             (executeWithLimits)
    │   └── WebhookDispatcher
    ├── Jobs/
    │   ├── RunCampaign → LoadCampaign → SendMessage   (pipeline)
    │   ├── ImportSubscribersJob, ExportSubscribersJob
    │   ├── DispatchCampaignWebhook (retry exponencial)
    │   └── VerifySubscriberEmail
    └── Http/Controllers/
        ├── Managers/Campaigns/    (panel admin)
        ├── Public/                (tracking + subscribe pages)
        └── Api/                   (Sanctum)
```

## Pipeline de envío

```
Cron campaign:execute-scheduled
  └─→ Campaign::checkAndExecuteScheduledCampaigns()
        └─→ Campaign::execute()
              └─→ dispatch RunCampaign (con JobMonitor)

RunCampaign::handle()
  └─→ Campaign::run()
        └─→ Bus::batch([LoadCampaign])
              └─→ then: si quedan suscriptores → run() recursivo
                  catch: setError()
                  finally: updateCache

LoadCampaign::handle()
  └─→ Campaign::loadDeliveryJobs(callback, limit=100)
        ├─→ getServersPool() → RouletteWheel
        ├─→ subscribersToSend() (excluye blacklist + ya enviados)
        └─→ por cada subscriber: dispatch SendMessage

SendMessage::handle()
  └─→ Campaign::prepareEmail(subscriber, server) → Symfony\Mime\Email
        ├─→ getCustomHeaders (X-Campaign-*, List-Unsubscribe, List-Unsubscribe-Post)
        ├─→ HtmlHandler pipeline (TransformTag, InjectTrackingPixel, TransformUrl, Spintax, ...)
        └─→ DKIM signing si sign_dkim + dominio verificado
  └─→ Tool::executeWithLimits([rateTrackers], null, [], fn => server->send($email))
        └─→ rate limit por servidor → RateLimitExceeded → release(60s)
  └─→ Campaign::trackMessage() → CampaignTrackingLog + WebhookDispatcher::emit('sent')
```

## Variables .env

```ini
# Email verification opcional
CAMPAIGN_VERIFIER_DRIVER=neverbounce       # neverbounce|zerobounce|null
CAMPAIGN_VERIFIER_API_KEY=xxxxxxxxxxxxxxxxx
```

## Tracking público

Los emails enviados incluyen estos endpoints (sin auth):

- `GET /campaign/track/open/{messageId}.png` — pixel 1x1
- `GET /campaign/track/click/{messageId}/{linkHash}` — redirect 302
- `GET|POST /campaign/track/unsubscribe/{subscriberUid}/{messageId}` — RFC 8058 one-click

Para que las URL apunten a tu propio dominio (en vez del de la app), crea un `TrackingDomain` con CNAME a tu host y configúralo en cada campaign.

## Páginas públicas de suscripción

Para captar leads desde una landing:

- `GET /campaign/subscribe/{listUid}` — formulario standalone
- `POST /campaign/subscribe/{listUid}` — alta + email confirm si la lista lo exige
- `GET /campaign/confirm/{token}` — doble opt-in
- `GET|POST /campaign/manage/{subscriberUid}` — centro de preferencias

Para embeber el formulario en otra web, usa el campo `subscribe_form_embed_code` de la lista.

## Webhooks de campaña

Configura webhooks por campaña (event = `sent | opened | clicked | bounced | feedback | unsubscribed | *`). El payload es JSON:

```json
{
  "event": "opened",
  "campaign_uid": "...",
  "campaign_id": 1,
  "timestamp": "2026-04-28T10:30:00+00:00",
  "subscriber_uid": "...",
  "email": "user@example.com",
  "message_id": "..."
}
```

El job `DispatchCampaignWebhook` reintenta con backoff `[60, 300, 900, 3600]` segundos (4 intentos máx).

## Bulk sender requirements (Gmail/Yahoo Feb 2024)

El módulo cumple los 3 requisitos clave:

| Requisito | Implementación |
|---|---|
| **DKIM signing** | `DkimGenerator` autogenera par RSA al crear `SendingDomain`. `prepareEmail()` firma con `Symfony\Component\Mime\Crypto\DkimSigner`. |
| **List-Unsubscribe-Post** | Header añadido en `getCustomHeaders()`. Route POST en `/campaign/track/unsubscribe/...` sin CSRF. |
| **Spam complaint rate < 0.3 %** | `FeedbackLoopHandler` IMAP procesa ARF y añade emails al blacklist global automáticamente. |

## Permisos

Definidos en `modules/Role/config/permissions.php`:

- `campaigns.view.all`, `campaigns.manage.all`, `campaigns.send.all`
- `campaigns.maillists.{view,manage,import,export}`
- `campaigns.templates.{view,manage}`
- `campaigns.automations.{view,manage,execute}`
- `campaigns.webhooks.{view,manage}`
- `campaign_sending_servers.{view,manage,verify}.all`
- `campaign_sending_servers.{domains,tracking_domains,blacklist,handlers}.{view,manage}`

## Comandos artisan

```bash
# Bootstrap
php artisan campaign:install [--fresh] [--dump]

# Cron
php artisan campaign:execute-scheduled
php artisan campaign:dispatch-automations
php artisan campaign-sending-servers:verify-tracking-domains [--uid=...] [--all]
php artisan campaign-sending-servers:verify-senders [--uid=...] [--all]
php artisan campaign-sending-servers:verify-dkim [--uid=...] [--all]
php artisan campaign-sending-servers:run-handlers [--type=bounce|feedback|all] [--limit=100]
```

## Tests

```bash
php artisan test --filter=Smoke
```

Cubre: migrate fresh, persistencia con encriptación de credenciales, máquina de estados, trackMessage, exclusión de duplicados, JobMonitor, RouletteWheel, blacklist, RateLimitExceeded.

## Troubleshooting

| Síntoma | Causa probable |
|---|---|
| Campañas se quedan en `queuing` | No hay worker corriendo (`queue:work`) |
| Campañas programadas no arrancan | Falta `schedule:run` en cron del sistema |
| Email no entrega | SendingServer mal configurado: `php artisan tinker` → `SendingServer::find(1)->mapType()->test()` |
| DKIM no firma | El SendingDomain no está `verified` o `signing_enabled=false` |
| `Unknown column` en runtime | Falta una columna del legacy: añadir migración suplementaria con ALTER TABLE |
| `RateLimitExceeded` constante | Cuota del servidor demasiado baja: revisar `quota_value/base/unit` |

## Arquitectura no-SaaS

Este módulo NO incluye:
- Plan / Subscription / Invoice / Customer billing
- Multi-tenant / sharding
- Sistema de créditos / quota por usuario

El control de acceso es 100% por permisos del módulo `Role`. Todos los recursos son globales.

Si necesitas multi-tenancy, conviene añadir un `workspace_id` a las tablas principales (campaigns, campaign_maillists, campaign_sending_servers) y un middleware que lo aplique como scope global.
