# Alsernet Live Chat — Shopify Theme App Extension

App Embed Block que carga el SDK de Alsernet en cualquier tema de Shopify (Online Store 2.0).

## Estructura

```
extensions/alsernet-chat/
├── blocks/
│   └── alsernet-chat.liquid   ← App Embed (target: head)
└── shopify.extension.toml
```

## Instalación (desarrollador)

Requiere [Shopify CLI](https://shopify.dev/docs/apps/tools/cli) y una **Shopify Partner App** ya creada.

```bash
# Desde la raíz de tu Shopify app
cp -r distributions/shopify/extensions/alsernet-chat extensions/

shopify app deploy
```

Tras el deploy, en Shopify Admin: **Online Store → Themes → Customize → App embeds**, activar **Alsernet Chat** y rellenar:
- URL de la API (ej. `https://panel.alsernet.com`)
- Website token

## Cómo funciona

- El bloque tiene `target: head`, así que se inyecta en el `<head>` antes del DOM.
- Define el stub `chat()` y carga `sdk.js` con `async`.
- Llama `chat('init', ...)` con el token configurado.
- Si hay `customer` autenticado en Liquid, llama `chat('identify', ...)` automáticamente.
- El adapter `shopify.ts` del SDK detecta `window.Shopify` y `window.ShopifyAnalytics`, intercepta `/cart/add|update|change` con un fetch wrapper, y actualiza el contexto.

## Webhooks (opcional)

Para registrar pedidos pagados en el panel:

1. En Shopify Admin: **Settings → Notifications → Webhooks** crear webhooks:
   - `Order paid` → `https://panel.alsernet.com/eng/api/sdk/webhook/shopify/{integrationId}`
   - `Customer create` → mismo endpoint
2. El secret de Shopify se configura en el panel (PlatformIntegration.webhook_secret).
3. El controller `PlatformWebhookController` valida `X-Shopify-Hmac-Sha256` y lee `X-Shopify-Topic`.

## Compatibilidad

- Shopify Online Store 2.0
- Shopify CLI 3.x
- API version 2024-04
