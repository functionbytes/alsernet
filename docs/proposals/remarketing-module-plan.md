# Módulo Remarketing — Plan de implementación

---

## 1. Resumen ejecutivo

El módulo **Remarketing** añade a Alsernet una capa de email marketing orientada a ecommerce: sincronización de tiendas (Shopify, WooCommerce, PrestaShop, Magento, BigCommerce), perfiles unificados de cliente, segmentación dinámica, automaciones de ciclo de vida (carrito abandonado, post-compra, win-back) y envío masivo con deliverability profesional (DKIM/SPF/DMARC, one-click unsubscribe RFC 8058, supresión automática por bounce/complaint). A diferencia del módulo Campaign (orientado a listas/newsletters genéricos) y del módulo Mailrelay (multi-provider de marketing existente), Remarketing centra su propuesta en el **data layer ecommerce**: órdenes, carritos, catálogo y eventos de comportamiento como fuente de triggers. El resultado es un módulo que compite con el segmento mid-market (Omnisend, Drip) sin pretender reemplazar a Klaviyo desde el día 1.

El MVP cubre conectores Shopify y WooCommerce, perfiles unificados con consent management completo (GDPR/LGPD), cuatro automaciones pre-construidas (welcome, cart abandoned, post-purchase, win-back), campañas one-off, segmentación estática y dinámica básica (RFM + tags), y KPIs de deliverability/engagement. El cumplimiento GDPR y RFC 8058 son requisitos de entrada, no post-MVP. El tiempo estimado es **10-12 semanas** con equipo de 3 personas (2 backend + 1 frontend).

El módulo se implementa como `modules/Remarketing/` siguiendo las convenciones nwidart del proyecto. Usa la cola `remarketing` nueva en Horizon. Para el envío se reutiliza el sistema multi-provider del módulo Mailrelay (Opción C del ADR-0001 en vigor: status quo + fronteras claras), añadiendo a Remarketing como consumidor del `ProviderManager` existente sin duplicar la capa de transporte. Las vistas se construyen con Blade + jQuery + Bootstrap 5.3. No se usa Livewire, Inertia ni React.

---

## 2. Alcance

### 2.1 En alcance v1 (MVP)

- Módulo Laravel `modules/Remarketing/` con ServiceProvider, rutas web y API, migraciones, seeders de permisos.
- Conector Shopify: autenticación OAuth + API key privada, webhooks HMAC, bulk sync inicial (GraphQL bulk operations), sync incremental.
- Conector WooCommerce: autenticación consumer key/secret, REST API v3, webhooks, plugin PHP para carrito abandonado.
- Modelo de datos completo (13 tablas descritas en sección 4).
- Perfiles unificados de cliente con external_ids por tienda/plataforma.
- Consent management: double opt-in, tabla `remarketing_consent_events` append-only, endpoint de unsub one-click, headers `List-Unsubscribe` y `List-Unsubscribe-Post`.
- Suppression list global con check obligatorio antes de cada envío.
- Cuatro automations pre-construidas con steps configurables: welcome, cart_abandoned, post_purchase, win_back.
- Campañas one-off: crear, programar, enviar, cancelar.
- Segmentación estática (tag-based) y dinámica simple (RFM: recency/frequency/monetary + has_done/has_not_done sobre eventos).
- Envío via `ProviderManager` de Mailrelay (reutilización, no duplicación). Cola dedicada `remarketing`.
- Pixel JS embebible (`public/remarketing/pixel.js`) para captura de page_view, product_view, add_to_cart, checkout_start en el lado cliente.
- Tracking de opens (pixel GIF 1x1) y clicks (redirect).
- KPIs: delivery rate, bounce rate, complaint rate, open rate (MPP-aware), CTR, unsubscribe rate, revenue per recipient.
- DKIM/DMARC setup checker en el wizard de tienda.
- DSR endpoints: export y delete de datos por customer.
- UI completa en `panel/remarketing/` y `panel/settings/remarketing/`.
- 34 permisos Spatie con seeder.
- Tests PHPUnit para happy path, failure y autorización.

### 2.2 Fuera de alcance v1 (postponed a v2/v3)

**v2 (+6-8 semanas):**
- Conectores PrestaShop, Magento, BigCommerce.
- Pixel GTM dataLayer.
- Browse abandonment automation.
- Back-in-stock y price-drop flows (requieren tabla `remarketing_stock_alerts`).
- A/B testing de subject/content con holdout group y significancia estadística.
- Editor visual de flows con branching (yes/no splits).
- SMS via Twilio con TCPA compliance.
- Predictive segments: CLV bucket (RFM), churn risk (logistic regression), next order ETA.

**v3 (+indefinido):**
- WhatsApp Business API.
- Web push (VAPID/ServiceWorker).
- Send-time optimization por usuario.
- Dynamic content blocks (recomendaciones renderizadas al open).
- Multi-touch attribution configurable.
- AI subject line generator.
- Replenishment flows.
- Multi-tenancy sharding >1000 cuentas.

---

## 3. Arquitectura

### 3.1 Diagrama de alto nivel (ASCII)

```
┌─────────────────────────────────────────────────────────────────────┐
│  SOURCES                                                            │
│  Shopify ──┐                                                        │
│  WooCommerce─┤  POST /r/webhooks/{platform}/{store_token}           │
│  (v2: PS/Mg/BC)                                                     │
└────────────┼────────────────────────────────────────────────────────┘
             │ HMAC verified, payload → queue(remarketing-webhooks)
             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  modules/Remarketing/app/                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ Connectors/  │  │  Services/   │  │    Jobs/                  │  │
│  │ Shopify      │  │ SyncService  │  │ ProcessWebhookJob         │  │
│  │ WooCommerce  │  │ ProfileSvc   │  │ SyncCatalogJob            │  │
│  │ (EcomConnector│ │ ConsentSvc   │  │ SyncCustomersJob          │  │
│  │  interface)  │  │ SegmentSvc   │  │ SyncOrdersJob             │  │
│  │              │  │ AutomationSvc│  │ EvaluateAutomationJob     │  │
│  │              │  │ CampaignSvc  │  │ SendEmailJob              │  │
│  │              │  │ DelivSvc     │  │ RecalculateSegmentJob      │  │
│  └──────────────┘  └──────────────┘  │ ReconcileCatalogJob       │  │
│                                      │ ProcessBounceJob           │  │
│                                      │ CalculateRfmJob            │  │
│                                      └──────────────────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ MariaDB: 13 tablas + Redis: segment sets + queues            │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  Envío → Mailrelay\Services\ProviderManager (reutilizado)           │
│          ↳ SES / SendGrid / Mailgun (driver elegido en settings)    │
└─────────────────────────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  PIXEL / TRACKING                                                   │
│  GET  /r/open/{message_id}.gif  → RegisterOpenJob                  │
│  GET  /r/click/{message_id}/{link_id} → RegisterClickJob + redirect │
│  POST /r/track  → JS pixel events (page_view, product_view, ...)   │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.2 Reutilización de módulos existentes

**Mailrelay vs Mailer (decisión):**

El ADR-0001 estableció Opción C (status quo). Remarketing NO crea una tercera capa de transporte. La decisión arquitectónica aquí es: **Remarketing usa el `ProviderManager` de Mailrelay para el envío**, inyectado como dependencia en `SendEmailJob`. Esto es consistente con ADR-0001 (Mailrelay = emails masivos con tracking pixel, multi-provider, campañas) y evita una cuarta implementación de drivers de email. Mailer no se usa: Remarketing no envía emails transaccionales de plataforma, envía emails de marketing ecommerce.

Si en el futuro se aplica Opción B del ADR (consolidar providers en Mailer), Remarketing solo necesita cambiar el import del `ProviderManager`.

**Campaign y Remarketing (relación):**

Campaign maneja listas y newsletters genéricos; Remarketing maneja ecommerce data (órdenes, carritos, catálogo). Son dominios distintos y deben coexistir sin solapamiento. Remarketing NO reutiliza modelos de Campaign. La única dependencia permitida es si en el futuro se quiere mostrar campañas de Remarketing en el dashboard de Campaign, pero en MVP son módulos independientes.

**MailsSettings:**

Remarketing hereda la configuración de servidor saliente (SMTP/API credentials) del `OutgoingEmailSettingsController` de MailsSettings a través de los settings de tienda. En la pantalla de configuración general de Remarketing, el usuario selecciona qué provider usar (delegando a Mailrelay's ProviderManager). No se duplican los formularios de configuración SMTP.

**Horizon (cola dedicada):**

Se añade supervisor `supervisor-remarketing` en `config/horizon.php` con cola `remarketing` (envíos masivos, alto volumen, concurrency limitada para respetar rate limits del ESP) y `remarketing-webhooks` (ingesta de webhooks, respuesta rápida <500ms, alta concurrency).

---

## 4. Modelo de datos

Todas las tablas tienen prefijo `remarketing_`. Charset `utf8mb4`. Todas las columnas `created_at`/`updated_at` a menos que se indique lo contrario.

---

### remarketing_stores

Representa una tienda conectada (una cuenta puede tener N tiendas).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| user_id | BIGINT UNSIGNED NOT NULL FK→users.id CASCADE | Propietario |
| platform | VARCHAR(30) NOT NULL | `shopify`, `woocommerce`, `prestashop`, `magento`, `bigcommerce` |
| name | VARCHAR(255) NOT NULL | Nombre descriptivo |
| domain | VARCHAR(255) NOT NULL | dominio de la tienda |
| access_token | TEXT NULL | Cifrado con `encrypted` cast |
| api_key | VARCHAR(255) NULL | Para plataformas con key/secret |
| api_secret | TEXT NULL | Cifrado |
| webhook_token | VARCHAR(64) NOT NULL UNIQUE | Token para verificar webhooks entrantes |
| status | ENUM('pending','active','error','paused') NOT NULL DEFAULT 'pending' | |
| last_synced_at | TIMESTAMP NULL | |
| settings | JSON NULL | Configuración adicional por plataforma |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(user_id)`, `(platform, domain)`, `(webhook_token)`.

---

### remarketing_customers

Perfil unificado de cliente. Un cliente puede comprar en múltiples tiendas; se unifica por email.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| email | VARCHAR(255) NOT NULL | Lowercase normalizado |
| email_hash | CHAR(64) NOT NULL | SHA-256 del email (identity resolution) |
| first_name | VARCHAR(100) NULL | |
| last_name | VARCHAR(100) NULL | |
| phone | VARCHAR(30) NULL | |
| country | CHAR(2) NULL | ISO 3166-1 alpha-2 |
| locale | VARCHAR(10) NULL | `es`, `en`, `pt-BR` |
| external_id | VARCHAR(255) NULL | ID en la plataforma de origen |
| tags | JSON NULL | Array de strings |
| attributes | JSON NULL | Atributos custom |
| consent_marketing | TINYINT(1) NOT NULL DEFAULT 0 | Email marketing |
| consent_confirmed_at | TIMESTAMP NULL | Momento del double opt-in confirm |
| double_optin_token | VARCHAR(128) NULL | Token HMAC pendiente de confirmar |
| double_optin_sent_at | TIMESTAMP NULL | |
| status | ENUM('subscribed','unsubscribed','bounced','complained','pending') NOT NULL DEFAULT 'pending' | |
| unsubscribed_at | TIMESTAMP NULL | |
| rfm_recency | TINYINT UNSIGNED NULL | 1-5 (calculado por job) |
| rfm_frequency | TINYINT UNSIGNED NULL | 1-5 |
| rfm_monetary | TINYINT UNSIGNED NULL | 1-5 |
| clv_historical | DECIMAL(12,2) NULL | Suma total de órdenes |
| orders_count | INT UNSIGNED NOT NULL DEFAULT 0 | |
| last_order_at | TIMESTAMP NULL | |
| birthday | DATE NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(store_id, email)` UNIQUE, `(email_hash)`, `(store_id, status)`, `(store_id, last_order_at)`, `(rfm_recency, rfm_frequency, rfm_monetary)`.

---

### remarketing_products

Catálogo sincronizado de la tienda.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| external_id | VARCHAR(255) NOT NULL | ID en la plataforma |
| sku | VARCHAR(100) NULL | |
| title | VARCHAR(500) NOT NULL | |
| description | TEXT NULL | |
| url | TEXT NOT NULL | |
| image_url | TEXT NULL | |
| price | DECIMAL(12,2) NOT NULL DEFAULT 0 | |
| compare_at_price | DECIMAL(12,2) NULL | Precio tachado |
| currency | CHAR(3) NOT NULL DEFAULT 'EUR' | |
| inventory | INT NOT NULL DEFAULT 0 | |
| vendor | VARCHAR(255) NULL | |
| tags | JSON NULL | |
| collections | JSON NULL | Array de nombres de colección |
| status | ENUM('active','archived','draft') NOT NULL DEFAULT 'active' | |
| metadata | JSON NULL | Datos adicionales de plataforma |
| synced_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(store_id, external_id)` UNIQUE, `(store_id, status)`, `(store_id, inventory)`, `(store_id, price)`.

---

### remarketing_orders

Órdenes sincronizadas. Soft delete no necesario (datos históricos inmutables por audit).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| customer_id | BIGINT UNSIGNED NULL FK→remarketing_customers.id SET NULL | NULL si el cliente fue eliminado |
| external_id | VARCHAR(255) NOT NULL | |
| order_number | VARCHAR(100) NULL | Número legible (`#1001`) |
| status | VARCHAR(50) NOT NULL | `pending`, `completed`, `cancelled`, `refunded` |
| total | DECIMAL(12,2) NOT NULL | |
| subtotal | DECIMAL(12,2) NOT NULL | |
| discount | DECIMAL(12,2) NOT NULL DEFAULT 0 | |
| shipping | DECIMAL(12,2) NOT NULL DEFAULT 0 | |
| tax | DECIMAL(12,2) NOT NULL DEFAULT 0 | |
| currency | CHAR(3) NOT NULL DEFAULT 'EUR' | |
| placed_at | TIMESTAMP NOT NULL | Fecha real de la orden en la plataforma |
| metadata | JSON NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(store_id, external_id)` UNIQUE, `(customer_id, placed_at)`, `(store_id, placed_at)`, `(store_id, status)`.

---

### remarketing_order_items

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| order_id | BIGINT UNSIGNED NOT NULL FK→remarketing_orders.id CASCADE | |
| product_id | BIGINT UNSIGNED NULL FK→remarketing_products.id SET NULL | |
| external_product_id | VARCHAR(255) NULL | Para lookup si product fue eliminado |
| title | VARCHAR(500) NOT NULL | Snapshot del nombre en el momento |
| sku | VARCHAR(100) NULL | |
| quantity | INT UNSIGNED NOT NULL | |
| price | DECIMAL(12,2) NOT NULL | Precio unitario |
| total | DECIMAL(12,2) NOT NULL | |
| image_url | TEXT NULL | |

Índices: `(order_id)`, `(product_id)`.

---

### remarketing_carts

Carritos activos / abandonados. Append-only via webhooks; se marca `abandoned` por job.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| customer_id | BIGINT UNSIGNED NULL FK→remarketing_customers.id SET NULL | NULL si anónimo |
| external_id | VARCHAR(255) NOT NULL | Token del cart en la plataforma |
| visitor_id | VARCHAR(64) NULL | Cookie `_visitor_id` del pixel |
| email | VARCHAR(255) NULL | Email capturado antes de checkout completo |
| items | JSON NOT NULL | Snapshot de líneas del carrito |
| total | DECIMAL(12,2) NOT NULL | |
| currency | CHAR(3) NOT NULL DEFAULT 'EUR' | |
| status | ENUM('active','abandoned','recovered','converted') NOT NULL DEFAULT 'active' | |
| abandoned_at | TIMESTAMP NULL | Momento en que se marcó abandonado |
| recovered_at | TIMESTAMP NULL | Momento de conversión post-recover |
| url | TEXT NULL | URL de recuperación del cart |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(store_id, external_id)` UNIQUE, `(store_id, status, abandoned_at)`, `(customer_id)`, `(visitor_id)`, `(email, store_id)`.

---

### remarketing_events

Tabla de eventos de comportamiento. Alto volumen — considerar particionado por mes (`PARTITION BY RANGE (YEAR(occurred_at)*100 + MONTH(occurred_at))`) en producción con >5M filas/mes.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| customer_id | BIGINT UNSIGNED NULL FK→remarketing_customers.id SET NULL | |
| visitor_id | VARCHAR(64) NULL | Cookie anónima pre-identificación |
| type | VARCHAR(60) NOT NULL | `page_view`, `product_view`, `add_to_cart`, `checkout_start`, `purchase`, `identify`, `placed_order`, `subscription_created` |
| properties | JSON NULL | Payload del evento |
| source | ENUM('pixel','webhook','api','manual') NOT NULL DEFAULT 'webhook' | |
| ip | VARCHAR(45) NULL | |
| user_agent | TEXT NULL | |
| occurred_at | TIMESTAMP NOT NULL | Momento real del evento |
| received_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Momento de inserción |

Sin `updated_at` (append-only). Sin soft delete.

Índices: `(store_id, customer_id, occurred_at)`, `(store_id, type, occurred_at)`, `(visitor_id, occurred_at)`, `(occurred_at)` — para particionado.

---

### remarketing_segments

Definición de segmentos (estáticos y dinámicos).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| name | VARCHAR(255) NOT NULL | |
| description | TEXT NULL | |
| type | ENUM('static','dynamic') NOT NULL DEFAULT 'dynamic' | |
| conditions | JSON NOT NULL | AST de condiciones (ver sección 7) |
| member_count | INT UNSIGNED NOT NULL DEFAULT 0 | Cache actualizado por RecalculateSegmentJob |
| last_calculated_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(store_id)`, `(store_id, type)`.

---

### remarketing_campaigns

Campañas one-off (newsletters, promotions).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| name | VARCHAR(255) NOT NULL | |
| subject | VARCHAR(255) NOT NULL | |
| preheader | VARCHAR(255) NULL | |
| from_name | VARCHAR(100) NOT NULL | |
| from_email | VARCHAR(255) NOT NULL | |
| template_id | BIGINT UNSIGNED NULL FK→remarketing_templates.id SET NULL | |
| segment_id | BIGINT UNSIGNED NULL FK→remarketing_segments.id SET NULL | NULL = toda la lista |
| status | ENUM('draft','scheduled','sending','sent','cancelled','paused') NOT NULL DEFAULT 'draft' | |
| scheduled_at | TIMESTAMP NULL | |
| started_at | TIMESTAMP NULL | |
| completed_at | TIMESTAMP NULL | |
| recipients_total | INT UNSIGNED NOT NULL DEFAULT 0 | |
| sent | INT UNSIGNED NOT NULL DEFAULT 0 | |
| delivered | INT UNSIGNED NOT NULL DEFAULT 0 | |
| bounced | INT UNSIGNED NOT NULL DEFAULT 0 | |
| opened | INT UNSIGNED NOT NULL DEFAULT 0 | |
| clicked | INT UNSIGNED NOT NULL DEFAULT 0 | |
| unsubscribed | INT UNSIGNED NOT NULL DEFAULT 0 | |
| complained | INT UNSIGNED NOT NULL DEFAULT 0 | |
| revenue | DECIMAL(12,2) NOT NULL DEFAULT 0 | Atribuido por click |
| settings | JSON NULL | attribution_window, etc. |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(store_id, status)`, `(store_id, scheduled_at)`, `(template_id)`.

---

### remarketing_automations

Flujos de automatización (welcome, cart_abandoned, etc.).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| name | VARCHAR(255) NOT NULL | |
| trigger | VARCHAR(60) NOT NULL | `welcome`, `cart_abandoned`, `post_purchase`, `win_back`, `browse_abandoned` |
| trigger_config | JSON NULL | delay_hours, days_inactive, etc. |
| status | ENUM('active','paused','draft') NOT NULL DEFAULT 'draft' | |
| runs_total | INT UNSIGNED NOT NULL DEFAULT 0 | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(store_id, trigger, status)`.

---

### remarketing_automation_steps

Pasos de cada automatización (step lineal en MVP).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| automation_id | BIGINT UNSIGNED NOT NULL FK→remarketing_automations.id CASCADE | |
| sort_order | TINYINT UNSIGNED NOT NULL DEFAULT 0 | Orden de ejecución |
| type | ENUM('wait','send_email') NOT NULL | Tipos v1; en v2 añadir `condition`, `tag`, `sms` |
| config | JSON NOT NULL | Para `wait`: `{hours: 24}`. Para `send_email`: `{template_id, subject, from_name, from_email}` |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(automation_id, sort_order)`.

---

### remarketing_automation_runs

Instancia de una automatización ejecutándose para un cliente específico.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| automation_id | BIGINT UNSIGNED NOT NULL FK→remarketing_automations.id CASCADE | |
| customer_id | BIGINT UNSIGNED NOT NULL FK→remarketing_customers.id CASCADE | |
| current_step | TINYINT UNSIGNED NOT NULL DEFAULT 0 | |
| status | ENUM('running','completed','cancelled','failed') NOT NULL DEFAULT 'running' | |
| context | JSON NULL | cart_id, order_id, etc. pasado al trigger |
| next_step_at | TIMESTAMP NULL | Cuándo ejecutar el siguiente step |
| started_at | TIMESTAMP NOT NULL | |
| completed_at | TIMESTAMP NULL | |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(automation_id, customer_id)` UNIQUE (evita runs duplicados), `(status, next_step_at)`, `(customer_id)`.

---

### remarketing_templates

Plantillas de email.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NULL FK→remarketing_stores.id SET NULL | NULL = template global |
| name | VARCHAR(255) NOT NULL | |
| type | ENUM('campaign','automation','transactional') NOT NULL DEFAULT 'campaign' | |
| subject | VARCHAR(255) NULL | Subject por defecto |
| preheader | VARCHAR(255) NULL | |
| html_content | LONGTEXT NOT NULL | HTML compilado (MJML → HTML o HTML puro) |
| json_content | JSON NULL | Fuente editable del template |
| thumbnail_url | TEXT NULL | Preview generado |
| is_global | TINYINT(1) NOT NULL DEFAULT 0 | Template de sistema (no editable) |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |
| deleted_at | TIMESTAMP NULL | Soft delete |

Índices: `(store_id)`, `(type, is_global)`.

---

### remarketing_messages

Registro de cada email enviado (individual). Alto volumen secundario.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| customer_id | BIGINT UNSIGNED NULL FK→remarketing_customers.id SET NULL | |
| campaign_id | BIGINT UNSIGNED NULL FK→remarketing_campaigns.id SET NULL | |
| automation_run_id | BIGINT UNSIGNED NULL FK→remarketing_automation_runs.id SET NULL | |
| email | VARCHAR(255) NOT NULL | Snapshot del email al momento del envío |
| subject | VARCHAR(255) NOT NULL | |
| status | ENUM('queued','sent','delivered','opened','clicked','bounced','complained','failed','suppressed') NOT NULL DEFAULT 'queued' | |
| provider | VARCHAR(30) NULL | `ses`, `sendgrid`, `mailgun` |
| provider_message_id | VARCHAR(255) NULL | ID del proveedor para tracking |
| open_token | VARCHAR(64) NOT NULL UNIQUE | Para pixel de apertura |
| click_token | VARCHAR(64) NOT NULL UNIQUE | Para redirect de clicks |
| opened_at | TIMESTAMP NULL | |
| clicked_at | TIMESTAMP NULL | |
| bounced_at | TIMESTAMP NULL | |
| bounce_type | ENUM('hard','soft') NULL | |
| complained_at | TIMESTAMP NULL | |
| sent_at | TIMESTAMP NULL | |
| revenue | DECIMAL(12,2) NOT NULL DEFAULT 0 | Atribuido |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Índices: `(store_id, status)`, `(store_id, sent_at)`, `(customer_id, sent_at)`, `(campaign_id)`, `(automation_run_id)`, `(open_token)`, `(click_token)`, `(provider_message_id)`.

---

### remarketing_suppressions

Lista de supresión global por store.

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| email | VARCHAR(255) NOT NULL | |
| reason | ENUM('hard_bounce','complaint','manual','gdpr_delete','unsubscribe') NOT NULL | |
| source_message_id | BIGINT UNSIGNED NULL FK→remarketing_messages.id SET NULL | |
| notes | TEXT NULL | |
| created_at | TIMESTAMP | |

Sin `updated_at` (append-only). Sin soft delete.

Índices: `(store_id, email)` UNIQUE.

---

### remarketing_consent_events

Audit trail inmutable de consentimiento. No se borra nunca (retención legal).

| Columna | Tipo | Notas |
|---|---|---|
| id | BIGINT UNSIGNED AUTO_INCREMENT PK | |
| store_id | BIGINT UNSIGNED NOT NULL FK→remarketing_stores.id CASCADE | |
| customer_id | BIGINT UNSIGNED NULL FK→remarketing_customers.id SET NULL | NULL si aún no hay perfil |
| email | VARCHAR(255) NOT NULL | Snapshot |
| event_type | ENUM('granted','confirmed','withdrawn','bounced','complained','imported') NOT NULL | |
| source | ENUM('popup','checkout','api','import','admin','double_optin_confirm') NOT NULL | |
| ip | VARCHAR(45) NULL | |
| user_agent | TEXT NULL | |
| form_url | TEXT NULL | |
| policy_version | VARCHAR(20) NULL | Version del texto de privacidad al momento |
| occurred_at | TIMESTAMP NOT NULL | |
| created_at | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | |

Sin `updated_at`. Sin soft delete. Sin borrado permitido (GDPR exige retener el audit trail incluso si se borra el perfil).

Índices: `(store_id, email, occurred_at)`, `(customer_id, occurred_at)`, `(occurred_at)`.

---

## 5. Conectores ecommerce

Interface base en `modules/Remarketing/app/Contracts/EcommerceConnector.php`:

```php
interface EcommerceConnector
{
    public function platform(): string;
    public function authenticate(array $credentials): bool;
    public function verifyWebhook(Request $request): bool;
    public function subscribeWebhooks(string $callbackBase): array;
    public function syncCatalog(callable $onChunk): void;
    public function syncCustomers(callable $onChunk): void;
    public function syncOrders(callable $onChunk, ?Carbon $since = null): void;
    public function handleWebhook(string $topic, array $payload): EventDTO;
}
```

`EventDTO` es un Value Object en `modules/Remarketing/app/DTOs/EventDTO.php` con `type`, `externalId`, `storeId`, `properties`, `occurredAt`.

---

### 5.1 Shopify

**Archivo:** `modules/Remarketing/app/Connectors/Shopify/ShopifyConnector.php`

**Auth:** API key privada (token) almacenada cifrada en `remarketing_stores.access_token`. En v2 se añade OAuth para apps públicas.

**Librería PHP:** `Shopify/shopify-api-php` (`shopify/shopify-api`) — ya disponible o añadir a `composer.json`.

**Sync inicial:**
- Catálogo: GraphQL Bulk Operation (`productSet` query → JSONL async). Job `SyncCatalogJob` con chunks de 250.
- Clientes: REST `/customers.json` con paginación cursor. Job `SyncCustomersJob`.
- Órdenes: REST `/orders.json?status=any` desde `created_at_min`. Job `SyncOrdersJob`.

**Sync incremental:** webhooks suscritos al conectar la tienda.

**Webhooks soportados (MVP):**
- `orders/create`, `orders/updated`
- `customers/create`, `customers/update`
- `customers/email_marketing_consent/update`
- `checkouts/create`, `checkouts/update`
- `products/update`
- `inventory_levels/update`

**Verificación:** `X-Shopify-Hmac-Sha256` header. El `ShopifyConnector::verifyWebhook()` valida HMAC-SHA256 con el `api_secret` del store antes de poner en cola.

**Carrito abandonado:** Shopify envía `checkouts/create` con email. Si 60 min después no existe una orden con el mismo checkout token, se marca `abandoned`.

---

### 5.2 WooCommerce

**Archivo:** `modules/Remarketing/app/Connectors/WooCommerce/WooCommerceConnector.php`

**Auth:** Consumer Key + Consumer Secret almacenados cifrados.

**Librería PHP:** `automattic/woocommerce` o Guzzle directo (Guzzle ya está en el proyecto).

**Sync inicial:** REST API v3 `/wp-json/wc/v3/products`, `/customers`, `/orders` con paginación `page`/`per_page=100`.

**Sync incremental:** webhooks WooCommerce (`woocommerce_webhook_payload` filter).

**Webhooks soportados (MVP):**
- `order.created`, `order.updated`
- `customer.created`, `customer.updated`
- `product.updated`

**Carrito abandonado:** WooCommerce no tiene webhook nativo de cart. Solución: plugin PHP mínimo (`remarketing-woo-bridge.php`) a instalar en la tienda WooCommerce que:
1. Hook `woocommerce_add_to_cart` → POST a `/r/webhooks/woocommerce/{store_token}` con payload `{topic: "cart.updated", ...}`.
2. Hook `woocommerce_cart_emptied` / `woocommerce_checkout_order_processed` → POST `{topic: "cart.converted"}`.

El plugin se genera como asset descargable desde la UI de Remarketing.

**Verificación webhook:** consumer secret como HMAC-SHA256 en header `X-WC-Webhook-Signature`.

---

### 5.3 PrestaShop (fase 2)

**Archivo:** `modules/Remarketing/app/Connectors/PrestaShop/PrestaShopConnector.php`

**Auth:** API key del Webservice PrestaShop. Webservice REST habilitado manualmente en el back-office PS.

**Sync:** REST XML/JSON paginado (`/api/products`, `/api/customers`, `/api/orders`).

**Carritos:** hook `actionCartSave` + módulo PHP de bridge (similar al de WooCommerce).

**Notas:** Compatibilidad PS 1.7/8.x heterogénea. En MVP no se implementa; se incluye el contrato de interface para facilitar la extensión en v2.

---

### 5.4 Magento (fase 3)

**Archivo:** `modules/Remarketing/app/Connectors/Magento/MagentoConnector.php`

REST API + Observer/Plugin para abandoned cart. Requiere módulo Magento instalable. Solo se implementa ante demanda enterprise concreta.

---

### 5.5 BigCommerce (fase 3)

**Archivo:** `modules/Remarketing/app/Connectors/BigCommerce/BigCommerceConnector.php`

REST API + webhooks ligeros (payloads solo IDs, requieren hidratación posterior). Misma lógica que Shopify pero sin bulk operations.

---

**Roadmap de conectores:**
- MVP: Shopify + WooCommerce.
- Fase 2: PrestaShop (diferenciador regional España/Francia/LATAM).
- Fase 3: Magento + BigCommerce (solo si hay demanda enterprise concreta).

---

## 6. Pixel de tracking

### Endpoint de ingesta

**Ruta:** `POST /r/track`
**Middleware:** `['web', 'throttle:120,1']` (sin auth; 120 req/min por IP)
**Controller:** `modules/Remarketing/app/Http/Controllers/Public/TrackController.php`
**Acción:** Valida `store_token` + `event_type`. Responde `200 OK` en <50ms. Despacha `ProcessPixelEventJob` a cola `remarketing`.

### Bundle JS

**Archivo:** `public/remarketing/pixel.js` (compilado vía Vite, fuente en `modules/Remarketing/resources/assets/pixel/`).

**Inclusión en la tienda:**
```html
<script>
window._rmk = window._rmk || [];
window._rmk.push(['store', 'STORE_TOKEN']);
</script>
<script src="https://app.example.com/remarketing/pixel.js" async></script>
```

**Cookie:** `_rmk_vid` (UUID v4, `SameSite=Lax`, 365 días, dominio del cliente via first-party). Almacena el visitor ID anónimo.

**Identity resolution:** cuando el pixel recibe un evento `identify` (email del form de newsletter, login, checkout), hace `POST /r/track` con `{type: "identify", email: "...", visitor_id: "..."}`. El job `ProcessPixelEventJob` busca o crea el customer y asocia todos los eventos históricos del `visitor_id` al `customer_id`.

**Eventos capturados:**

| Evento | Trigger JS | Properties |
|---|---|---|
| `page_view` | `DOMContentLoaded` | `{url, title, referrer}` |
| `product_view` | Página de producto | `{product_id, title, price, url}` |
| `add_to_cart` | Click en añadir al carrito | `{product_id, variant_id, quantity, price}` |
| `checkout_start` | Inicio de checkout | `{cart_total, items_count}` |
| `identify` | Email capturado | `{email, first_name}` |

La plataforma completa el `purchase` event vía webhook del servidor (más confiable que JS para este evento crítico).

---

## 7. Motor de segmentación

### Estructura JSON de condiciones

```json
{
  "operator": "AND",
  "conditions": [
    {
      "type": "property",
      "field": "country",
      "operator": "equals",
      "value": "ES"
    },
    {
      "type": "event",
      "event": "placed_order",
      "operator": "at_least",
      "count": 2,
      "window_days": 90
    },
    {
      "operator": "OR",
      "conditions": [
        {
          "type": "rfm",
          "field": "rfm_recency",
          "operator": "gte",
          "value": 4
        },
        {
          "type": "property",
          "field": "tags",
          "operator": "contains",
          "value": "vip"
        }
      ]
    }
  ]
}
```

### SegmentService

`modules/Remarketing/app/Services/SegmentService.php` — compila el JSON AST a un query Eloquent contra `remarketing_customers` con `EXISTS` correlacionados sobre `remarketing_events`. Devuelve `Collection` de customer IDs.

### RecalculateSegmentJob

`modules/Remarketing/app/Jobs/RecalculateSegmentJob.php` — recalcula `member_count` y actualiza `remarketing_segments.last_calculated_at`. Cola `remarketing`. Disparado:
- Al guardar/actualizar un segmento.
- Por el scheduler diario a las 03:00 para todos los segmentos activos.

**Cache Redis:** para segmentos dinámicos usados frecuentemente, mantener un Redis Set `rmk:seg:{id}:members` con los customer IDs actuales. La invalidación ocurre cuando `RecalculateSegmentJob` termina de recomputar.

**Escala:** SQL puro escala bien hasta ~500k clientes. Más allá de eso se requiere un motor dedicado (ClickHouse), que es fuera de alcance v1.

---

## 8. Motor de automations

### Arquitectura

Cada `remarketing_automations` es una plantilla de flow. Cuando se detecta el trigger, se crea un `remarketing_automation_runs` para el customer específico. `EvaluateAutomationJob` procesa los runs pendientes (`status=running AND next_step_at <= NOW()`), ejecuta el step actual, avanza al siguiente o marca `completed`.

El scheduler ejecuta `remarketing:process-automations` cada minuto (Artisan Command que despacha `EvaluateAutomationJob` por cada run pendiente).

### Triggers MVP

**welcome**
- Trigger: `remarketing_consent_events.event_type = 'confirmed'` (double opt-in confirmado).
- Detección: observer en `ConsentEvent::created()` con `event_type = confirmed`.
- Steps típicos: wait(0h) → send_email(bienvenida), wait(48h) → send_email(best products), wait(120h) → send_email(first purchase discount).

**cart_abandoned**
- Trigger: `remarketing_carts.status` cambia a `abandoned`. El job `MarkAbandonedCartsJob` (schedule cada 15 min) marca como `abandoned` los carts con `status=active AND updated_at < NOW()-60min` sin orden asociada.
- Detección: `MarkAbandonedCartsJob` despacha `TriggerAutomationJob` para cada cart marcado que tenga email.
- Steps típicos: wait(60min) → send_email(carrito 1), wait(23h) → send_email(carrito 2 con urgencia), wait(48h) → send_email(carrito 3 con descuento).
- Guard: no disparar si el customer ya convirtió en ese periodo.

**post_purchase**
- Trigger: `remarketing_orders` `status = completed` + `order_number = 1` (primer pedido) o cualquier pedido.
- Detección: observer `Order::created()` / webhook handler que dispara `TriggerAutomationJob`.
- Steps típicos: wait(0h) → send_email(confirmación/gracias), wait(14d) → send_email(pedir review), wait(30d) → send_email(cross-sell).

**win_back**
- Trigger: `CalculateRfmJob` detecta customer con `last_order_at < NOW()-90d AND rfm_frequency >= 2` (clientes que compraron antes pero están inactivos).
- Detección: `CalculateRfmJob` (schedule diario) despacha `TriggerAutomationJob` para nuevos candidatos. Guard con Redis Set para no re-disparar si ya hay un run activo del mismo trigger para ese customer.
- Steps típicos: wait(0h) → send_email(volvemos a verte), wait(15d) → send_email(oferta exclusiva).

### Steps configurables en UI

Cada step tiene `type` (wait / send_email) y `config` JSON editable desde la pantalla de edición del automation en `panel/remarketing/automations/{id}/edit`.

---

## 9. Envío y deliverability

### Proveedor de envío

Se usa el `ProviderManager` del módulo Mailrelay (ver sección 3.2). El `SendEmailJob` inyecta `ProviderManager` y delega el envío al provider configurado en la store (`remarketing_stores.settings.email_provider`). Si no está configurado, usa el provider por defecto del sistema.

### DKIM/SPF/DMARC

Wizard de setup de tienda incluye un `DeliverabilityCheckerService` que consulta DNS del dominio del sender y verifica:
- SPF: `TXT v=spf1 include:...` presente.
- DKIM: selector `rmk._domainkey` presente.
- DMARC: `TXT v=DMARC1; p=none; rua=...` presente.

Devuelve semáforo (verde/amarillo/rojo) mostrado en la pantalla de settings de la tienda.

### One-click List-Unsubscribe (RFC 8058)

Obligatorio desde 2024 para Gmail/Yahoo, 2025 para Microsoft. Todos los emails de marketing incluyen:

```
List-Unsubscribe: <https://app.example.com/r/unsubscribe/{token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

El token es HMAC-SHA256 del `message_id` + `customer_id`. El endpoint `GET /r/unsubscribe/{token}` procesa el unsub inmediatamente sin requerir login.

Los emails marcados como `is_transactional = true` en el template no llevan estos headers.

### Bounce/complaint handlers

El `ProviderManager` de Mailrelay ya gestiona webhooks de bounce/complaint (SES SNS, SendGrid Event Webhook, Mailgun Webhooks). Se extiende para que al recibir un bounce hard o complaint para un email de Remarketing:
1. Se actualiza `remarketing_messages.status`.
2. Se añade a `remarketing_suppressions`.
3. Se actualiza `remarketing_customers.status`.
4. Se registra en `remarketing_consent_events` con `event_type = bounced/complained`.

### Tracking

- **Opens:** cada email incluye `<img src="/r/open/{open_token}.gif" width="1" height="1">`. El endpoint `RegisterOpenJob` registra el open si no fue registrado en los últimos 30 min (deduplicación). Se expone "MPP-aware open rate" como KPI separado (opens que ocurrieron en <5 segundos de `sent_at` se marcan `probable_machine_open`).
- **Clicks:** todos los links en el email se reescriben a `/r/click/{message_id}/{link_hash}`. El endpoint redirige al destino y despacha `RegisterClickJob`.

### Cola `remarketing`

Horizon supervisor nuevo en `config/horizon.php` (ver sección 16). Rate limiting: el `SendEmailJob` verifica el rate limit del ESP configurado antes de procesar (usando Redis counter con TTL de 1 segundo). Si supera el límite, el job hace `$this->release(1)` para reintentar en 1 segundo.

### MJML (decisión)

Se usa **HTML Blade puro con tabla-based layout** en MVP, no MJML. MJML añade una dependencia Node y un paso de build que complica el deploy en el entorno actual (Herd). Los templates siguen el patrón de email responsivo con tablas (`<table role="presentation">`). Si en v2 los clientes exigen un editor drag-and-drop avanzado, se evalúa MJML con `mailpace/mjml-laravel` o Bee Free embed.

---

## 10. API

### 10.1 Privada (auth:sanctum)

Todas bajo middleware `['api', 'auth:sanctum', 'throttle:60,1']`, prefix `api/remarketing`, name `api.remarketing.`.

| Verbo | Ruta | Controller | Método | Propósito |
|---|---|---|---|---|
| GET | `api/remarketing/stores` | `StoreApiController` | `index` | Listar tiendas del usuario |
| POST | `api/remarketing/stores` | `StoreApiController` | `store` | Conectar tienda |
| GET | `api/remarketing/stores/{id}` | `StoreApiController` | `show` | Detalle de tienda |
| PUT | `api/remarketing/stores/{id}` | `StoreApiController` | `update` | Actualizar configuración |
| DELETE | `api/remarketing/stores/{id}` | `StoreApiController` | `destroy` | Desconectar tienda |
| POST | `api/remarketing/stores/{id}/sync` | `StoreApiController` | `sync` | Forzar sync completo |
| GET | `api/remarketing/stores/{id}/health` | `StoreApiController` | `health` | Estado webhooks y sync |
| GET | `api/remarketing/customers` | `CustomerApiController` | `index` | Listar customers con filtros |
| GET | `api/remarketing/customers/{id}` | `CustomerApiController` | `show` | Perfil de customer |
| PUT | `api/remarketing/customers/{id}` | `CustomerApiController` | `update` | Actualizar perfil |
| DELETE | `api/remarketing/customers/{id}` | `CustomerApiController` | `destroy` | DSR delete |
| GET | `api/remarketing/customers/{id}/events` | `CustomerApiController` | `events` | Timeline de eventos |
| GET | `api/remarketing/segments` | `SegmentApiController` | `index` | Listar segmentos |
| POST | `api/remarketing/segments` | `SegmentApiController` | `store` | Crear segmento |
| GET | `api/remarketing/segments/{id}` | `SegmentApiController` | `show` | Detalle |
| PUT | `api/remarketing/segments/{id}` | `SegmentApiController` | `update` | Actualizar |
| DELETE | `api/remarketing/segments/{id}` | `SegmentApiController` | `destroy` | Eliminar |
| GET | `api/remarketing/segments/{id}/preview` | `SegmentApiController` | `preview` | Preview de miembros |
| GET | `api/remarketing/campaigns` | `CampaignApiController` | `index` | Listar campañas |
| POST | `api/remarketing/campaigns` | `CampaignApiController` | `store` | Crear campaña |
| GET | `api/remarketing/campaigns/{id}` | `CampaignApiController` | `show` | Detalle |
| PUT | `api/remarketing/campaigns/{id}` | `CampaignApiController` | `update` | Actualizar |
| DELETE | `api/remarketing/campaigns/{id}` | `CampaignApiController` | `destroy` | Eliminar |
| POST | `api/remarketing/campaigns/{id}/schedule` | `CampaignApiController` | `schedule` | Programar |
| POST | `api/remarketing/campaigns/{id}/send-test` | `CampaignApiController` | `sendTest` | Enviar prueba |
| POST | `api/remarketing/campaigns/{id}/cancel` | `CampaignApiController` | `cancel` | Cancelar |
| GET | `api/remarketing/campaigns/{id}/stats` | `CampaignApiController` | `stats` | Métricas |
| GET | `api/remarketing/automations` | `AutomationApiController` | `index` | Listar |
| POST | `api/remarketing/automations` | `AutomationApiController` | `store` | Crear |
| GET | `api/remarketing/automations/{id}` | `AutomationApiController` | `show` | Detalle |
| PUT | `api/remarketing/automations/{id}` | `AutomationApiController` | `update` | Actualizar |
| DELETE | `api/remarketing/automations/{id}` | `AutomationApiController` | `destroy` | Eliminar |
| POST | `api/remarketing/automations/{id}/activate` | `AutomationApiController` | `activate` | Activar |
| POST | `api/remarketing/automations/{id}/pause` | `AutomationApiController` | `pause` | Pausar |
| GET | `api/remarketing/templates` | `TemplateApiController` | `index` | Listar templates |
| POST | `api/remarketing/templates` | `TemplateApiController` | `store` | Crear |
| GET | `api/remarketing/templates/{id}` | `TemplateApiController` | `show` | Detalle |
| PUT | `api/remarketing/templates/{id}` | `TemplateApiController` | `update` | Actualizar |
| DELETE | `api/remarketing/templates/{id}` | `TemplateApiController` | `destroy` | Eliminar |
| POST | `api/remarketing/templates/{id}/preview` | `TemplateApiController` | `preview` | Renderizar preview |
| GET | `api/remarketing/suppressions` | `SuppressionApiController` | `index` | Listar supresiones |
| POST | `api/remarketing/suppressions` | `SuppressionApiController` | `store` | Añadir manualmente |
| DELETE | `api/remarketing/suppressions/{id}` | `SuppressionApiController` | `destroy` | Eliminar |
| POST | `api/remarketing/dsr/export` | `DsrApiController` | `export` | GDPR export |
| POST | `api/remarketing/dsr/delete` | `DsrApiController` | `delete` | GDPR deletion |

### 10.2 Pública (sin auth, throttled)

Middleware `['web', 'throttle:120,1']` para tracking; `['web', 'throttle:10,1']` para unsub.

| Verbo | Ruta | Controller | Propósito |
|---|---|---|---|
| POST | `/r/track` | `TrackController@track` | Ingesta de eventos pixel JS |
| GET | `/r/unsubscribe/{token}` | `UnsubscribeController@handle` | One-click unsub RFC 8058 |
| POST | `/r/unsubscribe/{token}` | `UnsubscribeController@handle` | One-click unsub POST (RFC 8058) |
| GET | `/r/preferences/{token}` | `PreferencesController@show` | Centro de preferencias |
| POST | `/r/preferences/{token}` | `PreferencesController@update` | Guardar preferencias |
| GET | `/r/open/{message_id}.gif` | `TrackingController@open` | Pixel de apertura (GIF 1x1) |
| GET | `/r/click/{message_id}/{link_hash}` | `TrackingController@click` | Redirect de click |
| POST | `/r/webhooks/shopify/{store_token}` | `WebhookController@shopify` | Webhooks entrantes Shopify |
| POST | `/r/webhooks/woocommerce/{store_token}` | `WebhookController@woocommerce` | Webhooks entrantes WooCommerce |
| POST | `/r/webhooks/email-events/{provider}` | `WebhookController@emailEvents` | Bounces/complaints del ESP |

Los webhooks de plataformas verifican HMAC antes de poner en cola; responden `200 OK` en <200ms.

---

## 11. UI (Blade + jQuery + Bootstrap 5.3 + DevExpress)

### Panel principal (`panel/remarketing/`)

| Página | Blade | Ruta nombrada | Descripción |
|---|---|---|---|
| Dashboard | `modules/Remarketing/resources/views/dashboard/index.blade.php` | `remarketing.dashboard` | KPIs globales + gráfico de revenue por mes (dxChart) |
| Tiendas — listado | `modules/Remarketing/resources/views/stores/index.blade.php` | `remarketing.stores.index` | Tabla de tiendas con estado de sync y webhooks |
| Tiendas — conectar | `modules/Remarketing/resources/views/stores/create.blade.php` | `remarketing.stores.create` | Wizard: selección de plataforma + credenciales + verificación DKIM |
| Tiendas — editar | `modules/Remarketing/resources/views/stores/edit.blade.php` | `remarketing.stores.edit` | Configuración avanzada de tienda |
| Clientes — listado | `modules/Remarketing/resources/views/customers/index.blade.php` | `remarketing.customers.index` | Tabla con filtros por status/RFM/tags + exportar |
| Clientes — perfil | `modules/Remarketing/resources/views/customers/show.blade.php` | `remarketing.customers.show` | Perfil unificado: datos, consent, eventos, mensajes recibidos |
| Productos — listado | `modules/Remarketing/resources/views/products/index.blade.php` | `remarketing.products.index` | Catálogo sincronizado con búsqueda y filtros |
| Carritos abandonados | `modules/Remarketing/resources/views/carts/index.blade.php` | `remarketing.carts.index` | Listado de carts con estado y revenue recuperado |
| Segmentos — listado | `modules/Remarketing/resources/views/segments/index.blade.php` | `remarketing.segments.index` | Segmentos con member count y tipo |
| Segmentos — crear/editar | `modules/Remarketing/resources/views/segments/form.blade.php` | `remarketing.segments.create` / `edit` | Constructor visual de condiciones (jQuery dinámico) |
| Campañas — listado | `modules/Remarketing/resources/views/campaigns/index.blade.php` | `remarketing.campaigns.index` | Campañas con estado, scheduled_at, stats |
| Campañas — crear/editar | `modules/Remarketing/resources/views/campaigns/form.blade.php` | `remarketing.campaigns.create` / `edit` | Formulario: nombre, asunto, template, segmento, programación |
| Automations — listado | `modules/Remarketing/resources/views/automations/index.blade.php` | `remarketing.automations.index` | Automations con trigger, status, runs_total |
| Automations — editar | `modules/Remarketing/resources/views/automations/edit.blade.php` | `remarketing.automations.edit` | Editor lineal de steps (jQuery add/remove) |
| Templates — listado | `modules/Remarketing/resources/views/templates/index.blade.php` | `remarketing.templates.index` | Templates con thumbnail preview |
| Templates — crear/editar | `modules/Remarketing/resources/views/templates/form.blade.php` | `remarketing.templates.create` / `edit` | Editor HTML con preview en iframe |
| Reportes | `modules/Remarketing/resources/views/reports/index.blade.php` | `remarketing.reports.index` | Dashboard de métricas por campaña/automation (dxDataGrid + dxChart) |

### Panel de settings (`panel/settings/remarketing/`)

| Página | Blade | Ruta nombrada | Descripción |
|---|---|---|---|
| General | `modules/Remarketing/resources/views/settings/general.blade.php` | `settings.remarketing.general` | Provider de envío, sender por defecto, dominio de tracking |
| Consent | `modules/Remarketing/resources/views/settings/consent.blade.php` | `settings.remarketing.consent` | Textos legales de opt-in por idioma, policy_version |

**Nota sobre views adicionales (partials):**
- `modules/Remarketing/resources/views/partials/condition-row.blade.php` — fila reutilizable del constructor de segmentos.
- `modules/Remarketing/resources/views/partials/step-row.blade.php` — fila de step en el editor de automations.

Total aproximado de vistas Blade: **22 archivos** (17 páginas principales + partials + emails).

---

## 12. Permisos Spatie

Todos con `guard_name = 'web'`. Convención: `remarketing.{action}` o `remarketing.{entity}.{action}`.

```
remarketing.view                        (acceso general al módulo)
remarketing.manage                      (super-permiso, bypassa ownership)
remarketing.settings.view
remarketing.settings.update

remarketing.stores.view
remarketing.stores.create
remarketing.stores.update
remarketing.stores.delete

remarketing.customers.view
remarketing.customers.create
remarketing.customers.update
remarketing.customers.delete
remarketing.customers.export

remarketing.products.view

remarketing.segments.view
remarketing.segments.create
remarketing.segments.update
remarketing.segments.delete

remarketing.campaigns.view
remarketing.campaigns.create
remarketing.campaigns.update
remarketing.campaigns.delete
remarketing.campaigns.send

remarketing.automations.view
remarketing.automations.create
remarketing.automations.update
remarketing.automations.delete

remarketing.templates.view
remarketing.templates.create
remarketing.templates.update
remarketing.templates.delete

remarketing.reports.view

remarketing.suppressions.view
remarketing.suppressions.manage

remarketing.dsr.export
remarketing.dsr.delete
```

Total: **34 permisos**. Definidos en `modules/Remarketing/database/seeders/RemarketingPermissionsSeeder.php` con `Permission::firstOrCreate()`.

---

## 13. Compliance

### Double opt-in

Al crear un subscriber (vía popup, checkout opt-in, API import), el sistema:
1. Crea el `remarketing_customer` con `status = pending`, `consent_marketing = 0`.
2. Genera `double_optin_token` (HMAC-SHA256, expira en 7 días).
3. Envía email de confirmación con enlace `GET /r/optin/{token}`.
4. Al confirmar: `status = subscribed`, `consent_marketing = 1`, `consent_confirmed_at = now()`, inserta en `remarketing_consent_events` con `event_type = confirmed`.
5. Si el token expira sin confirmar: job nocturno limpia los `pending` > 7 días.

Geofence: si `customer.country` está en la UE o Brasil, el double opt-in es **obligatorio**. En países CAN-SPAM (US) es opcional pero activado por defecto.

### Consent audit trail

`remarketing_consent_events` es append-only. Nunca se borra, ni cuando se elimina el perfil (DSR delete anonimiza el `email` pero mantiene el registro con `email = 'deleted@gdpr.request'`).

### DSR endpoints (GDPR/LGPD)

`POST /api/remarketing/dsr/export` — devuelve JSON con todos los datos del customer: perfil, eventos, mensajes, consent_events. Procesado de forma asíncrona vía job; resultado disponible en 24h.

`POST /api/remarketing/dsr/delete` — anonimiza email en todas las tablas, elimina soft-delete el perfil, inserta `consent_events` con `event_type = withdrawn`. No borra el `consent_events` histórico.

### Suppression sincronizada entre tiendas

Una dirección en `remarketing_suppressions` bloquea el envío para esa tienda. Si la misma dirección intenta suscribirse a otra tienda del mismo `user_id`, el ConsentService verifica la suppression antes de crear el perfil.

### Unsubscribe en <1s

El endpoint `/r/unsubscribe/{token}` es síncrono: verifica HMAC, actualiza `remarketing_customers.status = unsubscribed`, inserta en `remarketing_suppressions` y `remarketing_consent_events`, todo en una transacción. Sin jobs intermedios para este flujo.

### Footer obligatorio en emails

Todos los templates incluyen un partial Blade con dirección física del remitente y link de unsub. El `SendEmailJob` rechaza el envío si el HTML compilado no contiene la cadena `{{UNSUBSCRIBE_URL}}` (excepto templates con `is_transactional = true`).

---

## 14. Roadmap por fases

### Fase 0 — MVP (10-12 semanas)

- Scaffolding del módulo: ServiceProvider, rutas, config, migrations, permisos.
- Modelo de datos: 13 tablas con migraciones y factories.
- Contratos e interface `EcommerceConnector`.
- Conector Shopify: auth, sync inicial (catálogo + customers + orders), webhooks, abandoned cart.
- Conector WooCommerce: auth, sync inicial, webhooks, plugin de bridge para cart.
- `ProfileService`, `ConsentService`, suppression list.
- Double opt-in completo con geofence EU/Brasil.
- `SegmentService` con builder estático y dinámico básico (property + event count).
- `RecalculateSegmentJob` + schedule diario.
- `CalculateRfmJob` + schedule diario.
- Campañas one-off: CRUD + schedule + send + cancel + stats.
- 4 automations pre-construidas (welcome, cart_abandoned, post_purchase, win_back).
- `EvaluateAutomationJob` + schedule cada minuto.
- `MarkAbandonedCartsJob` + schedule cada 15 min.
- `SendEmailJob` con `ProviderManager` de Mailrelay, headers RFC 8058, rate limiting.
- Tracking open/click + KPIs básicos.
- `DeliverabilityCheckerService` (DKIM/SPF/DMARC).
- DSR endpoints export + delete.
- Pixel JS embebible con 5 eventos y identity resolution.
- UI completa: 17 páginas Blade.
- API privada y pública (sección 10).
- Cola `remarketing` + `remarketing-webhooks` en Horizon.
- Tests PHPUnit: happy path, autorización, compliance basics.
- Seeder de permisos.

### Fase 1 — Should-have (+6-8 semanas)

- Conector PrestaShop (diferenciador regional).
- Browse abandonment automation.
- Back-in-stock flow + tabla `remarketing_stock_alerts`.
- Price drop flow.
- A/B testing: subject + holdout 5% + winner detection.
- Editor visual de flows con branching (yes/no splits, wait variable, condition).
- SMS via Twilio: opt-in, TCPA geofence US, quiet hours.
- Predictive segments: CLV bucket (RFM), churn risk (logistic regression PHP simple), next order ETA.
- Cohort analysis en Reportes.
- Webhook health dashboard por tienda.
- Send-test mejorado con inbox preview.

### Fase 2 — Nice-to-have

- Conectores Magento y BigCommerce.
- WhatsApp Business API (via Twilio/360dialog).
- Web push (VAPID/ServiceWorker).
- Send-time optimization por usuario.
- Dynamic content blocks.
- AI subject line generator (OpenAI/Anthropic).
- Multi-touch attribution configurable.
- Replenishment flows.
- ClickHouse para eventos >50M filas/mes.

---

## 15. Riesgos técnicos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Deliverability: cuenta abusiva contamina IP compartida | Alto — Gmail puede bloquear todo el dominio | Threshold automático: suspender store si complaint rate >0.3% o bounce rate >2%. IP dedicada para stores >100k sends/mes. Sub-dominio separado por store plan. |
| Webhooks Shopify no confiables (retry 48h, 19 fallos = unsub) | Alto — perfiles con datos stale | Reconciliación nocturna completa. Dashboard de webhook health. Re-suscripción automática al detectar unsub de Shopify. |
| Sincronización de catálogo grande (100k+ SKUs) satura API | Medio-alto — host de la tienda puede caer | Bulk operations Shopify GraphQL. Throttle adaptativo con `sleep()` entre chunks. Job resumable con cursor persistido en Redis. |
| Segmentación dinámica costosa en tiendas grandes (>500k clientes) | Medio | Redis Sets para cache de membership. Evaluar incrementalmente solo los segments que dependen del tipo de evento recibido. Límite de complejidad del AST (máx 5 condiciones anidadas en MVP). |
| Editor visual de flows (UX) lleva meses de ingeniería | Bajo (MVP es lineal) | MVP: steps lineales pre-construidos. v2: evaluar `litegraph.js` o similar antes de construir desde cero. |
| Compliance GDPR/TCPA — multas por incumplimiento | Alto legal | DSR desde día 1. Audit trail inmutable. Legal review antes de launch. DPA template para sub-procesadores. Geofence obligatorio. |
| Dependencia de `ProviderManager` de Mailrelay | Bajo-medio | Si Mailrelay cambia su API interna, `SendEmailJob` se rompe. Mitigación: wrapper `RemarketingMailer` que encapsula la llamada a ProviderManager, aislando el acoplamiento. |
| Cola `remarketing` crece sin freno (campañas masivas) | Medio | Rate limiting en `SendEmailJob`. Límite de recipients por campaña configurable. Monitoreo Horizon con alerta si la cola supera N jobs pendientes. |
| Costos de infraestructura escalan con volumen | Medio | Plan de pricing cubre margen. Archivado automático de `remarketing_events` > 12 meses a S3 (job mensual). Free tier limitado en sends, no en contactos. |
| Carts WooCommerce require plugin PHP en la tienda del cliente | Medio operacional | El plugin se genera y descarga desde la UI. Documentación clara de instalación. Fallback: el cliente puede implementar el hook manualmente via código. |

---

## 16. Checklist de comandos para arrancar el módulo

```bash
# 1. Crear el módulo base
php artisan module:make Remarketing --no-interaction

# 2. Registrar en bootstrap/providers.php (añadir manualmente en orden alfabético)
# use Modules\Remarketing\Providers\RemarketingServiceProvider;
# RemarketingServiceProvider::class,

# 3. Registrar en modules_statuses.json
# "Remarketing": true

# 4. Registrar en composer.json (autoload.psr-4)
# "Modules\\Remarketing\\": "modules/Remarketing/app/",
# "Modules\\Remarketing\\Database\\Factories\\": "modules/Remarketing/database/factories/",
# "Modules\\Remarketing\\Database\\Seeders\\": "modules/Remarketing/database/seeders/"

# 5. Regenerar autoload
composer dump-autoload

# 6. Limpiar todos los caches
php artisan optimize:clear

# 7. Verificar que el módulo aparece
php artisan module:list

# 8. Crear migraciones
php artisan module:make-migration create_remarketing_stores_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_customers_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_products_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_orders_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_order_items_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_carts_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_events_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_segments_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_campaigns_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_automations_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_automation_steps_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_automation_runs_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_templates_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_messages_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_suppressions_table Remarketing --no-interaction
php artisan module:make-migration create_remarketing_consent_events_table Remarketing --no-interaction

# 9. Ejecutar migraciones
php artisan module:migrate Remarketing

# 10. Crear seeder de permisos
php artisan module:make-seeder RemarketingPermissionsSeeder Remarketing --no-interaction

# 11. Ejecutar seeder
php artisan module:seed Remarketing --class=RemarketingPermissionsSeeder

# 12. Limpiar cache de Spatie
php artisan cache:clear

# 13. Verificar rutas
php artisan route:list --name=remarketing

# 14. Añadir supervisor en config/horizon.php (manual)
# supervisor-remarketing: queue=[remarketing], maxProcesses=5
# supervisor-remarketing-webhooks: queue=[remarketing-webhooks], maxProcesses=10, timeout=30

# 15. Formatear código
vendor/bin/pint --dirty

# 16. Ejecutar tests del módulo
php artisan test --compact modules/Remarketing/tests/
```

---

## 17. Estimación de esfuerzo (jornadas-persona)

**1 jornada = 8 horas de trabajo efectivo.**

### Fase 0 — MVP

| Área | Tareas principales | Jornadas |
|---|---|---|
| **database** | 16 migraciones + 8 factories + seeder permisos | 4 |
| **backend** | ServiceProvider, Contracts, DTOs, 2 Connectors (Shopify+WooCommerce), 5 Services, 11 Jobs, 2 Commands, 6 Policies, 5 Observers | 24 |
| **api** | 40+ endpoints API, 12 Resources, Form Requests | 10 |
| **frontend** | 17 páginas Blade + 4 partials + pixel JS (build) | 14 |
| **devops** | Horizon config, Supervisor, plugin WooCommerce bridge | 2 |
| **testing** | PHPUnit feature tests (happy + failure + auth) por módulo | 8 |
| **security** | Revisión GDPR/TCPA, DSR endpoints, audit trail, HMAC verification | 3 |
| **docs** | README del módulo, guía de conectores | 1 |
| **review** | Code review, conventions check, N+1 audit | 2 |
| **Total Fase 0** | | **68 jornadas** |

### Fase 1 — Should-have

| Área | Jornadas |
|---|---|
| database | 3 (stock_alerts, A/B variants, SMS opt-in) |
| backend | 18 (3 conectores extra, browse abandonment, back-in-stock, A/B, SMS, predictive) |
| api | 4 |
| frontend | 8 (editor de flows con branching, nuevas páginas, SMS) |
| testing | 6 |
| review + security | 3 |
| **Total Fase 1** | **42 jornadas** |

### Fase 2 — Nice-to-have

Estimación indicativa: **80-120 jornadas** según features elegidas (WhatsApp + push son 30+ jornadas solos por compliance Meta + VAPID).

---

## 18. Asignación de agentes por step

| Step | Agente | Descripción |
|---|---|---|
| 1 | **database** | Crear las 16 migraciones del modelo de datos con índices y FK. |
| 2 | **database** | Crear factories para las 8 entidades principales (Store, Customer, Product, Order, Cart, Event, Campaign, Automation). |
| 3 | **database** | Crear `RemarketingPermissionsSeeder` con los 34 permisos Spatie. |
| 4 | **backend** | Crear `modules/Remarketing/app/Providers/RemarketingServiceProvider.php` con NavService, config, views, migrations, routes, schedules, singletons y policies. |
| 5 | **backend** | Crear `EcommerceConnector` interface + `EventDTO` + base connector abstracta. |
| 6 | **backend** | Crear `ShopifyConnector`: auth, `verifyWebhook`, `subscribeWebhooks`, `syncCatalog` (GraphQL bulk), `syncCustomers`, `syncOrders`, `handleWebhook`. |
| 7 | **backend** | Crear `WooCommerceConnector`: auth, `verifyWebhook`, `subscribeWebhooks`, sync REST, `handleWebhook`. Generar plugin PHP WooCommerce bridge como asset descargable. |
| 8 | **backend** | Crear `ProfileService`: `findOrCreateByEmail`, `mergeVisitorToCustomer`, `updateFromWebhook`. |
| 9 | **backend** | Crear `ConsentService`: `grantConsent`, `confirmDoubleOptin`, `withdraw`, `sendDoubleOptinEmail`, geofence logic (EU/Brasil). |
| 10 | **backend** | Crear `SegmentService`: compilar JSON AST a Eloquent query, `getMembers`, `getMemberCount`. |
| 11 | **backend** | Crear `CampaignService`: `schedule`, `send` (dispatch `SendEmailJob` masivo), `cancel`, `calculateStats`. |
| 12 | **backend** | Crear `AutomationService`: `trigger`, `advance`, `cancel`. |
| 13 | **backend** | Crear `DeliverabilityCheckerService`: DNS lookup de SPF/DKIM/DMARC, devuelve semáforo. |
| 14 | **backend** | Crear los 11 Jobs: `ProcessWebhookJob`, `SyncCatalogJob`, `SyncCustomersJob`, `SyncOrdersJob`, `SendEmailJob`, `EvaluateAutomationJob`, `MarkAbandonedCartsJob`, `RecalculateSegmentJob`, `CalculateRfmJob`, `ProcessPixelEventJob`, `ProcessBounceJob`. |
| 15 | **backend** | Crear Commands: `remarketing:process-automations`, `remarketing:calculate-rfm`, `remarketing:reconcile-catalog`. Registrar en scheduler. |
| 16 | **backend** | Crear 8 Policies (Store, Customer, Segment, Campaign, Automation, Template, Suppression, Report). Registrar en ServiceProvider. |
| 17 | **backend** | Crear observers para ConsentEvent y Order que disparan automations. |
| 18 | **backend** | Crear `modules/Remarketing/routes/web.php` con rutas panel + settings. |
| 19 | **api** | Crear `modules/Remarketing/routes/api.php` con todos los endpoints privados (sección 10.1). |
| 20 | **api** | Crear rutas públicas (`/r/track`, `/r/unsubscribe`, `/r/open`, `/r/click`, `/r/webhooks/*`) en `routes/web.php` con throttle. |
| 21 | **api** | Crear 12 API Resources (`StoreResource`, `CustomerResource`, `ProductResource`, `OrderResource`, `CartResource`, `SegmentResource`, `CampaignResource`, `AutomationResource`, `TemplateResource`, `MessageResource`, `SuppressionResource`, `ConsentEventResource`). |
| 22 | **api** | Crear Form Requests: `StoreStoreRequest`, `UpdateStoreRequest`, `StoreCampaignRequest`, `UpdateCampaignRequest`, `StoreAutomationRequest`, `StoreSegmentRequest`, `StoreTemplateRequest` + DSR requests. |
| 23 | **api** | Crear API controllers: `StoreApiController`, `CustomerApiController`, `SegmentApiController`, `CampaignApiController`, `AutomationApiController`, `TemplateApiController`, `SuppressionApiController`, `DsrApiController`. |
| 24 | **api** | Crear controllers públicos: `TrackController`, `UnsubscribeController`, `PreferencesController`, `TrackingController` (open/click), `WebhookController` (shopify/woocommerce/email-events). |
| 25 | **frontend** | Crear views del panel principal: dashboard, stores (index + create + edit), customers (index + show), products (index), carts (index). |
| 26 | **frontend** | Crear views de campaigns (index + form), automations (index + edit con editor de steps jQuery), templates (index + form con preview iframe). |
| 27 | **frontend** | Crear view de segments/form con constructor visual de condiciones (jQuery: add/remove condition rows, operadores, tipos). |
| 28 | **frontend** | Crear view de reports con dxDataGrid + dxChart de revenue mensual. |
| 29 | **frontend** | Crear views de settings/general y settings/consent. |
| 30 | **frontend** | Crear pixel JS (`modules/Remarketing/resources/assets/pixel/pixel.js`) + Vite config + compilar a `public/remarketing/pixel.js`. |
| 31 | **devops** | Añadir supervisores `supervisor-remarketing` y `supervisor-remarketing-webhooks` en `config/horizon.php`. |
| 32 | **devops** | Documentar instalación del plugin WooCommerce bridge e instrucciones de configuración DNS para DKIM/SPF/DMARC. |
| 33 | **testing** | Tests PHPUnit para ConsentService (double opt-in, withdraw, geofence), ProfileService (merge), SuppressionService. |
| 34 | **testing** | Tests PHPUnit para Connectors (ShopifyConnector webhook verify, payload handling), SegmentService (AST compilation), CampaignService (send + suppress check). |
| 35 | **testing** | Tests PHPUnit para endpoints públicos (/r/unsubscribe, /r/track, /r/webhooks/*), DSR endpoints, y authorización de API privada. |
| 36 | **security** | Auditar HMAC verification en WebhookController, token generation en unsubscribe/optin, consent audit trail inmutabilidad, GDPR deletion (anonimización correcta). |
| 37 | **review** | Code review general: N+1 queries, eager loading, route names, permission naming, Form Request en todos los controllers, no inline styles en Blade. |
| 38 | **docs** | `modules/Remarketing/README.md` con guía de conectores, setup DKIM, uso del pixel, y fronteras con Campaign/Mailrelay. |

---

*Documento generado: 2026-05-02. Basado en investigación competitiva (`docs/research/remarketing-competition.md`) y análisis del codebase Alsernet (ADR-0001, modules/Helpdesk, modules/Campaign, modules/MailsSettings, config/horizon.php).*
