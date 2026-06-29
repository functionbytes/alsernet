# HelpdeskLivechat — Documentación

Documentación del módulo **HelpdeskLivechat** (canal web del helpdesk: widget + livechat + SDK de engagement).

## Estructura

| Documento | Audiencia | Contenido |
|---|---|---|
| [`../PLAN_SDK_ENGAGEMENT.md`](../PLAN_SDK_ENGAGEMENT.md) | Equipo dev | Plan global, fases, decisiones pendientes |
| [`architecture.md`](./architecture.md) | Arquitectos / dev | Diagrama de capas, fronteras con `Helpdesk` core, flujo de datos |
| [`database-schema.md`](./database-schema.md) | DB / backend | DDL detallado de las 6 tablas nuevas + roadmap de optimización |
| [`sdk-api-reference.md`](./sdk-api-reference.md) | Backend / integradores | Contratos REST `/hd/api/sdk/*` + WebSocket |
| [`sdk-js-reference.md`](./sdk-js-reference.md) | Integradores | API pública `window.chat` con ejemplos |
| [`integration-prestashop.md`](./integration-prestashop.md) | Integradores | Guía paso a paso PrestaShop / HTML / CSP / GDPR |
| [`examples/basic.html`](./examples/basic.html) | Integradores | Demo HTML funcional con todos los métodos |

## Estado del módulo

- ✅ Plan + documentación de soporte (este directorio).
- ✅ Esqueleto del módulo (`module.json`, ServiceProvider, 3 routes vacíos, 3 modelos base).
- ⏳ Schema nuevo (tablas livechat_events, scores, contexts, triggers, personalizations, recommendations).
- ⏳ API REST `/hd/api/sdk/*` (7 endpoints).
- ⏳ SDK TypeScript (vanilla).
- ⏳ Widget UI livechat.
- ⏳ Admin UI (settings + reglas).
- ⏳ Tests Feature + Unit + E2E.

Ver [`../PLAN_SDK_ENGAGEMENT.md`](../PLAN_SDK_ENGAGEMENT.md) sección §6 para el roadmap de fases con asignación de agentes.

## Convenciones específicas del módulo

| Aspecto | Convención |
|---|---|
| Conexión BD | `helpdesk` (compartida con módulo core) |
| Prefijo tablas nuevas | `helpdesk_livechat_*` |
| Permisos Spatie | `helpdesk.livechat.{entity}.{action}` |
| Routes name (público) | `helpdesk-livechat.widget.*` |
| Routes name (admin) | `settings.helpdesk-livechat.*` |
| API prefix público | `/hd/api/sdk/*` |
| Bundle output | `public/build-helpdesk-livechat/{widget,sdk}.js` |
| API JS pública | `window.chat` |
| Storage key visitor | `__hd_lc_vid` |
| WS canal visitor | `widget-session.{sessionToken}` |

## Glosario rápido

| Término | Significado |
|---|---|
| **website_token** | 32 chars, identifica el `Channels\Web` (canal/tenant). Público. |
| **session_token** | 64 chars, identifica una `WidgetSession`. Pseudo-secreto (sólo client + backend). |
| **visitor_id** | UUID v4 generado por el SDK, persiste cross-session en `localStorage`. |
| **inbox** | bandeja de agentes; un canal Web pertenece a un Inbox. |
| **score** | entero 0-100 calculado por `ScoringService`. |
| **segment** | `cold` / `warm` / `hot` derivado del score. |
| **trigger rule** | regla cliente-evaluable que dispara una acción (open chat, banner, …). |
| **personalization rule** | mutación DOM aplicada según condiciones. |
