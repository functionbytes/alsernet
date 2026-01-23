# 🔧 Plan de Correcciones - Notificaciones en Tiempo Real

**Objetivo**: Implementar broadcasting completo para notificaciones de documentos
**Tiempo estimado**: 30 minutos
**Dificultad**: Media
**Impacto**: CRÍTICO para tiempo real

---

## 🎯 Cambios Necesarios

### CRÍTICO - Priority 1

#### 1. Arreglar DocumentCreated Event

**Archivo**: `modules/Document/app/Events/DocumentCreated.php`

**Cambio 1**: Implementar ShouldBroadcast
```php
// AGREGAR esta línea en imports
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// CAMBIAR la clase de:
class DocumentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

// A:
class DocumentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
```

**Cambio 2**: Implementar broadcastOn() correctamente
```php
// REEMPLAZAR este método:
public function broadcastOn(): array
{
    return [];
}

// CON este:
public function broadcastOn(): array
{
    return [
        new \Illuminate\Broadcasting\Channel('documents.created'),
        new \Illuminate\Broadcasting\PrivateChannel('documents.'.$this->document->user_id)
    ];
}
```

**Cambio 3**: Agregar método toBroadcast()
```php
// AGREGAR este método NUEVO al final de la clase, antes del cierre }
public function toBroadcast(): array
{
    return [
        'id' => $this->document->id,
        'uid' => $this->document->uid,
        'title' => $this->document->title,
        'order_id' => $this->document->order_id,
        'order_reference' => $this->document->order_reference ?? $this->document->order_id,
        'customer_name' => $this->document->customer_firstname . ' ' . $this->document->customer_lastname,
        'type' => $this->document->document_type_id,
        'stage' => $this->document->current_stage,
        'created_at' => $this->document->created_at,
    ];
}

public function broadcastType(): string
{
    return 'document.created';
}
```

**Resultado**: El evento ahora se transmite por WebSocket

---

#### 2. Arreglar SendDocumentCreatedNotification Listener

**Archivo**: `modules/Document/app/Listeners/SendDocumentCreatedNotification.php`

**Cambio**: Implementar broadcast en notificación anónima

```php
// REEMPLAZAR la notificación anónima (líneas 60-86):

$notification = new class extends Notification implements \Illuminate\Contracts\Broadcasting\ShouldBroadcast, \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;

    public function __construct(
        public $document,
        public $groupKey
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];  // ✅ AGREGADO broadcast
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => '📄 Nuevo documento para validar',
            'message' => "Se ha creado un nuevo documento para la orden #{$this->document->order_id}. ".
                         "Cliente: {$this->document->customer_firstname} {$this->document->customer_lastname}. ".
                         "Requiere validación de {$this->groupKey}.",
            'icon' => 'fas fa-file-check',
            'color' => 'warning',
            'action_url' => url("/documents/show/{$this->document->uid}"),
            'action_text' => 'Validar documento',
            'priority' => 'high',
        ];
    }

    // ✅ AGREGADO método para broadcast
    public function toBroadcast($notifiable)
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'title' => '📄 Nuevo documento para validar',
            'message' => "Documento #{$this->document->order_id} requiere validación de {$this->groupKey}",
            'icon' => 'fas fa-file-check',
            'color' => 'warning',
            'action_url' => url("/documents/show/{$this->document->uid}"),
            'action_text' => 'Validar documento',
            'priority' => 'high',
            'document_id' => $this->document->id,
        ]);
    }

    // ✅ AGREGADO canales de broadcast
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\Channel('documents.created'),
        ];
    }

    // ✅ AGREGADO tipo de broadcast
    public function broadcastType(): string
    {
        return 'document.created.notification';
    }
};
```

**Resultado**: Las notificaciones ahora van a database Y se transmiten en tiempo real

---

### IMPORTANTE - Priority 2

#### 3. Crear DocumentApproved Notification

**Archivo**: `modules/Document/app/Notifications/DocumentApproved.php`

**Contenido** (archivo estaba VACÍO):

```php
<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentApproved extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public object $document,
        public string $approvedBy = 'Sistema'
    ) {}

    public function via(object $notifiable): array
    {
        $this->recipientUserId = $notifiable->id;
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return [
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'approved_by' => $this->approvedBy,
            'title' => '✅ Documento Aprobado',
            'message' => "El documento #{$orderRef} ha sido aprobado exitosamente",
            'icon' => 'fas fa-check-circle',
            'color' => 'success',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'high',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_reference' => $orderRef,
            'approved_by' => $this->approvedBy,
            'title' => '✅ Documento Aprobado',
            'message' => "El documento #{$orderRef} ha sido aprobado",
            'icon' => 'fas fa-check-circle',
            'color' => 'success',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'high',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn(): array
    {
        if (!$this->recipientUserId) {
            return [];
        }

        return [new Channel('public-notifications.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'document.approved';
    }
}
```

---

#### 4. Crear DocumentRejected Notification

**Archivo**: `modules/Document/app/Notifications/DocumentRejected.php`

**Contenido** (archivo estaba VACÍO):

```php
<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentRejected extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public object $document,
        public string $reason = '',
        public string $rejectedBy = 'Sistema'
    ) {}

    public function via(object $notifiable): array
    {
        $this->recipientUserId = $notifiable->id;
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return [
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy,
            'title' => '❌ Documento Rechazado',
            'message' => "El documento #{$orderRef} ha sido rechazado. Motivo: {$this->reason}",
            'icon' => 'fas fa-times-circle',
            'color' => 'danger',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'critical',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_reference' => $orderRef,
            'reason' => $this->reason,
            'rejected_by' => $this->rejectedBy,
            'title' => '❌ Documento Rechazado',
            'message' => "El documento #{$orderRef} ha sido rechazado. Motivo: {$this->reason}",
            'icon' => 'fas fa-times-circle',
            'color' => 'danger',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'critical',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn(): array
    {
        if (!$this->recipientUserId) {
            return [];
        }

        return [new Channel('public-notifications.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'document.rejected';
    }
}
```

---

### RECOMENDADO - Priority 3

#### 5. Crear DocumentStatusChanged Notification (Opcional)

**Archivo**: `modules/Document/app/Notifications/DocumentStatusChanged.php`

**Contenido** (archivo estaba VACÍO):

```php
<?php

namespace Modules\Document\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DocumentStatusChanged extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public ?int $recipientUserId = null;

    public function __construct(
        public object $document,
        public string $oldStatus = '',
        public string $newStatus = ''
    ) {}

    public function via(object $notifiable): array
    {
        $this->recipientUserId = $notifiable->id;
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return [
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_id' => $this->document->order_id,
            'order_reference' => $orderRef,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => '🔄 Estado del Documento Actualizado',
            'message' => "El documento #{$orderRef} cambió de estado: {$this->oldStatus} → {$this->newStatus}",
            'icon' => 'fas fa-sync-alt',
            'color' => 'info',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'medium',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $orderRef = $this->document->order_reference ?? $this->document->order_id;

        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'document_id' => $this->document->id,
            'document_uid' => $this->document->uid,
            'order_reference' => $orderRef,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'title' => '🔄 Estado Actualizado',
            'message' => "Documento #{$orderRef}: {$this->oldStatus} → {$this->newStatus}",
            'icon' => 'fas fa-sync-alt',
            'color' => 'info',
            'action_url' => route('documents.show', $this->document->uid),
            'action_text' => 'Ver documento',
            'priority' => 'medium',
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastOn(): array
    {
        if (!$this->recipientUserId) {
            return [];
        }

        return [new Channel('public-notifications.'.$this->recipientUserId)];
    }

    public function broadcastType(): string
    {
        return 'document.status.changed';
    }
}
```

---

## ✅ Checklist de Implementación

### Paso 1: Editar DocumentCreated.php
- [ ] Agregar `use ShouldBroadcast`
- [ ] Hacer clase implements ShouldBroadcast
- [ ] Reemplazar `broadcastOn()` para retornar canales válidos
- [ ] Agregar método `toBroadcast()`
- [ ] Agregar método `broadcastType()`

### Paso 2: Editar SendDocumentCreatedNotification.php
- [ ] Agregar `implements ShouldBroadcast, ShouldQueue` a la notificación
- [ ] Agregar `use Queueable` trait
- [ ] Cambiar `via()` a `['database', 'broadcast']`
- [ ] Agregar método `toBroadcast()`
- [ ] Agregar método `broadcastOn()`
- [ ] Agregar método `broadcastType()`

### Paso 3: Crear DocumentApproved.php
- [ ] Copiar contenido completo (arriba)
- [ ] Verificar imports

### Paso 4: Crear DocumentRejected.php
- [ ] Copiar contenido completo (arriba)
- [ ] Verificar imports

### Paso 5: Crear DocumentStatusChanged.php (Opcional)
- [ ] Copiar contenido completo (arriba)
- [ ] Verificar imports

### Paso 6: Verificación
- [ ] Ejecutar tests si existen
- [ ] Verificar composer autoload: `php artisan dump-autoload`
- [ ] Verificar no hay syntax errors: `php artisan route:list`

---

## 🧪 Pruebas después de cambios

### Prueba 1: Verificar clases cargan
```bash
cd /home2/webadminpruebas/web
php artisan tinker

# Verificar que las clases existen
\Modules\Document\Events\DocumentCreated::class
\Modules\Document\Notifications\DocumentApproved::class
\Modules\Document\Notifications\DocumentRejected::class

exit
```

### Prueba 2: Crear documento y verificar notificaciones
```bash
cd /home2/webadminpruebas/web
php artisan tinker

# Crear un documento de prueba
$doc = \Modules\Document\Entities\Document::factory()->create();

# Verificar que se creó la notificación
DB::table('notifications')->where('notifiable_type', 'App\Models\User')->get();

exit
```

### Prueba 3: Verificar Broadcasting

En el navegador (consola JavaScript):
```javascript
// Si Reverb está corriendo
const pusher = new Pusher(window.app_key, {
    wsHost: window.wsHost,
    wsPort: window.wsPort,
    wssPort: window.wssPort,
    scheme: window.scheme,
    forceTLS: true,
});

const channel = pusher.subscribe('documents.created');
channel.bind('document.created', (data) => {
    console.log('Documento creado:', data);
});
```

---

## 📊 Resumen de Cambios

| Archivo | Tipo | Cambios |
|---------|------|---------|
| DocumentCreated.php | Modificar | Agregar ShouldBroadcast + métodos |
| SendDocumentCreatedNotification.php | Modificar | Agregar broadcast a notificación |
| DocumentApproved.php | Crear | 138 líneas de código |
| DocumentRejected.php | Crear | 138 líneas de código |
| DocumentStatusChanged.php | Crear | 138 líneas de código (opcional) |

**Total de cambios**: ~500 líneas de código nuevo

---

## 🔗 Dependencias de Servidor

Después de estos cambios, **NECESITA**:

1. **Reverb corriendo**
   ```bash
   sudo systemctl start reverb.service  # systemd
   # o
   sudo supervisorctl start laravel-reverb  # Supervisor
   ```

2. **Queue Worker corriendo**
   ```bash
   sudo systemctl start queue-worker.service  # systemd
   # o
   sudo supervisorctl start laravel-queue-worker:*  # Supervisor
   ```

3. **Verifications**
   ```bash
   ps aux | grep reverb    # Debe mostrar proceso
   ps aux | grep queue:work  # Debe mostrar proceso
   ```

---

## ⚠️ Notas Importantes

- El archivo original de `SendDocumentCreatedNotification` crea una notificación anónima. Esto se puede mejorar creando una clase `DocumentCreatedNotification.php` pero por ahora se deja así.

- Los métodos `broadcastOn()` en Approved/Rejected/StatusChanged usan canal `public-notifications.{userId}` por consistencia con `DocumentStageAdvanced`.

- Las notificaciones implementan `ShouldQueue` para ejecutarse en background.

- Todos los eventos retornan un `broadcastType()` para identificar el tipo de notificación en el frontend.

---

**¿Quieres que implemente estos cambios automáticamente?**

