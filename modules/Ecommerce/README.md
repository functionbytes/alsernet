# Módulo Ecommerce

Plataforma completa de comercio electrónico para Laravel 12. Construida sobre `nwidart/laravel-modules` con arquitectura modular extensible.

## Stack

- **Framework:** Laravel 12 (PHP 8.4)
- **Database:** MariaDB
- **Cache/Queue:** Redis
- **Frontend:** Bootstrap 5.3 + jQuery + Font Awesome 6
- **Testing:** PHPUnit 11 (129+ tests)

## Features principales

### Tienda pública

- Catálogo con búsqueda profesional (Laravel Scout)
- Filtros laterales AJAX (precio, marca, categoría, atributos)
- Cards de producto con quick view modal
- Galería de imágenes con zoom y swipe móvil
- Carrito con drawer lateral (mini cart)
- Wishlist compartible vía link público
- Comparación de productos
- Checkout one-page con validación inline
- Cupones, gift cards, bundles
- Suscripciones recurrentes
- Reseñas con verified buyer badge + Q&A pública
- Recomendaciones inteligentes (cross-sell + co-occurrence)
- Tracking de orden por código

### Cuenta del cliente

- Dashboard con órdenes recientes y recomendaciones
- Historial completo de órdenes con timeline visual
- Reorder one-click
- Múltiples direcciones
- Wishlist y comparación
- Suscripciones (pausar/reactivar/cancelar)
- Búsquedas guardadas con notificación email
- GDPR data export (descargar todos sus datos)

### Panel admin

- Dashboard con KPIs y widgets
- CRUD de productos con bulk operations + inline edit precio/stock
- Drag-and-drop para reordenar categorías
- Editor WYSIWYG (TinyMCE) en descripciones, legal pages, email campaigns
- Quick search global (`Cmd+K`) productos/clientes/órdenes
- Notificaciones realtime con Reverb (orden nueva)
- Gestión de bundles y gift cards
- Email marketing con segmentación RFM (vip, inactive, new, recent_buyers)
- Reportes: ventas, comparativa, funnel, LTV, carritos abandonados, búsquedas, márgenes, forecasting de demanda, customer journey
- Generación de descripciones con IA (OpenAI GPT-4o-mini)

### Marketing y conversión

- Hero banner con slider
- Welcome popup con cupón
- Exit intent popup
- Stock urgency ("solo quedan X")
- Countdown timers en flash sales
- Cookie banner GDPR
- Cross-sell envío gratis en carrito
- Social proof en producto
- WhatsApp floating button
- AI Chatbot rule-based + LLM
- Cupón pop-up para primer visitante
- Newsletter
- Restock alerts
- Email automático de price drops

### Pagos

- Wompi (tarjeta, PSE, Nequi)
- COD (Pago contra entrega)
- Transferencia bancaria
- Arquitectura extensible — nuevos gateways como módulos plugin

### Seguridad

- 2FA disponible para admin
- Rate limiting por endpoint
- Sentry para error tracking
- Webhook signature verification
- CSRF en todos los forms
- Activity Log en modelos clave

### SEO técnico

- Sitemap.xml dinámico cacheado
- robots.txt configurable
- JSON-LD: Product, BreadcrumbList, Organization
- Open Graph + Twitter Cards
- Hreflang para multi-idioma
- Slugs SEO en producto/categoría/marca

### PWA

- Manifest.json instalable
- Service Worker con cache offline
- Push notifications support
- Install banner

### Internacionalización

- 3 idiomas: ES, EN, PT
- Tabla `ecommerce_translations` polimórfica
- Trait `HasTranslations` en Product, Category, Brand
- Selector de idioma en header

### Performance

- Cache estratégico Redis con tags
- Observers que invalidan cache automáticamente
- Image processing (Intervention/Image): thumb/medium/large × jpg/webp
- Lazy loading + srcset adaptativo
- Compresión Brotli/Gzip
- Cache headers para assets estáticos

## Variables de entorno

```env
# OpenAI (para chatbot LLM y descripciones automáticas)
OPENAI_API_KEY=

# Social login
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=

# Sentry
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

# Scout (búsqueda)
SCOUT_DRIVER=database
SCOUT_QUEUE=true
```

## Settings clave (`Modules\Core\Models\Setting`)

```php
// WhatsApp
ecommerce.whatsapp_number     // ej: 573001234567
ecommerce.whatsapp_message    // mensaje pre-rellenado

// Welcome popup
ecommerce.welcome_coupon_code     // BIENVENIDO10
ecommerce.welcome_coupon_percent  // 10

// Exit intent
ecommerce.exit_intent_coupon      // NOTEVAYAS
ecommerce.exit_intent_percent     // 15

// Free shipping
ecommerce.free_shipping_min       // 200

// Webhooks
ecommerce_webhook_url
ecommerce_webhook_secret
ecommerce_webhook_events          // CSV de eventos habilitados

// Analytics
ecommerce.ga4_id
ecommerce.meta_pixel_id
ecommerce.gtm_id

// Pagos
ecommerce_payment.wompi.status
ecommerce_payment.wompi.public_key
ecommerce_payment.wompi.private_key
ecommerce_payment.cod.status
ecommerce_payment.bank_transfer.status
```

## Comandos Artisan

```bash
# Newsletter / Email marketing
php artisan ecommerce:send-abandoned-carts        # Diario
php artisan ecommerce:send-restock-alerts         # Cada 5 min (scheduled)
php artisan ecommerce:notify-price-drops          # Diario (scheduled)
php artisan ecommerce:notify-saved-searches       # Diario
php artisan ecommerce:send-winback                # Mensual

# Operaciones
php artisan ecommerce:generate-invoices           # Genera facturas faltantes
php artisan ecommerce:cancel-expired-deletion-requests
php artisan ecommerce:update-flash-sales          # Actualiza estados de flash sales
php artisan ecommerce:process-subscription-renewals # Diario 03:00
php artisan ecommerce:refresh-sitemap             # Limpia cache de sitemap

# Imágenes
php artisan ecommerce:process-product-images      # Backfill de variantes
php artisan ecommerce:process-product-images --queue

# Búsqueda
php artisan scout:import "Modules\Ecommerce\Models\Product"
```

## Rutas principales

### Pública
- `GET /tienda` — Homepage
- `GET /tienda/producto/{slug}` — Detalle
- `GET /tienda/categoria/{slug}` — Listado por categoría
- `GET /tienda/marca/{slug}` — Listado por marca
- `GET /tienda/bundles` — Listado de bundles
- `GET /tienda/gift-cards` — Compra de gift cards
- `GET /seguimiento` — Tracking de orden
- `GET /sitemap.xml` — Sitemap
- `GET /robots.txt` — Robots
- `GET /legal/{slug}` — Páginas legales (terms, privacy, etc.)

### Cuenta del cliente
- `GET /tienda/cuenta` — Dashboard
- `GET /tienda/cuenta/ordenes` — Órdenes
- `POST /tienda/cuenta/ordenes/{order}/reorder` — Reorder one-click
- `GET /tienda/cuenta/suscripciones` — Suscripciones
- `GET /tienda/cuenta/busquedas-guardadas` — Saved searches
- `POST /tienda/cuenta/gdpr-export` — Solicitar export GDPR

### Admin
- `GET /panel/ecommerce` — Dashboard
- `GET /panel/ecommerce/products` — Productos
- `GET /panel/ecommerce/orders` — Órdenes
- `GET /panel/ecommerce/customers` — Clientes
- `GET /panel/ecommerce/discounts` — Cupones
- `GET /panel/ecommerce/bundles` — Bundles
- `GET /panel/ecommerce/gift-cards` — Gift cards
- `GET /panel/ecommerce/email-campaigns` — Campañas de email
- `GET /panel/ecommerce/legal-pages` — Páginas legales
- `GET /panel/ecommerce/newsletter` — Suscriptores
- `GET /panel/ecommerce/webhook-logs` — Logs de webhooks
- `GET /panel/ecommerce/reports` — Reportes
  - `/reports/comparison` — Período actual vs anterior
  - `/reports/funnel` — Embudo de conversión
  - `/reports/customer-ltv` — Lifetime Value
  - `/reports/abandoned-carts` — Carritos abandonados
  - `/reports/search` — Analytics de búsqueda
  - `/reports/margin` — Análisis de margen
  - `/reports/forecast` — Forecasting de demanda
  - `/reports/journey` — Customer journey

### API REST (`/api/v1/ecommerce`)

**Públicas:**
- `GET /products` — Listado con búsqueda Scout
- `GET /products/suggestions?q=` — Autocomplete
- `GET /cart`, `POST /cart`, `PUT /cart/{rowId}`, `DELETE /cart/{rowId}`
- `GET /cart/count` — Contador para AJAX
- `GET /filters` — Filtros disponibles
- `POST /coupons/apply`
- `GET /countries`, `/states`, `/cities`

**Autenticadas (Sanctum):**
- `GET/POST /orders`
- `GET/PUT /profile`
- `GET/POST/DELETE /addresses`
- `GET/POST/DELETE /wishlist`
- `GET /downloads/{item}` — Descargas digitales

## Roles y permisos

```
admin                 # Acceso total
ecommerce-admin       # Gestión completa del módulo
ecommerce-manager     # Gestión sin eliminar (vista, crear, editar)
```

Permisos clave:
- `ecommerce.product.{view,create,update,delete}`
- `ecommerce.order.{view,create,update,delete}`
- `ecommerce.customer.{view,create,update,delete}`
- `ecommerce.discount.{view,create,update,delete}`
- `bundle.{view,manage}`
- `gift-card.{view,manage}`
- `email-campaign.{view,manage,send}`
- `subscription.{view,manage}`
- `newsletter.{view,delete,export,manage}`
- `ecommerce.reports.{view,financial}`
- `ecommerce.legal-page.{view,manage}`

## Eventos broadcast

- `AdminOrderReceived` — Cuando llega orden nueva (Reverb channel `admin-orders`)

## Setup inicial

```bash
# 1. Migraciones
php artisan migrate
php artisan module:migrate Ecommerce

# 2. Permisos y roles
php artisan module:seed Ecommerce --class=EcommercePermissionsSeeder

# 3. Páginas legales por defecto
php artisan module:seed Ecommerce --class=EcommerceLegalPagesSeeder

# 4. Templates de email
php artisan module:seed Ecommerce --class=EcommerceOrderEmailTemplatesSeeder
php artisan module:seed Ecommerce --class=EcommerceAbandonedCartTemplatesSeeder

# 5. Datos demo (opcional)
php artisan module:seed Ecommerce --class=EcommerceDemoSeeder

# 6. Indexar productos para búsqueda
php artisan scout:import "Modules\Ecommerce\Models\Product"

# 7. Configurar settings desde el panel admin
# Ir a /panel/settings/ecommerce
```

## Tests

```bash
# Suite completa del módulo
php artisan test modules/Ecommerce/tests/

# Suite específica
php artisan test modules/Ecommerce/tests/Feature/ShopCheckoutTest.php

# Compact mode
php artisan test --compact modules/Ecommerce/tests/
```

129 tests / 312 aserciones cubriendo:
- Catálogo y carrito
- Checkout y cupones
- Cuenta del cliente
- Wishlist sharing
- Newsletter, restock alerts
- Email verification, social auth
- Webhooks, sitemap
- Bundles, gift cards, subscriptions
- Saved searches, GDPR export
- Reportes (margen, forecast, journey)
- Activity log

## Estructura del módulo

```
modules/Ecommerce/
├── app/
│   ├── Console/Commands/         # Comandos artisan (~10)
│   ├── Events/                   # Eventos del módulo
│   ├── Http/
│   │   ├── Controllers/Admin/    # ~50 controllers admin
│   │   ├── Controllers/Shop/     # ~15 controllers públicos
│   │   ├── Controllers/Api/      # ~13 API REST
│   │   ├── Middleware/
│   │   └── Requests/             # Form Requests
│   ├── Jobs/                     # Queue jobs
│   ├── Listeners/                # Event listeners
│   ├── Mail/                     # Mailables
│   ├── Models/                   # ~40 modelos
│   ├── Notifications/
│   ├── Observers/                # Cache invalidation
│   ├── Policies/                 # Spatie permissions
│   ├── Providers/
│   ├── Services/                 # Business logic
│   ├── Supports/                 # Helpers estáticos
│   └── Traits/                   # Reusable traits
├── database/
│   ├── factories/
│   ├── migrations/               # 30+ migraciones
│   └── seeders/
├── resources/
│   ├── lang/
│   └── views/
│       ├── emails/               # Templates HTML
│       ├── layouts/              # shop-wowy, shop, theme
│       ├── shop/                 # Vistas públicas
│       │   ├── partials/         # 30+ partials reutilizables
│       │   └── ...
│       └── (admin views)
├── routes/
│   ├── web.php                   # 200+ rutas
│   └── api.php
└── tests/
    └── Feature/                  # 129 tests
```

## Contribuir

Sigue las convenciones en `.claude/rules/`:
- Form Requests para validación (no inline)
- Eloquent siempre (no DB::)
- Eager loading para evitar N+1
- Bootstrap 5.3 + Font Awesome 6 (no Tabler)
- jQuery + AJAX (no Livewire/Inertia)
- Pint antes de commit: `vendor/bin/pint --dirty`

## Licencia

Propietario.
