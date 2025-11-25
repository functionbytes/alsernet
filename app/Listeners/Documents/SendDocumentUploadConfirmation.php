<?php

namespace App\Listeners\Documents;

use App\Events\Documents\DocumentUploaded;
use App\Mail\Documents\DocumentUploadedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDocumentUploadConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(DocumentUploaded $event): void
    {
        $document = $event->document->fresh(['customer', 'order']);

        if (!$document) {
            return;
        }

        $recipient = $document->customer?->email;

        if (!$recipient) {
            Log::warning('Document upload confirmation skipped: missing customer email', [
                'document_uid' => $document->uid ?? null,
                'order_id' => $document->order_id,
            ]);
            return;
        }

        try {
            Mail::to($recipient)->send(new DocumentUploadedMail($document));
        } catch (\Throwable $exception) {
            Log::error('Unable to send document upload confirmation', [
                'document_uid' => $document->uid ?? null,
                'order_id' => $document->order_id,
                'recipient' => $recipient,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
