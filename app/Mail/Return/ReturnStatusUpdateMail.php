<?php

namespace App\Mail\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $return;

    public function __construct(ReturnRequest $return)
    {
        $this->return = $return;
    }

    public function build()
    {
        $subject = 'Actualización de tu Solicitud de Devolución #' . $this->return->id_return_request;

        // Personalizar asunto según el estado
        if ($this->return->isCompleted()) {
            $subject = 'Tu Devolución ha sido Completada #' . $this->return->id_return_request;
        } elseif ($this->return->status->color === '#dc3545') { // Estado rechazado
            $subject = 'Información sobre tu Solicitud de Devolución #' . $this->return->id_return_request;
        }

        return $this->subject($subject)
            ->view('emails.return-status-update')
            ->with([
                'return' => $this->return,
                'customer_name' => $this->return->customer_name,
                'return_id' => $this->return->id_return_request,
                'order_id' => $this->return->id_order,
                'old_status' => $this->getPreviousStatus(),
                'new_status' => $this->return->getStatusName(),
                'status_color' => $this->return->status->color,
                'return_type' => $this->return->getReturnTypeName(),
                'logistics_mode' => $this->return->getLogisticsModeLabel(),
                'is_completed' => $this->return->isCompleted(),
                'is_refunded' => $this->return->is_refunded,
                'company_info' => config('returns.company_info'),
                'tracking_url' => $this->getTrackingUrl(),
                'next_steps' => $this->getNextSteps()
            ]);
    }

    /**
     * Obtener el estado anterior del historial
     */
    private function getPreviousStatus(): ?string
    {
        $previousHistory = $this->return->history()
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        if ($previousHistory) {
            return $previousHistory->status->getTranslation()->name ?? 'Desconocido';
        }

        return null;
    }

    /**
     * Obtener URL de seguimiento
     */
    private function getTrackingUrl(): string
    {
        return url('/returns/status?' . http_build_query([
                'order_id' => $this->return->id_order,
                'email' => $this->return->email
            ]));
    }

    /**
     * Obtener próximos pasos según el estado
     */
    private function getNextSteps(): array
    {
        $steps = [];

        switch ($this->return->status->id_return_state) {
            case 1: // Nuevo
                $steps = [
                    'Hemos recibido tu solicitud de devolución',
                    'Nuestro equipo la revisará en las próximas 24-48 horas',
                    'Te notificaremos cuando tengamos una respuesta'
                ];
                break;

            case 2: // Verificación
                if ($this->return->status->is_pickup) {
                    $steps = [
                        'Hemos programado la recogida de tu paquete',
                        'Recibirás un email con los detalles de recogida',
                        'Asegúrate de tener el paquete listo en la fecha acordada'
                    ];
                } else {
                    $steps = [
                        'Tu solicitud ha sido aprobada',
                        'Por favor, envía el producto a nuestra dirección de devoluciones',
                        'Incluye una copia de este email en el paquete'
                    ];
                }
                break;

            case 3: // Negociación
                $steps = [
                    'Necesitamos más información sobre tu solicitud',
                    'Revisa los comentarios en tu panel de devoluciones',
                    'Responde con la información adicional solicitada'
                ];
                break;

            case 4: // Resuelto
                if ($this->return->status->is_refunded) {
                    $steps = [
                        'Tu reembolso ha sido procesado',
                        'El dinero aparecerá en tu cuenta en 3-5 días hábiles',
                        'Recibirás un email de confirmación de pago'
                    ];
                } else {
                    $steps = [
                        'Tu solicitud ha sido resuelta',
                        'Revisa los detalles en tu panel de devoluciones',
                        'Contacta con nosotros si tienes alguna pregunta'
                    ];
                }
                break;

            case 5: // Cerrado
                if ($this->return->status->color === '#dc3545') { // Rechazado
                    $steps = [
                        'Lamentablemente no podemos procesar tu devolución',
                        'Revisa los motivos en tu panel de devoluciones',
                        'Puedes contactar con atención al cliente si tienes dudas'
                    ];
                } else { // Completado
                    $steps = [
                        'Tu devolución ha sido completada exitosamente',
                        'Gracias por confiar en nosotros',
                        'Esperamos verte pronto de nuevo'
                    ];
                }
                break;
        }

        return $steps;
    }
}
