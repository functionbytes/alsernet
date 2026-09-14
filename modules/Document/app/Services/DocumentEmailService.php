<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Jobs\MailTemplateJob;

class DocumentEmailService
{
    /**
     * Validar email antes de encolar y verificar rate limiting
     */
    private function validateAndDispatch(Document $document, string $emailType, array $emailData = [], ?int $adminId = null): bool
    {
        // Validar que existe email del cliente
        if (empty($document->customer_email)) {
            Log::warning('Cannot send email: missing customer email', [
                'document_uid' => $document->uid,
                'email_type' => $emailType,
            ]);

            return false;
        }

        // Rate limiting: 1 email del mismo tipo por documento cada 60 segundos
        $rateLimitKey = "email_sent:{$document->id}:{$emailType}";

        if (Cache::has($rateLimitKey)) {
            Log::warning('Email rate limit exceeded', [
                'document_uid' => $document->uid,
                'email_type' => $emailType,
                'retry_after' => Cache::get($rateLimitKey),
            ]);

            return false;
        }

        // Marcar en cache por 60 segundos
        Cache::put($rateLimitKey, now()->addSeconds(60), 60);

        // Encolar el job
        MailTemplateJob::dispatch($document, $emailType, $emailData, $adminId);

        return true;
    }

    public function sendInitialRequest(Document $document, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'request', [], $adminId);
    }

    public function sendReminder(Document $document, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'reminder', [], $adminId);
    }

    public function sendMissingDocumentsRequest(Document $document, array $missingDocs, ?string $reason = null, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'missing', [
            'missing_docs' => $missingDocs,
            'notes' => $reason,
        ], $adminId);
    }

    public function sendApprovalEmail(Document $document, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'approval', [], $adminId);
    }

    public function sendRejectionEmail(Document $document, string $reason, array $rejectedDocs = [], ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'rejection', [
            'reason' => $reason,
            'rejected_docs' => $rejectedDocs,
        ], $adminId);
    }

    public function sendCompletionEmail(Document $document, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'approval', [], $adminId);
    }

    public function sendUploadConfirmation(Document $document, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'upload', [], $adminId);
    }

    public function sendCustomEmail(Document $document, string $subject, string $message, ?int $adminId = null): bool
    {
        return $this->validateAndDispatch($document, 'custom', [
            'subject' => $subject,
            'message' => $message,
        ], $adminId);
    }

    public function processDocumentUpload(Document $document): void
    {
        $document = $document->fresh();

        if (! $document) {
            return;
        }

        if ($document->status_id) {
            $currentStatus = $document->status()->first();
            $allowedPreviousStatuses = ['pending', 'awaiting_documents'];

            if ($currentStatus && in_array($currentStatus->key, $allowedPreviousStatuses)) {
                $receivedStatus = DocumentStatus::where('key', 'received')->first();
                if ($receivedStatus) {
                    $document->update(['status_id' => $receivedStatus->id]);

                    Log::info('Document status set to Received', [
                        'document_uid' => $document->uid,
                        'previous_status' => $currentStatus->key,
                        'status_id' => $receivedStatus->id,
                    ]);

                    // Event removed: DocumentStatusChanged no está implementado como evento
                    // El cambio de estado ya se registra en el log arriba
                }
            }
        }

        // Enviar confirmación de carga (validación incluida en sendUploadConfirmation)
        $sent = $this->sendUploadConfirmation($document);

        if ($sent) {
            Log::info('Document upload confirmation job queued', [
                'document_uid' => $document->uid,
                'order_id' => $document->order_id,
                'recipient' => $document->customer_email,
                'sent_method' => 'async',
            ]);
        }
    }
}
