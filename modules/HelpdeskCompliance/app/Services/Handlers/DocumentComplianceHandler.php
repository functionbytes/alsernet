<?php

namespace Modules\HelpdeskCompliance\Services\Handlers;

use Modules\Document\Entities\Document;
use Modules\HelpdeskDocument\Support\PhoneMatcher;

/**
 * Cascades a GDPR erasure to the customer's Document expedientes (KYC files:
 * DNI, licencias, etc.). Documents are not linked by customer_id but by
 * customer_email / customer_cellphone_normalized (the same matching keys used
 * by HelpdeskDocument's ConversationDocumentLinker), so the handler receives
 * the email/phones captured BEFORE the core deletion anonymized or removed the
 * Customer row.
 *
 * Hard delete removes the Document rows entirely — Spatie MediaLibrary's
 * InteractsWithMedia deletes the associated media rows and physical files on
 * model deletion (Document has no SoftDeletes). Soft delete keeps the row for
 * statistics but redacts every customer PII field and deletes all media files
 * (the KYC attachments are PII themselves, mirroring how the core soft delete
 * removes conversation attachments).
 *
 * NOTA: los documentos viven en la conexión por defecto, no en 'helpdesk', así
 * que la transacción del job no los cubre; el handler es idempotente (borrar/
 * redactar lo ya borrado/redactado no hace nada) y el job reintenta.
 */
class DocumentComplianceHandler
{
    /**
     * @param  array<int, string>  $customerPhones  Teléfonos sin normalizar (phone / whatsapp_phone).
     * @return array{module: string, documents: int, media_files: int, mode: string}
     */
    public function handle(?string $customerEmail, array $customerPhones, bool $hard): array
    {
        $email = trim((string) $customerEmail);

        $normalizedPhones = array_values(array_unique(array_filter(array_map(
            static fn (string $phone): ?string => PhoneMatcher::normalize($phone),
            $customerPhones
        ))));

        if ($email === '' && $normalizedPhones === []) {
            return ['module' => 'HelpdeskDocument', 'documents' => 0, 'media_files' => 0, 'mode' => 'skipped'];
        }

        $documents = 0;
        $mediaFiles = 0;

        Document::query()
            ->with('media')
            ->where(function ($query) use ($email, $normalizedPhones): void {
                if ($email !== '') {
                    // customer_email es case-insensitive (utf8mb4_unicode_ci) e
                    // indexado: la igualdad simple ya matchea sin LOWER().
                    $query->orWhere('customer_email', $email);
                }

                if ($normalizedPhones !== []) {
                    $query->orWhereIn('customer_cellphone_normalized', $normalizedPhones);
                }
            })
            // ->with('media') precarga la relación una vez por lote de 100 en
            // vez de 2 queries por documento (count()+get()) — con un cliente
            // de muchos expedientes esto se acercaba al timeout del job
            // (ProcessComplianceCascadeJob, 120s).
            ->chunkById(100, function ($chunk) use (&$documents, &$mediaFiles, $hard): void {
                foreach ($chunk as $document) {
                    $mediaFiles += $document->media->count();

                    if ($hard) {
                        // delete() dispara el hook de InteractsWithMedia que borra
                        // filas de media y ficheros físicos (sin SoftDeletes aquí).
                        $document->delete();
                    } else {
                        // Borra TODOS los adjuntos (colecciones 'documents',
                        // 'additional_attachments' y cualquier otra): son PII.
                        foreach ($document->media as $media) {
                            $media->delete();
                        }

                        // El hook saving() del modelo re-sincroniza
                        // customer_cellphone_normalized a null al anular cellphone.
                        $document->update([
                            'customer_firstname' => null,
                            'customer_lastname' => null,
                            'customer_email' => null,
                            'customer_cellphone' => null,
                            'customer_dni' => null,
                            'customer_company' => null,
                            'uploaded_documents' => [],
                            'additional_attachments' => [],
                        ]);
                    }

                    $documents++;
                }
            });

        return [
            'module' => 'HelpdeskDocument',
            'documents' => $documents,
            'media_files' => $mediaFiles,
            'mode' => $hard ? 'deleted' : 'redacted',
        ];
    }
}
