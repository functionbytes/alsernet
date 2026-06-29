# Sistema de Notificaciones — Guía de Implementación

> Módulo: `Modules\Notification`  
> Última revisión: 2026-05-18

---

## Índice

1. [Arquitectura](#1-arquitectura)
2. [Prerequisitos](#2-prerequisitos)
3. [Registrar el tipo de notificación](#3-registrar-el-tipo-de-notificación)
4. [Crear la clase Notification](#4-crear-la-clase-notification)
5. [Dispatchar notificaciones](#5-dispatchar-notificaciones)
6. [Referencia de campos `toArray()`](#6-referencia-de-campos-toarray)
7. [Colores e iconos disponibles](#7-colores-e-iconos-disponibles)
8. [Widget del header](#8-widget-del-header)
9. [Publicar assets del módulo](#9-publicar-assets-del-módulo)
10. [Ejemplos reales](#10-ejemplos-reales)
11. [Checklist de implementación](#11-checklist-de-implementación)

---

## 1. Arquitectura

```
Módulo X dispara evento
        │
        ▼
Notification class (toArray → title, message, icon, color, action_url)
        │
        ├── database  → tabla notifications  ──► API /api/notifications
        │                                              │
        │                                              ▼
        │                                       NotificationResource
        │                                       (aplana datos para JS)
        │                                              │
        └── broadcast → Laravel Echo/Reverb ──────────┤
                                                       ▼
                                               notifications.js
                                               (header widget)
```

**Tabla:** `notifications` (Laravel nativa — `Illuminate\Notifications\DatabaseNotification`)  
**Canales soportados:** `database`, `broadcast`, `mail`  
**Broadcasting:** canal privado `users.{id}` escucha evento `.notification.new`

---

## 2. Prerequisitos

### 2.1 Trait en el modelo User

El modelo `App\Models\User` ya tiene ambos traits. **No es necesario modificarlo.**

```php
use HasNotificationSystem, Notifiable {
    HasNotificationSystem::routeNotificationFor insteadof Notifiable;
}
```

`HasNotificationSystem` provee:
- `canReceiveNotification(channel, type)` — respeta preferencias del usuario
- `unreadNotificationsCount()` — optimizado (evita N+1)
- `markAllNotificationsAsRead()`
- `getActivePushTokens()`

### 2.2 Ruta `notifications` tabla

Las migraciones de la tabla `notifications` ya corren con el módulo. No crear migraciones adicionales.

---

## 3. Registrar el tipo de notificación

En el `ServiceProvider` del módulo agregar el registro en `boot()`:

```php
use Modules\Notification\Services\NotificationTypeRegistry;

public function boot(): void
{
    // ... resto del boot ...
    $this->registerNotificationTypes();
}

protected function registerNotificationTypes(): void
{
    $this->app->booted(function () {
        $registry = $this->app->make(NotificationTypeRegistry::class);

        // Patrón: '{alias}.{evento}'
        $registry->register(
            key: 'reviews.new_review',
            label: 'Nueva reseña',
            description: 'Se dispara cuando se recibe una reseña nueva',
            defaultChannels: ['mail', 'database']
        );

        $registry->register(
            key: 'reviews.negative_review',
            label: 'Reseña negativa',
            description: 'Reseñas con calificación baja (1-3 estrellas)',
            defaultChannels: ['database']
        );
    });
}
```

**Convención de `key`:** `'{alias}.{evento}'` — ej: `attention.status_changed`, `blog.new_comment`

El key se usa en `via()` para consultar las preferencias del usuario y en la pantalla de configuración de notificaciones.

---

## 4. Crear la clase Notification

```php
<?php

namespace Modules\{ModuleName}\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}{Event}Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly {Entity} $entity
    ) {}

    public function via(mixed $notifiable): array
    {
        // Canales filtrados por preferencias del usuario
        $channels = array_values(array_filter(
            ['database', 'broadcast'],
            fn ($ch) => $notifiable->canReceiveNotification($ch, '{alias}.{evento}')
        ));

        return $channels ?: ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            // Obligatorios para el widget
            'title'      => 'Título corto de la notificación',
            'message'    => "El registro {$this->entity->name} fue actualizado",
            'action_url' => route('{alias}.show', $this->entity),

            // Opcionales (tienen defaults en el widget)
            'type'       => '{alias}_{evento}',        // identifica el tipo en BD
            'icon'       => 'fas fa-bell',              // Font Awesome 6
            'color'      => 'primary',                  // ver sección 7
            'entity_id'  => $this->entity->id,

            // Datos extra del módulo (no afectan el widget, pero quedan en BD)
            'entity_uid' => $this->entity->uid ?? null,
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
```

**Reglas del path:** `modules/{ModuleName}/app/Notifications/{Entity}{Event}Notification.php`

---

## 5. Dispatchar notificaciones

### Desde un Service (recomendado)

```php
use App\Models\User;
use Modules\{ModuleName}\Notifications\{Entity}{Event}Notification;

// A un solo usuario
$user->notify(new {Entity}{Event}Notification($entity));

// A múltiples usuarios
$users = User::query()->permission('{alias}.view')->get();
\Notification::send($users, new {Entity}{Event}Notification($entity));
```

### Usando NotificationService (canales múltiples)

```php
use Modules\Notification\Services\NotificationService;

$notificationService->sendToUser(
    user: $user,
    notification: new {Entity}{Event}Notification($entity),
    channels: ['database', 'mail']
);
```

### Desde un Event Listener (patrón preferido)

```php
// En el Listener
public function handle({Entity}Updated $event): void
{
    $admins = User::query()->permission('{alias}.manage')->get();
    \Notification::send($admins, new {Entity}UpdatedNotification($event->entity));
}
```

---

## 6. Referencia de campos `toArray()`

El `NotificationResource` lee el JSON de la columna `data` y expone estos campos al widget JS:

| Campo         | Obligatorio | Default JS     | Descripción                                      |
|---------------|-------------|----------------|--------------------------------------------------|
| `title`       | ✅          | `'Notificación'` | Título corto, máx ~50 chars                    |
| `message`     | ✅          | `''`            | Cuerpo, se trunca a 2 líneas en el widget       |
| `action_url`  | ✅          | `'#'`           | URL al hacer clic en la notificación            |
| `type`        | —           | `null`          | Slug del evento: `'attention_closed'`           |
| `icon`        | —           | `'fas fa-bell'` | Clase Font Awesome 6                            |
| `color`       | —           | `'primary'`     | Bootstrap color variant (ver sección 7)         |
| `entity_id`   | —           | `null`          | ID del modelo relacionado (para deep-links)     |
| `action_text` | —           | `'Ver'`         | Texto del botón en notificaciones de mail       |
| `priority`    | —           | `'normal'`      | `'low'`, `'normal'`, `'high'`, `'urgent'`       |

**Campos extra** (como `radicado`, `order_id`, etc.) se almacenan en BD pero el widget los ignora. Son útiles para lógica interna o correos personalizados.

---

## 7. Colores e iconos disponibles

### Colores Bootstrap

| Valor       | Uso sugerido                    |
|-------------|----------------------------------|
| `primary`   | Notificaciones informativas     |
| `success`   | Acciones completadas            |
| `warning`   | Alertas que requieren atención  |
| `danger`    | Errores o acciones críticas     |
| `info`      | Novedades del sistema           |
| `secondary` | Actividad general               |

### Iconos Font Awesome 6 recomendados

```
fas fa-bell          → genérico
fas fa-comment       → mensajes / comentarios
fas fa-star          → reseñas / valoraciones
fas fa-exclamation-triangle → alertas
fas fa-check-circle  → confirmaciones
fas fa-file-alt      → documentos / exports
fas fa-user-plus     → usuarios
fas fa-lock          → seguridad
fas fa-shopping-cart → órdenes / comercio
fas fa-envelope      → emails
fas fa-calendar-check → eventos / citas
fas fa-sync          → sincronizaciones
```

---

## 8. Widget del header

El widget ya está integrado en `modules/Theme/resources/views/theme/includes/header.blade.php`.

### Configuración del contenedor (sólo si se cambia)

```html
<div class="dropdown text-end"
     id="notifications-dropdown"
     data-api-index-route="{{ route('api.notifications.index') }}"
     data-api-read-route="{{ url('/api/notifications/{id}/read') }}"
     data-mark-all-read-route="{{ route('api.notifications.mark-all-read') }}"
     data-refresh-interval="60000"
     data-limit="4">
```

| `data-*`               | Descripción                                  | Default  |
|------------------------|----------------------------------------------|----------|
| `data-refresh-interval`| Ms entre auto-refreshes (60000 = 1 minuto)  | `60000`  |
| `data-limit`           | Máx notificaciones en el dropdown           | `4`      |

### Carga del JS

El script se carga automáticamente desde el header con `@push('scripts')`. No agregar manualmente en otras vistas.

### Flujo del widget

```
DOM ready
  │
  ├── Lee data-* del #notifications-dropdown
  ├── Llama GET /api/notifications?unread=true&limit=4
  │     ├── Muestra spinner (#notifications-loading)
  │     ├── Si hay notificaciones → renderiza en #notifications-list (simplebar)
  │     └── Si no hay → muestra #notifications-empty
  │
  ├── Actualiza #notification-badge (punto rojo pulsante si unread > 0)
  ├── Actualiza #unread-count-text ("3 nuevas")
  └── Auto-refresh cada 60s
      + Echo/Reverb: escucha canal privado users.{id} en tiempo real
```

---

## 9. Publicar assets del módulo

Los assets del módulo deben copiarse a `public/modules/` para ser servidos. Hacerlo **siempre que se modifique** el JS o CSS:

```bash
# Publicar assets del módulo Notification
php artisan vendor:publish --tag=notification-assets --force

# O manualmente (más rápido durante desarrollo):
cp modules/Notification/public/js/notifications.js public/modules/Notification/js/notifications.js
cp modules/Notification/public/css/notifications.css public/modules/Notification/css/notifications.css
```

El `NotificationServiceProvider` registra la publicación con el tag `notification-assets`:

```php
$this->publishes([
    __DIR__.'/../../public/css' => public_path('modules/Notification/css'),
    __DIR__.'/../../public/js'  => public_path('modules/Notification/js'),
], 'notification-assets');
```

> **Para nuevos módulos con assets propios:** seguir el mismo patrón. El path de servicio siempre es `public/modules/{ModuleName}/{tipo}/archivo`.

---

## 10. Ejemplos reales

### Módulo Attention — notificación de cierre

```php
// modules/Attention/app/Notifications/AttentionClosedNotification.php
public function toArray($notifiable): array
{
    return [
        'type'       => 'attention_closed',
        'title'      => 'PQRSF Cerrado',
        'message'    => "El PQRSF {$this->attention->radicado} ha sido cerrado",
        'icon'       => 'fas fa-check-circle',
        'color'      => 'success',
        'action_url' => route('attention.show', ['attention' => $this->attention->uid]),
        'entity_id'  => $this->attention->id,
    ];
}
```

Tipo registrado en `AttentionServiceProvider`:
```php
$registry->register('attention.status_changed', 'Estado PQRSF actualizado',
    'Cambios de estado en solicitudes de atención', ['database']);
```

### Módulo Reviews — notificación de reseña negativa

```php
// modules/Reviews/app/Notifications/NegativeReviewNotification.php
public function toArray($notifiable): array
{
    return [
        'type'       => 'reviews_negative',
        'title'      => 'Reseña negativa recibida',
        'message'    => "Nueva reseña de {$this->review->author}: {$this->review->rating}/5",
        'icon'       => 'fas fa-star',
        'color'      => 'danger',
        'action_url' => route('reviews.show', $this->review),
        'entity_id'  => $this->review->id,
    ];
}
```

### Módulo con canal mail

```php
public function via(mixed $notifiable): array
{
    $channels = array_values(array_filter(
        ['database', 'broadcast', 'mail'],
        fn ($ch) => $notifiable->canReceiveNotification($ch, 'backup.failed')
    ));
    return $channels ?: ['database'];
}

public function toMail(mixed $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Fallo en backup del sistema')
        ->greeting("Hola {$notifiable->name},")
        ->line('El backup programado ha fallado.')
        ->action('Ver detalles', route('settings.backup.index'))
        ->line('Revisa el panel para más información.');
}
```

---

## 11. Checklist de implementación

Al añadir notificaciones a un módulo:

- [ ] Crear `modules/{Module}/app/Notifications/{Entity}{Event}Notification.php`
- [ ] Implementar `ShouldQueue` + `Queueable`
- [ ] `via()` usa `canReceiveNotification()` con el type key correcto
- [ ] `toArray()` incluye `title`, `message`, `action_url`
- [ ] `toArray()` incluye `icon` y `color` apropiados (ver sección 7)
- [ ] `toBroadcast()` implementado si el canal `broadcast` está en `via()`
- [ ] Registrar el tipo en `{Module}ServiceProvider::registerNotificationTypes()`
- [ ] Usar `{alias}.{evento}` como key del tipo
- [ ] Dispatch desde Service/Listener, nunca desde Controller
- [ ] Tests con `Notification::fake()` + `Notification::assertSentTo()`

### Ejemplo de test

```php
public function test_notification_sent_on_entity_created(): void
{
    Notification::fake();

    $user = User::factory()->create();
    $entity = {Entity}::factory()->create(['user_id' => $user->id]);

    // Acción que dispara la notificación
    $this->actingAs($user)->post(route('{alias}.store'), [...]);

    Notification::assertSentTo(
        $user,
        {Entity}CreatedNotification::class,
        fn ($notification) => $notification->entity->id === $entity->id
    );
}
```

---

## API de notificaciones (referencia rápida)

| Método | Endpoint                          | Descripción                |
|--------|-----------------------------------|----------------------------|
| GET    | `/api/notifications`              | Lista notificaciones       |
| GET    | `/api/notifications?unread=1`     | Solo no leídas             |
| GET    | `/api/notifications?limit=10`     | Con límite personalizado   |
| GET    | `/api/notifications/stats`        | Totales y conteos          |
| POST   | `/api/notifications/{id}/read`    | Marcar una como leída      |
| POST   | `/api/notifications/mark-all-read`| Marcar todas como leídas   |
| DELETE | `/api/notifications/{id}`         | Eliminar una notificación  |

Todas las rutas requieren autenticación (`auth:web`). Throttle: 60 req/min lectura, 30 req/min escritura.

### Estructura de respuesta de `/api/notifications`

```json
{
  "notifications": [
    {
      "id": "uuid",
      "type": "attention_closed",
      "title": "PQRSF Cerrado",
      "message": "El PQRSF 2026-001 ha sido cerrado",
      "icon": "fas fa-check-circle",
      "color": "success",
      "action_url": "/panel/attention/abc123",
      "action_text": "Ver",
      "priority": "normal",
      "entity_id": 42,
      "is_read": false,
      "read_at": null,
      "created_at": "hace 5 minutos",
      "created_at_full": "2026-05-18T10:00:00+02:00"
    }
  ],
  "unread_count": 3
}
```
