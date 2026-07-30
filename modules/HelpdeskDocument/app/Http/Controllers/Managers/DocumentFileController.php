<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Concerns\AuthorizesConversationDocuments;

class DocumentFileController extends Controller
{
    use AuthorizesConversationDocuments;

    /**
     * Delete a single uploaded file (by document type) from a customer's
     * expediente, scoped to the conversation the agent is working on.
     *
     * This authenticated endpoint replaces the public `api.documents.files.delete`
     * route the inbox tab previously consumed: it enforces the helpdesk permission
     * and the same customer/document ownership guard as the panel, so an agent can
     * only delete files from a document that belongs to the conversation's customer.
     */
    public function destroy(Conversation $conversation, Document $document, string $docType): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        // Un expediente ya aprobado/completado es un registro cerrado: el botón
        // "Eliminar"/"Reemplazar" ya se oculta en el panel (_document-detail y
        // openFileDetail), pero el endpoint debe rechazarlo igual por si llega
        // una petición directa.
        if (in_array($document->status?->key, ['approved', 'completed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un archivo de un expediente ya aprobado.',
            ], 422);
        }

        $media = $document->getMedia('documents')
            ->first(fn ($item) => $item->getCustomProperty('document_type') === $docType);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el archivo del tipo indicado.',
            ], 404);
        }

        $media->delete();

        // getMedia() above cached the "media" relation on this instance; without
        // unsetting it, syncUploadedDocumentsJson() would recompute from that
        // stale, pre-delete collection and keep the just-deleted file listed.
        $document->unsetRelation('media');
        $document->syncUploadedDocumentsJson();

        // Only revert to "awaiting_documents" when the deletion actually leaves a
        // required document missing — deleting an extra file from an otherwise
        // complete expediente must not reset its status incondicionalmente.
        //
        // getMissingDocuments() reaches across into the Document module (document
        // type + requirement translations). The file is already deleted at this
        // point, so a failure there must not turn the whole request into a 500:
        // guard it defensively and report the deletion as successful regardless.
        $missingDocuments = [];

        try {
            $missingDocuments = $document->getMissingDocuments();

            if ($missingDocuments !== []) {
                $awaitingId = DocumentStatus::query()->where('key', 'awaiting_documents')->value('id');

                if ($awaitingId && $document->status_id !== $awaitingId) {
                    $document->status_id = $awaitingId;
                    $document->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('DocumentFile destroy: reversion to awaiting_documents skipped', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado correctamente.',
            'uploadedDocuments' => $document->uploaded_documents,
            'missingDocuments' => $missingDocuments,
            'currentStatus' => $document->status?->key,
        ]);
    }
}
