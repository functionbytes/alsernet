<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Helpdesk\Models\Conversation;

class DocumentFileController extends Controller
{
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
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $conversationEmail = mb_strtolower(trim((string) $customer?->email));
        $documentEmail = mb_strtolower(trim((string) $document->customer_email));

        abort_if($conversationEmail === '' || $conversationEmail !== $documentEmail, 404);

        $media = $document->getMedia('documents')
            ->first(fn ($item) => $item->getCustomProperty('document_type') === $docType);

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el archivo del tipo indicado.',
            ], 404);
        }

        $media->delete();
        $document->syncUploadedDocumentsJson();

        // Only revert to "awaiting_documents" when the deletion actually leaves a
        // required document missing — deleting an extra file from an otherwise
        // complete expediente must not reset its status incondicionalmente.
        if ($document->getMissingDocuments() !== []) {
            $awaitingId = DocumentStatus::query()->where('key', 'awaiting_documents')->value('id');

            if ($awaitingId && $document->status_id !== $awaitingId) {
                $document->status_id = $awaitingId;
                $document->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado correctamente.',
            'uploadedDocuments' => $document->uploaded_documents,
            'missingDocuments' => $document->getMissingDocuments(),
            'currentStatus' => $document->status?->key,
        ]);
    }
}
