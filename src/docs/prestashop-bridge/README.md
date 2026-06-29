# Remarketing Bridge — Módulo PrestaShop 1.7 / 8.x

Módulo PHP que instala en tu tienda PrestaShop para enviar webhooks en tiempo real a tu panel de remarketing/helpdesk.

## 📦 Instalación

1. Comprime esta carpeta como `remarketingbridge.zip`
2. En tu admin de PrestaShop ve a **Módulos → Módulos y Servicios → Subir un módulo**
3. Sube el ZIP e instálalo
4. Ve a **Configurar** e introduce los 3 datos:
   - **URL base del webhook**: `https://TU-DOMINIO.com/r/webhooks/prestashop`
   - **Store Token**: el token de tu tienda (lo ves en el panel Remarketing → Tiendas)
   - **API Secret**: el secreto para firmar webhooks (HMAC-SHA256)

## 🔐 Seguridad

Los webhooks se firman con **HMAC-SHA256** usando el `API Secret`. Tu backend verifica la firma en el header `X-Remarketing-Signature`. Si no coincide, rechaza el webhook.

## 📡 Eventos enviados

| Evento | Cuándo se dispara | Datos incluidos |
|---|---|---|
| `order.validated` | Al confirmarse un pago | ID, referencia, cliente, items, totales, estado, método de pago |
| `cart.updated` | Al guardarse un carrito | ID, productos, cantidades, precios, total |
| `order.updated` | Al cambiar estado de orden | ID, referencia, nuevo estado, total |
| `customer.created` | Registro de nuevo cliente | ID, email, nombre, newsletter, optin |
| `customer.updated` | Actualización de cliente | ID, email, nombre, newsletter, optin |

## 🛠️ Desarrollo / Debug

Para ver los webhooks que salen sin tocar la tienda real, usa el modo test:

```bash
curl -X POST https://TU-DOMINIO.com/r/webhooks/prestashop/TU_TOKEN \
  -H "Content-Type: application/json" \
  -H "X-Remarketing-Topic: order.validated" \
  -H "X-Remarketing-Signature: FIRMA_HMAC" \
  -d '{"order_id":"123","reference":"ORD-001","email":"test@test.com","total_paid":99.99,"items":[]}'
```

La firma HMAC se calcula así:
```php
$signature = base64_encode(hash_hmac('sha256', $jsonBody, $secret, true));
```

## 🗑️ Desinstalación

Desinstala desde el panel de PrestaShop. Se borrará la configuración (URL, token, secret).
