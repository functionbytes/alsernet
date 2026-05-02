# Remarketing / Email Marketing para Ecommerce — Investigacion Competitiva

> Documento de investigacion para diseñar un modulo Laravel propio de remarketing/email marketing
> integrado con Shopify, PrestaShop, WooCommerce, Magento y BigCommerce.
>
> Fecha: 2026-05-02
> Alcance: competidores, casos de uso, aspectos tecnicos, compliance, pricing y analitica.
> Metodo: ~15 busquedas web sobre fuentes oficiales y comparativas reputadas.

---

## 1. Resumen ejecutivo

El mercado de remarketing para ecommerce esta dominado por tres arquetipos:

1. **Premium/data-heavy** (Klaviyo, Bloomreach Engagement) — segmentacion en tiempo real,
   predictive analytics (CLV, churn risk), integracion profunda con Shopify, precio alto.
2. **Mid-market multi-canal** (Omnisend, Brevo, ActiveCampaign) — email + SMS + push integrados,
   workflows pre-construidos, mejor relacion precio/feature.
3. **All-rounder/entry** (Mailchimp, Privy, Yotpo Email) — UX simple, free tier generoso,
   pero menos profundidad para ecommerce data.

La diferenciacion ya **no esta en feature parity** (todos hacen abandoned cart, welcome series,
post-purchase). Esta en:

- **Calidad del data layer ecommerce** (eventos en tiempo real, profiles unificados, atribucion).
- **AI/predictive** (CLV bucket, churn risk, send-time optimization, content generation).
- **Multi-canal nativo** (email + SMS + WhatsApp + push compartiendo flows y consent).
- **Deliverability** (autenticacion automatica, IP warm-up, RFC 8058 one-click unsubscribe).

Para un modulo Laravel propio, la viabilidad depende menos de "construir el sender" (es facil con
Symfony Mailer + AWS SES/Mailgun) y mas de **construir el data pipeline** (catalogo + clientes +
eventos sincronizados desde 5 plataformas distintas) y **resolver compliance** desde el dia 1.

---

## 2. Tabla comparativa de competidores

| Plataforma | Foco | Email | SMS | WhatsApp | Push | Integracion Shopify | CLV/Churn AI | Pricing model | Free tier | USP |
|------------|------|-------|-----|----------|------|--------------------|--------------|---------------|-----------|-----|
| **Klaviyo** | Premium ecommerce | ✓ | ✓ (separado) | ✓ (separado) | — | Best-in-class, real-time | ✓ (CLV bucket, expected next order date) | Por contactos activos, sube rapido | 250 contactos / 500 sends | Profile unificado, behavioral segmentation, predictive analytics |
| **Omnisend** | Mid-market multi-canal | ✓ | ✓ (incluido) | ✓ | ✓ web push | Profunda, multi-canal en mismo flow | Limitado | Por contactos + sends SMS | 250 contactos / 500 sends | Email+SMS+push en un solo plan, mas barato que Klaviyo |
| **Mailchimp** | All-rounder generalista | ✓ | ✓ | — | — | Decente, no nativa ecommerce | Limitado | Por contactos | 500 contactos | UX simple, popular fuera de DTC |
| **ActiveCampaign** | Automation + CRM | ✓ | ✓ | — | — | Buena, no especifica ecom | ✓ predictive sending | Por contactos | No (trial) | CRM integrado, workflows complejos con branching |
| **Brevo** (Sendinblue) | Multi-canal economico | ✓ | ✓ | ✓ | — | Buena | AI segmentation | Por **sends** (no contactos) | 9k sends/mes / 100k contactos | Pricing por envio, transactional incluido |
| **Drip** | Ecommerce DTC | ✓ | ✓ | — | — | Buena | Visual workflow | Por contactos | Trial | Workflows visuales potentes |
| **Bloomreach Engagement** | Enterprise CDP | ✓ | ✓ | ✓ | ✓ | Plus + Magento + BC | ✓ Loomi AI (purchase, churn, CLV) | Custom enterprise | No | CDP completo, scenario builder con journey simulation |
| **Yotpo Email** | Stack Yotpo (loyalty/reviews) | ✓ | ✓ | — | — | Buena | Limitado | Por contactos | Si | Ideal si ya usas Yotpo Reviews/Loyalty |
| **Privy** | SMB Shopify | ✓ | ✓ | — | — | Solo Shopify | — | Por contactos | Si | Popups + email para Shopify SMB |
| **Rejoiner** | Service + software | ✓ | — | — | — | Buena | — | Custom | No | Managed service de cart recovery |

### Observaciones clave de la comparativa

- **Klaviyo marca el estandar** de profundidad ecommerce. Todos los demas se posicionan como
  "alternativa mas barata" o "alternativa multi-canal".
- **Brevo es el unico** que cobra por sends en lugar de contactos — favorable para listas grandes
  con poco envio, contraproducente para ecom con campañas semanales.
- **Bloomreach** apunta a enterprise (Shopify Plus, Magento, BigCommerce); no compite en SMB.
- **WhatsApp** lo ofrecen Klaviyo, Omnisend, Brevo, Bloomreach. Es el canal de mayor crecimiento
  en LATAM/EU pero con costes y compliance Meta especificos.
- **Push web** lo ofrecen pocos (Omnisend, Bloomreach). Es low-hanging fruit para diferenciar.

---

## 3. Casos de uso (priorizados por ROI/conversion)

### 3.1 Must-have (todos los competidores los tienen, son la base)

| Use case | Trigger | Timing recomendado | Conversion benchmark |
|----------|---------|---------------------|----------------------|
| **Abandoned cart** | `checkout_started` sin `order_placed` | Email 1: 30-60min · Email 2: 24h · Email 3: 72h con descuento | 10-15% recovery rate (hasta 36% con personalizacion) |
| **Welcome series** | `subscriber_created` (post double opt-in) | Email 1: inmediato · 2: dia 2 · 3: dia 5 | Open rates 40-60% |
| **Post-purchase** | `order_placed` | Confirmacion inmediata · Review request 7-14 dias post-delivery · Cross-sell 30 dias | Genera 25-30% del revenue email |
| **Browse abandonment** | Producto visto >2 veces sin add-to-cart | 1-4h despues de la sesion | 42% open rate, 0.6% conversion (10.7% click-to-conversion) |
| **Win-back / inactivos** | Sin compra en N dias (segun ciclo de producto) | 60/90/120 dias con incentivo escalado | 5-15% reactivacion |

### 3.2 Should-have (diferenciador competitivo)

| Use case | Trigger | Notas |
|----------|---------|-------|
| **Back-in-stock** | `inventory_qty` pasa de 0 a >0 + lista de espera | Conversion >20% (alta intencion). Requiere tabla `stock_alerts` |
| **Price drop** | `product.price` baja >X% sobre baseline | Auto-segmentar por wishlist o browse history |
| **Replenishment** | Producto consumible — `order_placed` + ciclo de vida del producto | Requiere catalog metadata `consumable_days` |
| **VIP / loyalty segments** | CLV > P90, frecuencia, recency | Segmento dinamico con AOV, LTV, ultima compra |
| **Birthday / anniversary** | `customer.birthday` o aniversario de primera compra | Cupon automatico |
| **Cross-sell / upsell post-purchase** | `order_placed` + product affinity (same collection o "bought together") | Requiere recomendador (matrix factorization simple o "frequently bought together") |

### 3.3 Nice-to-have (premium / fase 2)

- **Predictive next order date** (modelo simple de inter-purchase interval por cliente).
- **Churn risk score** (RFM + ML).
- **Send-time optimization** (modelo por usuario sobre historial de aperturas).
- **Dynamic content blocks** (bloque "recommended for you" renderizado al abrir el email).
- **A/B testing multivariante** (subject, send time, content, CTA).
- **Engagement scoring** y supresion automatica de bajos engagers.

---

## 4. Aspectos tecnicos clave

### 4.1 Integracion con plataformas de ecommerce

| Plataforma | Mecanismo principal | Eventos relevantes | Notas tecnicas |
|------------|--------------------|--------------------|----------------|
| **Shopify** | App publica/privada + Webhooks + GraphQL Admin API | `orders/create`, `orders/update`, `customers/create`, `customers_email_marketing_consent/update`, `checkouts/create`, `checkouts/update`, `products/update`, `inventory_levels/update` | HMAC-SHA256 verification (`X-Shopify-Hmac-Sha256`). Retry exponencial 48h, 19 fallos = unsubscribe. **Bulk operations GraphQL** para sync inicial de catalogos grandes (JSONL async). API version pinning obligatorio (current: `2026-01`). |
| **PrestaShop** | Modulo PHP + Webservice REST (XML/JSON) + Hooks | Hooks: `actionValidateOrder`, `actionCustomerAccountAdd`, `actionCartSave`, `actionUpdateQuantity`. CloudSync APIs en PS9. | OpenAPI 3.0 docs en PS Integration Framework. Modulo nativo `addWebserviceResources` para extender API. Compatibilidad PS 1.7 / 8.x / 9.x es heterogenea. |
| **WooCommerce** | REST API v3 + Webhooks WP + Plugin | `order.created`, `order.updated`, `customer.created`, `product.updated`. Abandoned cart NO es nativo — requiere plugin propio o hook a nivel de session/checkout. | Webhook delivery basico, sin retry sofisticado. Auth via consumer key/secret. Plugin debe gestionar carts abandonados via hooks `woocommerce_add_to_cart`, `woocommerce_cart_updated`. |
| **Magento (Adobe Commerce)** | REST API + GraphQL + Observers/Plugins | `sales_order_place_after`, `customer_register_success`, `checkout_cart_save_after`. | Mas complejo, requiere modulo Magento. Adobe Commerce Cloud > Magento Open Source en docs. |
| **BigCommerce** | REST API + Webhooks + Apps | `store/order/created`, `store/cart/updated`, `store/customer/created`. | Webhook payloads ligeros (solo IDs), requieren llamada API para hidratar. |

**Decision recomendada**: implementar **un adapter por plataforma** detras de una interfaz comun
(`EcommerceConnector`) con metodos `syncCatalog()`, `syncCustomers()`, `subscribeWebhooks()`,
`handleEvent(payload)`. El payload normalizado interno es el mismo (event sourcing).

### 4.2 Tracking de eventos del lado cliente

Tres patrones combinables:

1. **Server-side via webhook** (recomendado como primary): la plataforma ecommerce envia el evento.
   Ventaja: confiable, no afectado por adblockers. Desventaja: latencia + no captura "browse"
   sin compra.
2. **Pixel JS propio** (script `<script src="https://tu-app.com/pixel.js?store=xxx"></script>`):
   captura `viewed_product`, `viewed_collection`, `started_checkout`. Persistir cookie + match con
   email post-form-submit.
3. **GTM dataLayer hooks**: para clientes con stack avanzado, leer del dataLayer existente.

**Identity resolution**: cookie `_visitor_id` (UUID) generada al primer hit; cuando llega un
`identify` (email del form de newsletter, login, checkout), hacer `merge(visitor_id → customer_id)`
y reasociar todos los eventos historicos.

### 4.3 Sincronizacion de catalogo

- **Sync inicial**: bulk operation (Shopify GraphQL bulk, WooCommerce REST con paginacion alta,
  PrestaShop webservice paginado). Job en cola con chunks de 250 productos.
- **Sync incremental**: webhooks `products/update`, `inventory_levels/update`.
- **Reconciliacion periodica**: full sync nocturno con diff (hash `updated_at`). Necesario porque
  los webhooks fallan ~1-3% del tiempo.
- **Schema interno minimo**: `product_id_external`, `platform`, `sku`, `title`, `url`, `price`,
  `compare_at_price`, `image_url`, `inventory`, `tags[]`, `collection[]`, `vendor`, `metadata` (JSON).

### 4.4 Sincronizacion de clientes y pedidos

- **Customer profile unificado**: tabla `profiles` con `email` (PK natural),
  `external_ids` (JSON con `{platform, store, customer_id}`), `consent` (JSON con timestamp,
  source, ip, double_opt_in_at), `attributes` (JSON: name, locale, tags), `predicted` (JSON: clv,
  churn_risk, next_order_eta).
- **Eventos**: tabla `events` append-only con `profile_id`, `type` (`viewed_product`,
  `started_checkout`, `placed_order`, ...), `properties` (JSON), `occurred_at`, `received_at`.
  Indices por `(profile_id, occurred_at)` y `(type, occurred_at)`.
- Particionar `events` por mes si se espera >10M filas/mes.

### 4.5 Consent management (GDPR + CAN-SPAM + LGPD + CASL)

- **Double opt-in obligatorio en EU/LATAM** (no estricto en GDPR pero best practice y bloqueante en
  varios ESPs). Token firmado HMAC, expiracion 7d, log con IP/UA/timestamp.
- **Audit trail** persistente: tabla `consent_events` (profile_id, event_type
  `granted|withdrawn|confirmed`, source `popup|checkout|api`, ip, user_agent, form_url,
  policy_version, occurred_at). Retencion >= duracion del consent.
- **Unsubscribe**: link con token firmado, **un solo click** sin login. Procesar inmediatamente
  (no requerir confirmacion). Soportar `List-Unsubscribe` header (RFC 2369) y `List-Unsubscribe-Post`
  (RFC 8058 one-click) — **obligatorio** para Gmail/Yahoo bulk senders desde 2024.
- **Suppression list** global por cuenta: hard bounces, spam complaints, manual unsubs, GDPR
  requests. Check obligatorio antes de cualquier send.
- **Transactional vs marketing**: emails transaccionales (confirmacion pedido, envio, password
  reset) NO requieren consent ni admiten unsubscribe. Marcar la flag a nivel de Mailable/template.

### 4.6 Deliverability

- **Autenticacion**: SPF (`v=spf1 include:...`), DKIM (selector con clave 2048-bit publicada DNS),
  DMARC (`v=DMARC1; p=none; rua=mailto:...` para empezar, escalar a `quarantine` y `reject`).
- **One-click unsubscribe** (RFC 8058) — **obligatorio** desde mayo 2024 para Gmail/Yahoo, ahora
  Microsoft tambien (mayo 2025).
- **Tasas limite** Gmail/Yahoo bulk: spam complaints <0.3%, bounces <2%.
- **IP dedicada vs compartida**: IP dedicada solo justifica >100k sends/mes. Warm-up 4-6 semanas
  comenzando con 5-10 sends/dia, escalado x2 cada 2-3 dias hasta volumen objetivo. Empezar siempre
  con engaged recipients (ultimos 30d con apertura).
- **Sub-dominios separados** por proposito: `mail.tudominio.com` (marketing), `tx.tudominio.com`
  (transaccional), `notifications.tudominio.com`. Aisla reputacion.
- **ESPs recomendados**: AWS SES (mas barato, requiere reputation propia), SendGrid, Mailgun,
  Postmark (mejor para transactional). **Soportar varios via driver** (decision arquitectonica).
- **Webhook bounces / complaints**: feedback loop SES/SendGrid → actualizar suppression list
  automaticamente.

### 4.7 Segmentacion dinamica

- **Estatica**: `WHERE customer.tag IN (...)` evaluada al momento del send.
- **Dinamica/comportamental**: `WHERE EXISTS (events WHERE type=X AND occurred_at > NOW()-7d)`.
- Implementar con **query builder visual** (frontend) → AST → SQL preparado.
- Cache de membership en Redis con invalidacion por evento (set add/remove al recibir evento que
  cambia pertenencia).
- Operaciones tipicas: `has not done X in Y days`, `has done X at least N times`, `is in segment A
  AND in segment B`, `has property X = Y`.

### 4.8 A/B testing

- A/B sobre `subject`, `from_name`, `preheader`, `send_time`, `content` (template variant),
  `cta_color/copy`.
- **Holdout group** (5-10%) sin envio para medir lift incremental.
- Ganador por **revenue per recipient** o conversion (no solo open/click).
- Significancia estadistica minima (chi-square o Bayesian) antes de declarar ganador.

### 4.9 Predictive analytics

Empezar simple, **no ML deep learning desde el dia 1**:

- **CLV historico**: `SUM(orders.total)` agrupado por customer.
- **CLV predicho**: BG/NBD + Gamma-Gamma (`lifetimes` lib en Python via job, o implementacion PHP
  equivalente). Buckets: low/mid/high/whale.
- **Churn risk**: RFM scoring (recency/frequency/monetary). Logistic regression sobre
  `tiene_orden_proximos_30d` con features [recency_days, frequency_orders, monetary_total,
  emails_opened_30d, emails_clicked_30d].
- **Next order ETA**: media/mediana del inter-purchase interval del propio cliente (suficiente para
  P50 de clientes con >=3 compras).
- **Send-time optimization**: hora del dia mas frecuente de aperturas previas (simple). ML solo si
  hay >100 sends/usuario.

### 4.10 Multi-canal: SMS, WhatsApp, push

- **SMS**: gateway via Twilio/Vonage/MessageBird. Costos por pais altamente variables ($0.005-0.10
  por SMS). TCPA en US: opt-in expreso por escrito, cumple antes de las 8am o despues 9pm hora
  local del receptor, max 1 abandoned cart SMS por evento dentro de 48h.
- **WhatsApp Business API**: via Meta directamente (requiere business verification) o partner
  (Twilio, MessageBird, 360dialog). Templates pre-aprobados por Meta para campañas. Conversation
  pricing model: ventana 24h gratis tras user-initiated, fuera de ella requiere template aprobado.
- **Web push**: ServiceWorker + endpoint VAPID. Almacenar `subscription` (endpoint, p256dh, auth).
  No requiere email — buen canal para anonimos. Lib: web-push-php.

---

## 5. Compliance y regulatorio (resumen practico)

| Norma | Region | Aplicacion al modulo |
|-------|--------|---------------------|
| **GDPR** | EU/EEA | Consent explicito, double opt-in recomendado, audit trail, derecho a borrado/exportacion (DSR), DPA con sub-procesadores, registro de actividades de tratamiento. |
| **CAN-SPAM** | US | No requiere opt-in previo, pero exige unsub funcional <10 dias, direccion fisica del remitente, asunto no engañoso. |
| **CASL** | Canada | Express opt-in (mas estricto que CAN-SPAM, similar GDPR), incluye SMS. |
| **LGPD** | Brasil | Similar a GDPR, base legal `consentimento` o `legitimo interesse`. |
| **PECR** | UK | Soft opt-in permitido para clientes existentes en productos similares. |
| **TCPA** | US (SMS/calls) | Express written consent para promocional. Multas $500-1500 por mensaje. Opt-in NO compartible entre marcas. |
| **Meta WhatsApp Business Policy** | Global (canal) | Opt-in obligatorio, templates aprobados, no spam, ratio quality score afecta limites. |

**Implementacion minima del modulo**:

1. Tabla `consent_events` append-only.
2. Endpoint publico `GET /unsubscribe?token=...` con verificacion y borrado <1s.
3. Endpoint publico `GET /preferences?token=...` con preferencias granulares (email mkt, sms,
   whatsapp, frecuencia).
4. Endpoint API `POST /api/dsr/export` y `POST /api/dsr/delete` para cumplir derechos GDPR.
5. Headers `List-Unsubscribe` y `List-Unsubscribe-Post: List-Unsubscribe=One-Click` en cada email.
6. Footer obligatorio con direccion fisica + link unsub.
7. Bandera `is_transactional` en cada Mailable/template (los transaccionales NO llevan unsub).
8. Geofence consent: detectar pais del subscriber (por IP at-signup o `customer.country`) para
   aplicar politica correcta.

---

## 6. Pricing models comunes

| Modelo | Como funciona | Pros | Contras | Quien lo usa |
|--------|--------------|------|---------|--------------|
| **Por contactos activos** | Tier por # de profiles que reciben marketing en N dias | Predecible, incentiva limpieza de listas | Penaliza listas grandes con poco envio | Klaviyo, Mailchimp, ActiveCampaign |
| **Por sends** | Charge por email enviado | Justo para senders esporadicos | Imprevisible, cuesta caro al escalar campañas | Brevo, Postmark |
| **Hibrido** | Base por contactos + cargo extra por SMS/WhatsApp | Equilibrio | Confuso para el usuario | Omnisend |
| **Feature tiers** | Plan por features (auto, segmentacion avanzada, AI) | Empezar barato | Friccion al upgrade | Mailchimp Standard/Premium |
| **Usage-based AI** | Charge por inferencias AI | Escala con valor | Hard de presupuestar | Reciente en Klaviyo (Marketing Agent) |

**Free tier tipico**: 250-500 contactos / 500-9000 sends. Es marketing, no sostenible.

**Recomendacion**: para un modulo SaaS propio considerar **por contactos activos** con tiers
[1k, 5k, 10k, 25k, 50k, 100k+]. Multi-canal como add-on por evento (SMS/WhatsApp pass-through al
costo + margen). Plan Free hasta 500 contactos con branding del producto en el footer.

---

## 7. KPIs y analitica

### 7.1 KPIs estandar a exponer

| Categoria | KPI | Calculo | Benchmark ecom |
|-----------|-----|---------|----------------|
| **Deliverability** | Delivery rate | `delivered / sent` | >97% |
| | Bounce rate | `bounces / sent` | <2% (mas: penalty Gmail) |
| | Spam complaint rate | `complaints / delivered` | <0.3% |
| **Engagement** | Open rate (MPP-aware) | `unique_opens / delivered` | 20-40% (sesgo MPP iOS Apple) |
| | CTR | `unique_clicks / delivered` | 2-5% |
| | CTOR | `unique_clicks / unique_opens` | 10-20% |
| **Conversion** | Conversion rate | `conversions / delivered` | 0.3-1% (5% en flows automatizados) |
| | Revenue per recipient (RPR) | `revenue / delivered` | $0.10-1.06 |
| | Revenue per email (RPE) | igual que RPR | — |
| **Lista** | List growth rate | `(new_subs - unsubs - bounces) / total` | 1-3% mensual saludable |
| | Unsubscribe rate | `unsubs / delivered` | <0.5% |

### 7.2 Atribucion

- **Click-based attribution** (default): si el cliente hizo click en el email y compro en X dias,
  atribuir.
- **View-based attribution** (opcional): si abrio (no clicked) y compro en X dias, atribucion
  parcial.
- **Attribution windows configurables**: 1d, 5d (default Klaviyo), 7d, 30d.
- **Multi-touch**: ultimo click, primer click, lineal, decay temporal. Empezar con last-click,
  exponer el resto.

### 7.3 Funnel analysis

- Reportes flow-by-flow: `sent → delivered → opened → clicked → converted → revenue`.
- Cohort analysis: subscribers de mes M vs su retencion/CLV en M+1, M+2, ...
- Dashboard ejecutivo: revenue total, % revenue from email vs total store, top performing
  campaigns, top performing flows.

---

## 8. Features priorizadas para el modulo Laravel

### 8.1 MVP / Must-have (fase 1, 8-12 semanas)

1. **Conector Shopify** (webhooks + bulk sync de catalog/customers/orders).
2. **Conector WooCommerce** (REST API + webhooks + plugin propio para abandoned cart).
3. **Profile unificado** (tabla `profiles` con consent + external_ids).
4. **Consent management completo** (double opt-in, audit trail, unsubscribe RFC 8058, suppression
   list global).
5. **Builder de campaigns** (template engine simple — Blade o MJML — con drag-and-drop opcional).
6. **Flows / automations** con triggers basicos: `signup`, `order_placed`, `cart_abandoned`,
   `customer_inactive_n_days`. Editor visual lineal (sin branching avanzado).
7. **Pre-built flows**: welcome series, abandoned cart, post-purchase, win-back.
8. **Segmentacion estatica + dinamica simple** (RFM buckets + tag-based).
9. **Sender via driver** (SES default, SendGrid/Mailgun como alternativas).
10. **Deliverability tooling**: SPF/DKIM/DMARC checker en el setup wizard, `List-Unsubscribe`
    headers automaticos, suppression list automatica con feedback loops.
11. **KPIs basicos**: delivery, open, click, conversion, RPR.
12. **GDPR compliance**: DSR endpoints, audit trail, footer obligatorio.

### 8.2 Should-have (fase 2, +6-8 semanas)

13. **Conector PrestaShop** + **conector Magento** + **conector BigCommerce**.
14. **Pixel JS propio** + identity resolution (`viewed_product`, `started_checkout`).
15. **Browse abandonment flow**.
16. **Back-in-stock** + **price drop** flows con tabla de waitlists.
17. **A/B testing** subject/content con holdout group.
18. **SMS via Twilio** con TCPA compliance built-in (geofence, quiet hours, opt-in expreso).
19. **Editor visual de flows con branching** (yes/no splits, wait, send X).
20. **Predictive segments** simples: CLV bucket (RFM), churn risk (logistic regression), next
    order ETA (median IPI).

### 8.3 Nice-to-have (fase 3+)

21. **WhatsApp Business API** integration (via Twilio/360dialog).
22. **Web push** notifications.
23. **Send-time optimization** por usuario.
24. **Dynamic content blocks** (recomendaciones renderizadas al abrir).
25. **Multi-touch attribution** configurable.
26. **AI subject line generator** (LLM via OpenAI/Anthropic).
27. **Loyalty / VIP segmentation** automatica.
28. **Replenishment flows** con catalog metadata.

---

## 9. Decisiones tecnicas recomendadas (con tradeoffs)

| Decision | Recomendacion | Tradeoff |
|----------|---------------|----------|
| **Stack mailing** | Symfony Mailer (incluido en Laravel) + driver SES por defecto. Soportar SendGrid/Mailgun como alternativos. | SES es 10x mas barato pero requiere gestionar reputation. SendGrid/Postmark mejor onboarding. **Solucion**: driver-pattern, dejar elegir al cliente. |
| **Renderizado de email** | MJML (con `mailpace/mjml-laravel` o servicio externo) → HTML. | MJML añade dependencia node, pero genera HTML cross-client confiable. Alternativa: Blade puro + framework "responsive email" (mas trabajo). |
| **Cola de envio** | Laravel Horizon + Redis con queue dedicada `emails`. Job `SendEmailJob` con tries=3, backoff exponencial. | Horizon ya esta en el stack. **Cuidado**: rate-limit por ESP (SES: 1-200/sec segun reputation). Implementar `RateLimited` middleware. |
| **Eventos / event sourcing** | Tabla `events` particionada mensual + Redis stream para realtime. | Particionar es operationally pesado. Considerar TimescaleDB/ClickHouse si >50M eventos/mes. |
| **Webhooks ingestion** | Endpoint dedicado por plataforma (`/webhooks/shopify`, `/webhooks/woocommerce`). Verificacion HMAC inmediata, payload a queue (`process-webhook`), respuesta 200 OK <500ms. | NO procesar sincrono — Shopify desuscribe a los 19 fallos consecutivos. |
| **Catalog sync** | Initial: bulk operation por job. Incremental: webhook. Reconciliacion: scheduled command nocturno. | Reconciliacion nocturna pesada; programar fuera de horas pico de la tienda. |
| **Identity resolution** | Cookie `_visitor_id` UUID + servidor merge en `identify` event. | Cookies third-party morirán; pasar a first-party cookie (mismo dominio del cliente con CNAME) o server-side tracking. |
| **Segmentacion engine** | Query builder visual → JSON AST → SQL preparado contra `profiles + events` con `EXISTS` correlated. Cache de membership en Redis con invalidacion por evento. | SQL puro escala hasta ~1M profiles. Mas alla: motor dedicado (Materialize, ClickHouse). |
| **Predictive analytics** | Job nocturno (Schedule) con calculo RFM + logistic regression simple en PHP (rubix/ml). | rubix/ml en PHP no es state-of-art. Para >100k clientes, mover a service Python con FastAPI. |
| **A/B testing** | Random assignment al `send`, persistir variante en `sends.variant`. Calculo de significancia al cierre del test. | NO usar `random()` PHP — usar hash determinista del recipient para permitir reanalisis. |
| **Tracking pixel** | 1x1 GIF firmado con `send_id` HMAC. Click tracking via redirect `/r/{token}`. | MPP de Apple inflara open rates artificialmente — exponer "MPP-aware open rate" como KPI separado. |
| **Multi-tenancy** | Single DB, tenant_id en cada tabla, scope global. | Mas simple que DB-per-tenant. Para escalar a >1000 cuentas, considerar sharding por tenant. |
| **API publica** | REST con Sanctum, versionada `/api/v1/`. Webhooks salientes para que clientes consuman eventos del modulo. | Versionar desde el dia 1 evita breaking changes. |

---

## 10. Riesgos y dificultades tecnicas

### 10.1 Deliverability (riesgo alto)

- **Construir reputation de IP/dominio toma 4-6 semanas** y se pierde en dias por mala higiene.
- Si una cuenta envia spam, contamina la reputacion del pool compartido.
- **Mitigacion**: IP dedicada para clientes >100k sends/mes, separar sub-dominios, monitor activo
  de bounce/complaint rate con suspension automatica.
- Apple MPP infla open rates ~50% — los benchmarks de open son cada vez menos utiles.

### 10.2 Compliance (riesgo legal alto)

- Multas GDPR hasta 4% revenue global. Multas TCPA $500-1500 por mensaje.
- **Mitigacion**: consent audit trail desde dia 1, tests de DSR endpoints, contrato DPA template,
  legal review antes de launch.

### 10.3 Webhooks no son confiables al 100%

- Shopify retry 48h, WooCommerce mas debil. Eventos perdidos = profiles con datos stale.
- **Mitigacion**: reconciliacion nocturna, full-sync manual on-demand desde UI, dashboard de
  "webhook health" por tienda.

### 10.4 Sincronizacion de catalogo a escala

- Tiendas con 100k+ productos saturan APIs (Shopify 2 calls/sec REST, WooCommerce sin rate limit
  estricto pero el host del cliente puede caer).
- **Mitigacion**: bulk operations donde existan (Shopify GraphQL), throttle adaptativo,
  resumacion de jobs interrumpidos.

### 10.5 Segmentacion en tiempo real es cara

- Re-evaluar segmentos a cada evento es O(N events × M segments). En tiendas grandes es prohibitivo.
- **Mitigacion**: indices invertidos en Redis (set per segment), evaluar incrementalmente
  (event → recompute solo segments que dependen de ese tipo de evento).

### 10.6 Editor visual de flows complejos

- "Klaviyo flow builder" es ~1 año de UX engineering bien hecho.
- **Mitigacion**: empezar con flows lineales pre-construidos, mover a editor visual en fase 2.
  Considerar libreria visual existente (`reactflow`, `litegraph.js`).

### 10.7 Multi-canal añade complejidad x3

- SMS, WhatsApp, push tienen consent, costos, gateways y compliance distintos.
- **Mitigacion**: arquitectura `Channel` polimorfica desde dia 1 aunque solo se implemente email.
  Añadir SMS en fase 2, WhatsApp/push en fase 3.

### 10.8 Costos infra pueden disparar

- 1M emails/dia × $0.0001 SES = $100/dia solo en envio. Storage de eventos crece linealmente.
- **Mitigacion**: pricing del producto debe cubrir margenes. Plan free debe ser limitado en sends
  no en contactos. Archivar eventos >12 meses a S3/cold storage.

---

## 11. Propuesta de modulos Laravel (estructura sugerida)

```
modules/
├── Marketing/                    # core: profiles, consent, events, campaigns, flows
│   ├── Models/                   # Profile, ConsentEvent, Event, Campaign, Flow, Send, Segment
│   ├── Services/
│   │   ├── ConsentService.php
│   │   ├── SegmentService.php
│   │   ├── FlowEngine.php        # ejecuta automations
│   │   └── DeliverabilityService.php
│   ├── Channels/                 # email, sms, whatsapp, push (polimorfico)
│   ├── Jobs/                     # SendEmail, SyncCatalog, ProcessWebhook, ReconcileCatalog
│   └── Http/
├── MarketingShopify/             # connector Shopify
│   ├── Services/ShopifyConnector.php
│   ├── Http/Controllers/Webhooks/
│   └── Console/Commands/SyncShopify.php
├── MarketingWooCommerce/         # connector WooCommerce
├── MarketingPrestaShop/          # connector PrestaShop
├── MarketingMagento/             # connector Magento
├── MarketingBigCommerce/         # connector BigCommerce
├── MarketingPredictive/          # CLV, churn, RFM jobs
└── MarketingAnalytics/           # reportes, dashboards, attribution
```

Cada conector implementa `EcommerceConnector` con metodos:

```php
interface EcommerceConnector
{
    public function platform(): string;
    public function authenticate(array $credentials): bool;
    public function subscribeWebhooks(string $callbackUrl): array;
    public function syncCatalog(callable $onChunk): void;     // bulk
    public function syncCustomers(callable $onChunk): void;
    public function syncOrders(callable $onChunk, ?Carbon $since = null): void;
    public function handleWebhook(Request $request): EventDTO; // normaliza
}
```

---

## 12. Referencias / URLs consultadas

### Competidores (sitios oficiales y comparativas)

- [Klaviyo Pricing](https://www.klaviyo.com/pricing)
- [Klaviyo: AI Email Marketing & SMS](https://www.klaviyo.com/)
- [What is Predictive Analytics? — Klaviyo](https://www.klaviyo.com/solutions/ai/what-are-predictive-analytics)
- [Omnisend vs Klaviyo](https://www.omnisend.com/blog/omnisend-vs-klaviyo/)
- [Mailchimp vs. Klaviyo Comparison 2026 — Omnisend](https://www.omnisend.com/blog/klaviyo-vs-mailchimp/)
- [Klaviyo vs Mailchimp vs Omnisend — Chase Dimond](https://www.chasedimond.com/omnisend-vs-klaviyo-vs-mailchimp)
- [Brevo Pricing](https://www.brevo.com/pricing/)
- [ActiveCampaign vs. Brevo Feature Comparison](https://www.activecampaign.com/compare/brevo)
- [Mailchimp Pricing Plans](https://mailchimp.com/pricing/marketing/)
- [Bloomreach Engagement](https://www.bloomreach.com/en/products/engagement)
- [Bloomreach Marketing Automation Pricing](https://www.bloomreach.com/en/pricing/engagement)
- [Bloomreach Yotpo integration](https://documentation.bloomreach.com/engagement/docs/yotpo)
- [Klaviyo Pricing 2026 — emailtooltester](https://www.emailtooltester.com/en/reviews/klaviyo/pricing/)
- [Email Marketing Pricing 2026 — VerticalResponse](https://verticalresponse.com/blog/email-marketing-pricing-costs-models-and-how-to-budget-in-2026/)

### Documentacion tecnica oficial

- [Shopify Webhooks](https://shopify.dev/docs/api/webhooks/latest)
- [About Shopify webhooks](https://shopify.dev/docs/apps/build/webhooks)
- [Shopify Bulk operations with GraphQL Admin API](https://shopify.dev/docs/api/usage/bulk-operations)
- [Shopify Bulk query operations](https://shopify.dev/docs/api/usage/bulk-operations/queries)
- [Shopify Bulk import operations](https://shopify.dev/docs/api/usage/bulk-operations/imports)
- [Shopify Options to sync product data](https://shopify.dev/docs/apps/build/sales-channels/product-sync)
- [Shopify productSet mutation](https://shopify.dev/docs/api/admin-graphql/latest/mutations/productSet)
- [WooCommerce Webhooks Documentation](https://woocommerce.com/document/webhooks/)
- [WooCommerce Webhooks Developer Docs](https://developer.woocommerce.com/docs/best-practices/urls-and-routing/webhooks/)
- [PrestaShop Webservice API Developer Docs](https://devdocs.prestashop-project.org/9/webservice/)
- [PrestaShop Integration Framework APIs](https://docs.cloud.prestashop.com/8-apis/)
- [PrestaShop binshops/prestashop-rest GitHub](https://github.com/binshops/prestashop-rest)

### Deliverability y compliance

- [Email Deliverability Checklist 2026 — MailReach](https://www.mailreach.co/blog/email-deliverability-checklist)
- [Email Deliverability 2026: SPF/DKIM/DMARC — Egen](https://www.egenconsulting.com/blog/email-deliverability-2026.html)
- [IP warmup deliverability guide — Adobe Journey Optimizer](https://experienceleague.adobe.com/en/docs/journey-optimizer/using/configuration/implement-ip-warmup-plan/ip-warmup-deliverability-guide)
- [What's IP Warm Up? — MailReach](https://www.mailreach.co/blog/whats-ip-warm-up)
- [GDPR Email Marketing Consent and Compliance Guide — ComplyDog](https://complydog.com/blog/gdpr-email-marketing-consent-compliance-guide)
- [GDPR-ready email marketing — Omnisend](https://www.omnisend.com/blog/gdpr-video-gdpr-ready-email-marketing-automation-consent/)
- [GDPR and Consent — Mailjet](https://www.mailjet.com/resources/learn/gdpr/consent/)
- [TCPA Compliance for SMS — Bloomreach](https://www.bloomreach.com/en/blog/understanding-tcpa-and-ctia-compliance-for-sms-marketing-in-the-us)
- [2026 Guide to TCPA Compliance — Infobip](https://www.infobip.com/blog/tcpa-compliance-sms)

### Casos de uso, benchmarks, AI

- [Browse abandonment email examples — Omnisend](https://www.omnisend.com/blog/browse-abandonment-email/)
- [Cart Abandonment Rate Statistics 2026](https://www.emailvendorselection.com/cart-abandonment-rate-statistics/)
- [Abandoned Cart Emails — Shopify](https://www.shopify.com/blog/abandoned-cart-emails)
- [Abandoned cart email statistics — Stripo](https://stripo.email/blog/abandoned-cart-email-statistics-insights-and-key-metrics-for-boosting-conversions/)
- [Price drop email examples — Omnisend](https://www.omnisend.com/blog/price-drop-email/)
- [Klaviyo Predictive Analytics CLV — Stormy AI](https://stormy.ai/blog/klaviyo-predictive-analytics-clv-strategy)
- [AI in Email Marketing 2026 — ALM Corp](https://almcorp.com/blog/ai-in-email-marketing/)

---

## 13. Conclusiones accionables

1. **El feature gap con Klaviyo es de 12-18 meses de ingenieria continua**. No se cierra en MVP.
   Diferenciar por **mejor integracion regional (PrestaShop, mercado español/LATAM)** o por
   **pricing predecible** (no escalado abusivo en contactos).
2. **Compliance NO es opcional ni post-MVP**. GDPR + DMARC + RFC 8058 + DSR endpoints son MVP-gate.
3. **El data layer es el foso defensivo**. Construir bien `profiles + events + consent` con
   conectores robustos vale 10x mas que 50 templates bonitos.
4. **Empezar con Shopify + WooCommerce** (cubren ~80% del mercado SMB hispano). PrestaShop como
   diferenciador regional (mercado frances/español muy fuerte). Magento/BigCommerce solo si hay
   demanda enterprise concreta.
5. **Email solo en MVP**. SMS en fase 2 (es mucho trabajo de compliance regional). WhatsApp en
   fase 3 (Meta verification + templates es 2-3 meses solo de aprobaciones).
6. **Pricing recomendado**: por contactos activos (mas predecible para el cliente), free tier
   500 contactos con branding, primer plan $19/mes 1k, escalado lineal hasta 50k, luego custom.
7. **Riesgo principal a vigilar**: deliverability. Una cuenta abusiva contamina todo el pool.
   Implementar suspension automatica por threshold de bounces/complaints desde dia 1.
