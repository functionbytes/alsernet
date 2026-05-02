=== Remarketing — Woo Bridge ===
Contributors: alsernet
Tags: remarketing, woocommerce, abandoned cart, email marketing
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: MIT

Conecta una tienda WooCommerce con la plataforma Remarketing: pixel JS, identify automático, eventos de carrito y pedidos.

== Description ==

Este plugin instala dos piezas en tu tienda WooCommerce:

1. **Pixel JS** inyectado en `<head>` para tracking de page_view, product_view, add_to_cart, checkout_start.
2. **Webhooks** disparados desde hooks de WooCommerce para enviar eventos al endpoint de Remarketing en tiempo real:
   - `cart.updated` — al añadir o modificar el carrito.
   - `checkout.created` — al iniciar el proceso de checkout.
   - `order.completed` — al confirmar el pago.

Los webhooks se firman con HMAC-SHA256 (header `X-WC-Webhook-Signature`) usando el "shared secret" configurado.

== Installation ==

1. Sube la carpeta `woocommerce-bridge/` a `wp-content/plugins/`.
2. Activa el plugin desde Plugins.
3. Configura en Ajustes → Remarketing Bridge:
   - **Endpoint URL**: la URL del webhook de tu tienda en Remarketing (incluye el store_token).
   - **Pixel URL**: URL del pixel.js servido por la plataforma.
   - **Store token**: token público (visible en el panel de Remarketing → Tiendas → Detalle).
   - **Shared secret**: el `api_secret` de la tienda en Remarketing.

== Privacy ==

El plugin envía al endpoint configurado: email del cliente cuando está logueado o ha rellenado el checkout, datos de productos, totales y eventos de comportamiento. No envía contraseñas, datos de pago ni información sensible.

Asegúrate de:
- Tener consentimiento explícito de los visitantes (banner GDPR).
- Mostrar la política de privacidad antes del primer envío.
- En zonas con GDPR/LGPD, configurar el double opt-in en Remarketing.

== Changelog ==

= 1.0.0 =
* Versión inicial: pixel + webhooks + admin settings.
