# Remarketing

Módulo de **email marketing para ecommerce**: sincroniza tiendas externas (Shopify, WooCommerce, próximamente PrestaShop/Magento/BigCommerce) o internas (`modules/Ecommerce`), construye perfiles unificados de cliente, segmenta por comportamiento + RFM, envía campañas one-off y automatizaciones de ciclo de vida (welcome, carrito abandonado, post-compra, win-back), todo con deliverability profesional (DKIM/SPF/DMARC, RFC 8058 one-click unsubscribe, supresión automática por bounce/complaint).

A diferencia de `modules/Mailrelay` (que provee la **capa de transporte multi-provider** vía `ProviderManager`) y `modules/Mailer` (transactional), este módulo se concentra en la **capa de datos ecommerce** y **lógica de marketing**: clientes, productos, órdenes, carritos, eventos, segmentos, automations, plantillas, suppression list.

> Documentación de referencia: `docs/proposals/remarketing-module-plan.md` (blueprint), `docs/research/remarketing-competition.md` (competencia).

---

## Setup

### Activación

```bash
# Ya registrado en bootstrap/providers.php, modules_statuses.json y composer.json autoload.
composer dump-autoload --no-scripts
php artisan optimize:clear
php artisan module:list | grep Remarketing   # debe aparecer Enabled
```

### Migraciones (16 tablas)

```bash
php artisan migrate --path=modules/Remarketing/database/migrations
```

### Permisos Spatie (36 permisos)

```bash
php artisan module:seed Remarketing --class=RemarketingPermissionsSeeder
php artisan cache:clear
```

### Horizon supervisors

Se añadieron a `config/horizon.php`:
- `supervisor-remarketing` — cola `remarketing` (envíos, automations, sync, cálculos).
- `supervisor-remarketing-webhooks` — cola `remarketing-webhooks` (ingesta de webhooks, alta concurrency, timeout corto).

```bash
php artisan horizon:install   # solo si nunca se hizo
php artisan horizon           # arranca el daemon
```

### Schedule (registrado por el ServiceProvider)

| Comando | Frecuencia | Propósito |
|---|---|---|
| `remarketing:mark-abandoned-carts` | cada 15 min | Marca carts inactivos >60 min como `abandoned` y dispara automations |
| `remarketing:process-automations` | cada minuto | Despacha `EvaluateAutomationJob` por cada run pendiente |
| `remarketing:calculate-rfm` | diario 03:00 | Recalcula RFM de todos los customers + dispara win-back |
| `remarketing:reconcile-catalog` | diario 04:00 | Sync delta de catálogo, customers y orders |

Asegúrate de que el cron del proyecto está activo: `* * * * * cd /path/to/system && php artisan schedule:run >> /dev/null 2>&1`.

---

## Conexión de tienda

### Shopify

1. En Shopify Admin → Apps → **Develop apps** → **Create app**.
2. Permisos mínimos: `read_products`, `read_customers`, `read_orders`, `write_webhooks`.
3. Copia el **Admin API access token** (empieza con `shpat_...`).
4. En el panel: **Remarketing → Tiendas → Conectar** → selecciona Shopify.
5. Pega `domain` (`mi-tienda.myshopify.com`) y `access_token`.
6. Al guardar, el módulo:
   - Suscribe los webhooks (`orders/create`, `customers/update`, `checkouts/create`, etc.).
   - Despacha `SyncCatalogJob`, `SyncCustomersJob`, `SyncOrdersJob`.
7. Verifica en `Remarketing → Tiendas → {tienda} → Health` el estado de webhooks y deliverability (DKIM/SPF/DMARC del dominio remitente).

### WooCommerce

1. WooCommerce → **Settings → Advanced → REST API → Add key**. Permisos: Read/Write.
2. Copia `consumer_key` y `consumer_secret`.
3. Panel → **Remarketing → Tiendas → Conectar** → WooCommerce.
4. Pega `domain` (URL de la tienda), `api_key` (consumer_key), `api_secret` (consumer_secret).
5. WooCommerce no tiene webhook nativo de carrito; instala el plugin auxiliar:
   - Descarga: `modules/Remarketing/resources/plugins/woocommerce-bridge/`.
   - Sube la carpeta a `wp-content/plugins/` y activa.
   - Configura en **WP Admin → Ajustes → Remarketing Bridge** el endpoint, pixel URL, store_token y shared secret.

---

## Pixel JS para tracking en sitio del cliente

El pixel `public/remarketing/pixel.js` se sirve desde la URL pública del proyecto.

Snippet a embeber en la tienda (todas las páginas):

```html
<script>
window._rmkEndpoint = 'https://app.example.com/r/track';
window._rmk = window._rmk || [];
window._rmk.push(['store', 'STORE_TOKEN_PUBLICO']);
</script>
<script src="https://app.example.com/remarketing/pixel.js" async></script>
```

API JS:

| Comando | Acción |
|---|---|
| `_rmk.push(['store', token])` | Inicialización + page_view |
| `_rmk.push(['identify', email, firstName])` | Asocia visitor anónimo a un email |
| `_rmk.push(['track', 'product_view', { product_id, price, ... }])` | Custom event |

El pixel persiste un `_rmk_vid` en cookie first-party (`SameSite=Lax`, 365 días). Cuando llega un `identify`, el job `ProcessPixelEventJob` re-asigna todos los eventos históricos del visitor al customer.

---

## Compliance (GDPR / RFC 8058)

- **Double opt-in obligatorio** en países UE y Brasil (geofence en `ConsentService::isGeofenced()`).
- **Audit trail inmutable** en `remarketing_consent_events` — nunca se borra, ni siquiera con DSR delete.
- **One-click unsubscribe (RFC 8058)** automático en cada email:
  ```
  List-Unsubscribe: <https://app.example.com/r/unsubscribe/{click_token}>
  List-Unsubscribe-Post: List-Unsubscribe=One-Click
  ```
- **Suppression list** verificada antes de cada envío (`SendEmailJob::isSuppressed()`).
- **DSR endpoints**: `POST /api/remarketing/dsr/export` y `POST /api/remarketing/dsr/delete` (delete anonimiza email pero conserva consent_events).

---

## Deliverability checklist

Antes de lanzar campañas masivas, verifica:

| Check | Cómo | Razón |
|---|---|---|
| SPF | `dig +short TXT ejemplo.com` debe incluir el provider | Permite envío |
| DKIM | Selector del provider en `default._domainkey.ejemplo.com` | Anti-spoofing |
| DMARC | `_dmarc.ejemplo.com` con `p=none` mínimo, `quarantine` recomendado | Política de fallo |
| Warm-up | Empezar con 1k/día, x2 cada 3 días hasta volumen target | Reputación de IP |
| Lista limpia | Bounce rate <2%, complaint rate <0.3% | Evita blacklists |

`DeliverabilityCheckerService::check($domain)` valida los 3 primeros automáticamente — visible en `Remarketing → Tiendas → Health`.

---

## Fronteras con otros módulos

| Módulo | Responsabilidad | Relación con Remarketing |
|---|---|---|
| **Mailrelay** | Capa de transporte multi-provider (SES, SendGrid, Mailgun, Mailtrap, etc.) con failover automático | Remarketing **consume** `Mailrelay\Services\ProviderManager::sendWithFailover()` desde `SendEmailJob`. No duplica drivers. |
| **Mailer** | Emails transactional del sistema | Remarketing **NO usa** Mailer; sus emails son marketing, no transaccionales. |
| **Campaign** | Newsletters/listas genéricas | Dominio distinto. NO se reutilizan modelos. |
| **Ecommerce** (interno) | Catálogo y pedidos del comercio propio del sistema | Remarketing puede conectarse al data layer interno como una "tienda más" (roadmap: connector `InternalEcommerceConnector`). |
| **Engagement** | Tracking, scoring y personalización en sesión | Complementario: Engagement produce eventos/sessions/scoring. Remarketing los consume para email. |

Decisión arquitectónica documentada en `docs/adr/0001-mailer-vs-mailrelay.md` (Opción C: status quo + fronteras claras).

---

## Comandos útiles

```bash
# Ver rutas del módulo
php artisan route:list --name=remarketing
php artisan route:list --name=api.remarketing

# Listar tiendas activas vía tinker
php artisan tinker --execute='echo \Modules\Remarketing\Models\Store::where("status","active")->count();'

# Forzar sync delta inmediato (ignora schedule)
php artisan remarketing:reconcile-catalog

# Forzar marca de carritos abandonados ahora
php artisan remarketing:mark-abandoned-carts

# Forzar avance de automations pendientes
php artisan remarketing:process-automations

# Recálculo RFM completo (incluye trigger win-back)
php artisan remarketing:calculate-rfm
```

---

## Estructura del módulo

```
modules/Remarketing/
├── app/
│   ├── Connectors/                    # AbstractConnector + ConnectorRegistry
│   │   ├── Shopify/ShopifyConnector.php
│   │   └── WooCommerce/WooCommerceConnector.php
│   ├── Console/Commands/              # 4 comandos artisan
│   ├── Contracts/EcommerceConnector.php
│   ├── DTOs/EventDTO.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── *.php                  # Web controllers (panel)
│   │   │   ├── Api/                   # API endpoints (auth:sanctum)
│   │   │   ├── Public/                # Track, Unsubscribe, Tracking, Webhook
│   │   │   └── Settings/              # Settings panel
│   │   ├── Requests/{Web,Api,Public,Settings}/
│   │   └── Resources/                 # 13 API Resources
│   ├── Jobs/                          # 11 jobs queue
│   ├── Models/                        # 16 modelos Eloquent
│   ├── Observers/                     # OrderObserver, ConsentEventObserver
│   ├── Policies/                      # 8 policies
│   ├── Providers/                     # ServiceProvider + Route + Event
│   └── Services/                      # 6 services de dominio
├── config/config.php
├── database/migrations/               # 16 migraciones
├── database/seeders/RemarketingPermissionsSeeder.php
├── resources/
│   ├── assets/pixel/pixel.js          # Fuente del pixel
│   ├── plugins/woocommerce-bridge/    # Plugin WordPress descargable
│   └── views/                         # 24 vistas Blade
├── routes/web.php
├── routes/api.php
└── tests/Feature/
```

`public/remarketing/pixel.js` ← copia servible directamente del pixel.

---

## Roadmap

Implementado en **v1 (MVP)**:
- Conectores Shopify y WooCommerce.
- 4 automations pre-construidas (welcome, cart_abandoned, post_purchase, win_back).
- Campañas one-off + segmentación dinámica básica + RFM.
- Pixel JS + identity resolution.
- Compliance GDPR + RFC 8058.
- 109 rutas Web + 49 API.

Pendiente **v2**:
- Conector PrestaShop (diferenciador regional España/LATAM).
- Browse abandonment, back-in-stock, price-drop flows.
- A/B testing con holdout.
- Editor visual de flows (branching).
- SMS via Twilio.
- Predictive segments (CLV, churn risk).

Pendiente **v3**:
- Conectores Magento + BigCommerce.
- WhatsApp Business API.
- Web push.
- Send-time optimization.

Detalles en `docs/proposals/remarketing-module-plan.md` sección 14.

---

## Tests

```bash
php artisan test --compact modules/Remarketing/tests/
```

Los tests usan `DatabaseTransactions` y verifican que las migraciones del módulo estén aplicadas. Si la BD de testing no tiene las tablas, los tests se marcan automáticamente como `skipped`.

Para ejecutarlos en CI con BD limpia, antes corre:
```bash
php artisan migrate --database=testing --path=modules/Remarketing/database/migrations
php artisan db:seed --database=testing --class="Modules\\Remarketing\\Database\\Seeders\\RemarketingPermissionsSeeder"
```

---

## Soporte y debug

- Logs: `storage/logs/laravel.log` filtrar por `Remarketing`.
- Horizon: `https://app.example.com/horizon` ver supervisores `supervisor-remarketing*`.
- Telescope: requests, queries lentas, jobs failed.
- Webhook entrante: `tail -f storage/logs/laravel.log | grep WebhookController`.
