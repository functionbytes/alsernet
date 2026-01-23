# Guía de Implementación de Laravel Reverb

## Índice
- [Introducción](#introducción)
- [¿Qué es Laravel Reverb?](#qué-es-laravel-reverb)
- [Instalación y Configuración](#instalación-y-configuración)
- [Eventos y Broadcasting](#eventos-y-broadcasting)
- [Configuración del Cliente (Frontend)](#configuración-del-cliente-frontend)
- [Testing y Debugging](#testing-y-debugging)
- [Producción con Supervisor](#producción-con-supervisor)
- [Casos de Uso Comunes](#casos-de-uso-comunes)

---

## Introducción

Laravel Reverb es el servidor WebSocket oficial de Laravel introducido en Laravel 11. Proporciona broadcasting en tiempo real ultrarrápido, escalable y mantenido por el equipo de Laravel.

**Ventajas sobre Pusher/Ably:**
- ✅ Completamente gratuito y open source
- ✅ Sin límites de conexiones o mensajes
- ✅ Hosting en tu propio servidor
- ✅ Integración nativa con Laravel
- ✅ Compatible con Laravel Echo
- ✅ Soporte para canales privados y presencia

---

## ¿Qué es Laravel Reverb?

Reverb es un servidor WebSocket de primera clase para aplicaciones Laravel que permite:
- **Broadcasting en tiempo real** - Notificaciones instantáneas
- **Canales privados** - Comunicación segura autenticada
- **Canales de presencia** - Ver quién está en línea
- **Broadcasting de eventos** - Eventos de Eloquent, notificaciones, custom events

**Estado actual en tu proyecto:**
```env
BROADCAST_CONNECTION=reverb
BROADCAST_DRIVER=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=manager.test
REVERB_PORT=8080
REVERB_SCHEME=https
```

---

## Instalación y Configuración

### 1. Instalación (Ya completada en tu proyecto)

```bash
# Laravel Reverb ya está instalado según composer.json
# Si necesitas reinstalar:
php artisan install:broadcasting

# Esto instala:
# - laravel/reverb
# - Publica archivos de configuración
# - Actualiza .env con configuración de Reverb
```

### 2. Configuración del Archivo `.env`

Tu configuración actual ya está lista. Para entenderla mejor:

```env
# ID único de la aplicación Reverb
REVERB_APP_ID=123456

# Clave pública (se comparte con el frontend)
REVERB_APP_KEY=local-key

# Clave secreta (solo backend)
REVERB_APP_SECRET=local-secret

# Host donde corre Reverb (mismo que tu app)
REVERB_HOST=manager.test

# Puerto del servidor WebSocket
REVERB_PORT=8080

# Protocolo (https en producción, http en desarrollo)
REVERB_SCHEME=https

# Host del servidor (0.0.0.0 escucha en todas las interfaces)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Variables para Vite (frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3. Configuración de Broadcasting

Tu archivo `config/broadcasting.php` ya tiene configurado Reverb:

```php
'default' => env('BROADCAST_CONNECTION', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
    ],
],
```

### 4. Configuración de Canales en `routes/channels.php`

Tu archivo actual ya contiene ejemplos. Ejemplo de canales de autenticación:

```php
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

// Canal público - cualquiera puede escuchar
Broadcast::channel('notifications', function ($user) {
    return true;
});

// Canal privado - solo usuarios autenticados
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de presencia - ver quién está conectado
Broadcast::channel('document.{documentId}', function ($user, $documentId) {
    // Verificar si el usuario tiene acceso al documento
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ];
});

// Canal privado para equipos
Broadcast::channel('team.{teamId}', function ($user, $teamId) {
    return $user->belongsToTeam($teamId);
});
```

---

## Eventos y Broadcasting

### 1. Crear un Evento Broadcastable

```bash
php artisan make:event DocumentStageAdvanced
```

Ejemplo de evento para notificaciones de documentos:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Document;

class DocumentStageAdvanced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Document $document,
        public string $previousStage,
        public string $newStage,
        public ?string $message = null
    ) {}

    /**
     * Canales donde se transmite el evento
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->document->assigned_to),
            new Channel('documents'),
        ];
    }

    /**
     * Nombre del evento (opcional, por defecto es el nombre de la clase)
     */
    public function broadcastAs(): string
    {
        return 'document.stage.advanced';
    }

    /**
     * Datos que se envían al cliente
     */
    public function broadcastWith(): array
    {
        return [
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'previous_stage' => $this->previousStage,
            'new_stage' => $this->newStage,
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determinar si el evento debe transmitirse
     */
    public function broadcastWhen(): bool
    {
        return $this->document->assigned_to !== null;
    }
}
```

### 2. Despachar el Evento

```php
use App\Events\DocumentStageAdvanced;

// Despachar el evento
event(new DocumentStageAdvanced(
    document: $document,
    previousStage: 'pending',
    newStage: 'approved',
    message: 'Documento aprobado por el supervisor'
));

// O usando el helper broadcast()
broadcast(new DocumentStageAdvanced(...))->toOthers();
```

### 3. Broadcasting de Notificaciones

Tus notificaciones existentes pueden usar broadcasting automáticamente:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentStageAdvanced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $document,
        public $previousStage,
        public $newStage
    ) {}

    /**
     * Canales de notificación
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Datos para broadcasting
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'previous_stage' => $this->previousStage,
            'new_stage' => $this->newStage,
            'message' => "Documento {$this->document->code} avanzó a {$this->newStage}",
        ]);
    }

    /**
     * Datos para la base de datos
     */
    public function toArray($notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'document_code' => $this->document->code,
            'stage_change' => [
                'from' => $this->previousStage,
                'to' => $this->newStage,
            ],
        ];
    }
}
```

---

## Configuración del Cliente (Frontend)

### 1. Instalar Laravel Echo y Pusher JS

```bash
npm install --save-dev laravel-echo pusher-js
```

### 2. Configurar Laravel Echo en `resources/js/bootstrap.js`

Tu archivo actual ya tiene configuración básica. Aquí está la configuración completa para Reverb:

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

    // Autenticación para canales privados/presencia
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
    },

    // Opciones adicionales
    authEndpoint: '/broadcasting/auth',
    disableStats: true,
});

// Listeners globales para debugging
if (import.meta.env.DEV) {
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Reverb connected');
    });

    window.Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('❌ Reverb error:', err);
    });
}
```

### 3. Escuchar Eventos en el Frontend

#### Canal Público

```javascript
// Escuchar en un canal público
Echo.channel('documents')
    .listen('DocumentStageAdvanced', (e) => {
        console.log('Document updated:', e);

        // Actualizar UI
        showNotification({
            title: 'Documento actualizado',
            message: e.message,
            type: 'info'
        });

        // Actualizar tabla o lista de documentos
        refreshDocumentsList();
    });
```

#### Canal Privado

```javascript
// Escuchar en un canal privado del usuario
Echo.private(`user.${userId}`)
    .listen('DocumentStageAdvanced', (e) => {
        console.log('Personal notification:', e);

        // Mostrar notificación
        showNotification({
            title: 'Nuevo documento asignado',
            message: `Documento ${e.document_code} requiere tu atención`,
            type: 'warning'
        });
    });
```

#### Notificaciones de Usuario

```javascript
// Escuchar todas las notificaciones del usuario autenticado
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        console.log('Notification received:', notification);

        // Incrementar contador de notificaciones
        updateNotificationBadge(+1);

        // Agregar a la lista de notificaciones
        addNotificationToList(notification);
    });
```

#### Canal de Presencia

```javascript
// Canal de presencia - ver quién está editando un documento
Echo.join(`document.${documentId}`)
    .here((users) => {
        // Usuarios que ya están en el canal
        console.log('Users currently viewing:', users);
        updateUsersList(users);
    })
    .joining((user) => {
        // Un nuevo usuario se unió
        console.log('User joined:', user.name);
        addUserToList(user);
    })
    .leaving((user) => {
        // Un usuario se fue
        console.log('User left:', user.name);
        removeUserFromList(user);
    })
    .listen('DocumentUpdated', (e) => {
        // Evento en el canal de presencia
        console.log('Document updated by:', e.user);
    });
```

### 4. Ejemplo Completo de Componente de Notificaciones

```javascript
// resources/js/components/notifications.js

export class NotificationManager {
    constructor(userId) {
        this.userId = userId;
        this.unreadCount = 0;
        this.notifications = [];

        this.init();
    }

    init() {
        // Escuchar notificaciones en tiempo real
        Echo.private(`App.Models.User.${this.userId}`)
            .notification((notification) => {
                this.handleNewNotification(notification);
            });

        // Escuchar eventos personalizados
        Echo.private(`user.${this.userId}`)
            .listen('.document.stage.advanced', (e) => {
                this.handleDocumentUpdate(e);
            })
            .listen('.document.assigned', (e) => {
                this.handleDocumentAssignment(e);
            });
    }

    handleNewNotification(notification) {
        // Agregar a la lista
        this.notifications.unshift(notification);
        this.unreadCount++;

        // Actualizar badge
        this.updateBadge();

        // Mostrar toast
        this.showToast({
            title: notification.title || 'Nueva notificación',
            message: notification.message,
            type: notification.type || 'info'
        });

        // Reproducir sonido (opcional)
        this.playNotificationSound();
    }

    handleDocumentUpdate(event) {
        console.log('Document updated:', event);

        // Actualizar UI específica de documentos
        if (window.documentsTable) {
            window.documentsTable.reload();
        }
    }

    handleDocumentAssignment(event) {
        this.showToast({
            title: 'Nuevo documento asignado',
            message: `Te han asignado el documento ${event.document_code}`,
            type: 'warning',
            action: {
                label: 'Ver documento',
                url: `/documents/${event.document_id}`
            }
        });
    }

    updateBadge() {
        const badge = document.querySelector('#notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.classList.toggle('d-none', this.unreadCount === 0);
        }
    }

    showToast(options) {
        // Usando tu sistema de notificaciones existente
        if (window.toastr) {
            toastr[options.type || 'info'](options.message, options.title);
        }
    }

    playNotificationSound() {
        const audio = new Audio('/sounds/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(err => console.log('Audio blocked:', err));
    }

    markAsRead(notificationId) {
        axios.post(`/notifications/${notificationId}/read`)
            .then(() => {
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
            });
    }

    markAllAsRead() {
        axios.post('/notifications/mark-all-read')
            .then(() => {
                this.unreadCount = 0;
                this.updateBadge();
            });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const userId = document.querySelector('meta[name="user-id"]')?.content;

    if (userId) {
        window.notificationManager = new NotificationManager(userId);
    }
});
```

---

## Testing y Debugging

### 1. Iniciar el Servidor Reverb

```bash
# Desarrollo - con output verbose
php artisan reverb:start --debug

# Producción - gestionado por Supervisor (ver siguiente sección)
php artisan reverb:start
```

### 2. Verificar Configuración

```bash
# Verificar que broadcasting está configurado
php artisan tinker

>>> config('broadcasting.default')
=> "reverb"

>>> config('broadcasting.connections.reverb')
=> [
     "driver" => "reverb",
     "key" => "local-key",
     ...
   ]

# Probar conexión a Reverb
>>> event(new App\Events\TestEvent());
```

### 3. Debugging en el Frontend

```javascript
// Habilitar debugging de Pusher
window.Pusher.logToConsole = true;

// Verificar conexión
console.log(window.Echo.connector.pusher.connection.state);
// Debe mostrar: "connected"

// Listar canales activos
console.log(window.Echo.connector.pusher.allChannels());
```

### 4. Comando de Testing

Crear un evento de prueba:

```bash
php artisan make:event TestBroadcast
```

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TestBroadcast implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public string $message) {}

    public function broadcastOn(): Channel
    {
        return new Channel('test');
    }

    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
```

Probar desde Tinker:

```bash
php artisan tinker

>>> event(new App\Events\TestBroadcast('Hello from Reverb!'));
```

En el frontend:

```javascript
Echo.channel('test')
    .listen('TestBroadcast', (e) => {
        console.log('✅ Test message received:', e.message);
        alert('Reverb funciona! Mensaje: ' + e.message);
    });
```

### 5. Logs de Reverb

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Filtrar solo logs de broadcasting
tail -f storage/logs/laravel.log | grep Broadcasting
```

---

## Producción con Supervisor

Para mantener Reverb corriendo en producción, usa Supervisor. Ver archivo de configuración en la siguiente sección.

**Archivo de configuración**: `/etc/supervisor/conf.d/reverb.conf`

```ini
[program:reverb]
process_name=%(program_name)s
command=php /ruta/a/tu/proyecto/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/reverb.log
stopwaitsecs=3600
```

**Comandos**:

```bash
# Recargar configuración de Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar Reverb
sudo supervisorctl start reverb

# Ver estado
sudo supervisorctl status reverb

# Ver logs
sudo supervisorctl tail -f reverb
```

---

## Casos de Uso Comunes

### 1. Sistema de Notificaciones en Tiempo Real

**Backend:**
```php
// Cuando un documento avanza de etapa
User::find($assignedUserId)->notify(
    new DocumentStageAdvanced($document, $oldStage, $newStage)
);
```

**Frontend:**
```javascript
Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
        // Mostrar notificación toast
        toastr.info(notification.message);

        // Incrementar badge
        updateNotificationCount(+1);

        // Agregar a dropdown de notificaciones
        prependNotification(notification);
    });
```

### 2. Chat en Tiempo Real

**Backend:**
```php
broadcast(new MessageSent($user, $message))->toOthers();
```

**Frontend:**
```javascript
Echo.private('chat')
    .listen('MessageSent', (e) => {
        appendMessage(e.user, e.message);
    })
    .listenForWhisper('typing', (e) => {
        showTypingIndicator(e.user);
    });

// Enviar evento de "escribiendo"
Echo.private('chat').whisper('typing', {
    user: currentUser
});
```

### 3. Actualización de Dashboards en Tiempo Real

**Backend:**
```php
// Cuando cambian métricas importantes
broadcast(new MetricsUpdated($metrics));
```

**Frontend:**
```javascript
Echo.channel('dashboard')
    .listen('MetricsUpdated', (e) => {
        updateChart(e.metrics);
        updateCounters(e.metrics);
    });
```

### 4. Edición Colaborativa de Documentos

**Backend:**
```php
// Evento de presencia
Broadcast::channel('document.{id}', function ($user, $documentId) {
    if ($user->canEdit($documentId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
});

// Broadcasting de cambios
broadcast(new DocumentUpdated($document, $changes))->toOthers();
```

**Frontend:**
```javascript
Echo.join(`document.${documentId}`)
    .here((users) => {
        // Mostrar usuarios actuales
        showActiveUsers(users);
    })
    .joining((user) => {
        // Usuario se unió
        showToast(`${user.name} está viendo el documento`);
        addActiveUser(user);
    })
    .leaving((user) => {
        // Usuario se fue
        removeActiveUser(user);
    })
    .listen('DocumentUpdated', (e) => {
        // Aplicar cambios en tiempo real
        applyChanges(e.changes);
    });
```

### 5. Notificaciones de Sistema

**Backend:**
```php
// Broadcasting a todos los usuarios conectados
broadcast(new SystemMaintenanceScheduled($scheduledTime));
```

**Frontend:**
```javascript
Echo.channel('system')
    .listen('SystemMaintenanceScheduled', (e) => {
        showSystemAlert({
            type: 'warning',
            message: `Mantenimiento programado: ${e.scheduled_time}`,
            persistent: true
        });
    });
```

---

## Recursos Adicionales

- **Documentación oficial**: https://reverb.laravel.com
- **Laravel Broadcasting**: https://laravel.com/docs/12.x/broadcasting
- **Laravel Echo**: https://github.com/laravel/echo
- **Pusher Protocol**: https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol

---

## Troubleshooting

### Problema: No se conecta a Reverb

**Solución:**
1. Verificar que Reverb esté corriendo: `sudo supervisorctl status reverb`
2. Verificar puerto: `netstat -tuln | grep 8080`
3. Verificar logs: `tail -f storage/logs/reverb.log`
4. Verificar firewall: `sudo ufw status`

### Problema: Eventos no se reciben en el frontend

**Solución:**
1. Verificar que el evento implementa `ShouldBroadcast`
2. Verificar que el canal está correctamente autorizado en `routes/channels.php`
3. Verificar CSRF token en headers de autenticación
4. Habilitar `window.Pusher.logToConsole = true` para debugging

### Problema: Error de autenticación en canales privados

**Solución:**
1. Verificar que el middleware `auth:sanctum` está activo
2. Verificar endpoint `/broadcasting/auth` en `routes/web.php`
3. Verificar que el CSRF token es válido
4. Verificar que el usuario tiene permisos para el canal

---

**Última actualización**: 2025-01-11
**Autor**: Sistema de documentación Alsernet
