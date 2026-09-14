<?php

namespace Modules\Role\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;

class UserRoleChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Role $role,
        private readonly string $action,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        $label = $this->action === 'assigned' ? 'asignado' : 'removido';
        $preposition = $this->action === 'assigned' ? 'al' : 'del';

        return [
            'type' => 'role_changed',
            'title' => 'Cambio de rol',
            'message' => "Se te ha {$label} el rol \"{$this->role->name}\" {$preposition} sistema.",
            'role_id' => $this->role->id,
            'role_name' => $this->role->name,
            'action' => $this->action,
            'action_url' => route('settings.roles.show', $this->role),
        ];
    }
}
