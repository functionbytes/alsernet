<?php

namespace Modules\Attention\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Attention\Events\AttentionStatusChanged;
use Modules\Attention\Notifications\AttentionStatusChangedNotification;

class SendAttentionStatusChangedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(AttentionStatusChanged $event): void
    {
        try {
            $attention = $event->attention;
            $oldStatus = $event->oldStatus;
            $newStatus = $event->newStatus;

            // Log del cambio
            Log::info('Attention status changed', [
                'attention_id' => $attention->id,
                'radicado' => $attention->radicado,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
            ]);

            // Load relationships
            $attention->load(['type', 'category', 'assignedUser', 'department']);

            // Notificar al usuario asignado
            if ($attention->assigned_user_id && $attention->assignedUser) {
                $attention->assignedUser->notify(
                    new AttentionStatusChangedNotification($attention, $oldStatus, $newStatus)
                );
            }

            // Notificar al departamento
            if ($attention->department_id && $attention->department) {
                $department = $attention->department;

                // Load department users if not loaded
                if (! $department->relationLoaded('users')) {
                    $department->load('users');
                }

                foreach ($department->users as $user) {
                    // Evitar notificar al usuario asignado dos veces
                    if ($user->id !== $attention->assigned_user_id) {
                        $user->notify(
                            new AttentionStatusChangedNotification($attention, $oldStatus, $newStatus)
                        );
                    }
                }
            }

            // Si cambió a resuelto, el listener AttentionResolvedListener ya maneja el email al ciudadano
            // No duplicar lógica aquí

        } catch (\Exception $e) {
            Log::error('Error sending status changed notification: '.$e->getMessage(), [
                'attention_id' => $event->attention->id ?? null,
                'old_status' => $event->oldStatus->value ?? null,
                'new_status' => $event->newStatus->value ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
