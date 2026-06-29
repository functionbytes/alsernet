# HelpdeskLivechat — Schema de tablas nuevas

**Conexión:** `helpdesk` (definida en `config/database.php`).
**Prefijo:** `helpdesk_livechat_*` para coherencia con `helpdesk_widget_sessions`, `helpdesk_channel_webs`, etc.
**FKs cross-module:** referencian tablas del módulo `Helpdesk` en la misma conexión.

## Resumen de tablas

| Tabla | Propósito | Volumen estimado |
|---|---|---|
| `helpdesk_livechat_events` | Event log inmutable (page_view, ecommerce, custom) | Muy alto (millones/mes con TTL 90d) |
| `helpdesk_livechat_visitor_scores` | Score actual por sesión, denormalizado | 1 fila por sesión activa |
| `helpdesk_livechat_visitor_contexts` | Contexto dinámico (cart_value, items, etc.) | 1 fila por sesión activa |
| `helpdesk_livechat_trigger_rules` | Reglas configurables del cliente | Bajo (decenas por inbox) |
| `helpdesk_livechat_personalization_rules` | Reglas DOM swap | Bajo (decenas por inbox) |
| `helpdesk_livechat_recommendation_profiles` | Cache para recomendador | 1 fila por customer |

---

## 1. `helpdesk_livechat_events`

Event log inmutable. Append-only. TTL 90 días + sumarización mensual a tabla agregada.

```sql
CREATE TABLE helpdesk_livechat_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_token   VARCHAR(64)    NOT NULL,
    customer_id     BIGINT UNSIGNED NULL,
    inbox_id        BIGINT UNSIGNED NOT NULL,
    event_name      VARCHAR(64)    NOT NULL,
    properties      JSON           NULL,
    page_url        VARCHAR(2048)  NULL,
    page_title      VARCHAR(512)   NULL,
    referrer        VARCHAR(2048)  NULL,
    user_agent      VARCHAR(512)   NULL,
    ip_address      VARCHAR(45)    NULL,
    occurred_at     DATETIME       NOT NULL,
    received_at     DATETIME       NOT NULL,
    created_at      DATETIME       NULL,
    updated_at      DATETIME       NULL,

    INDEX idx_session_occurred  (session_token, occurred_at),
    INDEX idx_customer_event    (customer_id, event_name, occurred_at),
    INDEX idx_inbox_event       (inbox_id, event_name, occurred_at),
    INDEX idx_event_occurred    (event_name, occurred_at),

    CONSTRAINT fk_lc_events_customer
        FOREIGN KEY (customer_id) REFERENCES helpdesk_customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_lc_events_inbox
        FOREIGN KEY (inbox_id)    REFERENCES helpdesk_inboxes(id)   ON DELETE CASCADE
);
```

**Notas:**
- `session_token` no tiene FK a `helpdesk_widget_sessions.session_token` para tolerar eventos que llegan antes del init formal de sesión.
- `properties` es JSON libre; documentar el shape por evento estándar en este mismo archivo (sección 7).
- `occurred_at` viene del cliente (puede mentir, validar contra `received_at` y rechazar si delta > 24h).
- Considerar particionado por `RANGE (TO_DAYS(occurred_at))` mensual cuando supere 10M filas.

### Eventos estándar (shape de `properties`)

| `event_name` | `properties` |
|---|---|
| `page_view` | `{ url, title, referrer }` (auto desde `page_url`/`page_title`) |
| `session_start` | `{ device, country, viewport, language }` |
| `session_end` | `{ duration_seconds, pages_viewed }` |
| `product_view` | `{ id, name, price, currency, category, image_url? }` |
| `add_to_cart` | `{ id, name, price, currency, quantity }` |
| `remove_from_cart` | `{ id, quantity }` |
| `checkout_start` | `{ cart_value, items_count, currency }` |
| `purchase` | `{ order_id, total, currency, items: [{id, qty, price}] }` |
| `form_submit` | `{ form_id, fields_count }` |
| `link_click` | `{ url, text, external: bool }` |
| custom | libre |

---

## 2. `helpdesk_livechat_visitor_scores`

Denormalización del score actual para lecturas rápidas. Se actualiza vía `ScoringService` tras cada batch de eventos.

```sql
CREATE TABLE helpdesk_livechat_visitor_scores (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_token   VARCHAR(64)    NOT NULL UNIQUE,
    customer_id     BIGINT UNSIGNED NULL,
    inbox_id        BIGINT UNSIGNED NOT NULL,
    score           SMALLINT       NOT NULL DEFAULT 0,
    segment         ENUM('cold','warm','hot') NOT NULL DEFAULT 'cold',
    last_event_at   DATETIME       NULL,
    last_recalc_at  DATETIME       NULL,
    created_at      DATETIME       NULL,
    updated_at      DATETIME       NULL,

    INDEX idx_segment_score  (segment, score),
    INDEX idx_customer       (customer_id),

    CONSTRAINT fk_lc_scores_customer
        FOREIGN KEY (customer_id) REFERENCES helpdesk_customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_lc_scores_inbox
        FOREIGN KEY (inbox_id)    REFERENCES helpdesk_inboxes(id)   ON DELETE CASCADE
);
```

**Fórmula de score (inicial, ajustable):**

```
score = clamp(
    pages * 2
  + time_on_site_minutes * 1
  + cart_value_eur * 0.5
  + product_views * 3
  + add_to_carts * 8
  + checkout_starts * 20,
  0,
  100
)

segment = score < 25 ? 'cold' : score < 60 ? 'warm' : 'hot'
```

---

## 3. `helpdesk_livechat_visitor_contexts`

Contexto dinámico modificable por SDK (`chat.setContext({...})`). Una fila por sesión activa.

```sql
CREATE TABLE helpdesk_livechat_visitor_contexts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_token   VARCHAR(64)    NOT NULL UNIQUE,
    customer_id     BIGINT UNSIGNED NULL,
    inbox_id        BIGINT UNSIGNED NOT NULL,
    context         JSON           NOT NULL,
    updated_at      DATETIME       NULL,
    created_at      DATETIME       NULL,

    INDEX idx_customer (customer_id),

    CONSTRAINT fk_lc_contexts_customer
        FOREIGN KEY (customer_id) REFERENCES helpdesk_customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_lc_contexts_inbox
        FOREIGN KEY (inbox_id)    REFERENCES helpdesk_inboxes(id)   ON DELETE CASCADE
);
```

**Shape sugerido del JSON:**
```json
{
  "cart_value": 120.50,
  "currency": "EUR",
  "items_count": 3,
  "items": [{ "id": "SKU123", "qty": 1 }],
  "is_logged_in": true,
  "membership_tier": "gold"
}
```

---

## 4. `helpdesk_livechat_trigger_rules`

Reglas configurables por cliente (admin UI). Las consume el SDK al hacer `init()` y las re-evalúa lado cliente.

```sql
CREATE TABLE helpdesk_livechat_trigger_rules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inbox_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(160)   NOT NULL,
    description     VARCHAR(512)   NULL,
    conditions      JSON           NOT NULL,
    action          JSON           NOT NULL,
    priority        SMALLINT       NOT NULL DEFAULT 0,
    fires_per_session SMALLINT     NOT NULL DEFAULT 1,
    is_active       TINYINT(1)     NOT NULL DEFAULT 1,
    created_at      DATETIME       NULL,
    updated_at      DATETIME       NULL,

    INDEX idx_inbox_active (inbox_id, is_active, priority),

    CONSTRAINT fk_lc_triggers_inbox
        FOREIGN KEY (inbox_id) REFERENCES helpdesk_inboxes(id) ON DELETE CASCADE
);
```

**Shape `conditions`:**
```json
{
  "operator": "AND",
  "rules": [
    { "type": "url",   "operator": "contains", "value": "/checkout" },
    { "type": "score", "operator": ">=",       "value": 60 },
    { "type": "time_on_page", "operator": ">", "value": 30 },
    { "type": "context", "key": "cart_value", "operator": ">", "value": 100 }
  ]
}
```

**Shape `action`:**
```json
{ "type": "open_chat" }
{ "type": "show_banner", "html": "<div>...</div>", "selector": "body" }
{ "type": "redirect", "url": "https://..." }
{ "type": "callback", "name": "onCheckoutHelp" }
```

---

## 5. `helpdesk_livechat_personalization_rules`

Mutaciones DOM por reglas (cambiar texto de botones, mostrar banners contextuales).

```sql
CREATE TABLE helpdesk_livechat_personalization_rules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inbox_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(160)   NOT NULL,
    selector        VARCHAR(512)   NOT NULL,
    conditions      JSON           NULL,
    mutation        JSON           NOT NULL,
    is_active       TINYINT(1)     NOT NULL DEFAULT 1,
    created_at      DATETIME       NULL,
    updated_at      DATETIME       NULL,

    INDEX idx_inbox_active (inbox_id, is_active),

    CONSTRAINT fk_lc_personalizations_inbox
        FOREIGN KEY (inbox_id) REFERENCES helpdesk_inboxes(id) ON DELETE CASCADE
);
```

**Shape `mutation`:**
```json
{ "op": "text",      "value": "Hablar con asesor" }
{ "op": "attribute", "name": "href", "value": "javascript:chat.open()" }
{ "op": "insert_before", "html": "<div class='offer'>10% extra</div>" }
{ "op": "class",     "add": ["highlight"], "remove": ["dim"] }
```

---

## 6. `helpdesk_livechat_recommendation_profiles`

Cache para recomendador. Una fila por customer identificado.

```sql
CREATE TABLE helpdesk_livechat_recommendation_profiles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NOT NULL UNIQUE,
    viewed_products JSON           NULL,
    categories      JSON           NULL,
    cart_history    JSON           NULL,
    last_purchased_at DATETIME     NULL,
    updated_at      DATETIME       NULL,
    created_at      DATETIME       NULL,

    CONSTRAINT fk_lc_reco_customer
        FOREIGN KEY (customer_id) REFERENCES helpdesk_customers(id) ON DELETE CASCADE
);
```

**Shape JSON:**
```json
{
  "viewed_products": [
    { "id": "SKU1", "name": "...", "category": "shoes", "viewed_at": "...", "count": 3 }
  ],
  "categories": { "shoes": 5, "shirts": 2 },
  "cart_history": [{ "ids": ["SKU1","SKU2"], "value": 120, "abandoned_at": "..." }]
}
```

---

## 7. Roadmap de optimización

| Cuándo | Acción |
|---|---|
| > 1M filas en `helpdesk_livechat_events` | Job nightly: borrar > 90 días, agregar a `helpdesk_livechat_events_monthly`. |
| > 10M filas | Particionado mensual `RANGE (TO_DAYS(occurred_at))`. |
| > 100M filas | Considerar mover a ClickHouse / TimescaleDB y mantener solo último mes en MySQL. |

## 8. Archivos de migration esperados

```
modules/HelpdeskLivechat/database/migrations/
├── 2026_05_03_100001_create_helpdesk_livechat_events_table.php
├── 2026_05_03_100002_create_helpdesk_livechat_visitor_scores_table.php
├── 2026_05_03_100003_create_helpdesk_livechat_visitor_contexts_table.php
├── 2026_05_03_100004_create_helpdesk_livechat_trigger_rules_table.php
├── 2026_05_03_100005_create_helpdesk_livechat_personalization_rules_table.php
└── 2026_05_03_100006_create_helpdesk_livechat_recommendation_profiles_table.php
```

Todas con `down()` que hace `Schema::dropIfExists()`.

## 9. Factories y seeders

```
database/factories/
├── LivechatEventFactory.php
├── VisitorScoreFactory.php
├── VisitorContextFactory.php
├── TriggerRuleFactory.php
├── PersonalizationRuleFactory.php
└── RecommendationProfileFactory.php

database/seeders/
└── HelpdeskLivechatPermissionsSeeder.php
```
