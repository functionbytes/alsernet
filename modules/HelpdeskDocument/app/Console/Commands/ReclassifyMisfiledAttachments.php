<?php

namespace Modules\HelpdeskDocument\Console\Commands;

use Illuminate\Console\Command;
use Modules\Document\Entities\Document;

/**
 * Antes de que ChatGalleryDocumentController::documentMediaCollection()
 * usara Document::getRequiredDocuments() (derivado del DocumentType), comparaba
 * contra la columna denormalizada `required_documents`, que en muchos expedientes
 * quedó vacía/desactualizada. Resultado: subidas legítimas para un tipo requerido
 * real cayeron siempre en additional_attachments en vez de documents, y el
 * checklist de "Documentos requeridos" nunca las contó como completadas.
 *
 * Este comando reclasifica esos archivos históricos hacia la colección correcta,
 * sin tocar duplicados genuinos (varias subidas de prueba para el mismo tipo):
 * solo el primer candidato sin cubrir se mueve por tipo.
 */
class ReclassifyMisfiledAttachments extends Command
{
    protected $signature = 'helpdeskdocument:reclassify-attachments
                            {--document= : Limitar a un expediente por ID}
                            {--apply : Persistir los cambios (por defecto solo reporta, dry-run)}';

    protected $description = 'Mueve adjuntos internos mal categorizados a la colección documents cuando su upload_type coincide con un tipo requerido real y aún no está cubierto';

    public function handle(): int
    {
        $query = Document::query();

        if ($documentId = $this->option('document')) {
            $query->where('id', $documentId);
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply
            ? 'MODO APLICAR: los cambios se van a persistir.'
            : 'MODO DRY-RUN: solo se reporta, nada se modifica (usa --apply para aplicar).');
        $this->newLine();

        $totalScanned = 0;
        $totalMoved = 0;
        $totalDocuments = 0;

        // lazy() en vez de get(): recorre expedientes en lotes sin cargarlos todos
        // en memoria de una vez (puede ser una tabla grande en producción).
        foreach ($query->lazy(200) as $document) {
            $totalScanned++;
            $required = $document->getRequiredDocuments();

            if (empty($required)) {
                continue;
            }

            $existingTypes = $document->getMedia('documents')
                ->map(fn ($m) => $m->getCustomProperty('document_type'))
                ->filter()
                ->all();

            $candidates = $document->getMedia('additional_attachments')
                ->filter(function ($media) use ($required, $existingTypes) {
                    $uploadType = $media->getCustomProperty('upload_type');

                    return $uploadType
                        && in_array($uploadType, $required, true)
                        && ! in_array($uploadType, $existingTypes, true);
                });

            if ($candidates->isEmpty()) {
                continue;
            }

            $totalDocuments++;
            $this->line("Expediente #{$document->id} ({$document->uid}):");

            $claimedThisRun = [];

            foreach ($candidates as $media) {
                $uploadType = $media->getCustomProperty('upload_type');

                if (in_array($uploadType, $claimedThisRun, true)) {
                    $this->line("  - media #{$media->id} ({$media->file_name}) también es '{$uploadType}', pero ya se reclasificó otro con ese tipo — se deja como adjunto.");

                    continue;
                }

                $claimedThisRun[] = $uploadType;
                $this->line("  - media #{$media->id} ({$media->file_name}) → documents [{$uploadType}]");
                $totalMoved++;

                if ($apply) {
                    $media->collection_name = 'documents';
                    $media->setCustomProperty('document_type', $uploadType);
                    $media->save();
                }
            }

            if ($apply) {
                $document->unsetRelation('media');
                $document->syncUploadedDocumentsJson();
                $document->syncAdditionalAttachmentsJson();
            }
        }

        if ($totalScanned === 0) {
            $this->warn('No se encontraron expedientes.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Expedientes afectados: '.$totalDocuments.' · Archivos '.($apply ? 'movidos' : 'a mover').': '.$totalMoved);

        if (! $apply && $totalMoved > 0) {
            $this->comment('Corre de nuevo con --apply para aplicar los cambios.');
        }

        return self::SUCCESS;
    }
}
