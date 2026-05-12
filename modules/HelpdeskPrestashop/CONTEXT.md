# HelpdeskPrestashop — Contexto de integración PrestaShop

## ¿Qué hace este módulo?

Muestra datos comerciales del cliente de PrestaShop (pedidos, carritos abandonados, métricas) en la pestaña **Pedidos** del popover de contacto en la vista de tickets del Helpdesk. Los datos se obtienen del módulo `alsernetbridge` instalado en PrestaShop via HTTP con firma HMAC-SHA256.

## Arquitectura

```
Laravel Helpdesk
  → PrestashopContextService (HTTP + HMAC)
    → alsernetbridge/api.php (PrestaShop)
      → PS ObjectModels + Batch SQL
        → CartPresenter, Cart::getProducts(), OrderState, etc.
```

## Configuración

```env
ALSERNETBRIDGE_API_URL=http://localhost:8090/modules/alsernetbridge/api.php
ALSERNETBRIDGE_WEBHOOK_SECRET=fba6ea7e73e5afd426cb99e2065ca69c28471be3869b0fe8bf0f7d31c2a9434f
```

El secreto debe coincidir en ambos lados:
- **Laravel** `.env` → `ALSERNETBRIDGE_WEBHOOK_SECRET`
- **PrestaShop** `aalv_configuration.ALSERNETBRIDGE_WEBHOOK_SECRET` (tabla de config de PS, `id_configuration=4403`)

### Regenerar el secret (si se necesita rotar)

```bash
# 1. Generar nuevo secret
openssl rand -hex 32

# 2. Actualizar en Laravel (.env del sistema)
ALSERNETBRIDGE_WEBHOOK_SECRET=nuevo_valor

# 3. Actualizar en PrestaShop (MySQL de PS — Docker prestashop_mysql:3307)
mysql -u root -p -P 3307 -h 127.0.0.1 alvarez_db_bk \
  -e "UPDATE aalv_configuration SET value='nuevo_valor' WHERE id_configuration=4403;"

# 4. Limpiar caché del sistema
php artisan config:clear && php artisan cache:clear
```

> **Nota**: La tabla `aalv_configuration` de PS no tiene unique constraint en `name`. Si se inserta en vez de actualizar puede crear duplicados. Siempre usar UPDATE con `id_configuration=4403`.

## Autenticación HMAC-SHA256

Cada petición Laravel→PS lleva:
- Header `X-Alsernet-Signature: {hash_hmac('sha256', "$timestamp:$payload_json", $secret)}`
- Header `X-Alsernet-Timestamp: {unix_timestamp}`
- Header `X-Alsernet-Action: {action}`
- Body JSON: `{"action": "...", ...payload}`

**Firma con timestamp:**
El servidor calcula `hash_hmac('sha256', "$timestamp:$rawBody", $secret)` donde `$timestamp` es el unix timestamp del momento de la petición y `$rawBody` es el body JSON completo.

**Nota de compatibilidad:**
El lado PrestaShop (`alsernetbridge/api.php`) también acepta el formato legacy (firma sin timestamp) como fallback, permitiendo despliegues progresivos. Si la firma con timestamp falla, se intenta validar la firma sin timestamp.

**Acciones de escritura (idempotencia):**
Las acciones de escritura (`customer.add_message`, `order.add_note`, `order.start_return`) envían además:
- Header `X-Alsernet-Idempotency-Key: {uuid}`

Esto asegura que si la petición se reintenta (timeout, retry), PS no procesa dos veces la misma acción. PrestaShop cachea el resultado por idempotency key durante 1 hora.

## Endpoint PS: `customer.helpdesk_context`

Implementado en `alsernetbridge/api.php` → función `alsernet_customer_helpdesk_context()`.

Busca al cliente por email en `aalv_customer` usando `Customer::getCustomersByEmail()`.

### Respuesta

```json
{
  "ok": true,
  "data": {
    "customer": {
      "found": true,
      "id": 123456,
      "firstname": "Juan",
      "lastname": "García",
      "email": "juan@ejemplo.com",
      "orders_count": 15,
      "last_order_at": "2024-03-15 10:23:00",
      "ltv": 1523.50
    },
    "orders": [
      {
        "id": 652069,
        "reference": "ABCDEF",
        "placed_at": "2024-03-15 10:23:00",
        "currency_sign": "€",
        "requires_documentation": true,
        "sale_type": "escopeta",
        "payment_method": "Tarjeta de crédito",
        "state": {
          "name": "Pago aceptado",
          "color": "#32CD32",
          "is_shipped": false,
          "is_paid": true,
          "invoice_available": true,
          "delivery_available": false
        },
        "totals": {
          "products": 450.00,
          "products_excl": 371.90,
          "discount": 0,
          "wrapping": 0,
          "shipping": 5.99,
          "tax": 78.10,
          "total": 455.99
        },
        "lines": [
          {
            "name": "Escopeta Beretta A400",
            "reference": "BER-A400",
            "ean13": "8056024100001",
            "quantity": 1,
            "quantity_returned": 0,
            "unit_price_excl": 371.90,
            "unit_price_incl": 450.00,
            "discount": 0,
            "tax_rate": 21,
            "total": 450.00
          }
        ],
        "discounts": [{"name": "DESCUENTO10", "value_incl": 45.00, "free_shipping": false}],
        "payments": [{"method": "Tarjeta de crédito", "transaction_id": "TXN123", "amount": 455.99}],
        "tracking": [{"tracking_number": "ES12345678", "tracking_url": "...", "carrier_name": "SEUR", "shipped_at": "2024-03-16"}]
      }
    ],
    "carts": [
      {
        "id": 789,
        "updated_at": "2024-03-10 15:00:00",
        "currency_sign": "€",
        "is_virtual": false,
        "totals": {
          "total": 120.00,
          "products": 114.01,
          "shipping": 5.99,
          "shipping_label": "5,99 €"
        },
        "items": [
          {
            "id_product": 1001,
            "id_product_attribute": 5,
            "name": "Rifle Browning X-Bolt",
            "reference": "BRO-XBOLT-308",
            "quantity": 1,
            "attributes_small": "Calibre: .308 Win",
            "has_discount": false,
            "discount_amount": 0,
            "discount_to_display": null,
            "unit_price_original": 1200.00,
            "total_wt": 1200.00,
            "delivery_range_string": "Entrega en 3-5 días",
            "flags": [{"label": "Nuevo", "color": "#dc2626", "color_text": "#fff"}],
            "manufacturer": "Browning",
            "image_url": "https://tienda/img/p/1001-5-cart_default.jpg"
          }
        ],
        "vouchers": [],
        "fitting_services": []
      }
    ]
  }
}
```

## Campos especiales de Álvarez

### `requires_documentation` + `sale_type`

Derivado del Feature ID 23 de PS. PrestaShop Feature 23 almacena el tipo de venta:
- Value 263658 → `dni` — requiere copia de DNI
- Value 263659 → `escopeta` — licencia de escopeta
- Value 263660 → `rifle` — licencia de rifle
- Value 263661 → `corta` — licencia arma corta
- Values 290713/290714/290715 → balines (4.5mm, 5.5mm, 6.35mm)

Se obtiene buscando en `order_detail` los `id_product_attribute` → productos → feature_product con `id_feature=23`.

En el frontend se muestra un banner de alerta ámbar:
```
⚠ Requiere documentación: Licencia escopeta
```

### `tracking` — Tabla Álvarez específica

El tracking usa la tabla `aalv_orders_tracking` de Álvarez (no la nativa `order_carrier`):
- Join con `alsernet_orders_carrier_management` para el nombre del transportista
- Fallback a `order_carrier` nativo si la tabla Álvarez no existe

### `is_virtual` + `fitting_services`

`Cart::isVirtualCart()` en Álvarez detecta si el carrito contiene servicios (fitting, regalos, licencias).
Solo cuando `is_virtual=true` se llama `Cart::getProductsFitting()` que devuelve:
```json
[{"name": "Servicio ajuste carabina", "day": "2024-03-20", "hour": "10:00", "location": "A Coruña"}]
```

## Queries batch en api.php (evitan N+1)

El endpoint hace 6 queries batch para 10 órdenes:
1. Órdenes + totales + estado (`orders` JOIN `order_state_lang`)
2. Líneas de pedido (`order_detail` WHERE `id_order IN (...)`)
3. Descuentos de carrito (`order_cart_rule` WHERE `id_order IN (...)`)
4. Pagos (`order_payment` JOIN `orders` WHERE `order_reference IN (...)`)
5. Tipo de venta — Feature 23 (`product_feature` → `feature_value` WHERE `id_feature=23`)
6. Tracking Álvarez (`alsernet_orders_tracking` JOIN `alsernet_orders_carrier_management`)

## CartPresenter

Para los carritos abandonados se usa `CartPresenter::present($cart, false, $idLang)` del core de PS 1.7:
- Devuelve `products[]` con imágenes (`cover.bySize.cart_default.url`), variantes (`attributes_small`), flags (`flag[].{label, color, color_text}`), rangos de entrega (`delivery_range_string`), descuentos (`has_discount`, `discount_to_display`, `reduction`)
- Devuelve `totals` con `shipping_label` ya formateado
- Requiere `Context::getContext()->currency` seteado → se setea temporalmente con la moneda del carrito antes de llamar al presenter

## Frontend — pestaña Pedidos del popover

**Vista:** `modules/HelpdeskTickets/resources/views/managers/tickets/partials/_contact-popover.blade.php`

IDs clave:
- `#htk-cnt-ps-loading` — spinner mientras carga
- `#htk-cnt-ps-empty` — "Sin datos en PrestaShop"
- `#htk-cnt-ps-data` — contenedor de datos
- `#htk-cnt-ps-score` — bloque de métricas (LTV, nº pedidos, último pedido)
- `#htk-cnt-ps-ltv`, `#htk-cnt-ps-orders-count`, `#htk-cnt-ps-last-order`
- `#htk-cnt-ps-orders-list` — tarjetas de pedidos
- `#htk-cnt-ps-carts-block` — bloque de carritos (oculto si no hay)
- `#htk-cnt-ps-carts-list` — tarjetas de carritos

**JS:** `loadPrestashopContext(email)` + `buildOrderCard(o, idx)` + `buildCartCard(cart)` en `modules/HelpdeskTickets/public/js/tickets.js`

- `buildOrderCard`: tarjeta expandible (click en cabecera = `slideDown/slideUp`)
  - Banner `htk-doc-alert` si `requires_documentation`
  - Tabla de líneas con EAN13, cantidad devuelta
  - Desglose de totales (excl. IVA, descuento, embalaje, envío, IVA, total)
  - Pago: `fas fa-credit-card` + método
  - Tracking: `fas fa-truck` + número + URL + fecha envío

- `buildCartCard`: tarjeta de carrito abandonado
  - Badge "Virtual" si `is_virtual`
  - Items con imagen 50×50, variante, fabricante, flags PS (colores del servidor), rango de entrega, precio tachado si descuento
  - Cupones aplicados
  - Envío (shipping_label)
  - Servicios de fitting si existen

**Nota importante:** Los archivos JS/CSS tienen doble ubicación:
- Fuente: `modules/HelpdeskTickets/public/js/tickets.js`
- Servido: `public/modules/helpdesktickets/js/tickets.js`

Tras cualquier cambio hacer: `cp modules/HelpdeskTickets/public/js/tickets.js public/modules/helpdesktickets/js/tickets.js`

## Ruta API Laravel

```
GET /api/helpdeskprestashop/customers/{email}/context
```
Definida en: `modules/HelpdeskPrestashop/routes/api.php`

## Receptor de webhooks — Eventos desde PrestaShop

### Endpoint

```
POST /api/helpdeskprestashop/webhooks/event
```

Definido en: `modules/HelpdeskPrestashop/routes/webhooks.php`
Controlador: `PsEventReceiverController@handle`

### Autenticación

Cada webhook enviado desde PrestaShop lleva:
- Header `X-Alsernet-Timestamp: {unix_timestamp}` — timestamp al enviar
- Header `X-Alsernet-Signature: {hash_hmac('sha256', "$timestamp:$rawBody", $secret)}` — HMAC con firma incluida
- Header `X-Alsernet-Event: {tipo_evento}`

**Validación:**
1. Laravel verifica que `|time() - timestamp| ≤ 300 segundos` (ventana de ±5 minutos)
2. Recalcula el HMAC con `"$timestamp:$rawBody"` y lo compara con el header `X-Alsernet-Signature`
3. Si la firma no coincide, rechaza con `401 Unauthorized`

### Tipos de evento soportados

| Evento | Header `X-Alsernet-Event` | Evento Laravel | Consumidores |
|--------|---------------------------|----------------|-------------|
| Pedido creado | `order.created` | `PsOrderCreated` | Remarketing, Engagement |
| Cambio estado | `order.status_changed` | `PsOrderStatusChanged` | Remarketing, Engagement |
| Devolución | `order.return_requested` | `PsOrderReturned` | Remarketing, Engagement |
| Carrito abandonado | `cart.abandoned` | `PsCartAbandoned` | Remarketing, Engagement |
| Precio bajó | `product.price_dropped` | `PsPriceDropped` | Remarketing, Engagement |
| Stock disponible | `product.back_in_stock` | `PsBackInStock` | Remarketing, Engagement |

### Body del webhook

```json
{
  "data": {
    "customer_id": 12345,
    "email": "cliente@ejemplo.com",
    "order_id": 652069,
    ...
  }
}
```

El controller extrae `data` del body y despacha el evento Laravel correspondiente con esa información.

## Caché

- Laravel cachea la respuesta 5 minutos por `md5(email)` — configurable en `config/helpdeskprestashop.php` (`cache_ttl`)
- Para invalidar manualmente: `php artisan cache:clear`

## Módulo PS `alsernetbridge` — ubicación

```
/Users/developerts/Herd/alvarez/src/modules/alsernetbridge/
  api.php          ← endpoint pull (acceso externo)
  config.php       ← configuración del módulo
  alsernetbridge.php ← módulo PS principal (gestiona el backoffice y la config)
```

La función clave es `alsernet_customer_helpdesk_context(?int $idCustomer): ?array` en `api.php` (línea ~521).
