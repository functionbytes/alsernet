<?php

namespace App\Services;

use App\Models\Return;
use App\Models\ReturnCommunication;
use App\Mail\ReturnStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReturnNotificationService
{
    // Plantillas de email por estado
    private array $emailTemplates = [
        'pending' => [
            'subject' => 'Solicitud de Devolución Recibida - #:return_number',
            'template' => 'emails.returns.pending'
        ],
        'approved' => [
            'subject' => 'Devolución Aprobada - #:return_number',
            'template' => 'emails.returns.approved'
        ],
        'rejected' => [
            'subject' => 'Devolución Rechazada - #:return_number',
            'template' => 'emails.returns.rejected'
        ],
        'processing' => [
            'subject' => 'Devolución en Proceso - #:return_number',
            'template' => 'emails.returns.processing'
        ],
        'completed' => [
            'subject' => 'Devolución Completada - #:return_number',
            'template' => 'emails.returns.completed'
        ],
        'refund_processed' => [
            'subject' => 'Reembolso Procesado - #:return_number',
            'template' => 'emails.returns.refund_processed'
        ]
    ];

    /**
     * Enviar notificación por cambio de estado
     */
    public function notifyStatusChange(Return $return, string $previousStatus = null): void
    {
        try {
            // Crear registro de comunicación
            $communication = $this->createCommunicationRecord($return, $return->status);

            // Preparar datos para el email
            $emailData = $this->prepareEmailData($return, $previousStatus);

            // Enviar email
            Mail::to($return->customer_email)
                ->queue(new ReturnStatusMail($emailData));

            $communication->markAsSent();

            // Log de éxito
            Log::info('Return notification sent', [
                'return_id' => $return->id,
                'status' => $return->status,
                'recipient' => $return->customer_email
            ]);

        } catch (\Exception $e) {
    Log::error('Failed to send return notification', [
        'return_id' => $return->id,
        'error' => $e->getMessage()
    ]);

    if (isset($communication)) {
        $communication->markAsFailed($e->getMessage());
    }

    throw $e;
}
    }

    /**
     * Enviar email personalizado
     */
    public function sendCustomEmail(Return $return, array $data): ReturnCommunication
    {
        return DB::transaction(function () use ($return, $data) {
            $communication = $return->communications()->create([
                'type' => ReturnCommunication::TYPE_EMAIL,
                'recipient' => $data['recipient'] ?? $return->customer_email,
                'subject' => $data['subject'],
                'content' => $data['content'],
                'template_used' => 'custom',
                'sent_by' => auth()->user()->name ?? 'Sistema',
                'metadata' => [
                    'custom' => true,
                    'attachments' => $data['attachments'] ?? []
                ]
            ]);

            try {
                Mail::raw($data['content'], function ($message) use ($data, $return) {
                    $message->to($data['recipient'] ?? $return->customer_email)
                        ->subject($data['subject']);

                    if (!empty($data['attachments'])) {
                        foreach ($data['attachments'] as $attachment) {
                            $message->attach($attachment);
                        }
                    }
                });

                $communication->markAsSent();
            } catch (\Exception $e) {
                $communication->markAsFailed($e->getMessage());
                throw $e;
            }

            return $communication;
        });
    }

    /**
     * Enviar recordatorio de devolución pendiente
     */
    public function sendReminder(Return $return): void
    {
        // Verificar si ya se envió un recordatorio en las últimas 24 horas
        $recentReminder = $return->communications()
            ->where('template_used', 'reminder')
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($recentReminder) {
            return;
        }

        $emailData = [
            'return' => $return,
            'days_pending' => $return->created_at->diffInDays(now()),
            'template' => 'emails.returns.reminder'
        ];

        $communication = $this->createCommunicationRecord($return, 'reminder');

        try {
            Mail::to($return->customer_email)
                ->queue(new ReturnStatusMail($emailData));

            $communication->markAsSent();
        } catch (\Exception $e) {
            $communication->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener historial de comunicaciones
     */
    public function getCommunicationHistory(Return $return): array
    {
        return $return->communications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($communication) {
                return [
                    'id' => $communication->id,
                    'type' => $communication->type,
                    'subject' => $communication->subject,
                    'status' => $communication->status,
                    'sent_at' => $communication->sent_at?->format('d/m/Y H:i'),
                    'read_at' => $communication->read_at?->format('d/m/Y H:i'),
                    'sent_by' => $communication->sent_by,
                    'recipient' => $communication->recipient
                ];
            })
            ->toArray();
    }

    /**
     * Marcar comunicación como leída (para tracking de emails)
     */
    public function markAsRead(string $trackingId): void
{
    $communication = ReturnCommunication::where('metadata->tracking_id', $trackingId)
        ->first();

    if ($communication) {
        $communication->markAsRead();
    }
}

    // Métodos privados de apoyo

    private function createCommunicationRecord(Return $return, string $templateKey): ReturnCommunication
    {
        $template = $this->emailTemplates[$templateKey] ?? null;

        return $return->communications()->create([
            'type' => ReturnCommunication::TYPE_EMAIL,
            'recipient' => $return->customer_email,
            'subject' => str_replace(':return_number', $return->number, $template['subject'] ?? 'Actualización de Devolución'),
            'content' => '', // Se llenará con el contenido renderizado del template
            'template_used' => $templateKey,
            'sent_by' => 'Sistema',
            'metadata' => [
                'tracking_id' => uniqid('track_', true)
            ]
        ]);
    }

    private function prepareEmailData(Return $return, ?string $previousStatus): array
    {
        $template = $this->emailTemplates[$return->status] ?? null;

        return [
            'return' => $return,
            'previous_status' => $previousStatus,
            'template' => $template['template'] ?? 'emails.returns.default',
            'subject' => str_replace(':return_number', $return->number, $template['subject'] ?? 'Actualización de Devolución'),
            'tracking_url' => route('returns.track', ['tracking_id' => $return->communications()->latest()->first()->metadata['tracking_id'] ?? null]),
            'return_url' => route('returns.show', $return),
            'costs_summary' => $return->costs ? [
                'total_deductions' => $return->total_costs,
                'final_refund' => $return->final_refund
            ] : null
        ];
    }
}
