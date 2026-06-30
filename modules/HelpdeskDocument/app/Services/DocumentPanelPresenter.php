<?php

namespace Modules\HelpdeskDocument\Services;

use Illuminate\Support\Collection;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;

/**
 * Builds the view-model arrays for the document tab of the inbox right-panel.
 *
 * Two shapes:
 *  - list(): lightweight cards for the expediente list (version 1)
 *  - present(): the full detail (files, missing, notes, mails, actions) consumed
 *    by the detail partial (version 2). Mirrors the array the right-panel used
 *    to build inline.
 */
class DocumentPanelPresenter
{
    /**
     * Lightweight list items for the expediente cards.
     *
     * @param  Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    public function list(Collection $documents): array
    {
        return $documents->map(function (Document $doc): array {
            $statusKey = $doc->status?->key ?? 'pending';

            $group = match ($statusKey) {
                'approved', 'completed' => 'approved',
                'rejected', 'cancelled' => 'rejected',
                default => 'pending',
            };

            $required = collect((array) ($doc->required_documents ?? []));
            $fileTotal = $required->count();
            $fileUploaded = $doc->getMedia('documents')->count();
            $progressPct = $fileTotal > 0
                ? (int) round(($fileUploaded / $fileTotal) * 100)
                : ($fileUploaded > 0 ? 100 : 0);

            $progressAccent = match ($group) {
                'approved' => '#90bb13',
                'rejected' => '#FA896B',
                default => '#FEC90F',
            };

            $customerName = trim(($doc->customer_firstname ?? '') . ' ' . ($doc->customer_lastname ?? ''));

            return [
                'id' => (int) $doc->id,
                'uid' => $doc->uid,
                'order_reference' => $doc->order_reference ?: '—',
                'type_label' => $doc->documentType?->label ?? 'Documento',
                'customer_name' => $customerName ?: '',
                'status_key' => $statusKey,
                'status_label' => $doc->status?->label ?? 'Pendiente',
                'file_count' => $fileUploaded,
                'created_human' => $doc->created_at?->format('d/m/Y') ?? '—',
                'ago_human' => $doc->created_at?->diffForHumans(short: true) ?? '—',
                'group' => $group,
                'description' => $doc->documentType?->description ?? '',
                'file_total' => max($fileTotal, $fileUploaded),
                'file_uploaded' => $fileUploaded,
                'progress_pct' => $progressPct,
                'progress_accent' => $progressAccent,
            ];
        })->all();
    }

    /**
     * Full detail view-model for a single document.
     *
     * @return array<string, mixed>
     */
    public function present(Document $document): array
    {
        $document->loadMissing(['documentType', 'status', 'source', 'documentLoad', 'products', 'media', 'notes', 'mails', 'actions']);

        $statusKey = $document->status?->key ?? 'pending';

        try {
            $reqLabels = $document->getRequiredDocumentsWithLabels();
        } catch (\Throwable $e) {
            $reqLabels = collect((array) $document->required_documents)
                ->mapWithKeys(fn ($k) => [$k => ucfirst(str_replace('_', ' ', $k))])->all();
        }

        $files = [];
        $uploadedTypes = [];
        foreach ($document->getMedia('documents') as $m) {
            $dt = $m->getCustomProperty('document_type');
            if ($dt) {
                $uploadedTypes[] = $dt;
            }
            $files[] = [
                'id' => $m->id,
                'doc_type' => $dt,
                'name' => $reqLabels[$dt] ?? $m->file_name,
                'file_name' => $m->file_name,
                'type_label' => $reqLabels[$dt] ?? ($dt ?? 'Documento'),
                'status_key' => $statusKey,
                'mime' => $m->mime_type,
                'size_human' => $this->formatBytes($m->size),
                'uploaded_human' => $m->created_at?->format('d/m/Y H:i') ?? '—',
                'url' => $m->getUrl(),
            ];
        }

        $missing = [];
        foreach ($reqLabels as $k => $label) {
            if (! in_array($k, $uploadedTypes, true)) {
                $missing[] = ['key' => $k, 'label' => $label];
            }
        }

        $attachments = [];
        try {
            foreach ($document->getMedia('additional_attachments') as $m) {
                $attachments[] = [
                    'id' => $m->id,
                    'name' => $m->getCustomProperty('original_name', $m->file_name),
                    'mime' => $m->mime_type,
                    'size_human' => $this->formatBytes($m->size),
                    'uploaded_human' => $m->created_at?->format('d/m/Y H:i') ?? '—',
                    'url' => $m->getUrl(),
                ];
            }
        } catch (\Throwable $e) {
        }

        $notes = [];
        try {
            foreach ($document->notes->sortByDesc('created_at')->take(20) as $n) {
                $notes[] = [
                    'id' => $n->id,
                    'ts' => $n->created_at?->format('d/m/Y H:i') ?? '—',
                    'text' => $n->note ?? $n->content ?? $n->body ?? '',
                ];
            }
        } catch (\Throwable $e) {
        }

        $mails = [];
        try {
            $mailTypeLabels = DocumentMail::EMAIL_TYPE_LABELS;
            foreach ($document->mails->sortByDesc('sent_at')->take(10) as $mail) {
                $mails[] = [
                    'uid' => $mail->uid,
                    'type' => $mail->email_type,
                    'type_label' => $mailTypeLabels[$mail->email_type] ?? $mail->email_type,
                    'subject' => $mail->subject ?? '—',
                    'sent_at' => $mail->sent_at?->format('d/m/Y H:i') ?? ($mail->created_at?->format('d/m/Y H:i') ?? '—'),
                    'status' => $mail->status ?? 'sent',
                ];
            }
        } catch (\Throwable $e) {
        }

        $actions = [];
        try {
            foreach ($document->actions->sortByDesc('created_at')->take(20) as $a) {
                $actions[] = [
                    'ts' => $a->created_at?->format('d/m H:i') ?? '—',
                    'actor' => $a->performed_by_type ?? 'Sistema',
                    'description' => $a->action_name ?? $a->action_type,
                    'key' => $a->action_type,
                    'label' => $a->action_name ?? $a->action_type,
                ];
            }
        } catch (\Throwable $e) {
        }

        return [
            'id' => (int) $document->id,
            'uid' => $document->uid,
            'order_reference' => $document->order_reference ?? '—',
            'type_label' => $document->documentType?->label ?? 'Documento',
            'doc_type' => $document->documentType?->slug ?? '—',
            'status_key' => $statusKey,
            'status_label' => $document->status?->label ?? 'Pendiente',
            'validation_status' => $document->validation_status,
            'current_stage' => (int) ($document->current_stage ?? 1),
            'total_stages' => (int) ($document->total_stages ?? 1),
            'assigned_user_id' => $document->assigned_user_id,
            'customer_firstname' => $document->customer_firstname ?? '',
            'customer_lastname' => $document->customer_lastname ?? '',
            'customer_name' => trim(($document->customer_firstname ?? '').' '.($document->customer_lastname ?? '')),
            'customer_email' => $document->customer_email ?? '',
            'customer_phone' => $document->customer_cellphone ?? '—',
            'customer_dni' => $document->customer_dni ?? '—',
            'customer_company' => $document->customer_company ?? null,
            'origin' => $document->source?->label ?? '—',
            'system' => $document->documentLoad?->label ?? '—',
            'uploaded_by' => $document->customer_firstname ? trim($document->customer_firstname.' '.$document->customer_lastname) : 'Cliente',
            'created_human' => $document->created_at?->format('d/m/Y H:i') ?? '—',
            'updated_human' => $document->updated_at?->format('d/m/Y H:i') ?? '—',
            'products' => $document->products->map(fn ($p) => [
                'name' => $p->product_name ?? '—',
                'sku' => $p->product_reference ?? '—',
                'qty' => ($p->quantity ?? 1).'ud',
            ])->all(),
            'actions' => $actions,
            'files' => $files,
            'missing' => $missing,
            'attachments' => $attachments,
            'notes' => $notes,
            'mails' => $mails,
            'chat_files' => [],
        ];
    }

    private function formatBytes(mixed $bytes): string
    {
        $bytes = (int) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
