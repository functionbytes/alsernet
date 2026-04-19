# Módulo SEO

Gestión integral de SEO para Alsernet: meta tags, redirects, sitemap, robots.txt,
llms.txt, IndexNow, Schema.org, Core Web Vitals, auditoría automática.

## Instalación

El módulo se activa automáticamente. Aplica migraciones y siembra permisos:

```bash
php artisan migrate
php artisan db:seed --class="Modules\\Seo\\Database\\Seeders\\SeoPermissionsSeeder"
php artisan optimize:clear
```

## Configuración

Dos maneras, con precedencia: **DB (admin UI) > `.env` > defaults**.

### Opción A — UI admin (recomendado)

Ir a `/panel/setting/seo/settings` para editar todo sin tocar `.env`.

### Opción B — Variables `.env`

```env
# IndexNow (Bing/Yandex instant indexing)
SEO_INDEXNOW_ENABLED=true
SEO_INDEXNOW_KEY=alfanumérico-de-8-a-128-caracteres
SEO_INDEXNOW_AUTO_SUBMIT=true

# Webhooks
SEO_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SEO_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
SEO_WEBHOOK_SIGNING_SECRET=secret_para_HMAC

# Performance hints (mejora PageSpeed)
SEO_PRECONNECT=https://fonts.googleapis.com,https://fonts.gstatic.com
SEO_DNS_PREFETCH=https://cdn.example.com
SEO_HTML_CACHE_SECONDS=60
SEO_SECURITY_HEADERS=true

# Core Web Vitals beacon
SEO_WEB_VITALS_ENABLED=true
SEO_WEB_VITALS_SAMPLE_RATE=0.1
SEO_WEB_VITALS_RETENTION_DAYS=90

# Google PageSpeed Insights API (opcional)
SEO_PAGESPEED_API_KEY=AIza...

# llms.txt (AI crawlers)
SEO_LLMS_TXT_ENABLED=true
```

## Integración en el frontend

En el layout público (`modules/Page/resources/views/components/layouts/master.blade.php`):

```blade
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-Seo::seo-tags :model="$page" />
    @schemaOrg
    @hreflang(url()->current())
    {{-- El middleware PerformanceHintsMiddleware inyecta preconnect + security headers automáticamente --}}
</head>
<body>
    {{-- Contenido --}}
    @seoWebVitalsBeacon
</body>
```

### Componentes Blade optimizados para Core Web Vitals

```blade
{{-- Hero image (arriba del fold, LCP) --}}
<x-Seo::seo-hero-image src="/hero.jpg" alt="..." width="1200" height="630" />

{{-- Imágenes secundarias (lazy, evita CLS) --}}
<x-Seo::seo-image src="/photo.jpg" alt="..." width="800" height="600" />

{{-- Preview SERP en vivo (usado en edit de metas) --}}
<x-Seo::serp-preview :meta="$meta" />

{{-- Widget dashboard (score + vitals + alertas) --}}
<x-Seo::seo-health-widget />
```

## URLs públicas servidas

| URL | Contenido |
|---|---|
| `/robots.txt` | Directivas para crawlers (incluye AI bots) |
| `/llms.txt` | Descripción del sitio para LLMs |
| `/sitemap.xml` | Sitemap principal (módulo Page) |
| `/sitemap-pages.xml` | Solo páginas |
| `/sitemap-posts.xml` | Solo blog posts |
| `/sitemap-images.xml` | Imágenes indexables |
| `/sitemap-video.xml` | Videos |
| `/sitemap-news.xml` | Noticias recientes (Google News) |
| `/{key}.txt` | IndexNow key verification |
| `/api/seo/web-vitals` | Beacon endpoint (POST) |

## Admin routes

Todas bajo `/panel/setting/seo/`:

- `dashboard` — stats generales
- `metas` — CRUD de meta SEO por modelo
- `metas/{id}/schema-org` — editor JSON-LD por página
- `redirects` — 301/302/wildcard/regex
- `404-logs` — errores 404 con sugerencias
- `templates` — templates SEO reutilizables
- `orphans` — contenido sin SEO
- `static-urls` — URLs extra para sitemap
- `robots` — editor robots.txt
- `llms` — editor llms.txt
- `indexnow` — estado + submit manual
- `web-vitals` — dashboard Core Web Vitals (p75)
- `sitemap` — gestión sitemap
- `audit` — auditoría on-demand + bulk
- `audit/history` — histórico de auditorías
- `report` — reporte exportable
- `settings` — **config unificada (sin tocar .env)**
- `search-console/import` — importar CSV de GSC
- `verification` — códigos Google/Bing/etc
- `page-urls` — URLs del sitio integradas con Page

## Comandos artisan

```bash
# Auditorías
php artisan seo:health                     # Checklist diario (exit 0/1/2)
php artisan seo:health --json              # Output JSON para monitoreo
php artisan seo:pagespeed                  # Snapshot PageSpeed API (top 20)
php artisan seo:pagespeed --url=https://…  # Single URL
php artisan seo:pagespeed --desktop        # Desktop strategy
php artisan seo:check-broken-links         # Detecta links rotos

# Sitemap
php artisan seo:generate-sitemap
php artisan seo:ping-sitemap               # Notifica a Google/Bing

# IndexNow
php artisan seo:indexnow-submit https://...

# Schemas
php artisan seo:generate-schemas

# Mantenimiento
php artisan seo:cleanup                    # Limpia logs antiguos
php artisan seo:purge-web-vitals           # Purga Web Vitals viejos
php artisan seo:weekly-report              # Envía reporte por email
```

## Cron schedules (auto-registrados)

- `seo:cleanup-404-logs` — diario
- `seo:cleanup-audit-logs` — semanal
- `seo:weekly-report` — lunes 08:00
- `seo:weekly-audit` — domingo 02:00
- `seo:content-decay` — lunes 04:00
- `seo:purge-web-vitals` — diario 03:45
- `seo:health` — diario 07:00
- `seo:pagespeed-weekly` — martes 05:30 (solo si API key configurada)

## Webhooks salientes (HMAC-SHA256)

Cuando `SEO_WEBHOOK_SIGNING_SECRET` está seteado, cada webhook incluye:
- `X-Seo-Signature: sha256=<hex>`
- `X-Seo-Timestamp: <unix>`

Verificación en el receptor:
```js
const expected = crypto
    .createHmac('sha256', SECRET)
    .update(timestamp + '.' + rawBody)
    .digest('hex');
if (`sha256=${expected}` !== receivedSignature) throw new Error('Invalid');
```

## PageSpeed / Core Web Vitals

Dos fuentes de datos:

1. **Real User Monitoring (RUM)** — vía `@seoWebVitalsBeacon` en el layout.
   Los navegadores reportan a `/api/seo/web-vitals`. Dashboard en `/panel/setting/seo/web-vitals`.

2. **Lab data** — vía Google PageSpeed API. Requiere API key. Snapshots semanales
   guardados en `seo_pagespeed_snapshots`.

## Tests

```bash
vendor/bin/phpunit modules/Seo/tests
```

Cobertura:
- Feature: redirects, middleware, 404s, dashboard, keyword tracking, bulk audits,
  orphans, search console, templates, web vitals, llms.txt, IndexNow, Schema.org,
  observer, redirect middleware
- Unit: SeoService, SeoAuditService

## Extender

### Agregar SEO a un nuevo modelo

```php
use Modules\Seo\Traits\HasSeo;

class MiModelo extends Model
{
    use HasSeo;
}
```

Ahora el modelo tiene:
- Relación `seoMeta()` (MorphOne)
- Accessors `seo_title`, `seo_description`, `og_title`, etc.
- Helpers `isIndexable()`, `isFollowable()`, `updateSeoMeta(array)`

### Agregar un tipo de Schema.org

Editar `SchemaOrgController::TEMPLATES` + añadirlo a la validación
`UpdateSchemaOrgRequest::rules()` en la regla `schema_type`.

## Backup / restore de configuración

Desde `/panel/setting/seo/settings`:
- **Exportar JSON** → descarga toda la config SEO
- **Importar JSON** → restaura desde un archivo previo

Útil para mover config entre entornos (dev → staging → prod).
