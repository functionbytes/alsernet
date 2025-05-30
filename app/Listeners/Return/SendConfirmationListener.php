<?php

namespace App\Listeners\Return;

use App\Events\Return\ReturnCreated;
use App\Services\ReturnEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected $emailService;

    public function __construct(ReturnEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReturnCreated $event): void
    {
        try {
            // Verificar si debe enviar notificaciones
            if (!$event->shouldSendNotifications()) {
                Log::info('Confirmation email skipped', [
                    'return_id' => $event->return->id_return_request,
                    'reason' => 'Notifications disabled in configuration'
                ]);
                return;
            }

            // Verificar que tenemos un email válido
            if (empty($event->return->email) || !filter_var($event->return->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email address for confirmation', [
                    'return_id' => $event->return->id_return_request,
                    'email' => $event->return->email
                ]);
                return;
            }

            Log::info('Sending confirmation email', [
                'return_id' => $event->return->id_return_request,
                'email' => $event->return->email,
                'created_by' => $event->createdBy
            ]);

            // Enviar email de confirmación
            $this->emailService->sendReturnConfirmation($event->return);

            Log::info('Confirmation email sent successfully', [
                'return_id' => $event->return->id_return_request,
                'email' => $event->return->email,
                'sent_at' => now()->toISOString()
            ]);

            // Opcional: Marcar en la base de datos que se envió el email
            // $event->return->update(['confirmation_email_sent_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Failed to send confirmation email', [
                'return_id' => $event->return->id_return_request,
                'email' => $event->return->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para retry automático
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReturnCreated $event, \Throwable $exception): void
    {
        Log::critical('Confirmation email failed permanently', [
            'return_id' => $event->return->id_return_request,
            'email' => $event->return->email,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts ?? 'unknown'
        ]);

        // Opcional: Marcar el fallo en la base de datos
        // $event->return->update(['confirmation_email_failed_at' => now()]);
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
    public function withDelay(ReturnCreated $event): int
    {
        // Delay de 30 segundos para dar tiempo a que se genere el PDF
        return 30;
    }
}
