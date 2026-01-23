# API Reference - Laravel Reverb Module

Documentación completa de los endpoints API del módulo Reverb.

## Base URL

```
https://api.tu-dominio.com/api/reverb
```

## Autenticación

Todos los endpoints requieren token Bearer:

```
Authorization: Bearer YOUR_AUTH_TOKEN
```

## Endpoints

### Canales

#### Listar todos los canales

```http
GET /channels
```

**Permiso requerido:** `reverb.channels.view`

**Response:**

```json
{
  "public": [
    {
      "name": "orders",
      "type": "public",
      "subscribers": 5,
      "created_at": "2024-01-03T10:00:00Z"
    }
  ],
  "private": [
    {
      "name": "private-user.1",
      "type": "private",
      "subscribers": 1,
      "created_at": "2024-01-03T10:01:00Z"
    }
  ],
  "presence": [
    {
      "name": "presence-team.1",
      "type": "presence",
      "subscribers": 3,
      "created_at": "2024-01-03T10:02:00Z"
    }
  ]
}
```

#### Obtener detalles de un canal

```http
GET /channels/{channel}
```

**Parámetros:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| channel | string | Nombre del canal |

**Response:**

```json
{
  "name": "orders",
  "type": "public",
  "subscribers": 5,
  "created_at": "2024-01-03T10:00:00Z",
  "messages_count": 1523,
  "last_message_at": "2024-01-03T11:00:00Z"
}
```

#### Emitir evento a un canal

```http
POST /channels/{channel}/broadcast
```

**Permiso requerido:** `reverb.channels.broadcast`

**Body:**

```json
{
  "event": "order.created",
  "data": {
    "id": 123,
    "total": 99.99,
    "status": "completed"
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Event broadcasted to channel: orders",
  "channel": "orders",
  "event": "order.created",
  "subscribers_notified": 5
}
```

#### Obtener información de presencia

```http
GET /presence/{channel}
```

**Permiso requerido:** `reverb.presence.view`

**Response:**

```json
{
  "channel": "presence-team.1",
  "count": 3,
  "here": [
    {
      "id": 1,
      "name": "Juan",
      "email": "juan@example.com"
    },
    {
      "id": 2,
      "name": "María",
      "email": "maria@example.com"
    }
  ],
  "joining": [
    {
      "id": 3,
      "name": "Pedro",
      "email": "pedro@example.com"
    }
  ],
  "leaving": []
}
```

### Eventos

#### Listar eventos recientes

```http
GET /broadcast
```

**Permiso requerido:** `reverb.events.broadcast`

**Query Parameters:**

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| limit | int | 50 | Número máximo de eventos |
| filter | string | null | Filtrar por nombre de evento |
| channels | string | null | Canales separados por coma |
| since | string | null | Eventos desde ISO timestamp |

**Ejemplos:**

```
GET /broadcast?limit=100
GET /broadcast?filter=order.*
GET /broadcast?channels=orders,products
GET /broadcast?since=2024-01-03T10:00:00Z
```

**Response:**

```json
{
  "events": [
    {
      "id": "event-123",
      "event": "order.created",
      "channels": ["orders"],
      "subscribers": 5,
      "timestamp": "2024-01-03T11:00:00Z",
      "data": {
        "id": 123,
        "total": 99.99
      }
    }
  ],
  "total": 1,
  "limit": 50
}
```

#### Emitir evento a múltiples canales

```http
POST /broadcast
```

**Permiso requerido:** `reverb.events.broadcast`

**Body:**

```json
{
  "event": "inventory.updated",
  "channels": ["inventory", "private-warehouse.1"],
  "data": {
    "product_id": 456,
    "new_stock": 100,
    "previous_stock": 95
  }
}
```

**Response:**

```json
{
  "success": true,
  "message": "Event broadcasted successfully",
  "event": "inventory.updated",
  "channels": ["inventory", "private-warehouse.1"],
  "total_subscribers": 12
}
```

### Configuración

#### Ver configuración actual

```http
GET /settings
```

**Permiso requerido:** `reverb.settings.view`

**Response:**

```json
{
  "broadcaster": "reverb",
  "host": "ws.tu-dominio.com",
  "port": 443,
  "scheme": "wss",
  "server_host": "127.0.0.1",
  "server_port": 8080,
  "app_id": "reverb",
  "features": {
    "presence_tracking": true,
    "event_broadcasting": true,
    "channel_authentication": true
  }
}
```

#### Actualizar configuración

```http
PUT /settings
```

**Permiso requerido:** `reverb.settings.update`

**Body:**

```json
{
  "host": "ws.tu-dominio.com",
  "port": 443,
  "scheme": "wss",
  "server_host": "0.0.0.0",
  "server_port": 8080
}
```

**Response:**

```json
{
  "success": true,
  "message": "Configuration updated successfully"
}
```

#### Probar conexión

```http
POST /settings/test-connection
```

**Permiso requerido:** `reverb.settings.update`

**Response:**

```json
{
  "success": true,
  "message": "Successfully connected to Reverb server",
  "response_time_ms": 45
}
```

## Monitoreo

### Obtener estadísticas

```http
GET /monitoring/stats
```

**Permiso requerido:** `reverb.monitoring.view`

**Response:**

```json
{
  "server_status": true,
  "uptime": "2 días, 3 horas",
  "connections": 47,
  "memory_usage": {
    "current": 134217728,
    "peak": 201326592,
    "limit": 536870912
  },
  "cpu_usage": 2.5,
  "messages_processed": 15234,
  "timestamp": "2024-01-03T11:30:00Z"
}
```

### Obtener canales activos

```http
GET /monitoring/channels
```

**Permiso requerido:** `reverb.monitoring.view`

**Response:**

```json
{
  "public_channels": [
    {
      "name": "orders",
      "subscribers": 5
    },
    {
      "name": "notifications",
      "subscribers": 12
    }
  ],
  "private_channels": [
    {
      "name": "private-user.1",
      "subscribers": 1
    }
  ],
  "presence_channels": [
    {
      "name": "presence-team.1",
      "subscribers": 3
    }
  ],
  "total": 6
}
```

### Obtener conexiones activas

```http
GET /monitoring/connections
```

**Permiso requerido:** `reverb.monitoring.view`

**Response:**

```json
{
  "total": 47,
  "by_channel": {
    "orders": 5,
    "notifications": 12,
    "private-user.1": 1,
    "presence-team.1": 3
  },
  "by_user": {
    "1": 2,
    "2": 3,
    "3": 5
  }
}
```

## Códigos de error

| Código | Descripción |
|--------|-------------|
| 200 | OK - Solicitud exitosa |
| 400 | Bad Request - Parámetros inválidos |
| 401 | Unauthorized - Falta autenticación |
| 403 | Forbidden - Permisos insuficientes |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Validación fallida |
| 429 | Too Many Requests - Rate limit excedido |
| 500 | Internal Server Error - Error del servidor |
| 503 | Service Unavailable - Servidor no disponible |

## Rate limiting

Los endpoints están sujetos a rate limiting:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1704282600
```

Límites por endpoint:

| Endpoint | Límite |
|----------|--------|
| POST /broadcast | 10 req/min |
| POST /channels/*/broadcast | 20 req/min |
| GET /monitoring/* | 60 req/min |
| PUT /settings | 5 req/min |

## Webhooks

Suscríbete a eventos del servidor:

```php
// Registrar webhook
POST /webhooks/subscribe

{
    "event": "connection.established",
    "url": "https://tu-app.com/webhooks/reverb",
    "secret": "tu-secret-key"
}
```

**Eventos disponibles:**

- `connection.established` - Cuando se establece una conexión
- `connection.closed` - Cuando se cierra una conexión
- `channel.joined` - Cuando un usuario se une a un canal
- `channel.left` - Cuando un usuario sale de un canal
- `presence.joining` - Cuando un usuario entra a presence channel
- `presence.leaving` - Cuando un usuario sale de presence channel
- `event.broadcasted` - Cuando se emite un evento

## Ejemplos de uso

### Con cURL

```bash
# Listar canales
curl -H "Authorization: Bearer TOKEN" \
  https://api.tu-dominio.com/api/reverb/channels

# Emitir evento
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"event":"order.created","channels":["orders"],"data":{"id":123}}' \
  https://api.tu-dominio.com/api/reverb/broadcast
```

### Con axios (JavaScript)

```javascript
const api = axios.create({
  baseURL: 'https://api.tu-dominio.com/api/reverb',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Listar canales
await api.get('/channels');

// Emitir evento
await api.post('/broadcast', {
  event: 'order.created',
  channels: ['orders'],
  data: { id: 123 }
});
```

### Con PHP

```php
$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://api.tu-dominio.com/api/reverb',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ]
]);

// Emitir evento
$response = $client->post('/broadcast', [
    'json' => [
        'event' => 'order.created',
        'channels' => ['orders'],
        'data' => ['id' => 123]
    ]
]);

echo $response->getStatusCode(); // 200
echo $response->getBody();
```
