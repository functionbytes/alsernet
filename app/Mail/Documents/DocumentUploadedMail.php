<?php

namespace App\Mail\Documents;

use App\Models\Order\Document;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentUploadedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(protected Document $document)
    {
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $customer = $this->document->customer;
        $order = $this->document->order;

        $customerName = trim(sprintf(
            '%s %s',
            $customer?->firstname ?? '',
            $customer?->lastname ?? ''
        ));

        $orderReference = $this->document->label ?? $order?->reference ?? $this->document->order_id;

        $uploadedAt = $this->document->upload_at
            ? Carbon::parse($this->document->upload_at)
                ->timezone(config('app.timezone', 'UTC'))
                ->format('d/m/Y H:i')
            : null;

        return $this->subject('Confirmación de recepción de documentos ' . ($orderReference ? '#'.$orderReference : ''))
            ->view('mailers.documents.uploaded')
            ->with([
                'document' => $this->document,
                'customerName' => $customerName ?: $customer?->email,
                'orderReference' => $orderReference,
                'documentType' => $this->document->type,
                'uploadedAt' => $uploadedAt,
            ]);
    }
}
