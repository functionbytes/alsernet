# Remarketing Bridge — Módulo PrestaShop

Conecta una tienda PrestaShop 1.7 / 8.x con la plataforma **Remarketing**:

- Inyecta el pixel JS en todas las páginas (page_view, product_view, identify automático).
- Dispara webhooks firmados HMAC-SHA256 desde hooks nativos de PrestaShop:
  - `actionCartSave` → `cart.updated`
  - `actionValidateOrder` → `order.validated`
  - `actionCustomerAccountAdd` → `customer.created`
  - `actionObjectCustomerUpdateAfter` → `customer.updated`
  - `actionProductUpdate` → `product.updated`

## Instalación

1. Comprime esta carpeta `remarketingbridge/` en un ZIP:
   ```bash
   cd modules/Remarketing/resources/plugins/prestashop-bridge/
   zip -r remarketingbridge.zip remarketingbridge
   ```
2. Back office PrestaShop → **Módulos → Gestor de módulos → Subir un módulo** → arrastra el ZIP.
3. Tras instalar, click en **Configurar**.
4. Rellena:
   - **Endpoint URL** — `https://tu-dominio-laravel/r/webhooks/prestashop/{store_token}`
   - **Pixel URL** — `https://tu-dominio-laravel/remarketing/pixel.js`
   - **Track endpoint** — déjalo vacío (se autodetecta) o `https://tu-dominio-laravel/r/track`
   - **Store token** — el `webhook_token` público de la tienda en el panel Remarketing
   - **Shared secret** — el `api_secret` configurado en la tienda

## Verificación

Después de configurar, abre el front office y revisa:
- En **DevTools → Network** debe aparecer una petición a `/r/track` con `type=page_view`.
- Añadir un producto al carrito → cookie `_rmk_vid` se persiste y se dispara `cart.updated` al endpoint.
- Confirmar un pedido → llega `order.validated` con HMAC válido.

## Notas técnicas

- Todos los webhooks llevan `X-Remarketing-Signature` (header propio) y `X-WC-Webhook-Signature` (compat con scheme WooCommerce). El connector Laravel valida ambos.
- Los curl son best-effort con timeout 5s para no bloquear el flujo del cliente.
- Los errores se logean en `PrestaShopLogger` (severity 2).

## Compatibilidad

- PrestaShop 1.7.x ✓
- PrestaShop 8.0 / 8.1 ✓
- PrestaShop 9.x — no probado todavía.
- PHP 7.4 mínimo, PHP 8.2 recomendado.
