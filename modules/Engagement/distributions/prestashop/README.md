# Alsernet Live Chat — Módulo PrestaShop

Integración nativa para PrestaShop 1.7.0+ y 8.x. Carga el SDK Engagement en el frontstore, expone los datos del cliente/carrito/producto al adapter automáticamente y envía webhooks firmados HMAC al panel.

## Estructura del paquete

```
alsernet_chat/
├── alsernet_chat.php              ← Clase principal (Module)
├── config.xml                     ← Metadatos del módulo
├── cron.php                       ← Endpoint para sync de catálogo
├── index.php                      ← Sentinela seguridad
├── logo.png                       ← Icono back office (32x32)
├── translations/
│   ├── es.php · en.php · fr.php   ← 3 idiomas
│   └── index.php                  ← Sentinela
└── views/
    └── index.php                  ← Sentinela
```

## Instalación

```bash
# Construir el zip
cd modules/Engagement/distributions/prestashop
./build.sh
# Genera: alsernet_chat-1.0.0.zip
```

1. **Back office PrestaShop** → **Módulos → Catálogo → Subir un módulo** → seleccionar el `.zip`
2. **Configurar** desde el módulo:
   - **URL de la API**: `https://panel.alsernet.com`
   - **Website token**: del canal Web del inbox del Helpdesk
   - **Integration ID**: del panel `Configuración → Integraciones → Nueva PrestaShop`
   - **Webhook secret**: mostrado al crear la integración (cópialo, solo se muestra 1 vez)
   - **Sincronizar catálogo**: ON si quieres recomendaciones de productos

## Cómo funciona

### Frontend (cliente navega)
- Hook `displayHeader` inyecta el stub `chat()` y carga `sdk.js` con `async`.
- Llama `chat('init', ...)` con el token configurado.
- El adapter `prestashop.ts` del SDK detecta `window.prestashop` y lee:
  - `prestashop.cart` → items, total, currency
  - `prestashop.customer` → email, nombre, ID
  - `prestashop.product` → producto actual

### Backend (eventos servidor)
| Hook PrestaShop | Topic webhook | Significado |
|-----------------|---------------|-------------|
| `actionValidateOrder` | `actionValidateOrder` | Compra completada |
| `actionCustomerAccountAdd` | `actionCustomerAccountAdd` | Nuevo cliente registrado |
| `actionCartUpdateQuantityBefore` | `actionCartUpdateQuantityBefore` | Producto añadido/modificado en carrito |

Cada webhook envía a `POST /eng/api/sdk/webhook/prestashop/{integration_id}` con headers:
```
X-Alsernet-Signature: <hmac-sha256>
X-Alsernet-Topic: <hook_name>
Content-Type: application/json
```

### Catálogo (cron)
Si tienes catálogo activado, configurar cron del servidor:
```cron
0 */6 * * * curl -s "https://shop.example.com/modules/alsernet_chat/cron.php?secret=YOUR_SECRET&page=1"
```

El cron envía hasta 250 productos por llamada al endpoint `/eng/api/sdk/catalog/sync`.

## Verificación de instalación

Tras instalar y configurar:

1. **Frontend**: abrir tienda → DevTools Network → debe haber petición `GET sdk.js` (200) y `POST /eng/api/sdk/init` (200)
2. **Panel Alsernet** → **Visitantes en vivo** → debe aparecer la sesión
3. **Crear pedido test** → en panel → **Top eventos** → aparece `purchase`
4. **Catálogo**: ejecutar manualmente el cron URL → debe devolver `{"success":true,"count":N}`
5. **Webhook log**: `/panel/settings/engagement/webhook-logs/page` → debe mostrar entradas con status `processed`

## Compatibilidad

| Plataforma | Versión |
|-----------|---------|
| PrestaShop | 1.7.0 — 8.x |
| PHP | 7.4+ (probado en 8.0, 8.1, 8.2) |
| Multi-shop | ✅ (configuración compartida) |
| Multi-idioma | es, en, fr |

## Hooks registrados

```
displayHeader                       ← inyectar SDK (visible en cada página)
displayFooter                       ← reservado para futuras CTAs
actionValidateOrder                 ← webhook al confirmar pedido
actionCustomerAccountAdd            ← webhook al crear cuenta
actionCartUpdateQuantityBefore      ← webhook al modificar carrito
```

## Troubleshooting

| Síntoma | Causa | Solución |
|---------|-------|----------|
| SDK no carga (DevTools muestra 404 sdk.js) | URL API mal configurada | Revisar `URL de la API` (sin barra final, https) |
| `init` devuelve 401 | Token inválido | Revisar `Website token` desde panel del Helpdesk |
| Webhook no llega al panel | `integration_id` o `secret` mal | Reconfigurar en módulo + revisar `/panel/settings/engagement/webhook-logs/page` |
| Cron timeouts | Catálogo muy grande | Reducir `perPage` en `cron.php` o paginar |
| `Configuración guardada` aparece pero campos vacíos al recargar | Multi-shop config | Activar `Configurar para todas las tiendas` en multistore |

## Desarrollo

```bash
# Editar el módulo
vim modules/Engagement/distributions/prestashop/alsernet_chat/alsernet_chat.php

# Re-empaquetar
./build.sh
```
