# Alsernet Live Chat — Distribuciones nativas

Paquetes de integración para las principales plataformas e-commerce. Todos cargan el mismo SDK universal (`sdk.js`); solo cambia cómo se inyecta y cómo se exponen los datos del comerciante.

## Plataformas soportadas

| Plataforma | Carpeta | Distribución | Detección automática |
|-----------|---------|--------------|----------------------|
| **PrestaShop 1.7+ / 8.x** | `prestashop/` | Módulo `.zip` | `window.prestashop` |
| **WordPress + WooCommerce 4+** | `woocommerce/` | Plugin WP `.zip` | `window.__alsernet_woo` + `body.woocommerce` |
| **Shopify Online Store 2.0** | `shopify/` | Theme App Extension | `window.Shopify` + `window.ShopifyAnalytics` |
| **Cualquier otra** | `custom/` | Snippet HTML + API JS | Fallback `chat.platform.set()` |

## Arquitectura común

```
┌────────────────────────────────────────────────────────────┐
│ Sitio del comerciante                                      │
│   ┌──────────────────────────────────────────────────────┐ │
│   │ Distribución nativa (módulo / plugin / app extension)│ │
│   │  • Configura token + apiUrl                          │ │
│   │  • Carga sdk.js                                      │ │
│   │  • Expone datos del cliente/carrito/producto         │ │
│   └──────────────────────────────────────────────────────┘ │
│                          ↓                                 │
│   ┌──────────────────────────────────────────────────────┐ │
│   │ SDK universal (window.chat)                          │ │
│   │  • platformDetector → adapter correcto               │ │
│   │  • Auto-emite track/setContext/identify              │ │
│   └──────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────┘
                            ↓ HTTPS
┌────────────────────────────────────────────────────────────┐
│ Panel Alsernet                                             │
│  • SDK API (init, identify, track, context...)             │
│  • Webhook receiver (HMAC, multi-platform)                 │
│  • Scoring → broadcasts via Reverb                         │
└────────────────────────────────────────────────────────────┘
```

## Datos que cada distribución provee

| Dato | PrestaShop | Shopify | WooCommerce | Custom |
|------|-----------|---------|-------------|--------|
| Cart (items, value, currency) | `prestashop.cart` | `/cart.js` (intercept) | `wp_localize_script` → `window.__alsernet_woo.cart` | `chat.platform.set({cart})` |
| Customer (id, email, name) | `prestashop.customer` | `Shopify.customer` | `window.__alsernet_woo.customer` | `chat.platform.set({customer})` |
| Product (id, name, price) | `prestashop.product` | `ShopifyAnalytics.meta.product` | `window.__alsernet_woo.product` | `chat.platform.set({product})` |
| Cart change events | `updateCart` event | fetch interceptor | `added_to_cart` jQuery event | `chat.platform.set({cart})` |
| Server webhooks | `actionValidateOrder` | Admin → Notifications → Webhooks | `woocommerce_thankyou` action | POST manual |

## Verificación HMAC

Todas las distribuciones envían webhooks firmados con HMAC-SHA256:

| Plataforma | Header de firma | Header de topic |
|-----------|-----------------|-----------------|
| PrestaShop / Custom | `X-Alsernet-Signature` | `X-Alsernet-Topic` |
| Shopify | `X-Shopify-Hmac-Sha256` | `X-Shopify-Topic` |
| WooCommerce | `X-WC-Webhook-Signature` | `X-WC-Webhook-Topic` |

El secret se almacena en `engagement_platform_integrations.webhook_secret` (oculto en API responses).

## Cómo crear una integración nueva

1. **Backend**: alta de `PlatformIntegration` con la plataforma + secret.
2. **Comerciante**: instala la distribución de su plataforma + configura token e ID de integración.
3. El `PlatformWebhookController` recibe webhooks → `PlatformWebhookHandler` los traduce a eventos de Engagement → `ScoringService` actualiza score → Reverb avisa al SDK.

## Build del SDK

Antes de distribuir cualquier paquete:

```bash
cd modules/Engagement
npm run build
```

Esto genera `public/build-engagement/sdk.js` que las distribuciones cargan vía `<script src="...">`.
