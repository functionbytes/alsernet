# HelpdeskLivechat — SDK REST API Reference

**Base URL:** `https://{tu-dominio}/hd/api/sdk`
**Autenticación:** sin Sanctum. Validación por headers `X-Website-Token` (siempre) y `X-Session-Token` (cuando aplica).
**Rate limit:** `120 req/min` por defecto, sobre-escribible por endpoint.
**Content-Type:** `application/json` en request y response.
**Fechas:** ISO8601 (`2026-05-02T13:45:00+00:00`).
**Keys JSON:** `camelCase`.

## Headers comunes

| Header | Cuándo | Notas |
|---|---|---|
| `X-Website-Token` | Siempre | 32 chars, identifica el `Channels\Web` |
| `X-Session-Token` | Tras `/init` | 64 chars, identifica la `WidgetSession` |
| `X-SDK-Version` | Siempre (recomendado) | Permite degradación graciosa por versión |
| `X-Visitor-Id` | Opcional | UUID v4 generado por SDK; el backend lo persiste |

## Códigos de error comunes

| HTTP | Significado |
|---|---|
| `400` | Body malformado o headers faltantes |
| `401` | `X-Website-Token` inválido o `Channels\Web` no existe |
| `403` | Token válido pero canal deshabilitado |
| `404` | Recurso no encontrado |
| `422` | Validación falla — body con `errors` por campo |
| `429` | Rate limit excedido |
| `500` | Error interno; el SDK debe reintentar con backoff exponencial |

Formato uniforme de error:
```json
{
  "success": false,
  "message": "El token del sitio web no es válido",
  "errors": { "websiteToken": ["No coincide con ningún canal activo"] }
}
```

---

## 1. `POST /hd/api/sdk/init`

Inicia o recupera una sesión de visitor. Idempotente con el mismo `visitorId`.

**Headers:** `X-Website-Token`

**Body:**
```json
{
  "visitorId": "9d1c0f78-...",
  "fingerprint": "h3a8x2",
  "page": {
    "url": "https://shop.example.com/categoria/zapatos",
    "title": "Zapatos | ExampleShop",
    "referrer": "https://google.com/"
  },
  "device": {
    "userAgent": "...",
    "language": "es-ES",
    "viewport": { "width": 1280, "height": 720 },
    "timezone": "Europe/Madrid"
  }
}
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "visitorId": "9d1c0f78-...",
    "sessionToken": "tok_5fn3...64c",
    "customerId": null,
    "score": 12,
    "segment": "cold",
    "triggers": [
      {
        "id": 17,
        "name": "Carrito > 100€",
        "conditions": { "...": "..." },
        "action": { "type": "open_chat" },
        "firesPerSession": 1
      }
    ],
    "personalizations": [
      {
        "id": 4,
        "selector": ".btn-primary[data-track=buy]",
        "conditions": null,
        "mutation": { "op": "text", "value": "Hablar con asesor" }
      }
    ],
    "wsChannel": "widget-session.tok_5fn3...64c",
    "config": {
      "consent": "opt-in",
      "batchInterval": 5000,
      "batchSize": 10
    }
  }
}
```

---

## 2. `POST /hd/api/sdk/identify`

Vincula la sesión actual con un usuario identificado. Crea/actualiza `helpdesk_customers`.

**Headers:** `X-Website-Token`, `X-Session-Token`

**Body:**
```json
{
  "id": "USR-9012",
  "email": "ana@example.com",
  "name": "Ana Pérez",
  "phone": "+34666123456",
  "attributes": {
    "membershipTier": "gold",
    "signupDate": "2024-11-01"
  }
}
```

**Reglas:**
- `id` (externo) se guarda en `helpdesk_customers.custom_attributes.external_id`.
- Si existe `email` y ya hay un `Customer` con ese email → se vincula; no se duplica.
- `attributes` se mergea (no reemplaza) en `custom_attributes`.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "customerId": 4321,
    "linked": true,
    "score": 18,
    "segment": "cold"
  }
}
```

**Errores:**
- `422` si email malformado, ningún identificador (`id`/`email`/`phone`).

---

## 3. `POST /hd/api/sdk/track`

Acepta batch de eventos. **Asíncrono:** persiste en BD y encola `ProcessLivechatBatchJob` para scoring/triggers.

**Headers:** `X-Website-Token`, `X-Session-Token`

**Body:**
```json
{
  "events": [
    {
      "name": "page_view",
      "properties": { "url": "...", "title": "..." },
      "ts": "2026-05-02T13:45:00+00:00"
    },
    {
      "name": "product_view",
      "properties": {
        "id": "SKU123", "name": "Zapatilla X", "price": 79.90, "currency": "EUR"
      },
      "ts": "2026-05-02T13:45:32+00:00"
    },
    {
      "name": "add_to_cart",
      "properties": {
        "id": "SKU123", "price": 79.90, "currency": "EUR", "quantity": 1
      },
      "ts": "2026-05-02T13:46:11+00:00"
    }
  ]
}
```

**Validaciones:**
- `events` array requerido, `min:1`, `max:50` por request.
- Cada evento: `name` (string, max 64), `ts` (ISO8601, no más de 24h en el pasado), `properties` (object opcional, max 4KB).

**Response 202 (Accepted):**
```json
{
  "success": true,
  "data": {
    "accepted": 3,
    "rejected": 0,
    "score": 28,
    "segment": "warm",
    "triggersFired": [{ "id": 17, "action": { "type": "open_chat" } }]
  }
}
```

**Notas:**
- Si `score` cambia de segmento, se emite `ScoreThresholdCrossed` por el canal `widget-session.{token}`.
- Triggers con condiciones server-side pueden disparar y devolverse aquí.

---

## 4. `POST /hd/api/sdk/context`

Set/merge del contexto dinámico de la sesión.

**Headers:** `X-Website-Token`, `X-Session-Token`

**Body:**
```json
{
  "context": {
    "cartValue": 120.50,
    "currency": "EUR",
    "itemsCount": 3,
    "isLoggedIn": true
  }
}
```

**Comportamiento:** merge (no reemplazo). Para borrar una key, enviar `null`.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "context": { "...": "..." },
    "score": 32,
    "segment": "warm"
  }
}
```

---

## 5. `GET /hd/api/sdk/triggers`

Devuelve todas las reglas activas del canal. Cacheable con `ETag`.

**Headers:** `X-Website-Token`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "rules": [
      {
        "id": 17,
        "name": "Carrito > 100€",
        "conditions": {
          "operator": "AND",
          "rules": [
            { "type": "context", "key": "cartValue", "operator": ">", "value": 100 },
            { "type": "time_on_page", "operator": ">", "value": 30 }
          ]
        },
        "action": { "type": "open_chat" },
        "priority": 10,
        "firesPerSession": 1
      }
    ]
  }
}
```

**Cache:** SDK guarda en memoria + `localStorage` con TTL 5 minutos. ETag para revalidación.

---

## 6. `GET /hd/api/sdk/personalizations`

Reglas DOM activas del canal.

**Headers:** `X-Website-Token`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "rules": [
      {
        "id": 4,
        "selector": ".btn-primary[data-track=buy]",
        "conditions": {
          "operator": "AND",
          "rules": [{ "type": "score", "operator": ">=", "value": 60 }]
        },
        "mutation": { "op": "text", "value": "Hablar con asesor" }
      }
    ]
  }
}
```

---

## 7. `GET /hd/api/sdk/recommendations?limit=5`

Productos recomendados para el visitor actual.

**Headers:** `X-Website-Token`, `X-Session-Token`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": "SKU456",
        "name": "Zapatilla Y",
        "price": 89.90,
        "currency": "EUR",
        "imageUrl": "https://...",
        "url": "https://shop.example.com/p/SKU456",
        "reason": "viewed_category"
      }
    ]
  }
}
```

**Estrategia inicial:**
1. Productos vistos por categoría dominante.
2. Si `customer_id` existe: cart_history fall-back.
3. Si no hay datos: top productos del inbox (configurable).

---

## 8. WebSocket — canal `widget-session.{sessionToken}`

**Subscripción** (Echo/Reverb):
```js
window.Echo.channel(`widget-session.${sessionToken}`)
  .listen('.ScoreThresholdCrossed', (e) => {})
  .listen('.TriggerFired', (e) => {});
```

### Evento `ScoreThresholdCrossed`
```json
{
  "sessionToken": "tok_...",
  "previousSegment": "cold",
  "currentSegment": "warm",
  "score": 32,
  "occurredAt": "2026-05-02T13:50:11+00:00"
}
```

### Evento `TriggerFired`
```json
{
  "sessionToken": "tok_...",
  "ruleId": 17,
  "action": { "type": "open_chat" }
}
```

---

## 9. Rate limits por endpoint

| Endpoint | Límite |
|---|---|
| `POST /sdk/init` | 30/min/IP |
| `POST /sdk/identify` | 30/min/session |
| `POST /sdk/track` | 240/min/session (burst) |
| `POST /sdk/context` | 60/min/session |
| `GET /sdk/triggers` | 60/min/IP |
| `GET /sdk/personalizations` | 60/min/IP |
| `GET /sdk/recommendations` | 60/min/session |

Configurable por `config/helpdesklivechat.php`.

## 10. Versionado

- Versión inicial: incluida en el path implícitamente (`/hd/api/sdk/*` = v1).
- Cambios incompatibles → nuevo prefijo `/hd/api/sdk/v2/*` y soporte simultáneo de v1 durante 6 meses.
- El SDK envía `X-SDK-Version: 1.x.x`. El backend puede degradar respuestas si reconoce versiones antiguas.
