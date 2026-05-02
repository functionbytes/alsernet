# Alsernet Live Chat — Módulo PrestaShop

## Configuración tras instalación

1. **Módulos → Alsernet Chat → Configurar**
2. Rellenar campos:
   - **URL de la API**: panel Alsernet (ej. `https://panel.alsernet.com`)
   - **Website token**: del canal Web del Helpdesk
   - **Integration ID**: del panel `Configuración → Integraciones → PrestaShop`
   - **Webhook secret**: mostrado al crear la integración (cópialo ahora, solo se ve 1 vez)
   - **Sincronizar catálogo**: ON si quieres recomendaciones de productos

## Cron de catálogo (opcional)

Si activaste catálogo, configura en tu cron del servidor:

```bash
0 */6 * * * curl -s -X POST -H "X-Alsernet-Cron-Secret: TU_SECRET" \
    "https://tu-tienda.com/modules/alsernet_chat/cron.php?page=1"
```

## Hooks registrados

- `displayHeader` — carga del SDK en frontstore (solo)
- `actionValidateOrder` — webhook al confirmar pedido
- `actionCustomerAccountAdd` — webhook al crear cuenta
- `actionCartUpdateQuantityBefore` — webhook al cambiar carrito
- `actionDeleteGDPRCustomer` — compliance GDPR (eliminar datos)
- `actionExportGDPRData` — compliance GDPR (portabilidad de datos)

## Verificación

- Frontend: DevTools Network → debe haber `GET sdk.js` (200) y `POST /eng/api/sdk/init` (200)
- Panel → Visitantes en vivo → debe aparecer la sesión activa
- Webhook logs → `/panel/settings/engagement/webhook-logs/page`

## Compatibilidad

- PrestaShop 1.7 — 8.x
- PHP 7.4+

## Soporte

https://alsernet.com · soporte@alsernet.com
