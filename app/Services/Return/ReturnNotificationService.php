<?php

// app/Services/ReturnNotificationService.php

namespace App\Services\Return;

use App\Models\Return;
use App\Models\Return\ReturnCommunication;
use App\Mail\Return\ReturnStatusMail;
use App\Mail\Return\ReturnReminderMail;
use App\Mail\Return\ReturnCustomMail;
use App\Models\Return\ReturnRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReturnNotificationService
{
    /**
     * Plantillas de email por estado
     */
    private array $emailTemplates = [
        'pending' => [
            'subject' => 'Solicitud de Devolución Recibida - #:return_number',
            'template' => 'emails.returns.pending',
            'attachments' => []
        ],
        'approved' => [
            'subject' => 'Devolución Aprobada - #:return_number',
            'template' => 'emails.returns.approved',
            'attachments' => ['return_label'] // Adjuntar etiqueta de envío
        ],
        'rejected' => [
            'subject' => 'Devolución Rechazada - #:return_number',
            'template' => 'emails.returns.rejected',
            'attachments' => []
        ],
        'processing' => [
            'subject' => 'Devolución en Proceso - #:return_number',
            'template' => 'emails.returns.processing',
            'attachments' => []
        ],
        'completed' => [
            'subject' => 'Devolución Completada - #:return_number',
            'template' => 'emails.returns.completed',
            'attachments' => ['receipt'] // Adjuntar recibo
        ],
        'refund_processed' => [
            'subject' => 'Reembolso Procesado - #:return_number',
            'template' => 'emails.returns.refund_processed',
            'attachments' => ['refund_receipt']
        ],
        'reminder' => [
            'subject' => 'Recordatorio: Devolución Pendiente - #:return_number',
            'template' => 'emails.returns.reminder',
            'attachments' => []
        ],
        'shipping_reminder' => [
            'subject' => 'Recordatorio: Envíe su Devolución - #:return_number',
            'template' => 'emails.returns.shipping_reminder',
            'attachments' => ['return_label']
        ]
    ];

    /**
     * Configuración de reintentos
     */
    private array $retryConfig = [
        'max_attempts' => 3,
        'delay_between_attempts' => 300, // 5 minutos
        'backoff_multiplier' => 2
    ];

    /**
     * Enviar notificación por cambio de estado
     */
    public function notifyStatusChange(ReturnRequest $return, string $previousStatus = null): ReturnCommunication
    {
        try {
            DB::beginTransaction();

            // Validar que existe plantilla para el estado
            if (!isset($this->emailTemplates[$return->status])) {
                throw new \Exception("No hay plantilla de email para el estado: {$return->status}");
            }

        // Crear registro de comunicación
        $communication = $this->createCommunicationRecord($return, $return->status);

        // Preparar datos para el email
        $emailData = $this->prepareEmailData($return, $previousStatus);

        // Obtener archivos adjuntos si corresponde
        $attachments = $this->prepareAttachments($return, $return->status);

        // Enviar email
        $this->sendEmail($communication, $emailData, $attachments);

        // Marcar como enviado
        $communication->markAsSent();

        // Registrar evento
        $this->logNotificationEvent($return, 'status_change', [
            'previous_status' => $previousStatus,
            'new_status' => $return->status
        ]);

        DB::commit();

        return $communication;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to send return notification', [
                'return_id' => $return->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            public function sendCustomEmail(ReturnRequest $return, array $data): ReturnCommunication
            {
                return DB::transaction(function () use ($return, $data) {
                    // Validar datos requeridos
                    $this->validateCustomEmailData($data);

                    // Crear registro de comunicación
                    $communication = $return->communications()->create([
                        'type' => ReturnCommunication::TYPE_EMAIL,
                        'recipient' => $data['recipient'] ?? $return->customer_email,
                        'subject' => $data['subject'],
                        'content' => $data['content'],
                        'template_used' => $data['template'] ?? 'custom',
                        'sent_by' => auth()->user()->name ?? 'Sistema',
                        'metadata' => [
                            'custom' => true,
                            'attachments' => $data['attachments'] ?? [],
                            'cc' => $data['cc'] ?? null,
                            'bcc' => $data['bcc'] ?? null,
                            'reply_to' => $data['reply_to'] ?? null
                        ]
                    ]);

                    try {
                        // Preparar y enviar email
                        Mail::send([], [], function ($message) use ($data, $return, $communication) {
                            $message->to($data['recipient'] ?? $return->customer_email)
                                ->subject($data['subject']);

                            // HTML content
                            $message->html($this->wrapInTemplate($data['content'], $return));

                            // CC y BCC
                            if (!empty($data['cc'])) {
                                $message->cc($data['cc']);
                            }
                            if (!empty($data['bcc'])) {
                                $message->bcc($data['bcc']);
                            }

                            // Reply-to
                            if (!empty($data['reply_to'])) {
                                $message->replyTo($data['reply_to']);
                            }

                            // Archivos adjuntos
                            if (!empty($data['attachments'])) {
                                foreach ($data['attachments'] as $attachment) {
                                    if (Storage::exists($attachment)) {
                                        $message->attach(Storage::path($attachment));
                                    }
                                }
                            }

                            // Headers para tracking
                            $message->getHeaders()->addTextHeader(
                                'X-Return-ID',
                                $return->id
                            );
                            $message->getHeaders()->addTextHeader(
                                'X-Communication-ID',
                                $communication->id
                            );
                        });

                        $communication->markAsSent();

                        $this->logNotificationEvent($return, 'custom_email', [
                            'subject' => $data['subject'],
                            'recipient' => $data['recipient'] ?? $return->customer_email
                        ]);

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
    public function sendReminder(ReturnRequest $return, string $reminderType = 'general'): ReturnCommunication
    {
        // Verificar si ya se envió un recordatorio recientemente
        if ($this->hasRecentReminder($return, $reminderType)) {
            throw new \Exception('Ya se envió un recordatorio recientemente para esta devolución');
        }

        // Determinar tipo de recordatorio basado en el estado
        $templateKey = $this->determineReminderTemplate($return, $reminderType);

        $emailData = [
            'return' => $return,
            'days_pending' => $return->created_at->diffInDays(now()),
            'days_until_expiration' => $this->calculateDaysUntilExpiration($return),
            'template' => $this->emailTemplates[$templateKey]['template'],
            'subject' => str_replace(':return_number', $return->number, $this->emailTemplates[$templateKey]['subject']),
            'reminder_type' => $reminderType
        ];

        $communication = $this->createCommunicationRecord($return, $templateKey);

        try {
            $attachments = $this->prepareAttachments($return, $templateKey);
            $this->sendEmail($communication, $emailData, $attachments);
            $communication->markAsSent();

            $this->logNotificationEvent($return, 'reminder_sent', [
                'reminder_type' => $reminderType,
                'days_pending' => $emailData['days_pending']
            ]);

            return $communication;

        } catch (\Exception $e) {
            $communication->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Enviar notificación masiva a múltiples devoluciones
     */
    public function sendBulkNotification(array $returnIds, string $template, array $customData = []): array
{
    $results = [
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];

    $returns = ReturnRequest::whereIn('id', $returnIds)->get();

        foreach ($returns as $return) {
            try {
                if ($template === 'custom') {
                    $this->sendCustomEmail($return, array_merge($customData, [
                        'recipient' => $return->customer_email
                    ]));
                } else {
                    $this->notifyStatusChange($return);
                }

                $results['success']++;
                $results['details'][] = [
                    'return_id' => $return->id,
                    'status' => 'success'
                ];

            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'return_id' => $return->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Reenviar comunicación fallida
     */
    public function resendCommunication(ReturnCommunication $communication): bool
{
    if ($communication->status === ReturnCommunication::STATUS_SENT) {
        throw new \Exception('Esta comunicación ya fue enviada exitosamente');
    }

    try {
        // Incrementar contador de intentos
        $attempts = ($communication->metadata['attempts'] ?? 0) + 1;
        $communication->update([
            'metadata' => array_merge($communication->metadata ?? [], [
                'attempts' => $attempts,
                'last_attempt' => now()->toIso8601String()
            ])
        ]);

        // Preparar datos según el tipo de comunicación
        if ($communication->template_used === 'custom') {
            $emailData = [
                'subject' => $communication->subject,
                'content' => $communication->content,
                'template' => 'custom'
            ];
        } else {
            $emailData = $this->prepareEmailData($communication->return);
        }

        // Reenviar
        $this->sendEmail($communication, $emailData);
        $communication->markAsSent();

        return true;

    } catch (\Exception $e) {
        $communication->markAsFailed($e->getMessage());

        // Si excede el máximo de intentos, marcar como fallido permanentemente
        if ($attempts >= $this->retryConfig['max_attempts']) {
            $communication->update([
                'metadata' => array_merge($communication->metadata ?? [], [
                    'permanently_failed' => true,
                    'final_error' => $e->getMessage()
                ])
            ]);
        }

        throw $e;
    }
}

    /**
     * Obtener historial de comunicaciones con filtros
     */
    public function getCommunicationHistory(ReturnRequest $return, array $filters = []): array
    {
        $query = $return->communications();

        // Aplicar filtros
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $communications = $query->orderBy('created_at', 'desc')->get();

        return $communications->map(function ($communication) {
            return [
                'id' => $communication->id,
                'type' => $communication->type,
                'type_label' => $this->getTypeLabel($communication->type),
                'subject' => $communication->subject,
                'status' => $communication->status,
                'status_label' => $this->getStatusLabel($communication->status),
                'sent_at' => $communication->sent_at?->format('d/m/Y H:i'),
                'read_at' => $communication->read_at?->format('d/m/Y H:i'),
                'sent_by' => $communication->sent_by,
                'recipient' => $communication->recipient,
                'template_used' => $communication->template_used,
                'attempts' => $communication->metadata['attempts'] ?? 1,
                'can_resend' => $this->canResend($communication)
            ];
        })->toArray();
    }

    /**
     * Marcar comunicación como leída (para tracking de emails)
     */
    public function markAsRead(string $trackingId): void
{
    $communication = ReturnCommunication::where('metadata->tracking_id', $trackingId)
        ->first();

    if ($communication && $communication->status === ReturnCommunication::STATUS_SENT) {
        $communication->markAsRead();

        $this->logNotificationEvent($communication->return, 'email_read', [
            'communication_id' => $communication->id,
            'read_at' => now()->toIso8601String()
        ]);
    }
}

    /**
     * Obtener estadísticas de comunicaciones
     */
    public function getStatistics(Carbon $from = null, Carbon $to = null): array
{
    $query = ReturnCommunication::query();

    if ($from) {
        $query->where('created_at', '>=', $from);
    }
    if ($to) {
        $query->where('created_at', '<=', $to);
    }

    return [
        'total' => $query->count(),
        'by_status' => $query->groupBy('status')
            ->select('status', DB::raw('count(*) as count'))
            ->pluck('count', 'status')
            ->toArray(),
        'by_type' => $query->groupBy('type')
            ->select('type', DB::raw('count(*) as count'))
            ->pluck('count', 'type')
            ->toArray(),
        'by_template' => $query->whereNotNull('template_used')
            ->groupBy('template_used')
            ->select('template_used', DB::raw('count(*) as count'))
            ->pluck('count', 'template_used')
            ->toArray(),
        'delivery_rate' => $this->calculateDeliveryRate($query),
        'read_rate' => $this->calculateReadRate($query),
        'average_read_time' => $this->calculateAverageReadTime($query)
    ];
}

    // Métodos privados de apoyo

    private function createCommunicationRecord(ReturnRequest $return, string $templateKey): ReturnCommunication
    {
        $template = $this->emailTemplates[$templateKey] ?? null;

        if (!$template) {
            throw new \Exception("Plantilla no encontrada: {$templateKey}");
        }

        return $return->communications()->create([
            'type' => ReturnCommunication::TYPE_EMAIL,
            'recipient' => $return->customer_email,
            'subject' => str_replace(':return_number', $return->number, $template['subject']),
            'content' => '', // Se llenará con el contenido renderizado
            'template_used' => $templateKey,
            'sent_by' => auth()->user()->name ?? 'Sistema',
            'metadata' => [
                'tracking_id' => $this->generateTrackingId(),
                'return_status' => $return->status,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);
    }

    private function prepareEmailData(ReturnRequest $return, ?string $previousStatus = null): array
    {
        $template = $this->emailTemplates[$return->status] ?? $this->emailTemplates['pending'];

        return [
            'return' => $return,
            'previous_status' => $previousStatus,
            'previous_status_label' => $previousStatus ? $this->getStatusLabel($previousStatus) : null,
            'template' => $template['template'],
            'subject' => str_replace(':return_number', $return->number, $template['subject']),
            'tracking_url' => $this->generateTrackingUrl($return),
            'return_url' => route('customer.returns.show', ['return' => $return->id]),
            'portal_url' => route('customer.returns.search'),
            'costs_summary' => $this->getCostsSummary($return),
            'estimated_days' => $this->getEstimatedDays($return->status),
            'support_email' => config('returns.support_email', 'soporte@ejemplo.com'),
            'support_phone' => config('returns.support_phone', '900 123 456')
        ];
    }

    private function prepareAttachments(ReturnRequest $return, string $templateKey): array
    {
        $attachments = [];
        $templateConfig = $this->emailTemplates[$templateKey] ?? null;

        if (!$templateConfig || empty($templateConfig['attachments'])) {
            return $attachments;
        }

        foreach ($templateConfig['attachments'] as $attachmentType) {
            switch ($attachmentType) {
                case 'return_label':
                    if ($return->label_path && Storage::exists($return->label_path)) {
                        $attachments[] = [
                            'path' => Storage::path($return->label_path),
                            'name' => "etiqueta_devolucion_{$return->number}.pdf",
                            'mime' => 'application/pdf'
                        ];
                    }
                    break;

                case 'receipt':
                    $receiptPath = $this->generateReceipt($return);
                    if ($receiptPath) {
                        $attachments[] = [
                            'path' => $receiptPath,
                            'name' => "recibo_devolucion_{$return->number}.pdf",
                            'mime' => 'application/pdf'
                        ];
                    }
                    break;

                case 'refund_receipt':
                    $refundPath = $this->generateRefundReceipt($return);
                    if ($refundPath) {
                        $attachments[] = [
                            'path' => $refundPath,
                            'name' => "recibo_reembolso_{$return->number}.pdf",
                            'mime' => 'application/pdf'
                        ];
                    }
                    break;
            }
        }

        return $attachments;
    }

    private function sendEmail(ReturnCommunication $communication, array $emailData, array $attachments = []): void
    {
        $mailable = new ReturnStatusMail(array_merge($emailData, [
            'communication' => $communication,
            'attachments' => $attachments
        ]));

        $queue = $this->determineQueue($communication);

        Mail::to($communication->recipient)->queue($mailable->onQueue($queue));
    }

    private function validateCustomEmailData(array $data): void
    {
        $required = ['subject', 'content'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("El campo '{$field}' es requerido");
            }
        }

        if (!empty($data['recipient']) && !filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("El email del destinatario no es válido");
        }
    }

    private function wrapInTemplate(string $content, ReturnRequest $return): string
    {
        return view('emails.layouts.custom', [
            'content' => $content,
            'return' => $return,
            'footer' => true
        ])->render();
    }

    private function hasRecentReminder(ReturnRequest $return, string $type): bool
    {
        $hoursThreshold = config('returns.notifications.reminder_cooldown_hours', 24);

        return $return->communications()
            ->where('template_used', 'LIKE', '%reminder%')
            ->where('metadata->reminder_type', $type)
            ->where('created_at', '>', now()->subHours($hoursThreshold))
            ->exists();
    }

    private function determineReminderTemplate(ReturnRequest $return, string $type): string
    {
        if ($return->status === 'approved' && !$return->tracking_number) {
            return 'shipping_reminder';
        }

        return 'reminder';
    }

    private function calculateDaysUntilExpiration(ReturnRequest $return): int
    {
        $expirationDays = config('returns.expiration_days', 30);
        $daysSinceCreated = $return->created_at->diffInDays(now());

        return max(0, $expirationDays - $daysSinceCreated);
    }

    private function getCostsSummary(ReturnRequest $return): ?array
    {
        if (!$return->costs || $return->costs->isEmpty()) {
            return null;
        }

        return [
            'original_amount' => $return->original_amount,
            'total_deductions' => $return->total_costs,
            'final_refund' => $return->final_refund,
            'deductions' => $return->costs->map(function ($cost) {
                return [
                    'type' => $cost->cost_type_label,
                    'amount' => $cost->amount,
                    'description' => $cost->description
                ];
            })->toArray()
        ];
    }

    private function generateTrackingId(): string
    {
        return 'track_' . uniqid() . '_' . time();
    }

    private function generateTrackingUrl(ReturnRequest $return): string
    {
        $communication = $return->communications()->latest()->first();

        if (!$communication || !isset($communication->metadata['tracking_id'])) {
            return '';
        }

        return route('email.track', ['id' => $communication->metadata['tracking_id']]);
    }

    private function generateReceipt(ReturnRequest $return): ?string
    {
        // Aquí implementarías la generación del PDF del recibo
        // Por ejemplo, usando dompdf o similar
        return null;
    }

    private function generateRefundReceipt(ReturnRequest $return): ?string
    {
        // Aquí implementarías la generación del PDF del recibo de reembolso
        return null;
    }

    private function determineQueue(ReturnCommunication $communication): string
    {
        // Prioridad alta para ciertos tipos de comunicaciones
        $highPriority = ['approved', 'rejected', 'refund_processed'];

        if (in_array($communication->template_used, $highPriority)) {
            return 'high-priority-emails';
        }

        return config('returns.notifications.queue', 'emails');
    }

    private function getEstimatedDays(string $status): array
    {
        $estimates = [
            'pending' => ['min' => 1, 'max' => 3],
            'approved' => ['min' => 0, 'max' => 0],
            'processing' => ['min' => 2, 'max' => 5],
            'completed' => ['min' => 5, 'max' => 7]
        ];

        return $estimates[$status] ?? ['min' => 0, 'max' => 0];
    }

    private function canResend(ReturnCommunication $communication): bool
    {
        if ($communication->status === ReturnCommunication::STATUS_SENT) {
            return false;
        }

        $attempts = $communication->metadata['attempts'] ?? 0;
        $permanentlyFailed = $communication->metadata['permanently_failed'] ?? false;

        return !$permanentlyFailed && $attempts < $this->retryConfig['max_attempts'];
    }

    private function getTypeLabel(string $type): string
    {
        $labels = [
            'email' => 'Email',
            'sms' => 'SMS',
            'internal_note' => 'Nota Interna'
        ];

        return $labels[$type] ?? $type;
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Pendiente',
            'sent' => 'Enviado',
            'failed' => 'Fallido',
            'read' => 'Leído'
        ];

        return $labels[$status] ?? $status;
    }

    private function calculateDeliveryRate($query): float
    {
        $total = $query->count();
        if ($total === 0) return 0;

        $sent = $query->where('status', ReturnCommunication::STATUS_SENT)->count();

        return round(($sent / $total) * 100, 2);
    }

    private function calculateReadRate($query): float
    {
        $sent = $query->where('status', ReturnCommunication::STATUS_SENT)->count();
        if ($sent === 0) return 0;

        $read = $query->where('status', ReturnCommunication::STATUS_READ)->count();

        return round(($read / $sent) * 100, 2);
    }

    private function calculateAverageReadTime($query): ?float
    {
        $readCommunications = $query->where('status', ReturnCommunication::STATUS_READ)
            ->whereNotNull('sent_at')
            ->whereNotNull('read_at')
            ->get();

        if ($readCommunications->isEmpty()) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($readCommunications as $comm) {
            $totalMinutes += $comm->sent_at->diffInMinutes($comm->read_at);
        }

        return round($totalMinutes / $readCommunications->count(), 2);
    }

    private function logNotificationEvent(ReturnRequest $return, string $event, array $data = []): void
    {
        Log::info("Return notification event: {$event}", array_merge([
            'return_id' => $return->id,
            'return_number' => $return->number,
            'customer_email' => $return->customer_email,
            'timestamp' => now()->toIso8601String()
        ], $data));
    }

}
