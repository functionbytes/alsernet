<?php

namespace Modules\Document\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Services\DocumentActionService;
use Modules\Document\Services\DocumentEmailService;
use Modules\Document\Services\DocumentTypeService;
use Modules\Document\Traits\SendsDocumentEmails;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * API Controller Genérico para Validación de Documentos
 *
 * Detecta automáticamente el perfil del usuario autenticado y ejecuta
 * las acciones correspondientes. NO necesita rutas duplicadas por perfil.
 */
class DocumentValidationController extends Controller
{
    use SendsDocumentEmails;

    protected DocumentActionService $actionService;

    protected DocumentEmailService $emailService;

    public function __construct(
        DocumentActionService $actionService,
        DocumentEmailService $emailService
    ) {
        $this->actionService = $actionService;
        $this->emailService = $emailService;
    }

    /**
     * Detecta el perfil del usuario autenticado
     *
     * @return string 'administrative'|'weapons'|'accounting'|'manager'
     */
    protected function getUserProfile(): string
    {
        $user = auth()->user();

        // Detectar perfil basado en roles
        if ($user->hasRole('super-admin') || $user->hasRole('manager')) {
            return 'manager';
        }

        if ($user->hasRole('administrative')) {
            return 'administrative';
        }

        if ($user->hasRole('weapons')) {
            return 'weapons';
        }

        if ($user->hasRole('accounting')) {
            return 'accounting';
        }

        // Fallback
        return 'administrative';
    }

    /**
     * Aprobar etapa actual del documento
     */
    public function approveStage(Request $request, string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // Sin validación de permisos - permitir a usuarios autenticados
        // $this->authorize('approveStage', $document);

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
            'assigned_user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $result = $this->actionService->approveStage(
                $document,
                $validated['comments'] ?? null,
                $validated['assigned_user_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento aprobado exitosamente',
                'document' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rechazar documento
     */
    public function rejectStage(Request $request, string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // Sin validación de permisos - permitir a usuarios autenticados
        // $this->authorize('rejectStage', $document);

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $result = $this->actionService->rejectStage(
                $document,
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento rechazado',
                'document' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Enviar email de aprobación
     */
    public function sendApproval(Request $request, string $uid): JsonResponse
    {
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendApprovalEmail($doc, $adminId)
        // Sin verificación de autorización
        );
    }

    /**
     * Enviar email de rechazo
     */
    public function sendRejection(Request $request, string $uid): JsonResponse
    {
        $validated = $this->validateEmailRequest($request, [
            'reason' => 'required|string|min:10',
            'rejected_docs' => 'nullable|array',
        ]);

        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendRejectionEmail(
                $doc,
                $validated['reason'],
                $validated['rejected_docs'] ?? [],
                $adminId
            )
        // Sin verificación de autorización
        );
    }

    /**
     * Enviar email personalizado
     */
    public function sendCustomEmail(Request $request, string $uid): JsonResponse
    {
        $validated = $this->validateEmailRequest($request, [
            'subject' => 'required|string|max:200',
            'message' => 'required|string|min:10',
        ]);

        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendCustomEmail(
                $doc,
                $validated['subject'],
                $validated['message'],
                $adminId
            )
        );
    }

    /**
     * Enviar recordatorio
     */
    public function sendReminder(Request $request, string $uid): JsonResponse
    {
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendReminder($doc, $adminId)
        );
    }

    /**
     * Solicitar documentos iniciales
     */
    public function requestInitialDocuments(Request $request, string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // Sin verificación de autorización para permitir envío de correos

        try {
            $this->emailService->sendInitialRequest($document);
        } catch (\Exception $e) {
            Log::error('Failed to queue initial request email', [
                'document_uid' => $uid,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de documentos enviada',
            'recipient' => $document->customer_email,
        ]);
    }

    /**
     * Solicitar documentos faltantes
     */
    public function requestMissingDocuments(Request $request, string $uid): JsonResponse
    {
        $validated = $this->validateEmailRequest($request, [
            'missing_docs' => 'required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendMissingDocumentsRequest(
                $doc,
                $validated['missing_docs'],
                $validated['notes'] ?? null,
                $adminId
            )
        );
    }

    /**
     * Enviar notificación de documento
     */
    public function sendNotification(Request $request, string $uid): JsonResponse
    {
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendInitialRequest($doc, $adminId)
        );
    }

    /**
     * Enviar confirmación de carga
     */
    public function sendUploadConfirmation(Request $request, string $uid): JsonResponse
    {
        return $this->sendEmail(
            $request,
            $uid,
            fn ($doc, $adminId) => $this->emailService->sendUploadConfirmation($doc, $adminId)
        );
    }

    /**
     * Enviar solicitud de documentos faltantes
     */
    public function sendMissingDocuments(Request $request, string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        // Auto-detectar documentos faltantes si no se especifican
        $missingDocs = $request->input('missing_docs');

        if (empty($missingDocs)) {
            $missingDocs = $document->getMissingDocuments();

            if (empty($missingDocs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay documentos faltantes para solicitar',
                ], 422);
            }
        }

        // Crear request simulado con los documentos faltantes
        $request->merge([
            'missing_docs' => $missingDocs,
            'notes' => $request->input('notes', null),
        ]);

        return $this->requestMissingDocuments($request, $uid);
    }

    /**
     * Obtener historial de emails
     */
    public function emailHistory(string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        // $this->authorize('view', $document);

        $emails = $document->mails()
            ->orderBy('sent_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'emails' => $emails,
        ]);
    }

    /**
     * Preview de email
     */
    public function emailPreview(string $uid, string $mailUid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        // $this->authorize('view', $document);

        $email = $document->mails()
            ->where('uid', $mailUid)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'email' => $email,
        ]);
    }

    /**
     * Agregar nota al documento
     */
    public function addNote(Request $request, string $uid): JsonResponse
    {
        // Verificar permiso para agregar notas
        if (! auth()->user()->canDocument('add-notes')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para agregar notas.',
            ], 403);
        }

        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string|min:3',
            'is_internal' => 'boolean',
        ]);

        try {
            $note = $document->notes()->create([
                'content' => $validated['content'],
                'is_internal' => $validated['is_internal'] ?? true,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota agregada correctamente',
                'note' => $note,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar nota: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Actualizar nota del documento
     */
    public function updateNote(Request $request, string $uid, int $noteId): JsonResponse
    {
        // Verificar permiso para editar notas
        if (! auth()->user()->canDocument('edit-notes')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar notas.',
            ], 403);
        }

        $document = Document::where('uid', $uid)->firstOrFail();

        $note = $document->notes()->findOrFail($noteId);

        $validated = $request->validate([
            'content' => 'required|string|min:3',
        ]);

        try {
            $note->update([
                'content' => $validated['content'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'note' => $note,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar nota: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar nota del documento
     */
    public function deleteNote(Request $request, string $uid, int $noteId): JsonResponse
    {
        // Verificar permiso para eliminar notas
        if (! auth()->user()->canDocument('delete-notes')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar notas.',
            ], 403);
        }

        $document = Document::where('uid', $uid)->firstOrFail();

        $note = $document->notes()->findOrFail($noteId);

        try {
            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nota: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener historial de acciones
     */
    public function getActionHistory(string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // $this->authorize('view', $document);

        $history = $document->actions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Obtener historial de emails
     */
    public function getEmailHistory(string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // $this->authorize('view', $document);

        $emails = $document->mails()
            ->orderBy('sent_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'emails' => $emails,
        ]);
    }

    /**
     * Obtener timeline de estados
     */
    public function getStatusTimeline(string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // $this->authorize('view', $document);

        $timeline = $document->statusHistories()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Obtener información del siguiente grupo de validación
     */
    public function getNextStageInfo(string $uid): JsonResponse
    {
        $document = Document::where('uid', $uid)->firstOrFail();
        $profile = $this->getUserProfile();

        // $this->authorize('view', $document);

        if ($document->current_stage >= $document->total_stages) {
            return response()->json([
                'success' => false,
                'message' => 'El documento está en la última etapa',
            ], 422);
        }

        $nextStage = $document->current_stage + 1;
        $stages = $document->getValidationWorkflowStages();
        $nextGroupKey = $stages[$nextStage - 1] ?? null;

        if (! $nextGroupKey) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar el siguiente grupo',
            ], 422);
        }

        $nextGroup = DocumentValidatorGroup::findByKey($nextGroupKey);
        $users = $nextGroup ? $nextGroup->users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'is_primary' => $user->pivot->priority === 'primary',
            ];
        }) : collect();

        return response()->json([
            'success' => true,
            'next_stage' => $nextStage,
            'next_group_key' => $nextGroupKey,
            'next_group_label' => ucfirst($nextGroupKey),
            'users' => $users,
        ]);
    }

    /**
     * Obtener plantilla de email personalizado
     */
    public function getCustomEmailTemplate(): JsonResponse
    {
        $template = \Modules\Mailer\Models\MailerTemplate::where('key', 'custom_document')->first();

        if (! $template) {
            return response()->json([
                'success' => false,
                'message' => 'No hay plantilla configurada',
            ]);
        }

        return response()->json([
            'success' => true,
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'subject' => $template->subject,
            ],
        ]);
    }

    /**
     * Permite al administrador cargar documentos en nombre del cliente
     * Soporta múltiples archivos con tipos específicos (dni_frontal, dni_trasera, licencia, etc)
     */
    public function uploadDocument(Request $request, $uid)
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Validar que venga al menos un archivo
            $request->validate([
                'documents.*' => 'nullable|file|max:10240', // Máximo 10MB por archivo
            ]);

            $uploadedCount = 0;
            $uploadedFiles = [];
            $type = 'documents';

            // Log del inicio de la carga
            $documentsArray = $request->file('documents') ?? [];
            Log::info('adminUploadDocument START', [
                'uid' => $uid,
                'document_id' => $document->id,
                'files_received' => count($documentsArray),
                'array_keys' => is_array($documentsArray) ? array_keys($documentsArray) : [],
                'array_structure' => is_array($documentsArray) ? array_map(function ($f) {
                    return [
                        'name' => $f instanceof \Illuminate\Http\UploadedFile ? $f->getClientOriginalName() : 'N/A',
                        'type' => $f instanceof \Illuminate\Http\UploadedFile ? get_class($f) : gettype($f),
                    ];
                }, $documentsArray) : [],
            ]);

            // Procesar cada archivo del array documents
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $docType => $file) {
                    if ($file && $file->isValid()) {
                        Log::info('Processing file', [
                            'docType' => $docType,
                            'fileName' => $file->getClientOriginalName(),
                            'fileSize' => $file->getSize(),
                        ]);

                        // Recargar media del documento para asegurar que tenemos la versión más reciente
                        $document->load('media');

                        // Eliminar archivo anterior del mismo tipo si existe
                        $existingMedia = null;
                        foreach ($document->media as $media) {
                            $storedType = $media->getCustomProperty('document_type');
                            Log::info('Checking existing media', [
                                'mediaId' => $media->id,
                                'storedDocType' => $storedType,
                                'lookingFor' => $docType,
                                'match' => $storedType === $docType,
                            ]);

                            if ($storedType === $docType) {
                                $existingMedia = $media;
                                break;
                            }
                        }

                        if ($existingMedia) {
                            Log::info('Deleting existing media', ['mediaId' => $existingMedia->id]);
                            $existingMedia->delete();
                        }

                        // Agregar nueva media con propiedad custom para identificar el tipo
                        $media = $document->addMedia($file)
                            ->withCustomProperties(['document_type' => $docType])
                            ->toMediaCollection($type);

                        // Verificar que el custom property se guardó correctamente
                        $savedProperty = $media->getCustomProperty('document_type');
                        Log::info('Media uploaded and verified', [
                            'mediaId' => $media->id,
                            'fileName' => $media->file_name,
                            'docType' => $docType,
                            'savedProperty' => $savedProperty,
                            'propertyMatch' => $savedProperty === $docType,
                        ]);

                        // Asegurar que el archivo es accesible al servidor web
                        $mediaPath = $media->getPath();
                        if (file_exists($mediaPath)) {
                            @chmod($mediaPath, 0644);
                        }
                        // También cambiar permisos del directorio si es necesario
                        $mediaDir = dirname($mediaPath);
                        if (is_dir($mediaDir)) {
                            @chmod($mediaDir, 0755);
                        }

                        $uploadedFiles[] = [
                            'id' => $media->id,
                            'uuid' => $media->uuid,
                            'file' => $media->file_name,
                            'size' => $media->size,
                            'path' => $media->getUrl(),
                            'type' => $docType,
                        ];

                        $uploadedCount++;
                    }
                }
            }

            Log::info('adminUploadDocument END', [
                'uploadedCount' => $uploadedCount,
                'totalFiles' => count($uploadedFiles),
            ]);

            if ($uploadedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron archivos válidos',
                ], 400);
            }

            // Sincronizar JSON de documentos subidos con los archivos media actuales
            $document->syncUploadedDocumentsJson();

            // Actualizar timestamp de confirmación
            $document->update([
                'confirmed_at' => now(), // El admin confirma implícitamente
            ]);

            // NO enviar correo automático cuando el admin sube documentos
            // app(DocumentEmailService::class)->processDocumentUpload($document);

            return response()->json([
                'success' => true,
                'message' => 'Documento(s) cargado(s) correctamente por el administrador',
                'uploaded_count' => $uploadedCount,
                'statement_id' => $document->id,
                'files' => $uploadedFiles,
                'uploaded_documents' => $document->uploaded_documents,
                'missing_documents' => $document->getMissingDocuments(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza los campos required_documents y uploaded_documents de todos los documentos
     * Útil para migrar documentos antiguos o corregir inconsistencias
     * Puede filtrar por tipo específico si se proporciona
     */
    public function syncAllDocumentFields(Request $request)
    {
        try {
            $type = $request->query('type');
            $force = $request->query('force', false);

            if ($type) {
                $documents = Document::where('type', $type)->get();
            } else {
                $documents = Document::all();
            }

            if ($documents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron documentos para sincronizar',
                ], 404);
            }

            $synced = 0;
            $skipped = 0;

            foreach ($documents as $document) {
                // Si ya está sincronizado y no es force, omitir
                if (! $force && ! empty($document->required_documents) && ! empty($document->uploaded_documents)) {
                    $skipped++;

                    continue;
                }

                // 1. Ensure document has a valid type_id (default to 'general' if missing)
                if (! $document->type_id) {
                    $generalTypeId = $this->resolveDocumentTypeId('general');
                    if ($generalTypeId) {
                        $document->type_id = $generalTypeId;
                    } else {
                        // Can't sync without a valid document type
                        $skipped++;

                        continue;
                    }
                }

                // 2. Get the document type slug and generate required_documents
                $typeSlug = $document->documentType?->slug ?? 'general';
                $requiredDocs = DocumentTypeService::getRequiredDocuments($typeSlug);
                $document->required_documents = $requiredDocs;

                // 3. Generar uploaded_documents desde media actual
                $uploadedDocs = [];
                foreach ($document->getMedia('documents') as $media) {
                    $docType = $media->getCustomProperty('document_type', 'documento');
                    $uploadedDocs[$docType] = [
                        'id' => $media->id,
                        'file_name' => $media->file_name,
                        'size' => $media->size,
                        'url' => $media->getUrl(),
                        'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                    ];
                }
                $document->uploaded_documents = $uploadedDocs;

                // 4. Guardar el documento
                $document->save();

                $synced++;
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada: {$synced} sincronizados, {$skipped} omitidos",
                'data' => [
                    'total_documents' => $documents->count(),
                    'synced' => $synced,
                    'skipped' => $skipped,
                    'type_filter' => $type ?? 'todos',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sincronizando documentos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sube adjuntos adicionales desde la vista administrativa
     * Estos documentos son visibles para contabilidad y otros grupos
     */
    public function uploadAdditionalAttachment(Request $request, $uid)
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $request->validate([
                'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
                'notes' => 'nullable|string|max:500',
            ]);

            $file = $request->file('file');
            $notes = $request->input('notes');
            $uploadedBy = auth()->check() ? auth()->user()->full_name : 'Admin';

            // Sanitizar nombre de archivo para prevenir ataques XSS y path traversal
            $originalName = $file->getClientOriginalName();
            $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $sanitizedNotes = $notes ? htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') : null;

            // Guardar archivo en colección de adjuntos adicionales
            $media = $document->addMedia($file)
                ->usingFileName($sanitizedName)
                ->withCustomProperties([
                    'original_name' => $originalName,
                    'uploaded_by' => $uploadedBy,
                    'notes' => $sanitizedNotes,
                    'uploaded_at' => now()->toDateTimeString(),
                ])
                ->toMediaCollection('additional_attachments');

            // Asegurar permisos
            $mediaPath = $media->getPath();
            if (file_exists($mediaPath)) {
                @chmod($mediaPath, 0644);
            }
            $mediaDir = dirname($mediaPath);
            if (is_dir($mediaDir)) {
                @chmod($mediaDir, 0755);
            }

            // Sincronizar JSON
            $document->syncAdditionalAttachmentsJson();

            return response()->json([
                'success' => true,
                'message' => 'Adjunto adicional cargado correctamente',
                'file' => [
                    'id' => $media->id,
                    'name' => $media->getCustomProperty('original_name'),
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'uploaded_at' => $media->created_at->format('d/m/Y H:i'),
                    'uploaded_by' => $uploadedBy,
                    'notes' => $notes,
                ],
                'total_attachments' => $document->getMedia('additional_attachments')->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error uploading additional attachment', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar adjunto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todos los adjuntos adicionales de un documento
     */
    public function getAdditionalAttachments($uid)
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $attachments = $document->getAdditionalAttachmentsWithDetails();

            return response()->json([
                'success' => true,
                'attachments' => $attachments,
                'total' => count($attachments),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener adjuntos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar archivo adjunto adicional del documento
     */
    public function deleteAdditionalAttachment(string $uid, int $mediaId): JsonResponse
    {
        try {
            $document = Document::where('uid', $uid)->firstOrFail();

            // $this->authorize('update', $document);

            $media = Media::find($mediaId);

            if (! $media || $media->model_id !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado o no pertenece a este documento',
                ], 404);
            }

            $media->delete();

            // Cambiar estado a "awaiting_documents" cuando se elimina un attachment
            $document->status_id = DocumentStatus::where('key', 'awaiting_documents')->first()?->id;
            $document->save();

            // Refrescar datos del documento
            $document->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente',
                'data' => [
                    'status_id' => $document->status_id,
                    'current_status' => $document->status?->key,
                    'status_label' => $document->status?->label,
                    'source_id' => $document->source_id,
                    'load_id' => $document->load_id,
                    'sync_id' => $document->sync_id,
                    'upload_id' => $document->upload_id,
                    'requires_financing' => $document->requires_financing,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar documento: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve document type slug to type_id
     *
     * @param  string|null  $typeSlug  The type slug (e.g., 'general', 'dni', 'escopeta')
     * @return int|null The document_types.id or null if not found
     */
    private function resolveDocumentTypeId(?string $typeSlug): ?int
    {
        if (! $typeSlug) {
            return null;
        }

        $documentType = DocumentType::where('slug', $typeSlug)->first();

        return $documentType?->id;
    }
}
