# Setup: Integración Alvarez (PrestaShop + ERP)

> Pasos a seguir para activar la integración multi-fuente para el cliente Alvarez (o cualquier cliente que tenga PrestaShop + ERP propio).

---

## Pre-requisitos

- Docker corriendo el proyecto `manager` (acceso a Oracle)
- Acceso admin al panel Alsernet (proyecto `system`)
- Acceso admin a la tienda PrestaShop del cliente
- Inbox creado en el panel Alsernet para el cliente

---

## Paso 1 — Generar token Sanctum bridge (en el proyecto manager)

Entra al contenedor Docker del proyecto `manager` y ejecuta:

```bash
cd /var/www/html
php artisan erp:issue-bridge-token --user=system@bridge --label=alsernet-helpdesk
```

Salida esperada:
```
Token generado correctamente. Copia este valor y pégalo en el panel Alsernet:

  1|abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890

⚠ Este token NO se vuelve a mostrar. Si lo pierdes, revoca el actual y emite otro.
```

**Copia el token completo (incluye el `1|` al inicio).**

> Si quieres limitar las habilidades del token, usa `--abilities=customer:read --abilities=customer:write`. Por defecto da `*` (todas).

---

## Paso 2 — Configurar la integración ERP en el panel Alsernet

1. Ir a **Settings → Engagement → Integraciones de plataformas**
2. Click **Nueva integración**
3. Llenar:
   - **Inbox**: el inbox del cliente Alvarez
   - **Plataforma**: `ERP propio (Oracle / sistema interno)`
   - **URL de la tienda / API base**: `https://manager.alvarez.local/api/erp` *(la base URL donde está el proyecto manager — sin barra final)*
   - **Token de autenticación**: pega el token del paso 1
   - **Estrategia de búsqueda**: `Por email (recomendado)`
   - **Estado**: `Activa`
4. **Guardar**

El token se guarda **encriptado** automáticamente. Al volver a abrir la integración aparece como `•••••••••••• (configurado)`.

---

## Paso 3 — Instalar plugin PrestaShop v1.1.0

1. Empaquetar el plugin:
   ```bash
   cd /Users/developerts/Herd/system/modules/Engagement/distributions/prestashop
   zip -r alsernet_chat_v1.1.0.zip alsernet_chat/ -x "*.DS_Store"
   ```

2. Subir el ZIP en PrestaShop: **Backoffice → Modulos → Subir un módulo nuevo**

3. Si ya estaba instalado v1.0.0, **desinstalarlo y reinstalar** (no es upgrade automático). Los hooks nuevos requieren registro fresco.

4. Configurar el módulo:
   - **API URL**: `https://panel.alsernet.com` *(URL del panel Alsernet)*
   - **Website token**: token del canal Web del inbox
   - **Integration ID**: ID numérico de la integración PrestaShop (visto al crear la integración tipo `prestashop`)
   - **Webhook secret**: el secret de 64 chars mostrado al crear la integración PrestaShop
   - **Sincronizar catálogo**: opcional

---

## Paso 4 — Configurar la integración PrestaShop en el panel Alsernet

1. **Settings → Engagement → Integraciones de plataformas**
2. **Nueva integración**:
   - **Inbox**: el MISMO inbox del cliente Alvarez (puede tener múltiples integraciones)
   - **Plataforma**: `PrestaShop`
   - **URL de la tienda**: `https://www.alvarez.com` *(URL del frontend de la tienda)*
   - **Estado**: `Activa`
3. **Guardar** → te muestra el **Webhook URL** y el **Webhook secret**
4. Pega esos valores en el plugin PrestaShop (paso 3)

---

## Paso 5 — Validar la conexión

### Validar webhook PS → Panel
Crea una orden de prueba en PrestaShop. Debe aparecer un evento en:
- **Settings → Engagement → Webhook logs**
- BD: `SELECT * FROM engagement_events WHERE platform='prestashop' ORDER BY id DESC LIMIT 5;`

### Validar pull on-demand PrestaShop
Abre una conversación del inbox de un cliente que exista en PrestaShop. En el right-panel deben aparecer las tabs **Devol.**, **Cupones**, **Direc.** Al hacer clic, los datos se cargan vía AJAX.

Si no cargan, abre DevTools → Network y busca la request a `/panel/engagement/customer-data/lookup`. Si responde 401 = secret desincronizado.

### Validar pull on-demand ERP
En el right-panel deben aparecer las tabs **Gestión**, **Finanzas**, **Fidelización**. Al hacer clic, los datos se cargan vía AJAX desde el ERP.

Si no cargan, verificar en logs:
```bash
tail -f storage/logs/laravel.log | grep -E 'connector:erp'
```

---

## Paso 6 — Permisos del agente

Asegúrate de que el rol del agente que atenderá las conversaciones tenga el permiso:
```
helpdesk.conversations.view
```

Sin este permiso, el endpoint `/customer-data/lookup` devuelve 403 y los tabs ERP/PS quedan vacíos.

---

## Estructura de datos resultante

Tras configurar todo:

```
helpdesk_inboxes (id=12, name="Soporte Alvarez")
  ├─ engagement_platform_integrations (platform=prestashop, store_url=alvarez.com)
  └─ engagement_platform_integrations (platform=erp, store_url=manager.alvarez.local/api/erp)

helpdesk_customers (id=500, email=cliente@email.com)
  ├─ helpdesk_customer_external_ids (platform=prestashop, external_id=12345)
  └─ helpdesk_customer_external_ids (platform=erp, external_id=98765)
```

Cuando el agente abre la conversación de este cliente:
1. Right-panel muestra tabs por fuente (PS y ERP)
2. JS llama `/customer-data/lookup` con el email
3. El orchestrator descubre las dos integraciones del inbox
4. El `PrestaShopCustomerConnector` llama al api.php del plugin
5. El `ErpCustomerConnector` llama a `/api/erp/customer/...`
6. Las respuestas se renderizan en sus respectivos tabs con cache de 5 min (PS) y 30-300s (ERP, según action)

---

## Cache TTLs (revisable en `AbstractCustomerDataConnector`)

| Acción | TTL |
|--------|-----|
| profile, orders, returns, vouchers, deliveryNotes, invoices, bonuses | 300s (5 min) |
| addresses | 1800s (30 min) |
| messages, orderDetail | 300s (5 min) |
| cart, payments, loyaltyPoints | 60s (1 min) |
| debts, balance | 30s (sensible — financiero) |

Cache se invalida automáticamente al recibir webhook PS (`actionValidateOrder`, `actionCustomerAccountAdd`, etc.). Para ERP no hay invalidación push (sólo TTL) — es aceptable porque los TTL financieros son cortos.

---

## Troubleshooting

| Problema | Causa probable | Solución |
|----------|----------------|----------|
| Tabs ERP no aparecen | No hay integración tipo `erp` activa para el inbox | Crear integración (paso 2) |
| Tab vacío con "Sin actividad" | Email del customer no existe en ERP | Verificar email en `/api/erp/customer/search?q=email` |
| 401 en lookup | Secret PS desincronizado o token ERP revocado | Regenerar secret/token y reconfigurar |
| 403 en lookup | Agente sin permiso `helpdesk.conversations.view` | Asignar permiso al rol |
| Datos viejos | Cache no invalidado | Click en botón "Actualizar" del tab Pedidos |
| Webhook no llega | Plugin desactivado o URL del panel mal configurada | Verificar `Backoffice → Configuración avanzada → Webhooks/Hooks` |

---

## Revocar token bridge ERP

```bash
# Listar tokens activos
php artisan tinker
> Laravel\Sanctum\PersonalAccessToken::where('name', 'alsernet-helpdesk')->get();

# Revocar uno específico
> Laravel\Sanctum\PersonalAccessToken::find(1)->delete();
```

Luego volver a emitir uno nuevo (paso 1) y actualizar la integración (paso 2 — botón Editar).

---

## Versión y mantenimiento

- Plugin PrestaShop: `1.1.0` (config.xml + alsernet_chat.php)
- Migración tabla pivot: `2026_05_02_200000_create_helpdesk_customer_external_ids_table.php`
- Connectors registrados en `ConnectorFactory::$connectors`

Para añadir un nuevo cliente con la misma arquitectura, repetir pasos 1-6 con sus URLs propias.
