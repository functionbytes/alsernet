# Despliegue · PrestaShop en el chat (sep-2026)

Lista de pasos para llevar a producción el trabajo de "Alvarez PrestaShop en el Chat":
la pestaña Tienda, el workspace de pedido y de cliente, los vales, las extensiones `ext/*`,
los avisos de estado, los ajustes y las métricas.

## 1. Tienda (repo alvarez, módulo alsernetbridge)

1. Desplegar `modules/alsernetbridge`. La versión 1.2.5 incluye:
   - `helpers/extensions.php` y `helpers/ext/*.php`;
   - `helpers/voucher.php`;
   - el hook `actionEmailSendBefore`.
2. En el BO (Módulos → alsernetbridge) pulsar **Actualizar**. Así se ejecuta `upgrade/upgrade-1.2.5.php`, que añade la columna y registra el hook.
   - Para comprobarlo: la versión debe ser 1.2.5 en `aalv_module` y el hook debe estar registrado en `aalv_hook_module`.
3. Vaciar la caché de PS (Parámetros avanzados → Rendimiento).
4. Hacer un smoke test: en el panel de Laravel, Helpdesk · PrestaShop → Registro del puente debe mostrar la llamada `ping` en OK.

## 2. Laravel (webadmin)

1. Añadir estas variables de entorno (`.env` y `docker/laravel.env` en el servidor):
   - `ALSERNETBRIDGE_ADMIN_URL=https://<tienda>/<carpeta-admin>` para los enlaces "Abrir en PS".
   - `HELPDESK_PS_RETURN_ADDRESS="Álvarez Global Sport|Polígono de Pocomaco, Primera Avenida 81, Parcela C-13|15190 Mesoiro, La Coruña"`.
   - `HELPDESK_PS_IMAGE_FALLBACK_HOST`: **vacío en producción**. Solo sirve en staging, porque allí no hay imágenes.
   - Opcionales, porque tienen valores por defecto:
     - `HELPDESK_PS_VOUCHER_AGENT_LIMIT` (25) y `HELPDESK_PS_VOUCHER_APPROVER_LIMIT` (150);
     - `HELPDESK_PS_REFUND_*`, `HELPDESK_PS_PROMOS_*` y `HELPDESK_PS_LIVEHINTS_*`;
     - `HELPDESK_PS_ERPBRIDGE_GESTION_URL`.
   - Recrear el contenedor (`docker compose up -d`), no solo `restart`, porque `restart` no vuelve a leer `env_file`.
2. Ejecutar las migraciones del módulo. Todas son tablas nuevas; ninguna altera las existentes:
   - `2026_09_24_000001_opslog_create_helpdesk_ps_received_events_table`
   - `2026_09_24_000001_opsmap_create_helpdesk_ps_state_map_table`
   - `2026_09_24_000002_opsmap_create_helpdesk_ps_state_notices_table`
   - `2026_09_25_000001_orderlink_create_helpdesk_ps_order_links_table`
   - `2026_09_25_000002_settings_create_helpdesk_ps_settings_table`
3. Ejecutar los permisos y roles:
   `php artisan module:seed HelpdeskPrestashop` (o el seeder `HelpdeskPrestashopPermissionsSeeder`) y después `php artisan permission:cache-reset`.
   - Los permisos nuevos (vouchers.*, orders.erp, metrics.view, settings.manage y los de cada `config/ext/*.php`) se reparten por rol según `role_permissions`.
4. Publicar los assets:
   - copiar `modules/HelpdeskPrestashop/public/{js,css}` a `public/modules/helpdeskprestashop/`;
   - los `.min.js` solo se sirven si son más nuevos que su fuente.
5. Ejecutar `php artisan optimize:clear` y reiniciar Horizon y los workers, para que carguen el autoload nuevo (listeners de ext).
6. La ruta de webhooks ya no depende de `integration.enabled`. Hay que comprobar que la tienda firma con el mismo `ALSERNETBRIDGE_WEBHOOK_SECRET`.

## 3. Dependencias externas pendientes

- **ERP, facturas**: la tarjeta de la pestaña Pago queda en "no disponible" hasta que el DBA de Oracle conceda `GRANT SELECT` sobre `FACTURACLI_CENTRAL` y `LFACTURACLI_CENTRAL` al usuario del Manager. No hay endpoint de PDF: la factura fiscal la emite Gestión. PS tiene `PS_INVOICE` desactivado y todas las facturas tienen `number=0`.
- **Rever**: se muestran los cambios y las devoluciones de Rever, pero sin seguimiento del paquete, porque su API no lo expone.

## 4. Pruebas que NO se hicieron en vivo (hacerlas en un PS aislado)

Estas acciones escriben en el ERP real a través de los hooks de PS. Por eso no se probaron en staging, que está conectado al ERP:

- `cartpay.convert`: convertir un carrito en pedido;
- `refunds.issue_partial`: reembolso parcial;
- `orderedit.reorder_create` y `refunds.rma_set_state`: el cliente de prueba no tiene pedidos.

Hay que montar un PS con el ERP desconectado (o un hook de ERP simulado) y probar esas cuatro acciones antes de dar permisos a los agentes.

Sí se probaron en vivo, sobre el cliente de prueba PS 951821, y se dejaron en su estado original:
account.update (también el conflicto de versión), set_group, password_reset, edición y duplicado de vales, stock_alert y cartpay.empty.

## 5. Después del despliegue

- Revisar Helpdesk · PrestaShop → **Ajustes del chat**: límites de vales, pistas en vivo, etc.
- Revisar **Avisos de cambio de estado**: qué estados avisan al cliente y el aviso interno para el agente.
- Revisar **Mapeo de estados**, porque orderlink lo usa para cerrar las conversaciones vinculadas.
- En **Métricas del chat**, comprobar que entran eventos en las primeras horas.
