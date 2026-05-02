# Operación del módulo HelpdeskLivechat

Guía operativa para admins y agentes del Helpdesk: cómo monitorear, configurar y diagnosticar el SDK de engagement.

## Mapa de menús del panel

| Sección | URL | Permiso |
|---------|-----|---------|
| Configuración general | `/panel/settings/helpdesk/livechat` | `helpdesk.livechat.settings.view` |
| Reglas de activación | `/panel/settings/helpdesk/livechat/triggers/page` | `helpdesk.livechat.triggers.view` |
| Personalización DOM | `/panel/settings/helpdesk/livechat/personalizations/page` | `helpdesk.livechat.personalizations.view` |
| Integraciones de plataforma | `/panel/settings/helpdesk/livechat/platforms/page` | `helpdesk.livechat.platforms.view` |
| Pre-chat forms | `/panel/settings/helpdesk/pre-chat-forms` | `helpdesk.livechat.settings.view` |
| Visitantes en vivo | `/panel/helpdesk/live-visitors` | `helpdesk.livechat.events.view` |
| Analytics | `/panel/helpdesk/livechat/analytics` | `helpdesk.livechat.events.view` |

## Permisos disponibles

```
helpdesk.livechat.events.view              Ver dashboards y eventos crudos
helpdesk.livechat.scores.view              Ver scores y segmentos
helpdesk.livechat.triggers.{view,create,update,delete}
helpdesk.livechat.personalizations.{view,create,update,delete}
helpdesk.livechat.platforms.{view,create,update,delete}
helpdesk.livechat.settings.{view,update}
helpdesk.livechat.manage                   Super-permiso
```

## Flujo de alta para un nuevo cliente

1. **Crear el inbox Web** (helpdesk core)
2. **Anotar el website token** del canal Web
3. **Crear `PlatformIntegration`** desde Configuración → Integraciones → Nueva
   - Seleccionar inbox + plataforma (PrestaShop/Shopify/Woo/Custom)
   - El sistema genera `webhook_secret` (mostrado **una sola vez**)
   - Copiar `webhook_url` y `webhook_secret`
4. **Cliente instala la distribución** (módulo PrestaShop / plugin Woo / theme app Shopify) con:
   - `apiUrl` = URL del panel
   - `website_token` = token del paso 2
   - `integration_id` + `webhook_secret` = del paso 3
5. **Verificar conexión**:
   - Cliente carga su web → en Live Visitors debería aparecer
   - Si configuró webhooks: completar un pedido test → debería aparecer en Analytics → Top Events como `purchase`

## Configurar reglas de activación (triggers)

1. Configuración → Reglas de activación
2. Seleccionar inbox
3. Nueva regla:
   - **Condiciones** (combinables con AND/OR):
     - `time_on_page` > 30
     - `score` >= 60
     - `segment` == hot
     - `url` contains "/checkout"
     - `context.cartValue` > 100
   - **Acción**:
     - `open_chat` → abre el widget
     - `show_banner` → inyecta HTML antes de un selector
     - `redirect` → redirige a URL
     - `callback` → ejecuta `window.fnName(payload)`
4. Guardar → la regla se aplica en la próxima carga de página de los visitantes (vía `init`)

## Configurar personalización DOM

1. Configuración → Personalización DOM
2. Seleccionar selector CSS + operación:
   - `text` — cambia el texto
   - `attribute` — cambia un atributo (ej: `href`)
   - `insert_before/after` — inyecta HTML
   - `class` — añade/quita clases
3. Opcionalmente añadir condiciones (segmento, score, contexto)
4. Guardar

## Monitoreo

### Dashboards
**Analytics** (`/panel/helpdesk/livechat/analytics`):
- Visitantes únicos, eventos totales, score medio, % hot
- Eventos por día (chart)
- Distribución de segmentos (pie)
- Top 10 eventos
- Rendimiento de triggers

**Live Visitors** (`/panel/helpdesk/live-visitors`):
- Quién está navegando ahora mismo
- Score y segmento en tiempo real

### Logs y debug

```bash
# Logs de eventos del SDK (Laravel)
tail -f storage/logs/laravel.log | grep "livechat\|webhook"

# Cola Horizon — supervisor dedicado
php artisan horizon:status
# Ver workers de helpdesklivechat
```

### Webhook logs

Tabla `helpdesk_livechat_webhook_logs` registra TODOS los webhooks recibidos:

```sql
SELECT id, platform, topic, status, attempts, last_error, created_at
FROM helpdesk_livechat_webhook_logs
WHERE platform_integration_id = ?
ORDER BY created_at DESC
LIMIT 50;
```

Estados:
- `received` — acaba de llegar, en cola
- `processed` — OK
- `failed` — falló pero todavía hay reintentos pendientes
- `dead` — agotó 5 intentos, requiere intervención manual

Reintento manual de un dead-letter:

```php
// Tinker
use Modules\HelpdeskLivechat\Jobs\ProcessWebhookJob;
ProcessWebhookJob::dispatch($webhookLogId);
```

## Diagnóstico de problemas comunes

### "El SDK no carga"

1. Abrir DevTools → Network → buscar `sdk.js`
2. ¿404? → revisar `apiUrl` en la distribución (debe apuntar a tu panel)
3. ¿CORS error? → revisar `CORS_ALLOWED_ORIGINS` en `.env` (`*` o el dominio del cliente)
4. ¿401 en `/sdk/init`? → el `website_token` no es válido o el inbox está inactivo

### "Score no se actualiza en tiempo real"

1. Revisar que Reverb esté corriendo: `php artisan reverb:start`
2. Verificar config en `init` response → debe traer `data.config.realtime.{key,host,port}`
3. WebSocket en DevTools → debe conectar a `wss://host:6001`
4. Si bloqueado por proxy/firewall → score se actualiza igual via polling (track API), solo pierdes la inmediatez

### "Webhook no se procesa"

1. Buscar en `helpdesk_livechat_webhook_logs` por `last_error`
2. Si `status='dead'` después de 5 intentos → revisar el error y arreglar (DB caída, payload corrupto, etc.)
3. Si nunca llegó → revisar firma HMAC (secret regenerado? cliente usa secret antiguo?)

### "Triggers no disparan"

1. Verificar que el `inbox_id` de la regla coincide con el del visitante
2. Verificar `is_active = true`
3. Verificar `fired_count < fires_per_session` para esa sesión
4. En la consola del navegador del visitante: `chat.on('trigger:fired', console.log)` para debug

## Operaciones de mantenimiento

### Limpieza de eventos antiguos

```bash
# Ejecutar mensual (incluir en Schedule)
DELETE FROM helpdesk_livechat_events WHERE occurred_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
DELETE FROM helpdesk_livechat_webhook_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'processed';
```

### Regenerar webhook secret

Ir a Integraciones → fila → Regenerar secret. Mostrar el nuevo al cliente para que actualice su distribución (en PrestaShop hay que actualizar `Configuration::updateValue('ALSERNET_CHAT_WEBHOOK_SECRET', ...)`, en Woo desde el admin del plugin, en Shopify desde el panel del partner).

**Importante**: el secret antiguo deja de funcionar inmediatamente. Coordinar con el cliente.

### Sincronizar catálogo de productos

El catálogo de productos del recomendador se llena vía API:

```http
POST /hd/api/sdk/catalog/sync
X-Website-Token: ...
Content-Type: application/json

{
  "products": [
    { "productId": "SKU-001", "name": "Producto X", "price": 49.99,
      "currency": "EUR", "url": "https://...", "imageUrl": "...",
      "category": "electronics" }
  ]
}
```

Cada distribución debería ejecutar este sync periódicamente (cron job en PrestaShop, scheduled action en WP, app job en Shopify).

## Métricas de salud del módulo

| Indicador | Cómo medirlo | OK / Alerta |
|-----------|--------------|-------------|
| Eventos por minuto | Analytics → eventos por día / 1440 | OK > 1, alerta si 0 durante 1h en horario activo |
| Webhook fail rate | `SELECT AVG(status='dead') FROM webhook_logs WHERE created_at > 1h ago` | OK < 1%, alerta > 5% |
| Score recalculation lag | `MAX(updated_at - last_event)` en visitor_scores | OK < 30s, alerta > 5min |
| WS connections | Reverb dashboard | depende de tráfico |

## Contactos / escalación

- **Errores backend**: revisar logs Laravel + Horizon failed jobs
- **Errores SDK cliente**: usuario abrir DevTools → Console → buscar `[chat]`
- **Webhook dead-letter masivo**: revisar conectividad con la BD `helpdesk` y estado del job worker `supervisor-livechat`
