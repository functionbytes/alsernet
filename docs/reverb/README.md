# Laravel Reverb Module

Módulo de comunicación en tiempo real para Alsernet que integra **Laravel Reverb**, un servidor WebSocket nativo para Laravel que permite broadcast de eventos y comunicación bidireccional en tiempo real.

## Características principales

- ✅ **WebSocket servidor** - Servidor WebSocket nativo sin dependencias externas
- ✅ **Event Broadcasting** - Sistema de broadcast de eventos Laravel integrado
- ✅ **Canales privados** - Autenticación de canales para datos sensibles
- ✅ **Presence Channels** - Rastreo de presencia de usuarios en tiempo real
- ✅ **Monitoreo** - Dashboard de estadísticas y conexiones
- ✅ **Control de permisos** - Integración con Spatie Permission

## Instalación y configuración

### 1. Registrar el módulo

El módulo debe estar registrado en `config/modules.php`:

```php
'Reverb' => [
    'enabled' => true,
    'alias' => 'reverb',
]
```

### 2. Ejecutar seeders

```bash
php artisan db:seed --class="Modules\\Reverb\\Database\\Seeders\\CreateReverbPermissionsSeeder"
```

### 3. Configurar variables de entorno

Añade las siguientes variables a tu archivo `.env`:

```ini
# Reverb Broadcasting Configuration
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Reverb Server App ID
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
```

### 4. Iniciar el servidor Reverb

```bash
php artisan reverb:start
```

El servidor escuchará por defecto en `0.0.0.0:8080`.

## Uso

### Configurar Broadcasting en tu aplicación

En `config/broadcasting.php`, asegúrate que el broadcaster está configurado:

```php
'default' => env('BROADCAST_DRIVER', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT'),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'app_id' => env('REVERB_APP_ID'),
        'app_key' => env('REVERB_APP_KEY'),
    ],
]
```

### Crear un evento de broadcast

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
```

### Emitir el evento

```php
// En tu controlador o servicio
use App\Events\OrderCreated;

OrderCreated::dispatch($order);
```

### Escuchar eventos en JavaScript

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Escuchar eventos públicos
Echo.channel('orders').listen('order.created', (event) => {
    console.log('Nuevo pedido:', event.order);
});

// Escuchar eventos privados
Echo.private('user.' + userId).listen('message', (event) => {
    console.log('Mensaje recibido:', event.message);
});

// Escuchar cambios de presencia
Echo.join('chat-room').here((users) => {
    console.log('Usuarios presentes:', users);
}).joining((user) => {
    console.log('Usuario conectando:', user.name);
}).leaving((user) => {
    console.log('Usuario desconectando:', user.name);
});
```

## Tipos de canales

### Canales públicos

Accesibles a cualquiera que conozca el nombre del canal:

```php
public function broadcastOn(): array
{
    return [new Channel('public-chat')];
}
```

### Canales privados

Requieren autenticación del usuario:

```php
public function broadcastOn(): array
{
    return [new PrivateChannel('private-user.' . $this->user->id)];
}
```

### Presence channels

Rastrean la presencia de usuarios en un canal:

```php
public function broadcastOn(): array
{
    return [new PresenceChannel('presence-team.' . $this->team->id)];
}
```

## Rutas disponibles

### Configuración

- `GET /settings/reverb` - Ver configuración actual
- `GET /settings/reverb/edit` - Formulario de edición
- `PUT /settings/reverb/update` - Actualizar configuración
- `POST /settings/reverb/check-connection` - Probar conexión

### Monitoreo

- `GET /settings/reverb/monitoring` - Dashboard de monitoreo
- `GET /settings/reverb/monitoring/stats` - Estadísticas en JSON
- `GET /settings/reverb/monitoring/channels` - Lista de canales activos
- `GET /settings/reverb/monitoring/connections` - Conexiones activas

### API

- `GET /api/reverb/channels` - Listar canales
- `GET /api/reverb/channels/{channel}` - Detalles del canal
- `POST /api/reverb/channels/{channel}/broadcast` - Emitir evento
- `POST /api/reverb/broadcast` - Broadcast a múltiples canales
- `GET /api/reverb/presence/{channel}` - Info de presencia

## Permisos requeridos

El módulo utiliza los siguientes permisos (Spatie Permission):

- `reverb.settings.view` - Ver configuración
- `reverb.settings.update` - Actualizar configuración
- `reverb.channels.view` - Ver canales
- `reverb.channels.broadcast` - Emitir a canales
- `reverb.events.broadcast` - Broadcast de eventos
- `reverb.presence.view` - Ver presencia
- `reverb.monitoring.view` - Ver monitoreo
- `reverb.monitoring.debug` - Depuración avanzada

### Asignaciones por rol

**Super Admin**: Todos los permisos (`reverb.*`)

**Manager**:
- `reverb.settings.view`
- `reverb.channels.view`
- `reverb.events.broadcast`
- `reverb.presence.view`
- `reverb.monitoring.view`

## Configuración avanzada

### Producción con Nginx

Para producción con HTTPS, configura un proxy inverso:

```nginx
location /ws {
    proxy_pass http://localhost:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### Múltiples procesos Reverb

Para mayor escalabilidad, ejecuta múltiples procesos:

```bash
php artisan reverb:start --port=8080 &
php artisan reverb:start --port=8081 &
php artisan reverb:start --port=8082 &
```

Luego configura un load balancer (HAProxy, Nginx) para distribuir conexiones.

### Caché de canales

Los canales activos se pueden cachear para mejor rendimiento:

```php
// En tu servicio de canales
Cache::put('active-channels', $channels, now()->addMinutes(5));
```

## Troubleshooting

### Conexión rechazada

Verifica que:
1. El servidor Reverb está corriendo: `php artisan reverb:start`
2. El puerto 8080 no está bloqueado por firewall
3. Las variables de entorno están configuradas correctamente

### Eventos no se reciben

1. Asegúrate de que `BROADCAST_DRIVER=reverb` en `.env`
2. Verifica que el evento implementa `ShouldBroadcast`
3. Revisa la consola del navegador para errores de conexión
4. Comprueba permisos: `reverb.events.broadcast`

### Presencia no funciona

1. Verifica que usas `PresenceChannel` en lugar de `Channel`
2. Los usuarios deben estar autenticados
3. La política de canal debe permitir la presencia

## Recursos adicionales

- [Documentación oficial Laravel Reverb](https://laravel.com/docs/reverb)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Echo Documentation](https://github.com/laravel/echo)

## Soporte

Para problemas o sugerencias, contáctate con el equipo de desarrollo de Alsernet.
