# HelpdeskLivechat — Integración PrestaShop / HTML

Guía paso a paso para incrustar el SDK en una tienda PrestaShop o cualquier sitio HTML.

## 1. Obtener el `website_token`

1. Login en el panel del helpdesk.
2. Ir a **Ajustes → Livechat → Configuración del widget**.
3. Crear un canal `Web` (o seleccionar uno existente).
4. Copiar el `website_token` (32 chars).

## 2. Snippet de instalación universal

Añadir antes de `</body>` (o en `<head>` con `defer`):

```html
<script>
  (function(d,s){
    window.chat = window.chat || function(){ (chat.q = chat.q || []).push(arguments); };
    var j = d.createElement(s); j.async = 1;
    j.src = 'https://TU-DOMINIO.com/widget/script/PEGAR_WEBSITE_TOKEN_AQUI';
    var t = d.getElementsByTagName(s)[0]; t.parentNode.insertBefore(j, t);
  })(document, 'script');

  chat('init', { token: 'PEGAR_WEBSITE_TOKEN_AQUI' });
</script>
```

> El `token` se pasa **dos veces** (URL del loader + en `init`) para que el SDK pueda inicializarse aún si el host modifica el `<script src>`.

## 3. Integración PrestaShop específica

### 3.1 Vía hook (recomendado)

Crear un módulo PrestaShop `mychatlivechat` con:

```php
// modules/mychatlivechat/mychatlivechat.php
public function install()
{
    return parent::install()
        && $this->registerHook('displayBeforeBodyClosingTag')
        && $this->registerHook('actionCartSave')
        && $this->registerHook('actionPaymentConfirmation')
        && $this->registerHook('displayProductPage');
}

public function hookDisplayBeforeBodyClosingTag($params)
{
    $token = Configuration::get('MYCHATLIVECHAT_TOKEN');
    return $this->display(__FILE__, 'views/templates/hook/loader.tpl', [
        'token' => $token,
    ]);
}
```

`views/templates/hook/loader.tpl`:
```smarty
<script>
  window.chat = window.chat || function(){ (chat.q = chat.q || []).push(arguments); };
  (function(d,s){
    var j = d.createElement(s); j.async = 1;
    j.src = 'https://TU-DOMINIO.com/widget/script/{$token|escape:'html'}';
    d.head.appendChild(j);
  })(document, 'script');

  chat('init', { token: '{$token|escape:'html'}', consent: true });

  {if $cookie->id_customer}
    chat('identify', {
      id: '{$cookie->id_customer}',
      email: '{$cookie->email|escape:'html'}',
      name:  '{$cookie->customer_firstname|escape:'html'} {$cookie->customer_lastname|escape:'html'}'
    });
  {/if}
</script>
```

### 3.2 Hooks de eventos ecommerce

```php
public function hookActionCartSave($params)
{
    $cart = $params['cart'];
    if (!$cart) return;

    $items = [];
    $total = 0;
    foreach ($cart->getProducts() as $p) {
        $items[] = ['id' => $p['id_product'], 'qty' => (int)$p['cart_quantity']];
        $total  += (float)$p['total_wt'];
    }

    Media::addJsDef([
        'helpdeskLivechatCart' => [
            'cartValue'   => $total,
            'itemsCount'  => count($items),
            'currency'    => $this->context->currency->iso_code,
            'items'       => $items,
        ],
    ]);
}
```

En `loader.tpl`, debajo del `init`:
```smarty
{if isset($helpdeskLivechatCart)}
  chat('setContext', {literal}{{/literal}
    cartValue: {$helpdeskLivechatCart.cartValue|floatval},
    itemsCount: {$helpdeskLivechatCart.itemsCount|intval},
    currency: '{$helpdeskLivechatCart.currency|escape:'html'}'
  {literal}}{/literal});
{/if}
```

### 3.3 Eventos de producto

`views/templates/hook/productPage.tpl`:
```smarty
<script>
  chat('track', 'product_view', {literal}{{/literal}
    id: '{$product->id|escape:'html'}',
    name: '{$product->name|escape:'html'}',
    price: {$product->price|floatval},
    currency: '{$currency->iso_code|escape:'html'}',
    category: '{$category->name|escape:'html'}'
  {literal}}{/literal});
</script>
```

### 3.4 Compra confirmada

`hookActionPaymentConfirmation`:
```php
public function hookActionPaymentConfirmation($params)
{
    $order = new Order($params['id_order']);
    $items = [];
    foreach ($order->getProducts() as $p) {
        $items[] = [
            'id'    => $p['product_id'],
            'qty'   => (int)$p['product_quantity'],
            'price' => (float)$p['unit_price_tax_incl'],
        ];
    }

    Media::addJsDef([
        'helpdeskLivechatPurchase' => [
            'orderId'  => $order->reference,
            'total'    => (float)$order->total_paid,
            'currency' => $this->context->currency->iso_code,
            'items'    => $items,
        ],
    ]);
}
```

En el front:
```smarty
{if isset($helpdeskLivechatPurchase)}
  chat('track', 'purchase', {literal}{{/literal}
    orderId: '{$helpdeskLivechatPurchase.orderId|escape:'html'}',
    total: {$helpdeskLivechatPurchase.total|floatval},
    currency: '{$helpdeskLivechatPurchase.currency|escape:'html'}',
    items: {$helpdeskLivechatPurchase.items|@json_encode nofilter}
  {literal}}{/literal});
{/if}
```

---

## 4. Content Security Policy (CSP)

Si tu tienda tiene CSP estricta, añadir:

```
Content-Security-Policy:
  script-src 'self' 'unsafe-inline' https://TU-DOMINIO.com;
  connect-src 'self' https://TU-DOMINIO.com wss://TU-DOMINIO.com;
  frame-src https://TU-DOMINIO.com;
  img-src 'self' data: https://TU-DOMINIO.com;
```

Notas:
- `script-src` necesita el dominio donde se sirve `sdk.js` y `widget.js`.
- `connect-src` para REST + WebSocket (Reverb).
- `frame-src` sólo si se usa iframe en el widget.
- **Evitar** `'unsafe-eval'`. El SDK no lo necesita.

## 5. GDPR / Cookies

El SDK tiene 3 modos de consent:

| Modo | Comportamiento |
|---|---|
| `consent: true` en `init()` | Tracking arranca de inmediato (modo opt-out o consent ya dado) |
| `consent: false` (default) | Tracking en stand-by; nada sale al backend hasta `setConsent(true)` |
| `setConsent(false)` tras init | Tracking se pausa; eventos en cola se descartan |

Patrón típico con banner cookies:
```js
// Al cargar la página
chat('init', { token: '...', consent: false });

// Cuando el usuario acepta cookies
document.getElementById('accept-cookies').addEventListener('click', () => {
  chat('setConsent', true);
});

// Cuando rechaza
document.getElementById('reject-cookies').addEventListener('click', () => {
  chat('setConsent', false);
});
```

El SDK respeta `navigator.doNotTrack === '1'` y no inicializa tracking automático sin consent explícito.

---

## 6. Verificación

Tras instalar:

1. Abrir la consola del navegador.
2. `chat('init', { token: '...', debug: true })`.
3. Esperar log `[chat] ready { sessionToken, visitorId }`.
4. Navegar a otra página → `[chat] track page_view`.
5. En el panel admin, ir a **Livechat → Eventos** y verificar que aparece la sesión + page_view.
6. Probar `chat('open')` → el widget debe abrirse en la esquina configurada.

## 7. Troubleshooting

| Síntoma | Posible causa | Acción |
|---|---|---|
| 401 en `/sdk/init` | Token inválido o canal inactivo | Verificar token y estado del canal en admin |
| Eventos no llegan | CSP bloquea `connect-src` | Revisar headers de CSP |
| Widget no abre | CSP `frame-src` faltante o `script-src` no incluye dominio | Ajustar CSP |
| Bundle pesa > 50 KB | Bundle analyzer reporta dependencia inflada | Reportar issue, revisar `vite.config.js` |
| Score no cambia | `consent: false` y no se llamó `setConsent(true)` | Llamar `setConsent(true)` o pasar `consent: true` en `init` |
| Identify no vincula | Email malformado o ya asociado a otro customer | Verificar logs del backend |

---

## 8. Configuración avanzada

### Override apiUrl
Si el dominio del backend no coincide con el del loader:
```js
chat('init', {
  token: '...',
  apiUrl: 'https://api.tu-dominio.com'
});
```

### Auto-track desactivado
```js
chat('init', {
  token: '...',
  autoTrack: { pageView: false, sessionLifecycle: false }
});
```

### Hooks personalizados
```js
chat('on', 'trigger:fired', ({ ruleId, action }) => {
  if (action.type === 'custom_callback') {
    miFuncionCustom(action.payload);
  }
});
```
