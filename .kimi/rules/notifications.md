# Rule: notifications

# Notifications & Mailables Rules

## Notification Structure

```php
<?php

namespace Modules\{ModuleName}\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}StatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly {Entity} $entity
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => '{alias}_{event}',
            'title' => 'Titulo corto',
            'message' => "El registro {$this->entity->id} cambio de estado",
            'entity_id' => $this->entity->id,
            'action_url' => route('{alias}.show', $this->entity),
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
```

## Notification with Email Channel

```php
public function via(mixed $notifiable): array
{
    return ['database', 'mail'];
}

public function toMail(mixed $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Asunto del correo')
        ->greeting("Hola {$notifiable->name},")
        ->line('Cuerpo del mensaje.')
        ->action('Ver detalle', route('{alias}.show', $this->entity))
        ->line('Gracias por usar el sistema.');
}
```

## Dynamic Channel Selection

```php
public function via(mixed $notifiable): array
{
    $channels = array_filter(
        ['database', 'broadcast', 'mail'],
        fn ($ch) => $notifiable->canReceiveNotification($ch, '{alias}.{event}')
    );

    return array_values($channels) ?: ['database'];
}
```

## Mailable Structure

```php
<?php

namespace Modules\{ModuleName}\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}CreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly {Entity} $entity
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuevo registro creado');
    }

    public function content(): Content
    {
        return new Content(view: '{alias}::emails.{entity}-created');
    }
}
```

## Rules

- **Notification naming**: `{Entity}{Event}Notification` — `AttentionClosedNotification`
- **Mailable naming**: `{Entity}{Event}Mail` — `AttentionCreatedMail`
- **Always queue**: implement `ShouldQueue` on both notifications and mailables
- **`toArray()` keys**: snake_case, always include `type`, `title`, `message`, `action_url`
- **`type` key**: `{alias}_{event}` format — `attention_closed`, `user_created`
- **`via()` method**: dynamic channel selection using `canReceiveNotification()` when user preferences apply
- **Broadcasting**: always implement `toBroadcast()` when `broadcast` channel is included in `via()`
- **Mail views**: store in `modules/{ModuleName}/resources/views/emails/`
- **Dispatch notifications**: `$user->notify(new {Entity}Notification($entity))` in service layer
- **Batch notifications**: use `Notification::send($users, new Notification)` for bulk
- **Testing**: use `Notification::fake()` + `Notification::assertSentTo()`, `Mail::fake()` + `Mail::assertSent()`
- **No business logic** in `toArray()` — it should only format data already on the model
- **ISO8601 dates**: `->toIso8601String()` for all date fields in `toArray()`
