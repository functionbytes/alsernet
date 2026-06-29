# Guía de integración del SDK de Engagement

> Documento para desarrolladores que quieren integrar el tracking de Engagement en sus sitios web.

---

## Instalación

Incluye el script en tu sitio web, preferiblemente antes del cierre de `</body>`:

```html
<script type="module">
  import { initEngagement } from 'https://system.test/eng/api/assets/engagement-sdk';

  const eng = await initEngagement({
    websiteToken: 'TU_WEBSITE_TOKEN',
    visitorId: 'identificador-unico-del-visitante', // opcional
  });
</script>
```

O con la versión clásica (loader):

```html
<script src="https://system.test/eng/api/assets/livechat-widget-loader" async></script>
<script>
  window.__engagementConfig = {
    websiteToken: 'TU_WEBSITE_TOKEN',
  };
</script>
```

---

## Métodos disponibles

### `eng.identify(email, traits)`

Identifica al visitante y lo enlaza con un cliente existente.

```javascript
await eng.identify('cliente@ejemplo.com', {
  name: 'Juan Pérez',
  phone: '+34600123456',
  plan: 'premium',
});
```

### `eng.track(eventName, properties)`

Registra un evento del visitante.

```javascript
// Eventos recomendados
await eng.track('page_view', { url: window.location.href, title: document.title });
await eng.track('product_view', { sku: 'ABC123', name: 'Producto X', price: 29.99 });
await eng.track('add_to_cart', { sku: 'ABC123', quantity: 2 });
await eng.track('checkout_start', { cart_value: 59.98 });
await eng.track('purchase', { order_id: 'ORD-123', total: 59.98, currency: 'EUR' });
```

### `eng.context(key, value)` / `eng.context(object)`

Almacena contexto del visitante para usar en triggers y personalizaciones.

```javascript
await eng.context('membership_level', 'gold');
await eng.context({
  membership_level: 'gold',
  last_purchase_date: '2026-05-01',
});
```

### `eng.page(url, title, referrer)`

Notifica un cambio de página (para SPAs).

```javascript
await eng.page('/productos/abc123', 'Producto X - Mi Tienda', document.referrer);
```

---

## Eventos recomendados por plataforma

### PrestaShop

```javascript
// En product.tpl
await eng.track('product_view', {
  sku: '{$product.reference}',
  name: '{$product.name|escape:"javascript"}',
  price: {$product.price_amount},
  category: '{$product.category_name}',
});

// En order-confirmation.tpl
await eng.track('purchase', {
  order_id: '{$order->id}',
  total: {$order->total_paid},
  currency: '{$currency->iso_code}',
  items: [].map.call(document.querySelectorAll('.order-line'), line => ({
    sku: line.dataset.productReference,
    quantity: parseInt(line.dataset.productQuantity),
  })),
});
```

### Shopify

```javascript
// En theme.liquid (product page)
{% if product %}
await eng.track('product_view', {
  sku: '{{ product.selected_or_first_available_variant.sku }}',
  name: '{{ product.title | escape }}',
  price: {{ product.selected_or_first_available_variant.price | divided_by: 100.0 }},
});
{% endif %}

// En checkout.liquid (order status)
{% if order %}
await eng.track('purchase', {
  order_id: '{{ order.order_number }}',
  total: {{ order.total_price | divided_by: 100.0 }},
  currency: '{{ order.currency }}',
});
{% endif %}
```

### WooCommerce

```javascript
// functions.php o template
add_action('wp_footer', function () {
    if (is_product()) {
        global $product;
        echo "<script>eng.track('product_view', { sku: '{$product->get_sku()}', name: '".esc_js($product->get_name())."', price: {$product->get_price()} });</script>";
    }
    if (is_order_received_page()) {
        $order = wc_get_order(get_query_var('order-received'));
        echo "<script>eng.track('purchase', { order_id: '{$order->get_id()}', total: {$order->get_total()}, currency: '{$order->get_currency()}' });</script>";
    }
});
```

---

## Geolocalización e idioma

El SDK puede enviar información de geolocalización para personalización por país:

```javascript
await eng.init({
  websiteToken: 'TU_TOKEN',
  geo: {
    country: 'ES',
    city: 'Madrid',
    region: 'Madrid',
    timezone: 'Europe/Madrid',
    language: navigator.language || 'es',
  },
});
```

> Nota: La detección de IP se hace automáticamente en el servidor si el SDK no envía geo explícitamente.

---

## Atribución UTM

El SDK detecta automáticamente parámetros UTM de la URL. También puedes forzar valores:

```javascript
await eng.init({
  websiteToken: 'TU_TOKEN',
  attribution: {
    utm_source: 'newsletter',
    utm_medium: 'email',
    utm_campaign: 'verano2026',
    utm_term: 'ofertas',
    utm_content: 'banner_top',
  },
});
```

---

## Personalización DOM

Las reglas de personalización definidas en el panel se aplican automáticamente después de `init()`. No requiere código adicional.

---

## Triggers y automatización

Los triggers definidos en el panel se evalúan automáticamente después de cada evento `track()`. Si un trigger se activa, recibirás un mensaje por el canal WebSocket:

```javascript
eng.onTrigger((trigger) => {
  console.log('Trigger activado:', trigger.name, trigger.action);
});
```

---

## Debug

Activa el modo debug para ver logs en consola:

```javascript
const eng = await initEngagement({
  websiteToken: 'TU_TOKEN',
  debug: true,
});
```

---

## Soporte

Para más información, revisa el panel de administración en `https://system.test/panel/settings/engagement`.
