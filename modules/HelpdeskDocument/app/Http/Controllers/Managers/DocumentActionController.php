<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Document\Entities\Document;
use Modules\Document\Http\Controllers\Api\DocumentsController;
use Modules\Document\Http\Controllers\Api\DocumentValidationController;
use Modules\Document\Support\DocumentMediaFiles;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Concerns\AuthorizesConversationDocuments;
use Modules\HelpdeskDocument\Http\Requests\Managers\AddDocumentNoteRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\ApproveDocumentStageRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\AssignDocumentValidatorRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\RejectDocumentStageRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\SendCustomDocumentEmailRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\UpdateDocumentClientRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\UploadDocumentAttachmentRequest;
use Modules\HelpdeskDocument\Http\Requests\Managers\UploadDocumentFilesRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helpdesk-scoped proxy for the document expediente's mutating actions.
 *
 * The inbox document tab used to wire upload/approve/reject/assign/notes/
 * send-* / attachment / update actions straight to the `api.documents.*`
 * routes, which were only protected by `auth:web` — any authenticated user
 * could mutate any document by UID. These proxy endpoints enforce the helpdesk
 * permission (applied on the routes) plus the customer/email ownership guard
 * shared via AuthorizesConversationDocuments, then delegate the real work to
 * the Document module so no business logic or lifecycle event is duplicated.
 */
class DocumentActionController extends Controller
{
    use AuthorizesConversationDocuments;

    public function __construct(
        private readonly DocumentValidationController $validation,
        private readonly DocumentsController $documents,
    ) {}

    public function upload(Conversation $conversation, Document $document, UploadDocumentFilesRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->uploadDocument($request, $document->uid);
    }

    public function assign(Conversation $conversation, Document $document, AssignDocumentValidatorRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->assignUser($request, $document->uid);
    }

    public function approveStage(Conversation $conversation, Document $document, ApproveDocumentStageRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->approveStage($request, $document->uid);
    }

    public function rejectStage(Conversation $conversation, Document $document, RejectDocumentStageRequest $request): JsonResponse
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

    public function sendCustomEmail(Conversation $conversation, Document $document, SendCustomDocumentEmailRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->sendCustomEmail($request, $document->uid);
    }

    /**
     * Las notas NO se delegan: el guard del módulo Document
     * (canDocument('add-notes'/'delete-notes')) exige pertenecer a un grupo
     * validador — pensado para su panel admin — y dejaba en 403 a agentes
     * del inbox que ya pasaron helpdesk.documents.manage + ownership. La
     * operación es un create/delete simple sin eventos de ciclo de vida,
     * con el mismo contrato JSON que el delegado.
     */
    public function addNote(Conversation $conversation, Document $document, AddDocumentNoteRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        $note = $document->notes()->create([
            'content' => $request->validated('content'),
            'is_internal' => $request->validated('is_internal') ?? true,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nota agregada correctamente',
            'note' => $note,
        ]);
    }

    public function deleteNote(Conversation $conversation, Document $document, int $noteId): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        $document->notes()->findOrFail($noteId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Nota eliminada correctamente',
        ]);
    }

    public function uploadAttachment(Conversation $conversation, Document $document, UploadDocumentAttachmentRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->uploadAdditionalAttachment($request, $document->uid);
    }

    public function deleteAttachment(Conversation $conversation, Document $document, int $mediaId): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->deleteAdditionalAttachment($document->uid, $mediaId);
    }

    public function update(Conversation $conversation, Document $document, UpdateDocumentClientRequest $request): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        // Force the target UID to the ownership-checked document so a tampered
        // body `uid` cannot redirect the update to an unrelated expediente.
        $request->merge(['uid' => $document->uid]);

        return $this->documents->update($request);
    }

    /**
     * Historial de acciones del expediente. Reemplaza el consumo directo de
     * `api.documents.action-history`, que exige rol super-admin|supervisor y
     * dejaba el botón "Ver historial completo" en 403 para agentes del inbox.
     */
    public function actionHistory(Conversation $conversation, Document $document): JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return $this->validation->getActionHistory($document->uid);
    }

    /**
     * Descarga en ZIP de todos los archivos del expediente. Reemplaza el
     * consumo directo de `api.documents.download-zip` (rol super-admin|
     * supervisor) por el permiso helpdesk + ownership de la conversación.
     */
    public function downloadZip(Conversation $conversation, Document $document): StreamedResponse|JsonResponse
    {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        // 29-sep-2026: delegaba en DocumentValidationController::downloadZip(),
        // que ya no existe (500). Se arma aquí, leyendo cada media de su disco
        // (documents_private) en vez de rutas públicas.
        $mediaItems = collect(DocumentMediaFiles::COLLECTIONS)
            ->flatMap(fn (string $collection) => $document->getMedia($collection))
            ->filter(function ($media) {
                try {
                    return Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
                } catch (\Throwable) {
                    return false;
                }
            });

        if ($mediaItems->isEmpty()) {
            return response()->json(['message' => 'El expediente no tiene archivos para descargar.'], 404);
        }

        $zipName = 'expediente-'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $document->uid).'.zip';
        $zipPath = tempnam(sys_get_temp_dir(), 'hdzip_');

        $zip = new \ZipArchive;
        if ($zipPath === false || $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'No se pudo generar el ZIP.'], 500);
        }

        $used = [];
        foreach ($mediaItems as $media) {
            // Nombre original solo para mostrar: sin rutas ni duplicados.
            $name = DocumentMediaFiles::originalName((string) $media->getCustomProperty('original_name', $media->file_name));
            $entry = $media->collection_name.'/'.$name;
            for ($i = 2; isset($used[mb_strtolower($entry)]); $i++) {
                $entry = $media->collection_name.'/'.pathinfo($name, PATHINFO_FILENAME).'_'.$i
                    .(pathinfo($name, PATHINFO_EXTENSION) !== '' ? '.'.pathinfo($name, PATHINFO_EXTENSION) : '');
            }
            $used[mb_strtolower($entry)] = true;

            $disk = Storage::disk($media->disk);
            $relative = $media->getPathRelativeToRoot();

            if (config("filesystems.disks.{$media->disk}.driver") === 'local') {
                $zip->addFile($disk->path($relative), $entry);
            } else {
                $zip->addFromString($entry, (string) $disk->get($relative));
            }
        }
        $zip->close();

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $zipName, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
