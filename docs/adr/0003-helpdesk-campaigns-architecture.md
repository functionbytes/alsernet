# ADR 0003: HelpdeskCampaigns enterprise architecture

- **Estado**: Aceptado
- **Fecha**: 2026-05-06
- **Decisor**: arquitectura

---

## Contexto

`HelpdeskCampaigns` arrancó como CRUD básico de banners/popups. Para llevarlo
a producción real necesitábamos:

1. **Tracking público** sin auth para que widgets externos puedan registrar
   impresiones/clicks.
2. **Async processing** para que el endpoint público responda <50ms incluso
   con tráfico alto.
3. **Eventos del ciclo de vida** (publicada / pausada / reanudada / finalizada)
   para integraciones externas y audit log.
4. **Lifecycle automático**: campañas con `published_at` futuro se activan
   solas; con `ends_at` pasado o goal alcanzado se cierran solas.
5. **Frequency capping** para no abrumar al mismo visitante.
6. **Targeting por audiencia**, idealmente con segments del módulo Engagement.
7. **A/B testing** con asignación pegajosa por visitante (mismo visitante
   siempre ve la misma variante).
8. **Approval workflow** para entornos enterprise donde otra persona aprueba
   antes de publicar.

## Decisiones

### Pipeline de tracking público

```
JS widget (cliente)
   │ POST /helpdesk/campaigns/track/view  { campaign_id, visitor }
   ▼
ImpressionTrackingController::recordView
   │ TargetingService::matches  → 200 TARGETING_MISMATCH si no
   │ FrequencyCapService::shouldShow → 200 FREQUENCY_CAPPED si no
   │ VariantSelector::pick → variant pegajosa por visitor hash
   │ Genera UUID impression_id
   │ RecordImpressionJob::dispatch (queue: impressions)
   ▼
JS recibe { impression_id, variant, ... }
   │ Renderiza popup con content/appearance del variant
   │ Si user clickea CTA → POST /track/click/{uuid}
   ▼
RecordImpressionJob (worker)
   │ INSERT helpdesk_campaign_impressions
   │ CampaignImpressionRecorded::dispatch
   ▼
UpdateCampaignImpressionCounters (worker)
   │ UPDATE helpdesk_campaigns SET impressions_count++
```

Why async: el endpoint público vive bajo carga real, cada milisegundo cuenta.
Mover el INSERT y los counters al worker mantiene la respuesta <50ms.

### Lifecycle automático

Dos jobs cada minuto (vía Laravel scheduler):

- **`PublishScheduledCampaignsJob`** activa campañas con
  `status = 'scheduled'` y `published_at <= now()`. Si la campaña requiere
  aprobación, espera a que `approved_at` esté presente.
- **`EndExpiredCampaignsJob`** cierra campañas con `ends_at <= now()` o cuyo
  `goal_value` fue alcanzado (impressions/clicks counters denormalizados).

### Eventos lifecycle → integraciones

```
CampaignPublished/Paused/Resumed/Ended
       │
       ├── LogCampaignActivity            (spatie/activitylog)
       ├── SendCampaignStatusNotification (database + broadcast)
       └── DispatchCampaignWebhooks       (HTTP POST a URLs configuradas)
```

`config('helpdeskcampaigns.webhooks')` permite suscribirse a eventos. Cada
webhook puede tener `secret` opcional para HMAC-SHA256 sobre el body.

### Approval workflow

Estados: `draft → pending_approval → scheduled → active → ended`.
Permisos: `helpdesk.campaigns.update` para enviar a aprobación,
`helpdesk.campaigns.manage` para aprobar/rechazar.

Si `approval_required = false`, el flujo se salta `pending_approval`.

### Frequency capping

Identidad del visitante en orden de precisión:

1. `customer_id` (logged-in)
2. `customer_session_id` (cookie anónima `hd_session_id`)
3. `ip_address` (último recurso)

Reglas por campaña:

- `max_impressions_per_user`: límite duro de vistas totales por visitante.
- `cooldown_minutes`: tiempo mínimo entre vistas consecutivas.

### A/B testing

Tabla `helpdesk_campaign_variants` (campaign_id, label, weight 0-100, content,
appearance). `VariantSelector::pick` usa `crc32("{campaign_id}:{visitor}")`
para asignar el bucket determinístico → mismo visitante siempre ve la misma
variante. Tracking por `variant_id` en `helpdesk_campaign_impressions`.

### Targeting

Campo `conditions` (JSON) en la campaña. Reglas evaluadas por
`TargetingService::matches`:

| Condición | Tipo | Ejemplo |
|-----------|------|---------|
| `url_match` | glob | `/checkout/*` |
| `device_types` | array | `["mobile", "desktop"]` |
| `countries` | array | `["ES", "PT"]` |
| `languages` | array | `["es", "ca"]` |
| `segments` | array of segment IDs | `[12, 34]` (requires Engagement) |

Las condiciones de Engagement (`segments`, `min_visits`) se omiten
silenciosamente si Engagement no está instalado (fail-open).

## Consecuencias

### Positivas

- Endpoint público responde <50ms — apto para tráfico web real.
- Campañas funcionan sin intervención humana: se activan/cierran solas según
  reglas (time-based o goal-based).
- A/B testing produce datos estadísticamente limpios (asignación pegajosa).
- Webhooks permiten integrar con Slack, Zapier, n8n, etc. sin cambiar código.
- Approval workflow opcional cumple requisitos de gobernanza enterprise.

### Negativas / pendientes

- El widget JS (`public/js/widget.js`) es el mínimo viable: render simple de
  popup. Slide-in / banner / full-screen aún se renderizan como popup.
- Geo lookup (`country` en impressions) está nullable — implementación de
  GeoIP queda fuera de scope.
- Sin UI para gestionar variants A/B aún (se crean via API).

## Archivos relevantes

- `app/Events/Campaign{Published,Paused,Resumed,Ended,ImpressionRecorded}.php`
- `app/Listeners/{LogCampaignActivity,SendCampaignStatusNotification,UpdateCampaignImpressionCounters,DispatchCampaignWebhooks}.php`
- `app/Jobs/{RecordImpressionJob,CleanupOldImpressionsJob,PublishScheduledCampaignsJob,EndExpiredCampaignsJob}.php`
- `app/Services/{FrequencyCapService,TargetingService,VariantSelector}.php`
- `app/Notifications/Campaign{Published,Ended}Notification.php`
- `app/Observers/CampaignObserver.php`
- `app/Http/Controllers/Public/ImpressionTrackingController.php`
- `app/Http/Controllers/Api/CampaignsApiController.php`
- `app/Http/Resources/CampaignResource.php`
- `routes/{managers,api,portal}.php`
- `database/migrations/2026_05_06_*` (counters + frequency/goals + variants)
- `public/js/widget.js`
- `config/config.php` (webhooks, retention, default frequency)
