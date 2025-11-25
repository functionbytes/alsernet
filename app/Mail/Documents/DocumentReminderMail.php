<?php

namespace App\Mail\Documents;

use App\Models\Order\Document;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected Document $document)
    {
    }

    public function build(): self
    {
        $document = $this->document->loadMissing('order', 'customer');
        $customer = $document->customer;
        $order = $document->order;

        $customerName = trim(sprintf(
            '%s %s',
            $customer?->firstname ?? '',
            $customer?->lastname ?? ''
        ));

        $orderReference = $document->label ?? $order?->reference ?? $document->order_id;

        $uploadDeadline = $document->created_at
            ? Carbon::parse($document->created_at)->addDays(3)->format('d/m/Y')
            : null;

        $uploadPortalTemplate = config('documents.upload_portal_url');
        $uploadUrl = $uploadPortalTemplate
            ? str_replace('{uid}', $document->uid, rtrim($uploadPortalTemplate))
            : null;

        return $this->subject('Recordatorio: sube la documentación para tu pedido ' . ($orderReference ? '#'.$orderReference : ''))
            ->view('mailers.documents.reminder')
            ->with([
                'document' => $document,
                'customerName' => $customerName ?: $customer?->email,
                'orderReference' => $orderReference,
                'documentType' => $document->type,
                'uploadDeadline' => $uploadDeadline,
                'uploadUrl' => $uploadUrl,
                'documentUid' => $document->uid,
            ]);
    }
}
