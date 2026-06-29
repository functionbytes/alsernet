# HelpdeskLivechat — SDK JavaScript Reference

API pública del SDK para integradores. Todo se expone en `window.chat`.

## Instalación

```html
<script>
  (function(d,s){
    var j=d.createElement(s); j.async=1;
    j.src='https://tu-dominio.com/widget/script/WEBSITE_TOKEN_AQUI';
    d.head.appendChild(j);
  })(document,'script');
</script>
```

El loader inyecta `sdk.js`. La función `chat` queda disponible en cuanto el script termina de parsear, pero sus llamadas se encolan hasta que el SDK esté listo:

```html
<script>
  window.chat = window.chat || function(){ (chat.q = chat.q || []).push(arguments); };
  chat('init', { token: 'WEBSITE_TOKEN_AQUI' });
</script>
```

> Patrón de cola estilo Segment/Intercom — permite usar `chat()` antes de cargar el SDK.

## Inicialización

```js
chat.init({
  token: 'WEBSITE_TOKEN_AQUI',          // requerido
  apiUrl: 'https://api.tu-dominio.com',  // opcional, default: derivado del script src
  debug: true,                            // opcional, logs en consola
  consent: false                          // opcional, default false (espera setConsent)
});
```

**Lo que pasa internamente:**
1. Lee/genera `visitor_id` (UUID v4) en `localStorage` (`__hd_lc_vid`).
2. Si `consent === false`, no hace `init` real hasta que se invoque `setConsent(true)`.
3. `POST /hd/api/sdk/init` → recibe `sessionToken`, `score`, `segment`, `triggers`, `personalizations`.
4. Suscribe al canal WS `widget-session.{sessionToken}`.
5. Aplica personalizations sobre el DOM.
6. Arranca motor de triggers cliente.
7. Emite evento `page_view` automático y suscribe a History API para SPAs.

## API completa

### `chat.identify(user)`

Vincula la sesión con un usuario identificado.

```js
chat.identify({
  id: 'USR-9012',
  name: 'Ana Pérez',
  email: 'ana@example.com',
  phone: '+34666123456',
  attributes: {
    membershipTier: 'gold',
    signupDate: '2024-11-01'
  }
});
```

- `email` o `id` requerido (al menos uno).
- `attributes` se mergea en `helpdesk_customers.custom_attributes`.

### `chat.track(eventName, properties?)`

Emite un evento custom o ecommerce.

```js
chat.track('product_view', { id: 'SKU1', name: 'Zapatilla X', price: 79.90, currency: 'EUR' });
chat.track('add_to_cart',  { id: 'SKU1', price: 79.90, currency: 'EUR', quantity: 1 });
chat.track('checkout_start', { cartValue: 159.80, itemsCount: 2, currency: 'EUR' });
chat.track('purchase', {
  orderId: 'ORD-7891',
  total: 159.80,
  currency: 'EUR',
  items: [{ id: 'SKU1', qty: 2, price: 79.90 }]
});
chat.track('newsletter_subscribe', { source: 'footer' });   // custom
```

- El evento entra en cola; se envía en batch (10 eventos / 5s).
- Si el navegador está offline, la cola se persiste en IndexedDB y se reintenta al volver online.
- Al cerrar pestaña se hace `flush` con `navigator.sendBeacon`.

### `chat.setContext(context)`

Mergea contexto dinámico de la sesión.

```js
chat.setContext({
  cartValue: 120.50,
  currency: 'EUR',
  itemsCount: 3,
  isLoggedIn: true
});
```

Usado por triggers (`{type: 'context', key: 'cartValue', operator: '>', value: 100}`).

### `chat.setConsent(granted)`

Activa o desactiva el tracking. En modo opt-in, `init()` queda en stand-by hasta `setConsent(true)`.

```js
chat.setConsent(true);   // arranca tracking
chat.setConsent(false);  // pausa tracking, NO borra eventos en cola (se descartan)
```

### `chat.open()` / `chat.close()`

Abre o cierra el widget de chat. La primera llamada a `open()` carga el bundle del widget vía import dinámico (lazy, ~150 KB que no se cargan si nadie abre el chat).

```js
document.querySelector('#help-button').addEventListener('click', () => chat.open());
```

### `chat.on(eventName, handler)`

Suscribe a eventos internos.

| `eventName` | Payload |
|---|---|
| `ready` | `{ sessionToken, visitorId }` — SDK inicializado |
| `score:changed` | `{ score, segment, previousSegment }` |
| `trigger:fired` | `{ ruleId, action }` |
| `chat:opened` / `chat:closed` | `{}` |
| `error` | `{ code, message, original? }` |

```js
chat.on('score:changed', ({ segment, score }) => {
  console.log(`Visitor segment: ${segment} (${score})`);
});

chat.on('trigger:fired', ({ ruleId, action }) => {
  // hook custom para integradores
});
```

### `chat.q` (cola pre-load)

Si llamas `chat()` antes de que el SDK termine de cargar, las llamadas se acumulan en `chat.q`. El SDK las consume al inicializar. **No la manipules manualmente.**

---

## Eventos ecommerce — convenciones

Para que scoring/triggers/recomendador funcionen consistentemente, sigue este shape exacto:

```js
chat.track('product_view', {
  id:        'SKU123',          // string, requerido
  name:      'Zapatilla X',     // string
  price:     79.90,             // número (no string)
  currency:  'EUR',             // ISO 4217
  category:  'shoes/running',   // path con / (opcional)
  imageUrl:  'https://...'      // opcional
});

chat.track('add_to_cart', {
  id:        'SKU123',
  price:     79.90,
  currency:  'EUR',
  quantity:  1
});

chat.track('remove_from_cart', { id: 'SKU123', quantity: 1 });

chat.track('checkout_start', {
  cartValue:    159.80,
  itemsCount:   2,
  currency:     'EUR'
});

chat.track('purchase', {
  orderId:    'ORD-7891',
  total:      159.80,
  currency:   'EUR',
  items: [
    { id: 'SKU123', qty: 1, price: 79.90 },
    { id: 'SKU456', qty: 1, price: 79.90 }
  ]
});
```

> Eventos custom (`chat.track('mi_evento', {...})`) son válidos pero **no contribuyen al scoring** salvo que lo configures en `ScoringService::handleCustomEvent()`.

---

## Auto-tracking

Estos eventos se emiten automáticamente:

- `page_view` — al cargar y en cada `pushState`/`replaceState` (SPAs).
- `session_start` — primer evento de la sesión.
- `session_end` — al disparar `visibilitychange:hidden` o tras 30 min de inactividad.

Para desactivarlos:
```js
chat.init({ token: '...', autoTrack: { pageView: false, sessionLifecycle: false } });
```

---

## Performance

| Métrica | Objetivo |
|---|---|
| Bundle gzipped | < 50 KB |
| Tiempo a `ready` (3G fast) | < 500 ms |
| Bloqueo render | 0 (todo `async defer`) |
| Memoria steady state | < 5 MB |
| Eventos por segundo (sostenidos) | hasta 20/s con batch |

El SDK usa `requestIdleCallback` para inicialización no crítica y `MutationObserver` debounced para personalización DOM.

---

## Compatibilidad

| Navegador | Versión mínima |
|---|---|
| Chrome / Edge | 80 |
| Firefox | 78 |
| Safari | 13 |
| iOS Safari | 13 |
| Samsung Internet | 14 |

IE11 **no soportado**. Para fallback, mostrar widget HTML estático.

---

## Privacidad y consent

- `chat.setConsent(false)` (default si `consent: false` en `init()`) → ningún request al backend.
- Si el visitor usa Do Not Track, el SDK respeta y no inicializa tracking salvo que se haga `setConsent(true)` explícito.
- PII (`name`, `email`, `phone`) sólo se envía via `identify()`. Nunca se loguea aunque `debug: true`.

---

## Testing manual

```js
// En la consola del navegador, con SDK ya cargado:
chat.track('debug_event', { foo: 'bar' });
chat.setContext({ cartValue: 200 });
chat.identify({ email: 'test@example.com' });
chat.open();
```

Con `debug: true`, todas las acciones se loguean prefijadas con `[chat]`.

---

## Errores comunes

| Error | Causa | Solución |
|---|---|---|
| `chat is not defined` | Loader no se ejecutó | Verificar `<script>` en `<head>` o `<body>` correcto |
| `Token inválido` | `website_token` mal copiado | Copiar de panel admin del Helpdesk |
| Eventos no aparecen | `consent: false` y no se llamó `setConsent(true)` | Llamar `setConsent(true)` |
| Widget no abre | Bloqueo CSP | Añadir CSP `script-src` con dominio del SDK |
| Doble carga | Múltiples instalaciones del loader | Idempotencia: el SDK detecta `window.chat.q` ya inicializado |
