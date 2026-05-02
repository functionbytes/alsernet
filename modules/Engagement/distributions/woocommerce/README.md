# Alsernet Live Chat — Plugin WordPress / WooCommerce

Plugin de WordPress que carga el SDK de Alsernet, expone los datos de cliente/carrito/producto al adapter WooCommerce y envía webhooks firmados de pedidos.

## Instalación

1. Comprimir la carpeta `alsernet-chat/` en un `.zip`:
   ```bash
   cd distributions/woocommerce
   zip -r alsernet-chat.zip alsernet-chat
   ```
2. En el admin de WordPress: **Plugins → Añadir nuevo → Subir plugin** y seleccionar el `.zip`.
3. Activar.
4. Ir a **Ajustes → Alsernet Chat** y rellenar:
   - URL de la API
   - Website token
   - Integration ID (opcional, solo para webhooks)
   - Webhook secret (debe coincidir con el guardado en el panel Alsernet)

## Cómo funciona

- `wp_enqueue_scripts` carga `sdk.js` con `defer` desde la API.
- `wp_head` imprime un script que:
  - Define `window.__alsernet_woo = { cart, customer, product }`.
  - Inicializa el SDK con `chat('init', { token, apiUrl, consent: true })`.
- El adapter `woocommerce.ts` del SDK lee de `window.__alsernet_woo` y captura eventos jQuery `added_to_cart`, `removed_from_cart`, etc.
- `woocommerce_thankyou` envía un webhook firmado HMAC al endpoint `/eng/api/sdk/webhook/woocommerce/{id}`.
- `user_register` envía webhook `customer.created`.

## Acciones soportadas

| Acción WP | Endpoint webhook | Topic |
|-----------|------------------|-------|
| `woocommerce_thankyou` | `/eng/api/sdk/webhook/woocommerce/{id}` | `order.created` |
| `user_register` | `/eng/api/sdk/webhook/woocommerce/{id}` | `customer.created` |

## Compatibilidad

- WordPress 5.5+
- WooCommerce 4.0+ (plugin funciona también sin WooCommerce, solo sin webhooks)
- PHP 7.4+
