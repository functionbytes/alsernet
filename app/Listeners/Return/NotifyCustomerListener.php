<?php

namespace App\Listeners\Return;

use App\Events\Return\ReturnStatusChanged;
use App\Services\ReturnEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotifyCustomerListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $emailService;

    // Configuración de reintentos
    public $tries = 3;
    public $timeout = 120;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(ReturnEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReturnStatusChanged $event): void
    {
        try {
            // Verificar si debe notificar al cliente
            if (!$this->shouldNotifyCustomer($event)) {
                Log::info('Customer notification skipped', [
                    'return_id' => $event->return->id_return_request,
                    'reason' => $this->getSkipReason($event),
                    'status_id' => $event->newStatus->id_return_status,
                    'send_email' => $event->newStatus->send_email ?? false,
                    'shown_to_customer' => $event->newStatus->shown_to_customer ?? false
                ]);
                return;
            }

            // Verificar email válido
            if (!$this->hasValidEmail($event->return->email)) {
                Log::warning('Invalid email for customer notification', [
                    'return_id' => $event->return->id_return_request,
                    'email' => $event->return->email ?? 'null'
                ]);
                return;
            }

            // Prevenir duplicados usando cache
            $cacheKey = "notification_sent_{$event->return->id_return_request}_{$event->newStatus->id_return_status}";
            if (Cache::has($cacheKey)) {
                Log::info('Notification already sent, skipping duplicate', [
                    'return_id' => $event->return->id_return_request,
                    'status_id' => $event->newStatus->id_return_status
                ]);
                return;
            }

            Log::info('Sending status update notification', [
                'return_id' => $event->return->id_return_request,
                'email' => $this->maskEmail($event->return->email),
                'previous_status' => $this->getStatusName($event->previousStatus),
                'new_status' => $this->getStatusName($event->newStatus),
                'transition_type' => $event->getTransitionType()
            ]);

            // Recargar la devolución con el nuevo estado para el email
            $event->return->refresh();
            $event->return->load(['status.state', 'returnType', 'returnReason']);

            // Enviar notificación de cambio de estado
            $this->emailService->sendStatusUpdateNotification($event->return);

            // Marcar como enviado en cache (24 horas)
            Cache::put($cacheKey, true, now()->addHours(24));

            Log::info('Status update notification sent successfully', [
                'return_id' => $event->return->id_return_request,
                'email' => $this->maskEmail($event->return->email),
                'sent_at' => now()->toISOString(),
                'is_completed' => $event->isCompleted(),
                'is_rejected' => $event->isRejected(),
                'attempt' => $this->attempts()
            ]);

            // Enviar notificaciones adicionales según el tipo de cambio
            $this->sendAdditionalNotifications($event);

        } catch (\Exception $e) {
            Log::error('Failed to send customer notification', [
                'return_id' => $event->return->id_return_request,
                'email' => $this->maskEmail($event->return->email ?? ''),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries
            ]);

            // Re-lanzar para retry automático
            throw $e;
        }
    }

    /**
     * Verificar si debe notificar al cliente
     */
    private function shouldNotifyCustomer(ReturnStatusChanged $event): bool
    {
        // Verificar configuración básica
        if (!$event->shouldNotifyCustomer()) {
            return false;
        }

        // Verificar configuración global
        if (!config('returns.notifications.notify_customer_on_status_change', true)) {
            return false;
        }

        // No notificar si es un cambio lateral sin importancia
        if ($event->getTransitionType() === 'lateral' && empty($event->description)) {
            return false;
        }

        // No notificar cambios muy frecuentes (menos de 1 hora)
        $lastNotificationKey = "last_notification_{$event->return->id_return_request}";
        if (Cache::has($lastNotificationKey)) {
            $lastNotification = Cache::get($lastNotificationKey);
            if (now()->diffInMinutes($lastNotification) < 60) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener razón por la que se saltó la notificación
     */
    private function getSkipReason(ReturnStatusChanged $event): string
    {
        if (!$event->newStatus->send_email) {
            return 'Status not configured to send email';
        }

        if (!$event->newStatus->shown_to_customer) {
            return 'Status not shown to customer';
        }

        if (!config('returns.notifications.notify_customer_on_status_change', true)) {
            return 'Customer notifications disabled globally';
        }

        if ($event->getTransitionType() === 'lateral' && empty($event->description)) {
            return 'Lateral transition without description';
        }

        return 'Notification conditions not met';
    }

    /**
     * Verificar si el email es válido
     */
    private function hasValidEmail(?string $email): bool
    {
        return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Obtener nombre del estado de forma segura
     */
    private function getStatusName($status): string
    {
        try {
            $translation = $status->getTranslation();
            return $translation ? $translation->name : ($status->state->name ?? 'Desconocido');
        } catch (\Exception $e) {
            return 'Desconocido';
        }
    }

    /**
     * Enmascarar email para logs
     */
    private function maskEmail(string $email): string
    {
        if (empty($email)) {
            return '';
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        $maskedUsername = strlen($username) > 2
            ? substr($username, 0, 2) . str_repeat('*', strlen($username) - 2)
            : str_repeat('*', strlen($username));

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Enviar notificaciones adicionales según el contexto
     */
    private function sendAdditionalNotifications(ReturnStatusChanged $event): void
    {
        try {
            // Notificación especial para completado exitoso
            if ($event->isCompleted()) {
                $processingDays = $event->return->created_at->diffInDays(now());

                Log::info('Return completed successfully', [
                    'return_id' => $event->return->id_return_request,
                    'processing_days' => $processingDays,
                    'within_sla' => $processingDays <= 7
                ]);

                // Marcar para envío de encuesta de satisfacción (después de 1 día)
                $surveyKey = "satisfaction_survey_{$event->return->id_return_request}";
                Cache::put($surveyKey, [
                    'return_id' => $event->return->id_return_request,
                    'completion_date' => now()->toISOString(),
                    'processing_days' => $processingDays
                ], now()->addDays(2));
            }

            // Notificación especial para rechazado
            if ($event->isRejected()) {
                Log::info('Return was rejected', [
                    'return_id' => $event->return->id_return_request,
                    'reason' => $event->description,
                    'can_appeal' => $this->canAppeal($event->return)
                ]);

                // Marcar para seguimiento de satisfacción
                $rejectionKey = "rejection_follow_up_{$event->return->id_return_request}";
                Cache::put($rejectionKey, [
                    'return_id' => $event->return->id_return_request,
                    'rejection_date' => now()->toISOString(),
                    'reason' => $event->description
                ], now()->addDays(7));
            }

            // Notificación de recogida programada
            if ($event->newStatus->is_pickup ?? false) {
                Log::info('Pickup scheduled for return', [
                    'return_id' => $event->return->id_return_request,
                    'pickup_date' => $event->return->pickup_date,
                    'logistics_mode' => $event->return->logistics_mode
                ]);

                // Programar recordatorio de recogida
                $reminderKey = "pickup_reminder_{$event->return->id_return_request}";
                Cache::put($reminderKey, [
                    'return_id' => $event->return->id_return_request,
                    'pickup_date' => $event->return->pickup_date,
                    'reminder_sent' => false
                ], now()->addDays(1));
            }

            // Actualizar cache de última notificación
            Cache::put("last_notification_{$event->return->id_return_request}", now(), now()->addHours(2));

        } catch (\Exception $e) {
            Log::warning('Failed to send additional notifications', [
                'return_id' => $event->return->id_return_request,
                'error' => $e->getMessage()
            ]);
            // No re-lanzar, las notificaciones adicionales no son críticas
        }
    }

    /**
     * Verificar si el cliente puede apelar
     */
    private function canAppeal($return): bool
    {
        // Lógica de negocio para determinar si se puede apelar
        return $return->created_at->diffInDays(now()) <= 30;
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReturnStatusChanged $event, \Throwable $exception): void
    {
        Log::critical('Customer notification failed permanently', [
            'return_id' => $event->return->id_return_request,
            'email' => $this->maskEmail($event->return->email ?? ''),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'final_attempt' => true,
            'event_data' => $event->getEventData()
        ]);

        // Notificar a administradores sobre el fallo crítico
        $this->notifyAdminsAboutFailure($event, $exception);

        // Marcar en cache que falló para evitar reintentos
        $failureKey = "notification_failed_{$event->return->id_return_request}_{$event->newStatus->id_return_status}";
        Cache::put($failureKey, [
            'failed_at' => now()->toISOString(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ], now()->addHours(24));
    }

    /**
     * Notificar a administradores sobre fallos críticos
     */
    private function notifyAdminsAboutFailure(ReturnStatusChanged $event, \Throwable $exception): void
    {
        try {
            $adminEmail = config('returns.notifications.admin_email');
            if (!empty($adminEmail)) {
                // Aquí se podría enviar un email a los administradores
                Log::channel('critical')->critical('Admin notification needed: Customer notification failed', [
                    'return_id' => $event->return->id_return_request,
                    'customer_email' => $this->maskEmail($event->return->email ?? ''),
                    'error' => $exception->getMessage(),
                    'admin_email' => $adminEmail
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins about notification failure', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determinar la cola para este listener
     */
    public function viaQueue(): string
    {
        return 'emails';
    }

    /**
     * Determinar el delay antes de procesar
     */
    public function withDelay(ReturnStatusChanged $event): int
    {
        // Delay diferente según el tipo de notificación
        if ($event->isCompleted() || $event->isRejected()) {
            return 30; // 30 segundos para estados finales
        }

        return 120; // 2 minutos de delay para otros cambios
    }

    /**
     * Determinar si debe ejecutarse basado en condiciones adicionales
     */
    public function shouldQueue(ReturnStatusChanged $event): bool
    {
        return $this->shouldNotifyCustomer($event) &&
            $this->hasValidEmail($event->return->email);
    }

    /**
     * Obtener el número de intentos actuales
     */
    public function attempts(): int
    {
        return $this->attempts ?? 1;
    }
}
