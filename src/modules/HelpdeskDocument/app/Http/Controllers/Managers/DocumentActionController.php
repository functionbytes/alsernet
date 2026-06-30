<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;
use Modules\Document\Http\Controllers\Api\DocumentsController;
use Modules\Document\Http\Controllers\Api\DocumentValidationController;
use Modules\Helpdesk\Models\Conversation;

/**
 * Helpdesk-scoped proxy for the document expediente's mutating actions.
 *
 * The inbox document tab used to wire upload/approve/reject/assign/notes/
 * send-* / attachment / update actions straight to the `api.documents.*`
 * routes, which were only protected by `auth:web` — any authenticated user
 * could mutate any document by UID. These proxy endpoints enforce the helpdesk
 * permission (`can:helpdesk.conversations.view`, applied on the routes) plus the
 * same customer/email ownership guard as DocumentPanelController/DocumentFileController,
 * then delegate the real work to the Document module so no business logic or
 * lifecycle event is duplicated.
 */
class DocumentActionController extends Controller
{
    public function __construct(
        private readonly DocumentValidationController $validation,
        private readonly DocumentsController $documents,
    ) {}

    public function upload(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->uploadDocument($request, $document->uid);
    }

    public function assign(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->assignUser($request, $document->uid);
    }

    public function approveStage(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->approveStage($request, $document->uid);
    }

    public function rejectStage(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->rejectStage($request, $document->uid);
    }

    public function sendNotification(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendNotification($request, $document->uid);
    }

    public function sendReminder(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendReminder($request, $document->uid);
    }

    public function sendUploadConfirmation(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendUploadConfirmation($request, $document->uid);
    }

    public function sendApproval(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendApproval($request, $document->uid);
    }

    public function sendMissing(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendMissingDocuments($request, $document->uid);
    }

    public function sendRejection(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendRejection($request, $document->uid);
    }

    public function sendCustomEmail(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendCustomEmail($request, $document->uid);
    }

    public function addNote(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->addNote($request, $document->uid);
    }

    public function deleteNote(Conversation $conversation, Document $document, int $noteId, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->deleteNote($request, $document->uid, $noteId);
    }

    public function uploadAttachment(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->uploadAdditionalAttachment($request, $document->uid);
    }

    public function deleteAttachment(Conversation $conversation, Document $document, int $mediaId): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->deleteAdditionalAttachment($document->uid, $mediaId);
    }

    public function update(Conversation $conversation, Document $document, Request $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        // Force the target UID to the ownership-checked document so a tampered
        // body `uid` cannot redirect the update to an unrelated expediente.
        $request->merge(['uid' => $document->uid]);

        return $this->documents->update($request);
    }

    /**
     * Guard: the document must belong to the conversation's customer (email match),
     * mirroring DocumentPanelController/DocumentFileController, so an agent cannot
     * mutate an unrelated expediente through these endpoints.
     */
    private function assertDocumentBelongsToConversation(Conversation $conversation, Document $document): void
    {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $conversationEmail = mb_strtolower(trim((string) $customer?->email));
        $documentEmail = mb_strtolower(trim((string) $document->customer_email));

        abort_if($conversationEmail === '' || $conversationEmail !== $documentEmail, 404);
    }
}
