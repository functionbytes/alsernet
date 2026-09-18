# HelpdeskCampaigns

Módulo de campañas in-app: popups, banners, slide-ins y full-screen overlays.
Tracking en tiempo real de impressions y clicks, A/B testing, frequency
capping, targeting por audiencia y workflow de aprobación.

## Setup rápido

```bash
# Aplicar migrations (se aplican automáticamente al cargar el módulo)
php artisan module:migrate HelpdeskCampaigns

# Sembrar permisos
php artisan module:seed HelpdeskCampaigns --class="Modules\\HelpdeskCampaigns\\Database\\Seeders\\HelpdeskCampaignsPermissionsSeeder"
```

## Embeber el widget en una web externa

```html
<script src="https://app.example.com/modules/helpdeskcampaigns/js/widget.js"
        data-base-url="https://app.example.com"
        data-campaign-id="42"
        data-customer-id="123"
        data-show-after-seconds="3"></script>
```

| data-attr | tipo | descripción |
|-----------|------|-------------|
| `data-base-url` | string | URL base de la app Laravel |
| `data-campaign-id` | int | ID de la campaña a mostrar |
| `data-customer-id` | int? | ID del cliente logged-in (opcional) |
| `data-show-after-seconds` | int | Delay antes de disparar (default 0) |
| `data-auto-start` | bool | `false` para arrancar manualmente con `HelpdeskCampaigns.start()` |

El widget se identifica con cookie `hd_session_id` (creada si no existe).

## Endpoints

### Manager (auth + role:super-admin|super-settings)
- `GET /panel/helpdesk/campaigns` — listado
- `POST /panel/helpdesk/campaigns` — crear
- `POST /panel/helpdesk/campaigns/{id}/{publish,pause,resume,end}` — lifecycle
- `POST /panel/helpdesk/campaigns/bulk-action` — operaciones masivas
- `POST /panel/helpdesk/campaigns/{id}/{submit-for-approval,approve,reject}`
- `GET /panel/helpdesk/campaigns/{id}/statistics` — JSON KPIs
- `GET /panel/helpdesk/campaigns/{id}/statistics/timeline?days=30` — JSON serie
- `GET /panel/helpdesk/campaigns/{id}/statistics/export` — CSV download
- `GET /panel/helpdesk/campaigns/{id}/activity` — activity log

### API REST (Sanctum, throttle 60/min)
Mounted at `/api/v1/helpdesk/campaigns` — full CRUD + lifecycle actions.

### Public (sin auth, throttle 120/min)
- `POST /helpdesk/campaigns/track/view` — registrar impresión
- `POST /helpdesk/campaigns/track/click/{uuid}` — registrar click

## Eventos disparados

```php
CampaignPublished::class    // status: draft|scheduled → active
CampaignPaused::class       // status: active → paused
CampaignResumed::class      // status: paused → active
CampaignEnded::class        // status: * → ended
CampaignImpressionRecorded  // INSERT en helpdesk_campaign_impressions
```

Cada uno tiene listeners para activity log + notifications + webhooks.

## Configuración (`config/helpdeskcampaigns.php`)

```php
return [
    'impressions_retention_days' => 180,

    // Webhook subscribers
    'webhooks' => [
        [
            'url' => 'https://hooks.slack.com/...',
            'secret' => env('SLACK_HOOK_SECRET'), // opcional, firma HMAC-SHA256
            'events' => ['campaign.published', 'campaign.ended'], // null = todos
        ],
    ],

    'default_frequency' => [
        'max_impressions_per_user' => null, // sin límite
        'cooldown_minutes' => null,
    ],
];
```

Variables `.env`:

- `HELPDESK_CAMPAIGNS_IMPRESSIONS_RETENTION_DAYS=180`
- `HELPDESK_CAMPAIGNS_DEFAULT_MAX_IMPRESSIONS=`
- `HELPDESK_CAMPAIGNS_DEFAULT_COOLDOWN_MINUTES=`

## Schedule (corre solo si Laravel scheduler está activo)

| Job | Frecuencia | Qué hace |
|-----|-----------|----------|
| `PublishScheduledCampaignsJob` | cada minuto | Activa campañas cuyo `published_at` ya llegó |
| `EndExpiredCampaignsJob` | cada minuto | Cierra campañas con `ends_at` pasado o goal alcanzado |
| `CleanupOldImpressionsJob` | diario 03:30 | Borra impressions más viejos que `impressions_retention_days` |

## Permisos

- `helpdesk.campaigns.view`
- `helpdesk.campaigns.create`
- `helpdesk.campaigns.update`
- `helpdesk.campaigns.delete`
- `helpdesk.campaigns.manage` (incluye approve/reject + bulk)

## A/B testing

```php
$campaign = Campaign::find(42);
$campaign->variants()->createMany([
    ['label' => 'A', 'weight' => 50, 'content' => [...], 'appearance' => [...]],
    ['label' => 'B', 'weight' => 50, 'content' => [...], 'appearance' => [...]],
]);
```

`VariantSelector` asigna variante pegajosa por `crc32(campaign_id:visitor_id)`.
Tracking se registra por `variant_id` para análisis por variante.

## Targeting

```php
$campaign->update([
    'conditions' => [
        'url_match' => '/checkout/*',
        'device_types' => ['desktop', 'tablet'],
        'countries' => ['ES', 'PT'],
        'languages' => ['es'],
        'segments' => [12, 34], // IDs de Engagement\Models\Segment
    ],
]);
```

## Frequency capping

```php
$campaign->update([
    'max_impressions_per_user' => 5,    // máximo 5 vistas por visitante
    'cooldown_minutes' => 60,            // 1h entre vistas
]);
```

## Tests

```bash
php artisan test --filter=HelpdeskCampaigns
php artisan test --filter=CampaignLifecycleEventsTest
```

## ADR

Ver `docs/adr/0003-helpdesk-campaigns-architecture.md` para el diseño técnico
detallado.
