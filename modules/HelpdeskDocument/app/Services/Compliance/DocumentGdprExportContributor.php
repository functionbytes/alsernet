<?php

namespace Modules\HelpdeskDocument\Services\Compliance;

use Modules\Document\Entities\Document;
use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

/**
 * GDPR right-of-access section for the customer's Document expedientes (KYC).
 *
 * Match por email O teléfono normalizado — las mismas claves que usa
 * ConversationDocumentLinker / la cascada de borrado, pero aquí con OR (no
 * fallback): el export legal debe listar todo lo que el borrado borraría.
 *
 * DECISIÓN — los binarios (escaneos de DNI, licencias...) NO se incluyen en el
 * ZIP: un expediente puede acumular decenas de MB en escaneos y el ZIP se
 * construye en /tmp de forma síncrona dentro de la request del admin; el
 * derecho de acceso queda cubierto con los metadatos completos + el inventario
 * de ficheros (nombre, tipo, tamaño, fecha), y el interesado puede pedir
 * copias concretas. Si se quisieran incluir, debería moverse a un job en cola.
 */
class DocumentGdprExportContributor implements GdprExportContributor
{
    public function sectionKey(): string
    {
        return 'documents';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(Customer $customer): array
    {
        $email = trim((string) $customer->email);

        $phones = array_values(array_unique(array_filter(array_map(
            static fn (?string $phone): ?string => PhoneMatcher::normalize($phone),
            [$customer->phone, $customer->whatsapp_phone]
        ))));

        if ($email === '' && $phones === []) {
            return [];
        }

        return Document::query()
            ->with(['status:id,key,label', 'documentType:id,label', 'media'])
            ->where(function ($query) use ($email, $phones): void {
                if ($email !== '') {
                    $query->orWhere('customer_email', $email);
                }

                if ($phones !== []) {
                    $query->orWhereIn('customer_cellphone_normalized', $phones);
                }
            })
            ->orderBy('created_at')
            ->get()
            ->map(fn (Document $document): array => [
                'id' => $document->id,
                'uid' => $document->uid,
                'type' => $document->documentType?->label,
                'status' => $document->status?->label ?? $document->status?->key,
                'customer_firstname' => $document->customer_firstname,
                'customer_lastname' => $document->customer_lastname,
                'customer_email' => $document->customer_email,
                'customer_cellphone' => $document->customer_cellphone,
                'customer_dni' => $document->customer_dni,
                'customer_company' => $document->customer_company,
                'order_reference' => $document->order_reference,
                'required_documents' => $document->required_documents,
                'created_at' => $document->created_at?->toIso8601String(),
                // Inventario de ficheros; los binarios no viajan en el ZIP (ver
                // docblock de la clase).
                'files' => $document->media->map(fn ($media): array => [
                    'file_name' => $media->file_name,
                    'collection' => $media->collection_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'document_type' => $media->getCustomProperty('document_type'),
                    'uploaded_at' => $media->created_at?->toIso8601String(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
