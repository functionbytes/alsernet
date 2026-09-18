# HelpdeskErp — Contexto de integración ERP

## ¿Qué hace este módulo?

Muestra datos comerciales del cliente (datos personales, balance, pedidos ERP e facturas) en la pestaña **ERP** del popover de contacto en la vista de tickets del Helpdesk.

## Arquitectura

La fuente de datos es el **proyecto `manager`** (`/Users/developerts/Herd/manager/src`), que conecta directamente con Oracle vía PDO OCI8 (extensión PHP) y expone una API REST completa.

```
system (Helpdesk) → manager API (JSON/REST) → Oracle ERP
```

El proyecto `system` **NO** conecta directamente al ERP. Solo llama a la API del manager.

## Configuración

```env
# URL base del proyecto manager (sin trailing slash)
# Manager corre en Docker — nginx expone puerto 8080 al host
ERP_MANAGER_URL=http://localhost:8080

# Token Sanctum opcional (las rutas de cliente son abiertas, sin auth requerida)
ERP_BRIDGE_TOKEN=

# TTL en segundos para el contexto del cliente cuando se encuentra (default: 10 min)
HELPDESK_ERP_CACHE_TTL=600

# TTL en segundos para negative cache cuando el cliente no se encuentra (default: 1 min)
# Evita golpear el manager repetidamente para emails inexistentes
HELPDESK_ERP_MISS_TTL=60

# Segundos antes de la expiración real en los que se considera el caché stale
# y se lanza un RefreshErpContextJob en background (stale-while-revalidate)
HELPDESK_ERP_STALE_GRACE=60

# Timeout HTTP para llamadas a la API del manager (segundos)
HELPDESK_ERP_HTTP_TIMEOUT=15

# Secret HMAC compartido con el proyecto manager para validar webhooks
# que notifican que el escaneo de pedidos Oracle ha terminado
# Generar con: openssl rand -hex 32
ERP_WEBHOOK_SECRET=
```

Si `ERP_MANAGER_URL` está vacío, el servicio devuelve contexto vacío sin hacer ninguna llamada HTTP.

El `ERP_BRIDGE_TOKEN` es opcional porque las rutas `/api/erp/customer/*` en el manager **no requieren autenticación** (solo tienen middleware `['api']`). Se puede configurar si en el futuro se añade auth a esas rutas.

## API del manager — Endpoints usados

Base URL: `{ERP_MANAGER_URL}/api/erp/customer`

Todas las respuestas JSON con formato `{ data: ..., meta: ... }`.

### `GET /api/erp/customer/search?q={email}`

Busca cliente por email. Devuelve array de resultados. El servicio toma el primero con email exacto.

```json
{
  "data": [
    {
      "id": 12345,
      "label": "Juan",
      "surnames": "García López",
      "email": "juan@ejemplo.com",
      "cif": "12345678Z"
    }
  ]
}
```

### `GET /api/erp/customer/{id}`

Resumen del cliente. Devuelve `data` con:
- `phones[0].number` — teléfono principal
- `addresses[0].city`, `addresses[0].province` — ciudad y provincia
- `payment_method_id` — forma de pago habitual
- `cif` — NIF/CIF

### `GET /api/erp/customer/{id}/balance`

Balance financiero. Devuelve `data` con:
- `balance.invoiced` — total facturado
- `balance.collected` — total cobrado
- `balance.pending` — saldo pendiente
- `risk.current` — riesgo actual
- `risk.max_allowed` — límite de crédito
- `loyalty_points` — puntos de fidelización

### `GET /api/erp/customer/{id}/orders?limit={n}`

Pedidos del cliente. En la primera llamada puede devolver datos vacíos mientras Oracle ejecuta el escaneo completo en background:

```json
{
  "data": [],
  "meta": { "loading": true, "retry_after": 35 }
}
```

Cuando está disponible:
```json
{
  "data": [
    {
      "id": 99001,
      "number": "2024/1234",
      "status": "SERV",
      "date": "2024-03-15",
      "expected_date": "2024-03-20",
      "served_date": "2024-03-19",
      "warehouse": "CENTRAL",
      "type": "P",
      "observations": "Entrega urgente"
    }
  ]
}
```

### `GET /api/erp/customer/{id}/invoices?limit={n}`

Facturas del cliente:
```json
{
  "data": [
    {
      "id": 55001,
      "number": "F-2024-00123",
      "series": "F",
      "year": 2024,
      "date": "2024-03-20",
      "status": "PAID",
      "simplified": false,
      "payment_method": "Transferencia"
    }
  ]
}
```

## Flujo del servicio (ErpContextService) y estrategia de caché

1. Consulta el caché Redis por clave `'erp_ctx_' . md5(email)`
2. Si está en caché:
   - Devuelve los datos cacheados
   - Si está dentro de la ventana `stale_grace` antes de expirar → dispara `RefreshErpContextJob` en background (`ShouldBeUnique`, `uniqueFor: 30s`)
3. Si no está en caché o ha expirado:
   - `GET /search?q={email}` → toma el primer resultado con email exacto
   - Con el `id` del cliente, llama **en paralelo** (Http::pool):
     - `GET /{id}` (summary)
     - `GET /{id}/balance`
     - `GET /{id}/orders?limit=10`
     - `GET /{id}/invoices?limit=5`
   - Normaliza y cachea:
     - **Con cliente encontrado**: TTL = `cache_ttl` (600s por defecto)
     - **Cliente no encontrado**: TTL = `miss_ttl` (60s por defecto) → negative cache
     - **Oracle aún cargando (`orders_loading: true`)**: **NO se cachea** (TTL = 0)
     - **Error transitorio**: TTL corto (~5s) si se reciben excepciones

4. Estructura de retorno:

```php
[
    'customer' => [
        'found'           => true,
        'id'              => int,
        'name'            => string,
        'email'           => string,
        'nif'             => string|null,
        'phone'           => string|null,
        'city'            => string|null,
        'province'        => string|null,
        'credit_limit'    => float|null,
        'balance_pending' => float|null,
        'balance_invoiced'=> float|null,
        'loyalty_points'  => int|null,
        'payment_terms'   => string|null,
    ],
    'orders'         => [...],   // array de pedidos normalizados
    'invoices'       => [...],   // array de facturas normalizadas
    'orders_loading' => bool,    // true si Oracle aún carga los pedidos
]
```

5. En caso de error (HTTP o excepción): devuelve `['customer' => ['found' => false], 'orders' => [], 'invoices' => [], 'orders_loading' => false]` con `_error` si es necesario

## Flujo de `orders_loading` y notificación via Reverb

1. **Primer llamada** (Oracle aún escaneando):
   - Manager devuelve `{data: [], meta: {loading: true, retry_after: 35}}`
   - Sistema propaga `orders_loading: true` al frontend
   - Frontend muestra spinner "Cargando pedidos desde Oracle…"

2. **Mientras carga** (no se cachea):
   - El TTL de caché es 0 → cada refresh consulta al manager
   - Frontend realiza polling automático cada 35 segundos (con backoff)

3. **Cuando termina el escaneo**:
   - Manager llama webhook `POST /api/helpdeskErp/webhooks/orders-ready` (HMAC signed)
   - Sistema invalida el caché e emite evento Reverb `ErpOrdersReady`
   - Frontend recibe evento en canal `erp-orders-ready.{md5_hash}`, abandona el polling, hace refetch

4. **Fallback sin Reverb**:
   - Si `window.Echo` no está disponible: continúa polling (hasta 3 intentos × 35s = 105s máx)
   - Timeout de seguridad: 90s sin evento → fallback automático al polling

**Nota**: `orders_loading: true` y la ventana `stale_grace` son conceptos separados.
- `orders_loading: true` → no cachear (TTL = 0), activamente cargando en Oracle
- `stale_grace` → caché envejecido pero aún válido, refrescar en background sin bloquear la respuesta

## Ficheros clave

| Fichero | Descripción |
|---------|-------------|
| `app/Services/ErpContextService.php` | Lógica core: consulta al manager, caché con stale-while-revalidate, TTL condicional |
| `app/Services/CustomerTimelineService.php` | Agregación cronológica de eventos (ERP + PrestaShop + Helpdesk) con resolución defensiva de `HelpdeskPrestashop` |
| `app/Http/Controllers/Api/ErpContextController.php` | Endpoints: context, timeline, search, refresh, orderDetail, health, warmCache |
| `app/Http/Controllers/Api/WebhookController.php` | Webhook: `POST /webhooks/orders-ready`, validación HMAC + timestamp, broadcasting Reverb |
| `app/Jobs/RefreshErpContextJob.php` | Job único por email (ShouldBeUnique) que refrescar caché en background cuando entra en stale_grace |
| `app/Jobs/WarmErpCacheJob.php` | Job en queue `helpdesk-erp-warming` para pre-cachear lotes de emails |
| `app/Console/Commands/WarmErpCacheCommand.php` | Comando CLI `helpdeskerp:warm-cache` (programado cada 30 min) que recoge emails de clientes con tickets/conversaciones abiertos |
| `app/Events/ErpOrdersReady.php` | Evento ShouldBroadcast para notificar al frontend via Reverb canal privado `erp-orders-ready.{hash}` |
| `database/seeders/HelpdeskErpPermissionsSeeder.php` | Crea 4 permisos y los asigna a roles `admin`, `super-admin`, `super-administrador` |
| `config/config.php` | Todas las variables config: `manager_url`, `bridge_token`, `cache_ttl`, `miss_ttl`, `stale_grace`, `http_timeout`, `orders_limit`, `invoices_limit`, `webhook_secret` |
| `routes/api.php` | Definición de todas las rutas (context, timeline, search, refresh, health, orderDetail, cache/warm) |
| `module.json` | Declara dependencia opcional: `"requires": ["HelpdeskPrestashop"]` |

## Frontend — pestaña ERP del popover

**Vista:** `modules/HelpdeskTickets/resources/views/managers/tickets/partials/_contact-popover.blade.php`

IDs clave:
- `#htk-cnt-erp-loading` — spinner mientras carga
- `#htk-cnt-erp-empty` — "Sin datos en ERP"
- `#htk-cnt-erp-data` — contenedor de datos
- `#htk-erp-name`, `#htk-erp-nif`, `#htk-erp-credit`, `#htk-erp-balance`, `#htk-erp-terms`, `#htk-erp-city`
- `#htk-erp-orders-block` / `#htk-erp-orders-list` — bloque de pedidos ERP
- `#htk-erp-invoices-block` / `#htk-erp-invoices-list` — bloque de facturas ERP

**JS:** función `loadErpContext(email)` en `modules/HelpdeskTickets/public/js/tickets.js`

## Rutas API del módulo (sistema)

Todas las rutas están bajo `/api/helpdeskErp` con middleware común `['api', 'auth:sanctum', 'throttle:60,1']` excepto el webhook.

### GET `/api/helpdeskErp/customers/search`

Busca clientes por email, teléfono o NIF (min. 3 caracteres).

- **Permiso requerido**: `helpdeskErp.view`
- **Query params**: `q` (texto de búsqueda), `type` (email|phone|nif, default: email)
- **Respuesta**: `{data: [...]}`

### GET `/api/helpdeskErp/customers/{email}/context`

Retorna contexto comercial ERP del cliente (datos personales, balance, pedidos, facturas).

- **Permiso requerido**: `helpdeskErp.view`
- **Middleware adicional**: `audit.access:erp,context_view` (logging GDPR)
- **Respuesta**: `{success: true, data: {customer, orders, invoices, ordersLoading, meta}}`

### GET `/api/helpdeskErp/customers/{email}/timeline`

Retorna eventos cronológicos agregados (pedidos/facturas ERP, pedidos/carritos PrestaShop, conversaciones Helpdesk).

- **Permiso requerido**: `helpdeskErp.view`
- **Query params**: `limit` (default: 50, max: 100)
- **Respuesta**: `{data: [{type, source, date, title, data}, ...]}`
- **Nota**: PrestaShop events se resuelven defensivamente (módulo `HelpdeskPrestashop` opcional)

### POST `/api/helpdeskErp/customers/{email}/context/refresh`

Invalida el caché Redis del cliente y retorna datos frescos.

- **Permiso requerido**: `helpdeskErp.refresh`
- **Respuesta**: `{success: true, message: "...", data: {...}}`

### GET `/api/helpdeskErp/customers/{customer}/orders/{order}`

Detalle de un pedido específico (proxied desde manager).

- **Permiso requerido**: `helpdeskErp.orders.detail.view`
- **Path params**: `customer` (int), `order` (int)
- **Respuesta**: `{success: true, data: {...}}`

### GET `/api/helpdeskErp/health`

Estado de la conexión al manager ERP (latencia, accesibilidad Oracle).

- **Permiso requerido**: `helpdeskErp.health.view`
- **Respuesta**: `{success: true, data: {status, manager_url, oracle_reachable, latency_ms}}`

### POST `/api/helpdeskErp/cache/warm`

Dispara jobs para pre-cachear una lista de emails (máx. 50).

- **Permiso requerido**: `helpdeskErp.refresh`
- **Body**: `{emails: ["...@...", "...@..."]}`
- **Respuesta**: `{ok: true, queued: N}`

### POST `/api/helpdeskErp/webhooks/orders-ready`

Webhook llamado por el manager cuando el escaneo Oracle de pedidos de un cliente termina.

- **Middleware**: `['api']` solo (sin auth:sanctum)
- **Autenticación**: HMAC-SHA256 en headers
  - `X-Erp-Signature: hash_hmac('sha256', $timestamp . ':' . $rawBody, $secret)`
  - `X-Erp-Timestamp: <epoch>` (válido ±5 min)
- **Body**: `{email: "...", customer_id: 123}`
- **Acción**: invalida caché, emite evento Reverb `ErpOrdersReady`
- **Respuesta**: `{ok: true}` o error `{ok: false, error: "..."}`

## Proyecto manager — estructura Oracle

- **Conexión**: `'oracle'` (PDO OCI8, configurada en `manager/.env`)
- **Modelos**: `modules/Erp/app/Models/Oracle/`
  - `Cliente/ClienteCent` — tabla `cliente_cent`
  - `Pedido/PedidocliCentral` — tabla `pedidocli_central`
  - `Factura/FacturacliCentral` — tabla `facturacli_central`
- **Controlador**: `modules/Erp/app/Http/Controllers/Api/CustomerController`
- **Rutas**: `modules/Erp/routes/api.php` bajo `Route::middleware(['api'])` — sin autenticación requerida

## Infraestructura Docker del manager

El proyecto manager corre en Docker (`/Users/developerts/Herd/manager/docker/docker-compose.yml`).

| Contenedor | IP Docker | Puerto host | Función |
|---|---|---|---|
| `nginx` | `172.20.0.2` | `8080` (HTTP), `8443` (HTTPS) | Proxy al FPM |
| `app` | `172.20.0.3` | — | PHP-FPM con OCI8 |

- **Desde el `system` (Herd)**: acceder vía `http://localhost:8080` (port binding del host)
- **MySQL del manager**: `host.docker.internal:3306` → mismo MySQL del host (base `managerchat`)
- **Oracle**: `192.168.253.8:1521`, servicio `GESTCENT`, usuario `lectura` / pass `alsernet`, schema `DEVELOPER`
- **OCI8**: instalado en el contenedor `app`, extensión PHP funcional
- **Configuración Oracle**: cargada desde tabla `settings` de `managerchat` (keys `oracle_host`, `oracle_port`, etc.) por `ErpServiceProvider` vía `Setting::getErpSettings()`

### Tablas mínimas requeridas en `managerchat`

Si se resetea la BD del manager, estas tablas deben existir antes de que funcione la API ERP:

```bash
# Desde el contenedor Docker
docker exec app sh -c "cd /var/www && php artisan migrate --path=modules/Core/database/migrations/2025_12_29_014765_create_core_langs_table.php --force"
docker exec app sh -c "cd /var/www && php artisan migrate --path=modules/Core/database/migrations/2025_12_29_014766_create_settings_table.php --force"
```

Luego insertar config Oracle:
```sql
INSERT INTO settings (`key`, `value`, `created_at`, `updated_at`) VALUES
('oracle_host', '192.168.253.8', NOW(), NOW()),
('oracle_port', '1521', NOW(), NOW()),
('oracle_database', 'GESTCENT', NOW(), NOW()),
('oracle_service_name', 'GESTCENT', NOW(), NOW()),
('oracle_username', 'lectura', NOW(), NOW()),
('oracle_password', 'alsernet', NOW(), NOW()),
('oracle_schema', 'DEVELOPER', NOW(), NOW()),
('oracle_charset', 'AL32UTF8', NOW(), NOW())
ON DUPLICATE KEY UPDATE value=VALUES(value);
```

## Webhooks — Reverb push

### Endpoint webhook

`POST /api/helpdeskErp/webhooks/orders-ready`

Middleware: solo `api` (sin `auth:sanctum`). Autenticación vía HMAC en el controller.

Headers requeridos:
- `X-Erp-Signature: hash_hmac('sha256', $timestamp . ':' . $rawBody, $secret)`
- `X-Erp-Timestamp: <epoch Unix>` (se rechaza si difiere más de ±5 min)
- `Content-Type: application/json`

Body JSON:
```json
{ "email": "user@example.com", "customer_id": 123 }
```

Secret en `.env`: `ERP_WEBHOOK_SECRET` (compartido con el manager).

### Lo que hace el webhook

1. Verifica firma HMAC y timestamp (anti-replay de 5 min)
2. Invalida el caché Redis del cliente (`forgetCache(email)`)
3. Emite evento `ErpOrdersReady` → Reverb → frontend

### Lo que el manager debe hacer

Cuando el background scan Oracle termina:

```php
$ts   = time();
$body = json_encode(['email' => $email, 'customer_id' => $customerId]);
$sig  = hash_hmac('sha256', $ts . ':' . $body, env('SYSTEM_WEBHOOK_SECRET'));

Http::withHeaders([
    'X-Erp-Signature' => $sig,
    'X-Erp-Timestamp' => (string) $ts,
    'Content-Type'    => 'application/json',
])->withBody($body, 'application/json')
  ->post(env('SYSTEM_WEBHOOK_URL') . '/api/helpdeskErp/webhooks/orders-ready');
```

Configurar en manager `.env`:
```
SYSTEM_WEBHOOK_URL=https://system.test
SYSTEM_WEBHOOK_SECRET=<mismo valor que ERP_WEBHOOK_SECRET en system>
```

### Canal Reverb broadcasting

- Channel name: `erp-orders-ready.{md5(strtolower(email))}`
- Event: `.erp.orders.ready` (dot prefix por `broadcastAs`)
- Payload: `{email_hash, customer_id, timestamp}`
- Canal público (no privado): el hash no es información sensible y simplifica auth en el cliente

### Frontend (tickets.js)

Cuando `loadErpContext` recibe `ordersLoading: true` y `meta.broadcastChannel`:

1. Frontend se suscribe al canal Echo (`window.Echo.channel(channelName)`)
2. Cuando llega el evento `.erp.orders.ready`, abandona el canal y hace refetch (caché ya invalidado)
3. Timeout de seguridad: 90s sin evento → fallback al polling (35s × max 3 intentos)
4. Sin `window.Echo` disponible → fallback al polling existente (sin cambio de comportamiento)

El canal llega en `resp.meta.broadcastChannel` de la respuesta del endpoint context.

## Permisos (Spatie Permission)

El seeder `HelpdeskErpPermissionsSeeder` crea 4 permisos y los asigna automáticamente a los roles `admin`, `super-admin`, `super-administrador`:

| Permiso | Descripción | Usado en |
|---------|-------------|----------|
| `helpdeskErp.view` | Ver contexto ERP, timeline, buscar clientes | endpoints context, timeline, search |
| `helpdeskErp.refresh` | Refrescar caché manualmente, pre-cachear lotes | endpoints refresh, cache/warm |
| `helpdeskErp.health.view` | Ver health check del manager | endpoint health |
| `helpdeskErp.orders.detail.view` | Ver detalle de pedidos | endpoint orders/detail |

Para ejecutar el seeder: `php artisan db:seed --class="Modules\\HelpdeskErp\\Database\\Seeders\\HelpdeskErpPermissionsSeeder"`

## Dependencias de módulos

El archivo `module.json` declara: `"requires": ["HelpdeskPrestashop"]`

Aunque la dependencia está listada, se resuelve de forma **defensiva** en `CustomerTimelineService`:
- Si `HelpdeskPrestashop` no está disponible o es un error al cargar → timeline solo retorna eventos ERP + Helpdesk (sin eventos PrestaShop)
- No causa fallo de la integración ERP

### Test manual del webhook

```bash
TS=$(date +%s)
SECRET=$(grep ERP_WEBHOOK_SECRET /Users/developerts/Herd/system/.env | cut -d= -f2)
BODY='{"email":"test@test.com","customer_id":123}'
SIG=$(printf '%s:%s' "$TS" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST https://system.test/api/helpdeskErp/webhooks/orders-ready \
  -H "Content-Type: application/json" \
  -H "X-Erp-Signature: $SIG" \
  -H "X-Erp-Timestamp: $TS" \
  -d "$BODY"
# Esperado: {"ok":true}
```

## Limitaciones conocidas

- Pedidos cacheados hasta 10 minutos en Redis (`cache_ttl` configurable) — cambios recientes en el ERP pueden no aparecer hasta que expire el caché. Se puede forzar refresh manual con `POST /cache/warm` o endpoint `POST .../context/refresh`.
- Endpoint `/balance` requiere GRANT SELECT adicionales en Oracle al usuario `lectura` — actualmente devuelve error 403 de Oracle. Los campos `credit_limit`, `balance_pending`, `balance_invoiced` aparecen como `null` en el popover hasta que el DBA conceda el acceso.

## Mantenimiento y operaciones

### Comando programado de pre-caché

El comando `helpdeskerp:warm-cache` se ejecuta automáticamente cada 30 minutos (vía Laravel Scheduler):

```bash
docker exec system php artisan helpdeskerp:warm-cache --limit=200
```

Qué hace:
1. Recoge hasta 200 emails únicos de clientes con tickets/conversaciones abiertos
2. Dispara `WarmErpCacheJob` en la queue `helpdesk-erp-warming` (chunks de 50 emails)
3. Pre-cachea contexto ERP para los siguientes 10 minutos

Queues requeridas:
- `helpdesk-erp` — para `RefreshErpContextJob` (background refresh cuando entra en stale_grace)
- `helpdesk-erp-warming` — para `WarmErpCacheJob` (pre-caché en lotes)

### Monitoreo

- **Pulse**: métricas en `helpdesk_erp_request` (duración, caché hit/miss, encontrado/no encontrado)
- **Logs**: warnings en caso de conexión fallida, firma HMAC inválida, timestamp expirado
- **Health check**: `GET /api/helpdeskErp/health` (requiere permiso `helpdeskErp.health.view`)
