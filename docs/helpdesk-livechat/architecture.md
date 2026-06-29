# HelpdeskLivechat — Arquitectura interna

**Estado:** documento vivo. Actualizar al cerrar cada fase.

## 1. Posición en la arquitectura modular

`HelpdeskLivechat` es la capa pública del módulo `Helpdesk`. Aporta todo lo que mira a internet:

- Widget de chat embebido en sitios externos.
- API pública sin autenticación (`/hd/api/*`).
- SDK JavaScript con tracking, scoring, triggers, personalización, recomendador.
- Configuración del canal web (look & feel, pre-chat, business hours).
- Reglas de automatización del lado cliente.

`Helpdesk` (núcleo) provee los modelos canónicos del dominio: `Inbox`, `Customer`, `Conversation`, `ConversationItem`, statuses, priorities. `HelpdeskLivechat` los referencia por FK + `use` cross-module, sin duplicar.

```
┌────────────────────────────────────────────────────────────────────┐
│                     HelpdeskLivechat                               │
│                                                                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │  Widget UI   │  │   SDK JS     │  │   Settings (admin)       │  │
│  │  (React)     │  │   (vanilla)  │  │   - widget config        │  │
│  │              │  │              │  │   - trigger rules        │  │
│  │              │  │              │  │   - personalization      │  │
│  └──────┬───────┘  └──────┬───────┘  │   - events read-only     │  │
│         │                 │          └──────────────────────────┘  │
│         ▼                 ▼                                        │
│  ┌──────────────────────────────────────┐                          │
│  │  HTTP API  /hd/api/{conversation,    │                          │
│  │  messages, settings, sdk/*}          │                          │
│  └─────────────────┬────────────────────┘                          │
│                    │                                               │
│  ┌─────────────────┴────────────────────┐                          │
│  │  Services + Jobs + Events            │                          │
│  │  (TrackingIngest, Scoring, Trigger,  │                          │
│  │   Recommender, SessionLink)          │                          │
│  └─────────────────┬────────────────────┘                          │
│                    │                                               │
│  ┌─────────────────┴────────────────────┐                          │
│  │  Modelos: WidgetSession, PageView,   │                          │
│  │  Channels\Web, LivechatEvent,        │                          │
│  │  VisitorScore, VisitorContext,       │                          │
│  │  TriggerRule, PersonalizationRule,   │                          │
│  │  RecommendationProfile               │                          │
│  └─────────────────┬────────────────────┘                          │
└────────────────────┼───────────────────────────────────────────────┘
                     │ (FK + cross-module use)
                     ▼
┌────────────────────────────────────────────────────────────────────┐
│                          Helpdesk (core)                           │
│   Inbox · Customer · Conversation · ConversationItem · statuses    │
└────────────────────────────────────────────────────────────────────┘
```

## 2. Fronteras y responsabilidades

| Vive en `Helpdesk` | Vive en `HelpdeskLivechat` |
|---|---|
| Modelos de dominio canónico | Modelos de canal web y engagement |
| Reglas de tickets/conversaciones | Reglas de widget, triggers, personalización |
| API agentes / panel | API pública del widget + SDK |
| Notificaciones a agentes | Notificaciones broadcast a visitor (WS) |
| Eventos `Conversation*` (privados) | Eventos `ScoreThresholdCrossed`, `TriggerFired` |

**Regla:** si una capacidad sólo tiene sentido para el visitante (web), va en `HelpdeskLivechat`. Si sirve a varios canales (web + email + social), va en `Helpdesk`.

## 3. Flujo de datos — primer visitante hasta conversación

```
1. <script src="/widget/script/{websiteToken}"></script>     [host site]
2. SDK arranca init() → POST /hd/api/sdk/init                [HelpdeskLivechat]
   - resuelve Channels\Web por website_token
   - busca/crea WidgetSession con session_token
   - devuelve { visitorId, sessionToken, score, segment, triggers, personalizations }
3. SDK pinta personalizaciones DOM, monta motor de triggers
4. Usuario navega → page_view, scroll, ecommerce events
   → batch en cola → POST /hd/api/sdk/track                  [HelpdeskLivechat]
   → ScoringService recalcula → broadcast WS si cambia segmento
5. Trigger dispara: chat.open() → widgetBridge carga widget.js (lazy)
6. Visitor manda mensaje → POST /hd/api/conversation         [HelpdeskLivechat → Helpdesk]
   → crea Conversation + ConversationItem en helpdesk_*
   → broadcast a agentes via Helpdesk events
7. Agente responde → ConversationMessageCreated → visitor recibe via WS
```

## 4. Conexión BD

Todos los modelos de `HelpdeskLivechat` usan `protected $connection = 'helpdesk'`. Las tablas nuevas (prefijo `helpdesk_livechat_*`) viven en la misma BD que las del core para permitir JOINs.

## 5. Build & assets

| Bundle | Source | Output |
|---|---|---|
| Widget UI completo | `resources/assets/widget/widget-embed.ts` | `public/build-helpdesk-livechat/widget.js` |
| SDK engagement | `resources/assets/sdk/index.ts` | `public/build-helpdesk-livechat/sdk.js` |

`vite.config.js` en raíz del módulo define dos entradas. SDK NO depende del widget — el widget se carga dinámicamente sólo si `chat.open()` se invoca.

## 6. Real-time

Reverb compartido con el resto de la app. Canales del módulo:

| Canal | Tipo | Quién subscribe | Eventos |
|---|---|---|---|
| `widget-session.{sessionToken}` | público (validado) | SDK del visitor | `ScoreThresholdCrossed`, `TriggerFired` |
| `helpdesk-livechat.conversation.{id}` | público firmado / privado | widget UI del visitor | mensajes del agente |
| (reutilizado) `helpdesk.conversation.{id}` | privado | agentes | mensajes del visitor |

> Decisión final del canal del visitor en Fase 2.

## 7. Permisos y autorización

- Endpoints SDK: sin Sanctum, validados por `X-Website-Token` (header) + `X-Session-Token` cuando aplica. Middleware `EnsureWebsiteToken` resuelve el inbox y bloquea si el token no existe.
- Endpoints settings: Sanctum + Spatie permissions con prefijo `helpdesk.livechat.*`.
- Widget API legacy `/hd/api/conversation/*`: sigue el patrón actual (validación por `website_token` en el body).

## 8. Naming y convenciones

- Tablas: `helpdesk_livechat_{entity}` (snake plural).
- Permisos: `helpdesk.livechat.{entity}.{action}`.
- Routes name: `helpdesk-livechat.widget.*` (público) y `settings.helpdesk-livechat.*` (admin).
- Modelos: PascalCase singular sin prefijo (`LivechatEvent`, no `HelpdeskLivechatEvent`).
- Eventos: `{Entity}{PastTense}` con `ShouldBroadcast`.
- Permisos en seeders: `firstOrCreate`, guard `web`.

## 9. Tests

- Feature en `tests/Feature/`: un archivo por endpoint público y por flujo end-to-end.
- Unit en `tests/Unit/`: services aislados (Scoring, TriggerEvaluator, Recommender).
- JS unit con Vitest en `tests/js/`.
- E2E con Chrome DevTools MCP en CI: visitor anónimo recorre página, dispara trigger, abre chat, manda mensaje, recibe respuesta del agente simulado.
