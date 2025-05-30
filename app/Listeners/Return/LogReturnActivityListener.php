<?php

namespace App\Listeners\Return;

use App\Events\Return\ReturnCreated;
use App\Events\Return\ReturnStatusChanged;
use App\Events\Return\ReturnCompleted;
use App\Events\Return\ReturnPaymentProcessed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogReturnActivityListener
{
    /**
     * Handle return created event.
     */
    public function handleReturnCreated(ReturnCreated $event): void
    {
        $this->logActivity('return_created', $event->getEventData(), [
            'level' => 'info',
            'channel' => 'returns',
            'tags' => ['return', 'created', $event->createdBy],
            'context' => [
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent
            ]
        ]);

        // Registrar en tabla de auditoría si es necesario
        $this->recordAuditEntry('return_created', $event->return->id_return_request, $event->getEventData());
    }

    /**
     * Handle return status changed event.
     */
    public function handleReturnStatusChanged(ReturnStatusChanged $event): void
    {
        $this->logActivity('return_status_changed', $event->getEventData(), [
            'level' => 'info',
            'channel' => 'returns',
            'tags' => ['return', 'status_change', $event->getTransitionType()],
            'context' => [
                'user_id' => auth()->id(),
                'employee_id' => $event->changedBy,
                'session_id' => session()->getId(),
                'transition_type' => $event->getTransitionType()
            ]
        ]);

        // Log especial para transiciones importantes
        if ($event->isCompleted()) {
            $this->logActivity('return_completed_success', array_merge($event->getEventData(), [
                'processing_time_days' => $event->return->created_at->diffInDays(now()),
                'final_status' => $event->newStatus->getTranslation()->name ?? 'Desconocido'
            ]), [
                'level' => 'notice',
                'channel' => 'returns',
                'tags' => ['return', 'completed', 'success']
            ]);
        }

        if ($event->isRejected()) {
            $this->logActivity('return_rejected', array_merge($event->getEventData(), [
                'rejection_reason' => $event->description
            ]), [
                'level' => 'warning',
                'channel' => 'returns',
                'tags' => ['return', 'rejected']
            ]);
        }

        $this->recordAuditEntry('return_status_changed', $event->return->id_return_request, $event->getEventData());
    }

    /**
     * Handle return completed event.
     */
    public function handleReturnCompleted(ReturnCompleted $event): void
    {
        $eventData = array_merge($event->getEventData(), [
            'satisfaction_metrics' => $event->getSatisfactionMetrics()
        ]);

        $this->logActivity('return_completed', $eventData, [
            'level' => 'notice',
            'channel' => 'returns',
            'tags' => ['return', 'completed', $event->completionType],
            'context' => [
                'completion_type' => $event->completionType,
                'total_amount' => $event->totalAmount,
                'processing_time' => $event->getProcessingTimeDays(),
                'completed_by' => $event->completedBy
            ]
        ]);

        // Métricas de negocio
        $this->logBusinessMetrics('return_completion', [
            'completion_type' => $event->completionType,
            'processing_days' => $event->getProcessingTimeDays(),
            'amount' => $event->totalAmount,
            'within_sla' => $event->getSatisfactionMetrics()['is_within_sla']
        ]);

        $this->recordAuditEntry('return_completed', $event->return->id_return_request, $eventData);
    }

    /**
     * Handle return payment processed event.
     */
    public function handleReturnPaymentProcessed(ReturnPaymentProcessed $event): void
    {
        $level = $event->isSuccessful() ? 'info' : 'error';
        $tags = ['return', 'payment', $event->payment->payment_status, $event->payment->payment_method];

        $this->logActivity('return_payment_processed', $event->getEventData(), [
            'level' => $level,
            'channel' => 'payments',
            'tags' => $tags,
            'context' => [
                'payment_method' => $event->payment->payment_method,
                'amount' => $event->payment->amount,
                'transaction_id' => $event->payment->transaction_id,
                'processed_by' => $event->processedBy
            ]
        ]);

        // Log especial para fallos de pago
        if ($event->isFailed()) {
            $this->logActivity('return_payment_failed', array_merge($event->getEventData(), [
                'failure_context' => 'Payment processing failed for return'
            ]), [
                'level' => 'error',
                'channel' => 'payments',
                'tags' => ['return', 'payment', 'failed']
            ]);
        }

        $this->recordAuditEntry('return_payment_processed', $event->return->id_return_request, $event->getEventData());
    }

    /**
     * Log structured activity
     */
    private function logActivity(string $activity, array $data, array $options = []): void
    {
        $level = $options['level'] ?? 'info';
        $channel = $options['channel'] ?? 'default';
        $tags = $options['tags'] ?? [];
        $context = $options['context'] ?? [];

        $logData = [
            'activity' => $activity,
            'data' => $data,
            'tags' => $tags,
            'context' => $context,
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel($channel)->{$level}("Return Activity: {$activity}", $logData);
    }

    /**
     * Log business metrics
     */
    private function logBusinessMetrics(string $metric, array $data): void
    {
        $metricsData = [
            'metric' => $metric,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'date' => now()->format('Y-m-d'),
            'hour' => now()->format('H')
        ];

        Log::channel('metrics')->info("Business Metric: {$metric}", $metricsData);
    }

    /**
     * Record audit entry in database
     */
    private function recordAuditEntry(string $action, int $returnId, array $data): void
    {
        try {
            DB::table('return_audit_log')->insert([
                'return_id' => $returnId,
                'action' => $action,
                'data' => json_encode($data),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            // No fallar si no se puede guardar en auditoría
            Log::warning('Failed to record audit entry', [
                'return_id' => $returnId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the events this listener should handle
     */
    public function subscribe($events): array
    {
        return [
            ReturnCreated::class => 'handleReturnCreated',
            ReturnStatusChanged::class => 'handleReturnStatusChanged',
            ReturnCompleted::class => 'handleReturnCompleted',
            ReturnPaymentProcessed::class => 'handleReturnPaymentProcessed',
        ];
    }
}
