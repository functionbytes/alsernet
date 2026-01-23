# Módulo Reverb - Laravel WebSocket en tiempo real

Módulo de Alsernet para integrar **Laravel Reverb**, proporcionando comunicación WebSocket nativa y broadcasting de eventos en tiempo real.

## Descripción

Este módulo encapsula toda la funcionalidad de Laravel Reverb, permitiendo:

- ✅ Servidor WebSocket nativo sin dependencias externas
- ✅ Broadcasting de eventos en tiempo real
- ✅ Canales públicos, privados y de presencia
- ✅ Autenticación y control de permisos integrados
- ✅ Dashboard de monitoreo y estadísticas
- ✅ API REST para gestionar broadcasting
- ✅ Configuración administrable desde panel

## Estructura del módulo

```
modules/Reverb/
├── app/
│   ├── Channels/              # Autenticación de canales
│   ├── Events/                # Eventos broadcastables
│   ├── Http/
│   │   └── Controllers/       # Controladores (Settings, Monitoring, API)
│   └── Providers/             # Service Providers
├── config/                    # Configuración del módulo
├── database/
│   └── seeders/              # Seeders de permisos y configuración
├── resources/
│   └── views/                # Vistas Blade (Settings, Monitoring)
├── routes/
│   ├── api.php               # Rutas API
│   └── web.php               # Rutas web (admin)
├── module.json               # Definición del módulo
└── README.md                 # Este archivo
```

## Instalación rápida

### 1. Verificar que el módulo está registrado

El módulo debe estar registrado en `modules.php`:

```php
'Reverb' => [
    'enabled' => true,
]
```

### 2. Ejecutar seeders

```bash
php artisan db:seed --class="Modules\\Reverb\\Database\\Seeders\\CreateReverbPermissionsSeeder"
```

Esto crea los permisos necesarios en tu base de datos.

### 3. Configurar variables de entorno

Añade a tu `.env`:

```ini
BROADCAST_DRIVER=reverb
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

### 4. Iniciar el servidor Reverb

```bash
php artisan reverb:start
```

El servidor escuchará en `0.0.0.0:8080` Por defecto.

## Uso básico

### Crear un evento broadcastable

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated implements ShouldBroadcast
{
    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }
}
```

### Disparar el evento

```php
OrderCreated::dispatch($order);
```

### Escuchar en JavaScript

```javascript
Echo.channel('orders')
    .listen('OrderCreated', (event) => {
        console.log('Nueva orden:', event.order);
    });
```

## Rutas disponibles

### Panel administrativo

- `GET /settings/reverb` - Ver configuración
- `GET /settings/reverb/edit` - Editar configuración
- `PUT /settings/reverb/update` - Guardar cambios
- `GET /settings/reverb/monitoring` - Dashboard de monitoreo

### API

- `GET /api/reverb/channels` - Listar canales activos
- `POST /api/reverb/broadcast` - Emitir eventos
- `GET /api/reverb/monitoring/stats` - Estadísticas del servidor

## Permisos

El módulo usa los siguientes permisos (Spatie Permission):

- `reverb.settings.view` - Ver configuración
- `reverb.settings.update` - Editar configuración
- `reverb.channels.view` - Ver canales
- `reverb.channels.broadcast` - Emitir a canales
- `reverb.events.broadcast` - Broadcast de eventos
- `reverb.presence.view` - Ver presencia
- `reverb.monitoring.view` - Ver monitoreo

### Asignación por rol

- **super-admin**: Todos los permisos
- **manager**: Ver, broadcast limitado, monitoreo

## Archivos clave

| Archivo | Propósito |
|---------|-----------|
| `module.json` | Metadatos del módulo |
| `app/Providers/ReverServiceProvider.php` | Proveedor principal |
| `config/config.php` | Configuración del módulo |
| `routes/api.php` | Endpoints API |
| `routes/web.php` | Rutas administrativas |
| `database/seeders/CreateReverbPermissionsSeeder.php` | Seeders de permisos |

## Configuración

Edita `config/config.php` para personalizar:

```php
return [
    'server' => [
        'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
        'port' => env('REVERB_SERVER_PORT', 8080),
    ],
    'broadcasting' => [
        'host' => env('REVERB_HOST', 'localhost'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
    // ... más configuración
];
```

## Monitoreo

Accede a `/settings/reverb/monitoring` para ver:

- Estado del servidor
- Conexiones activas
- Canales abiertos
- Uso de memoria

## Documentación detallada

Consulta los archivos de documentación:

- `docs/reverb/README.md` - Guía completa
- `docs/reverb/EXAMPLES.md` - Ejemplos de código
- `docs/reverb/PRODUCTION.md` - Despliegue en producción
- `docs/reverb/API.md` - Referencia API completa

## Troubleshooting

### El servidor Reverb no inicia

```bash
# Verifica que el puerto no está en uso
lsof -i :8080

# Intenta con un puerto diferente
php artisan reverb:start --port=9000
```

### No recibo eventos

1. Verifica `BROADCAST_DRIVER=reverb` en `.env`
2. Comprueba que el evento implementa `ShouldBroadcast`
3. Verifica permisos: `reverb.events.broadcast`

### Conexión WebSocket rechazada

1. Firewall: Abre puerto 8080
2. Proxy: Configura WebSocket en Nginx/Apache
3. SSL: Usa `wss://` para conexiones seguras

## Soporte

Para reportar problemas o sugerencias, contacta con el equipo de desarrollo de Alsernet.

## Licencia

Parte de Alsernet. Uso interno exclusivamente.
