# Ejemplos de uso - Laravel Reverb

Ejemplos prácticos de cómo usar el módulo Reverb en tu aplicación.

## 1. Notificaciones en tiempo real

### Crear el evento

```php
<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'type' => $this->notification->type,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}
```

### Disparar el evento

```php
<?php

namespace App\Http\Controllers;

use App\Events\NotificationReceived;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $notification = Notification::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
        ]);

        NotificationReceived::dispatch($notification, $request->user()->id);

        return response()->json(['success' => true]);
    }
}
```

### Escuchar en JavaScript

```javascript
// En tu componente Vue o JavaScript
Echo.private(`notifications.user.${userId}`)
    .listen('notification.received', (data) => {
        console.log('Nueva notificación:', data);

        // Actualizar UI
        addNotificationToList(data);
        showToast(data.title, data.message);
    });
```

## 2. Chat en tiempo real

### Modelo de mensaje

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'message'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Evento de mensaje

```php
<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public int $conversationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("chat.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'message' => $this->message->message,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
```

### Controlador de chat

```php
<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, int $conversationId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = ChatMessage::create([
            'conversation_id' => $conversationId,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        MessageSent::dispatch($message, $conversationId);

        return response()->json($message);
    }
}
```

### Vista Vue con escucha

```vue
<template>
    <div class="chat-container">
        <div class="messages">
            <div v-for="msg in messages" :key="msg.id" class="message">
                <strong>{{ msg.user.name }}</strong>: {{ msg.message }}
            </div>
        </div>

        <form @submit.prevent="sendMessage">
            <input v-model="newMessage" type="text" placeholder="Escribe un mensaje...">
            <button type="submit">Enviar</button>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const messages = ref([]);
const newMessage = ref('');
const conversationId = ref(1);

onMounted(() => {
    // Cargar mensajes previos
    fetch(`/api/conversations/${conversationId.value}/messages`)
        .then(r => r.json())
        .then(data => messages.value = data);

    // Escuchar nuevos mensajes
    Echo.channel(`chat.${conversationId.value}`)
        .listen('message.sent', (data) => {
            messages.value.push(data);
        });
});

const sendMessage = async () => {
    if (!newMessage.value) return;

    await fetch(`/api/conversations/${conversationId.value}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ message: newMessage.value }),
    });

    newMessage.value = '';
};
</script>
```

## 3. Seguimiento de presencia

### Evento de presencia

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UserPresence implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public int $teamId,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("team.{$this->teamId}"),
        ];
    }
}
```

### En JavaScript

```javascript
// Conectar al canal de presencia
Echo.join(`team.${teamId}`)
    .here((users) => {
        // Usuarios actualmente en el canal
        console.log('Usuarios presentes:', users);
        updateUserList(users);
    })
    .joining((user) => {
        // Nuevo usuario se conecta
        console.log(`${user.name} se conectó`);
        addUserToList(user);
    })
    .leaving((user) => {
        // Usuario se desconecta
        console.log(`${user.name} se desconectó`);
        removeUserFromList(user);
    });
```

## 4. Actualización de inventario en tiempo real

### Evento de actualización

```php
<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public Product $product,
        public int $previousStock,
        public int $newStock,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('inventory-updates'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'previous_stock' => $this->previousStock,
            'new_stock' => $this->newStock,
            'change' => $this->newStock - $this->previousStock,
        ];
    }
}
```

### Escuchar en dashboard

```javascript
Echo.channel('inventory-updates')
    .listen('inventory.updated', (data) => {
        console.log(`${data.product_name}: ${data.previous_stock} → ${data.new_stock}`);

        // Actualizar gráfico o tabla de inventario
        updateInventoryChart(data);
    });
```

## 5. Notificaciones de progreso de tareas

### Job con progreso

```php
<?php

namespace App\Jobs;

use App\Events\JobProgressUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessLargeFile implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $fileId,
        public int $userId,
    ) {}

    public function handle()
    {
        $file = File::find($this->fileId);
        $lines = file($file->path);
        $total = count($lines);

        foreach ($lines as $index => $line) {
            // Procesar línea
            processLine($line);

            // Emitir progreso cada 10 líneas
            if (($index + 1) % 10 === 0) {
                $progress = (int)(($index + 1) / $total * 100);

                JobProgressUpdated::dispatch(
                    $this->fileId,
                    $this->userId,
                    $progress,
                    "$index/$total líneas procesadas"
                );
            }
        }
    }
}
```

### Evento de progreso

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class JobProgressUpdated implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(
        public int $fileId,
        public int $userId,
        public int $progress,
        public string $status,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("job-progress.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'job.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'file_id' => $this->fileId,
            'progress' => $this->progress,
            'status' => $this->status,
        ];
    }
}
```

### UI con barra de progreso

```vue
<template>
    <div v-if="uploadActive" class="progress-container">
        <p>{{ progressStatus }}</p>
        <progress :value="progress" max="100"></progress>
        <span>{{ progress }}%</span>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const progress = ref(0);
const progressStatus = ref('');
const uploadActive = ref(false);
const userId = ref(1);

onMounted(() => {
    Echo.private(`job-progress.${userId.value}`)
        .listen('job.progress', (data) => {
            uploadActive.value = true;
            progress.value = data.progress;
            progressStatus.value = data.status;

            if (data.progress === 100) {
                setTimeout(() => {
                    uploadActive.value = false;
                }, 1000);
            }
        });
});
</script>
```

## Mejores prácticas

1. **Validar permisos** - Siempre valida que el usuario puede acceder al canal
2. **Cachear cuando sea posible** - No emitas eventos innecesariamente
3. **Usar canales privados** - Para datos sensibles
4. **Implementar reconexión** - Echo maneja esto automáticamente
5. **Monitorear conexiones** - Usa el dashboard para ver si algo funciona mal

## Debugging

Habilita debug en el archivo de configuración para ver más información:

```php
// config/reverb.php
'debug' => env('REVERB_DEBUG', true), // En desarrollo
```

Revisa los logs de Reverb en la consola cuando ejecutas:

```bash
php artisan reverb:start
```
