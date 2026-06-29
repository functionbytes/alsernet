# Alsernet Live Chat — Integración Custom

Para sitios que no son PrestaShop, Shopify ni WooCommerce (CMS propios, plain HTML, frameworks como Magento/OpenCart/Drupal Commerce, headless storefronts, etc.).

## Cómo integrar

1. Copiar el contenido de `snippet.html` y pegarlo dentro del `<head>` de tu sitio.
2. Reemplazar `TU_WEBSITE_TOKEN` por el token del canal Web del inbox.
3. Reemplazar `https://panel.alsernet.com` por la URL real de tu panel Alsernet.

## Cuándo el SDK detecta automáticamente

Si tu plataforma expone alguno de estos globales el SDK los usa sin código adicional:

| Plataforma | Global detectado |
|-----------|------------------|
| PrestaShop | `window.prestashop` |
| Shopify | `window.Shopify` |
| WooCommerce | `window.wc_cart_fragments_params` o body class `woocommerce` |

Si el adapter detectado es `'custom'`, debes alimentar los datos manualmente:

```js
chat('platform', 'set', {
    cart: { items: 3, value: 129.50, currency: 'EUR', products: [...] },
    customer: { id: '42', email: 'user@example.com', name: 'María' },
    product: { id: 'sku-X', name: 'Producto X', price: 49.99 }
});
```

Esta llamada puede hacerse cuantas veces necesites (al cambiar de página, al añadir al carrito, etc). El SDK emite automáticamente `setContext()` y `track('cart_updated')`.

## API completa expuesta

```js
// Inicialización
chat('init', { token, apiUrl, consent: true });

// Tracking manual
chat('track', 'page_view', { page: '/products/x' });
chat('track', 'add_to_cart', { productId: 'X', value: 49.99 });
chat('track', 'purchase', { orderId: '42', value: 129.50 });

// Identificación
chat('identify', { id, email, name, phone, attributes: {...} });

// Contexto custom
chat('setContext', { plan: 'pro', cartValue: 129.50 });

// Plataforma manual
chat('platform', 'set', { cart, customer, product });
chat('platform', 'current'); // → 'prestashop' | 'shopify' | 'woocommerce' | 'custom'
chat('platform', 'cart');    // snapshot actual
chat('platform', 'customer');
chat('platform', 'product');

// Widget
chat('open');
chat('close');

// Recomendaciones
chat('getRecommendations'); // Promise<RecommendedProduct[]>

// Eventos
chat('on', 'ready', (payload) => console.log(payload));
chat('on', 'score:changed', (e) => console.log(e.score, e.segment));
chat('on', 'trigger:fired', (e) => console.log(e.ruleId, e.action));
chat('on', 'platform:cart_updated', (cart) => console.log(cart));
```

## GDPR / cookies

El SDK respeta el consentimiento. Si tu sitio gestiona cookies con Cookiebot, OneTrust, etc.:

```js
// Inicializar SIN consentimiento
chat('init', { token: 'XYZ', apiUrl: '...', consent: false });

// Cuando el usuario acepta cookies de marketing/analytics
window.addEventListener('cookieConsent.accepted', () => {
    chat('setConsent', true);
});
```

## CSP

El SDK requiere que tu CSP permita:

```
script-src 'self' https://panel.alsernet.com 'unsafe-inline';
connect-src 'self' https://panel.alsernet.com wss://panel.alsernet.com:6001;
```

## Webhooks server-to-server (opcional)

Si quieres registrar pedidos pagados directamente desde tu backend (sin depender de tracking del cliente):

```bash
POST https://panel.alsernet.com/eng/api/sdk/webhook/custom/{integrationId}
Headers:
  Content-Type: application/json
  X-Alsernet-Signature: hmac_sha256(body, secret)
  X-Alsernet-Topic: order.paid
Body:
  { "order_id": "42", "email": "user@example.com", "total": 129.50 }
```

El secret se obtiene del panel al crear la integración. Verificación HMAC automática del lado servidor.
