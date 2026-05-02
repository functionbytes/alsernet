# Plan — SDK de Customer Engagement dentro de `HelpdeskLivechat`

**Fecha:** 2026-05-02
**Última revisión:** 2026-05-02 (todo unificado en `modules/HelpdeskLivechat`).
**Objetivo:** Diseñar y desarrollar un SDK JS completo (tipo Intercom/Drift/Crisp) que viva dentro del módulo `HelpdeskLivechat`, junto con el widget UI, las APIs públicas, tracking avanzado, scoring, triggers, personalización dinámica y recomendador.
**Stack:** Laravel 12 + nwidart modules + Reverb + Vite + TypeScript.

---

## 1. Estado actual del módulo `HelpdeskLivechat`

Confirmado leyendo `modules/HelpdeskLivechat/`:

### Ya existe

- `module.json` con `requires: ["Helpdesk"]`, alias `helpdesklivechat`.
- ServiceProvider registrando 3 archivos de rutas con prefijos:
  - `web-widget.php` → middleware `web`, sin prefijo (rutas público / embed).
  - `widget.php` → middleware `api` + `throttle:120,1`, prefijo `/hd/api`, name `helpdesk-livechat.widget.`.
  - `settings.php` → middleware `web,auth`, prefijo `/panel/settings/helpdesk`, name `settings.helpdesk-livechat.`.
- Conexión BD separada: **`helpdesk`** (todos los modelos del módulo usan `protected $connection = 'helpdesk'`).
- Sidebar: entrada "Livechat" en `settings`.
- 3 migrations: `helpdesk_channel_webs`, `helpdesk_widget_sessions`, `helpdesk_widget_page_views`, + una de unificación de alias web↔widget.
- 3 modelos: `Channels\Web` (con `website_token`, `hmac_token`, `getWidgetConfig()`), `WidgetSession`, `WidgetPageView`.
- Estructura de carpetas creada: `app/{Http,Models,Providers,Services}`, `database/{migrations,factories,seeders}`, `routes/`, `resources/{assets,js,views}`, `tests/`.

### Falta todo

- Controllers, Form Requests, Resources, services con lógica.
- Eventos broadcast + `routes/channels.php`.
- Migrations + modelos para tracking events / scoring / triggers / personalización / recomendador.
- Frontend: widget UI (puede reaprovechar lo que esté en `modules/Helpdesk/resources/assets/js/widget/` o reescribirse aquí) + SDK JS.
- Vite config, build pipeline, output a `public/build-helpdesk-livechat/`.
- Permissions seeder `HelpdeskLivechatPermissionsSeeder`.
- Tests (Feature + Unit) — el directorio existe pero está vacío.

---

## 2. Decisión de arquitectura — todo dentro de `HelpdeskLivechat`

```
                         ┌──────────────────────────────────────┐
        <script>────────►│         SDK (window.chat)            │
                         │  init / identify / track / context   │
                         │  open / close / setConsent / on      │
                         └──────┬─────────────────────┬─────────┘
                                │                     │
                  REST + WS     │                     │     REST (legacy)
                                ▼                     ▼
              ┌───────────────────────────────────────────────────┐
              │           HelpdeskLivechat (este módulo)          │
              │                                                   │
              │  /hd/api/sdk/*       — SDK endpoints (nuevos)     │
              │  /hd/api/conversation, /hd/api/messages — widget  │
              │  /widget/script/{token} — embed loader            │
              │                                                   │
              │  Tablas: helpdesk_livechat_events, scores,        │
              │  contexts, trigger_rules, personalization_rules,  │
              │  recommendation_profiles                          │
              │                                                   │
              │  Widget UI + SDK build                            │
              └─────────────────────┬─────────────────────────────┘
                                    │
                                    ▼
                 ┌───────────────────────────────────────┐
                 │        Helpdesk (módulo core)         │
                 │  Inbox, Conversation, Customer,       │
                 │  ConversationItem, eventos broadcast  │
                 └───────────────────────────────────────┘
```

**Reglas de fronteras:**
- `Helpdesk` mantiene los modelos canónicos: `Inbox`, `Customer`, `Conversation`, `ConversationItem`, status, priority, etc.
- `HelpdeskLivechat` añade todo lo del canal web: widget UI, widget API, SDK, tracking, scoring, triggers, personalización, recomendador.
- `HelpdeskLivechat` referencia entidades de `Helpdesk` por FK + `use` cross-module. No duplicamos modelos.
- Un cambio en `Helpdesk` (e.g. nueva columna en `helpdesk_customers`) solo lo gestiona el equipo de Helpdesk; `HelpdeskLivechat` consume.

---

## 3. Backend — extensiones a `HelpdeskLivechat`

### 3.1 Migrations + tablas nuevas

Prefijo coherente con las existentes: `helpdesk_livechat_*`.

| Tabla | Columnas clave | Índices |
|---|---|---|
| `helpdesk_livechat_events` | `id`, `session_token`, `customer_id` (nullable FK `helpdesk_customers`), `inbox_id` (FK `helpdesk_inboxes`), `event_name`, `properties JSON`, `page_url`, `occurred_at` | `(session_token, occurred_at)`, `(customer_id, event_name, occurred_at)`, `(inbox_id, event_name, occurred_at)` |
| `helpdesk_livechat_visitor_scores` | `id`, `session_token UNIQUE`, `customer_id` (nullable), `score INT`, `segment ENUM(cold,warm,hot)`, `last_event_at`, `updated_at` | `(segment, score)` |
| `helpdesk_livechat_visitor_contexts` | `id`, `session_token UNIQUE`, `customer_id`, `context JSON`, `updated_at` | — |
| `helpdesk_livechat_trigger_rules` | `id`, `inbox_id` (FK), `name`, `conditions JSON`, `action JSON`, `priority`, `is_active`, `fires_per_session INT` | `(inbox_id, is_active)` |
| `helpdesk_livechat_personalization_rules` | `id`, `inbox_id` (FK), `name`, `selector`, `conditions JSON`, `mutation JSON`, `is_active` | `(inbox_id, is_active)` |
| `helpdesk_livechat_recommendation_profiles` | `id`, `customer_id UNIQUE`, `viewed_products JSON`, `categories JSON`, `cart_history JSON`, `updated_at` | — |

Todas en conexión `helpdesk` (igual que el resto del módulo).

### 3.2 Modelos nuevos (en `app/Models/`)

```
app/Models/
├── Channels/Web.php            (ya existe)
├── WidgetSession.php           (ya existe)
├── WidgetPageView.php          (ya existe)
├── LivechatEvent.php           (nuevo)
├── VisitorScore.php            (nuevo)
├── VisitorContext.php          (nuevo)
├── TriggerRule.php             (nuevo)
├── PersonalizationRule.php     (nuevo)
└── RecommendationProfile.php   (nuevo)
```

Todos con `protected $connection = 'helpdesk'`, `casts()` method, relaciones explícitas, return types.

### 3.3 Controllers & rutas API

Añadir a **`routes/widget.php`** (prefijo `/hd/api`, throttle 120/1, name `helpdesk-livechat.widget.`):

```
POST   /hd/api/sdk/init                     name: sdk.init
POST   /hd/api/sdk/identify                 name: sdk.identify
POST   /hd/api/sdk/track                    name: sdk.track
POST   /hd/api/sdk/context                  name: sdk.context
GET    /hd/api/sdk/triggers                 name: sdk.triggers.index
GET    /hd/api/sdk/personalizations         name: sdk.personalizations.index
GET    /hd/api/sdk/recommendations          name: sdk.recommendations.index
```

Estructura controllers:
```
app/Http/Controllers/Api/Sdk/
├── InitController.php
├── IdentifyController.php
├── TrackController.php
├── ContextController.php
├── TriggerController.php
├── PersonalizationController.php
└── RecommendationController.php
```

Form Requests en `app/Http/Requests/Sdk/`. Resources en `app/Http/Resources/Sdk/`.

Middleware nuevo `EnsureWebsiteToken` (resuelve `Channels\Web` por header `X-Website-Token` o body `website_token`, lo inyecta en el request, valida `hmac_token` cuando aplica).

### 3.4 Services

```
app/Services/
├── TrackingIngestService.php       # absorbe batch, valida, encola
├── ScoringService.php              # score = f(time, pages, cart_value, events)
├── TriggerEvaluator.php            # server-side fallback
├── RecommenderService.php          # productos vistos + categoría + carrito
├── SessionLinkService.php          # vincula evento ↔ WidgetSession
└── WidgetConfigService.php         # devuelve config completo por website_token
```

### 3.5 Jobs + Events

```
app/Jobs/
├── ProcessLivechatBatchJob.php     # queue: 'helpdesklivechat'
└── RecalculateScoreJob.php

app/Events/
├── ScoreThresholdCrossed.php       # ShouldBroadcast
└── TriggerFired.php                # ShouldBroadcast
```

### 3.6 Broadcast channels — `routes/channels.php` (nuevo)

```
widget-session.{sessionToken}       # público, validado por longitud + existencia
helpdesk-livechat.conversation.{id} # privado o firmado por session_token (decidir)
```

> ⚠️ Decisión pendiente: cómo recibe el visitor anónimo los mensajes del agente. Opciones:
> - (a) Canal público dedicado validado por `session_token`.
> - (b) Reutilizar el canal de `Helpdesk` con autorización custom.
> - (c) Polling REST en MVP, WS en fase 2.

### 3.7 Permissions

```
helpdesk.livechat.events.view
helpdesk.livechat.scores.view
helpdesk.livechat.triggers.view
helpdesk.livechat.triggers.create
helpdesk.livechat.triggers.update
helpdesk.livechat.triggers.delete
helpdesk.livechat.personalizations.view
helpdesk.livechat.personalizations.create
helpdesk.livechat.personalizations.update
helpdesk.livechat.personalizations.delete
helpdesk.livechat.settings.view
helpdesk.livechat.settings.update
helpdesk.livechat.manage
```

Seeder: `HelpdeskLivechatPermissionsSeeder` con `firstOrCreate`, guard `web`.

---

## 4. Frontend — Widget UI + SDK

### 4.1 Estructura

```
modules/HelpdeskLivechat/resources/assets/
├── widget/                      # widget UI (chat completo)
│   ├── widget-embed.ts          # script loader que inyecta widget en host
│   ├── widget-entry.tsx         # punto de entrada SPA
│   ├── components/
│   ├── screens/
│   └── echo.ts                  # Reverb / Echo
├── sdk/                         # SDK engagement (sin React)
│   ├── core/
│   │   ├── init.ts
│   │   ├── config.ts
│   │   ├── session.ts
│   │   ├── eventBus.ts
│   │   └── lifecycle.ts
│   ├── modules/
│   │   ├── tracking/
│   │   ├── identify/
│   │   ├── context/
│   │   ├── scoring/
│   │   ├── triggers/
│   │   ├── personalization/
│   │   ├── chat/                # widgetBridge (lazy load del widget)
│   │   ├── automation/
│   │   └── recommender/
│   ├── services/
│   │   ├── apiClient.ts
│   │   ├── transport.ts
│   │   ├── queue.ts             # IndexedDB → fallback localStorage
│   │   ├── storage.ts
│   │   └── realtime.ts
│   ├── utils/
│   ├── index.ts                 # define window.chat
│   └── types.ts
└── styles/                      # CSS del widget
```

### 4.2 Vite config — dos entradas, dos bundles

`vite.config.js` en raíz del módulo:

| Entry | Output | Tamaño objetivo |
|---|---|---|
| `widget-embed.ts` | `public/build-helpdesk-livechat/widget.js` | bundle widget completo |
| `sdk/index.ts` | `public/build-helpdesk-livechat/sdk.js` | < 50 KB gz (gate CI 60 KB) |

### 4.3 API pública del SDK

```ts
window.chat = {
  init(opts: { token: string; apiUrl?: string; debug?: boolean; consent?: boolean }): void;
  identify(user: { id?: string; name?: string; email?: string; [k: string]: any }): void;
  track(eventName: string, properties?: Record<string, any>): void;
  setContext(ctx: Record<string, any>): void;
  setConsent(granted: boolean): void;
  open(): void;
  close(): void;
  on(event: string, handler: Function): void;
};
```

### 4.4 Decisiones técnicas

- TypeScript estricto. Vanilla en SDK (sin React).
- visitor_id UUID v4 en `localStorage` (`__hd_lc_vid`). Cookie respaldo SameSite=Lax.
- Cola: IndexedDB preferente, localStorage fallback. Flush en online + `visibilitychange:hidden` con `navigator.sendBeacon`.
- Batch: 10 eventos o 5 segundos.
- `async defer` + `requestIdleCallback`.
- Personalization usa `MutationObserver` debounced.
- SDK NO renderiza UI (banners DOM aparte). El chat lo abre vía `widgetBridge.ts` con import dinámico de `widget.js`.
- CSP-friendly (sin `eval`, sin `new Function`).
- Consent: si tenant requiere opt-in, `init()` queda en stand-by hasta `setConsent(true)`.

---

## 5. Settings (admin UI) — qué hace falta dentro del módulo

Ya hay `routes/settings.php` con prefijo `/panel/settings/helpdesk` y middleware `web,auth`. Implementar:

| Vista | Ruta | Permiso |
|---|---|---|
| Configuración del widget (look & feel, pre-chat, offline, business hours) | `settings.helpdesk-livechat.index` | `helpdesk.livechat.settings.view` |
| Reglas de triggers (DataGrid + form modal) | `settings.helpdesk-livechat.triggers.*` | `helpdesk.livechat.triggers.*` |
| Reglas de personalización | `settings.helpdesk-livechat.personalizations.*` | `helpdesk.livechat.personalizations.*` |
| Eventos (read-only DataGrid con filtros) | `settings.helpdesk-livechat.events.index` | `helpdesk.livechat.events.view` |
| Scores / segmentos (read-only) | `settings.helpdesk-livechat.scores.index` | `helpdesk.livechat.scores.view` |

Bootstrap 5.3 + DevExpress jQuery + Font Awesome 6 (regla del proyecto).

---

## 6. Plan de fases (con asignación de agentes)

| Fase | Entregable | Agentes |
|---|---|---|
| **0. Estado** | Plan + documentación de arquitectura, schema, API, integración. (en curso) | docs |
| **1. Schema** | 6 migrations + factories + seeders + 6 modelos Eloquent. | database |
| **2. API SDK** | 7 controllers + middleware `EnsureWebsiteToken` + Form Requests + Resources + services + permissions seeder + tests Feature. | api, backend, testing |
| **3. SDK Core** | TS: core, services, utils. Vite config + build pipeline. visitor_id + session persistence. Cola IndexedDB. | frontend |
| **4. SDK Tracking** | tracking/identify/context. page_view auto. Eventos ecommerce. Vitest unit tests. | frontend, testing |
| **5. Scoring + Triggers** | ScoringService backend. localScorer + engine SDK. Sync score WS. Admin UI reglas. | backend, frontend |
| **6. Personalización + Recommender** | domMutator. Recommender productos vistos+categoría. Endpoint /recommendations. | backend, frontend |
| **7. Widget UI livechat** | Migrar/construir widget completo dentro de `resources/assets/widget/`. widgetBridge en SDK. | frontend |
| **8. Multicanal scaffolding** | Interfaces email/sms/whatsapp. Sin APIs externas. | backend |
| **9. Documentación final + ejemplo** | docs/helpdesk-livechat/ completos. HTML demo. README PrestaShop. | docs |
| **10. QA + perf** | Bundle gate CI. E2E Chrome DevTools MCP. Lighthouse. Coverage. | testing, performance |

---

## 7. Riesgos y consideraciones

- **CSP estricta** (PrestaShop) — documentar headers requeridos.
- **ITP/Safari** — `localStorage` 7 días. Documentar y considerar cookie HttpOnly.
- **GDPR** — `setConsent()` obligatorio en jurisdicciones UE.
- **Bundle size** — gate CI estricto.
- **Coexistencia widget / SDK** — namespacing.
- **Coste Reverb** — evitar suscripciones duplicadas.
- **Conexión BD `helpdesk`** — todas las tablas nuevas deben usar la misma conexión.

---

## 8. Decisiones a confirmar antes de empezar Fase 1

1. **API surface:** `window.chat` como pediste. ¿Alias `window.helpdeskLivechat` interno OK?
2. **Retención eventos:** TTL **90 días** + sumarización mensual a `helpdesk_livechat_events_monthly`. ¿Confirmas?
3. **Consent default:** opt-in (UE-safe) vs opt-out, configurable por inbox. ¿Cuál default global?
4. **Canal WS visitor:** público firmado por `session_token`. ¿OK o polling REST en MVP?
5. **Admin UI MVP scope:** sólo CRUD `trigger_rules` + `personalization_rules`, vistas read-only de eventos/scores. ¿Suficiente?
6. **Widget UI:** ¿reaprovechar el código React existente en `modules/Helpdesk/resources/assets/js/widget/` (mover/copiar) o reescribir desde cero aquí?

Una vez confirmes los 6 puntos arrancamos **Fase 1 (schema)** y **Fase 2 (API SDK)** en paralelo con agentes especializados.

---

## 9. Documentación de soporte

Generada en paralelo a este plan:

- `docs/helpdesk-livechat/architecture.md` — arquitectura interna y fronteras.
- `docs/helpdesk-livechat/database-schema.md` — DDL detallado de las 6 tablas nuevas.
- `docs/helpdesk-livechat/sdk-api-reference.md` — contratos REST `/hd/api/sdk/*`.
- `docs/helpdesk-livechat/sdk-js-reference.md` — API pública `window.chat` con ejemplos.
- `docs/helpdesk-livechat/integration-prestashop.md` — guía de embed en PrestaShop / HTML.
- `docs/helpdesk-livechat/examples/basic.html` — demo de integración.
