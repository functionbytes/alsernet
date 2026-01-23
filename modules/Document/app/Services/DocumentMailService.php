<?php

namespace Modules\Document\Services;

use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;

class DocumentMailService
{
    public static function sendUploadNotification(Document $document): bool
    {
        try {
            $email = $document->customer_email;

            if (! $email) {
                Log::warning('No email found for document notification', [
                    'document_uid' => $document->uid,
                    'order_id' => $document->order_id,
                ]);

                return false;
            }

            $result = DocumentEmailTemplateService::sendInitialRequest($document);

            if ($result) {
                Log::info('Document upload notification sent successfully', [
                    'document_uid' => $document->uid,
                    'recipient' => $email,
                    'order_id' => $document->order_id,
                ]);
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Failed to send document upload notification', [
                'document_uid' => $document->uid ?? null,
                'order_id' => $document->order_id,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function sendReminder(Document $document): bool
    {
        try {

            $document = $document->fresh();

            if (! $document) {
                Log::warning('Document not found for reminder');

                return false;
            }

            if (empty($document->getMissingDocuments())) {
                Log::info('Document is complete, skipping reminder', [
                    'document_uid' => $document->uid,
                ]);

                return false;
            }

            $email = $document->customer_email;

            if (! $email) {
                Log::warning('No email found for document reminder', [
                    'document_uid' => $document->uid,
                    'order_id' => $document->order_id,
                ]);

                return false;
            }

            $result = DocumentEmailTemplateService::sendReminder($document);

            if ($result) {
                Log::info('Document reminder sent successfully', [
                    'document_uid' => $document->uid,
                    'recipient' => $email,
                    'order_id' => $document->order_id,
                ]);
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Failed to send document reminder', [
                'document_uid' => $document->uid ?? null,
                'order_id' => $document->order_id,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function sendUploadedConfirmation(Document $document): bool
    {
        try {
            $email = $document->customer_email;

            if (! $email) {
                Log::warning('No email found for document confirmation', [
                    'document_uid' => $document->uid,
                    'order_id' => $document->order_id,
                ]);

                return false;
            }

            // Usar plantilla de BD
            $result = DocumentEmailTemplateService::sendUploadConfirmation($document);

            if ($result) {
                Log::info('Document uploaded confirmation sent successfully', [
                    'document_uid' => $document->uid,
                    'recipient' => $email,
                    'order_id' => $document->order_id,
                ]);
            }

            return $result;
        } catch (\Throwable $exception) {
            Log::error('Failed to send document uploaded confirmation', [
                'document_uid' => $document->uid ?? null,
                'order_id' => $document->order_id,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    public static function sendAll(Document $document, array $types = ['notification', 'confirmation']): array
    {
        $results = [
            'notification' => false,
            'confirmation' => false,
            'reminder' => false,
        ];

        try {
            if (in_array('notification', $types)) {
                $results['notification'] = self::sendUploadNotification($document);
            }

            if (in_array('confirmation', $types)) {
                $results['confirmation'] = self::sendUploadedConfirmation($document);
            }

            if (in_array('reminder', $types)) {
                $results['reminder'] = self::sendReminder($document);
            }

            return $results;
        } catch (\Throwable $exception) {
            Log::error('Batch email sending failed', [
                'document_uid' => $document->uid ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return $results;
        }
    }
}
