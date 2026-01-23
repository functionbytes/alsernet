<?php

namespace Modules\Document\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentLoad;
use Modules\Document\Entities\DocumentSource;
use Modules\Document\Entities\DocumentStatus;
use Modules\Document\Entities\DocumentSync;
use Modules\Document\Entities\DocumentType;
use Modules\Document\Entities\DocumentUploadType;
use Modules\Document\Events\DocumentCreated;
use Modules\Document\Jobs\MailTemplateJob;
use Modules\Document\Services\DocumentEmailService;
use Modules\Document\Services\DocumentTypeService;
use Modules\Document\Traits\SendsDocumentEmails;
use Modules\Prestashop\Entities\Orders\Order as PrestashopOrder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentsController extends Controller
{
    use SendsDocumentEmails;
    /**
     * Sincroniza un documento con los datos de su orden e importa productos
     * Método helper reutilizable para sincronización de datos y productos
     */
    private function syncDocumentWithOrder(Document $document, PrestashopOrder $order): bool
    {
        // Obtener el cliente
        $customer = $order->customer;

        if (! $customer) {
            return false;
        }

        // Llenar los datos desnormalizados de la orden y cliente
        $document->order_reference = $order->reference ?? $document->order_reference;
        $document->order_date = $order->date_add ?? $document->order_date;

        // Obtener dirección de envío
        $deliveryAddress = $order->deliveryAddress;

        $document->customer_id = $customer->id_customer;
        // Nombre y apellido vienen de la dirección de envío
        $document->customer_firstname = $deliveryAddress?->firstname ?? $customer->firstname;
        $document->customer_lastname = $deliveryAddress?->lastname ?? $customer->lastname;
        $document->customer_email = $customer->email;
        // DNI/SIRET vienen de la dirección de envío
        $document->customer_dni = $deliveryAddress?->dni ?? $deliveryAddress?->vat_number ?? null;
        // Empresa viene de la dirección de envío
        $document->customer_company = $deliveryAddress?->company ?? null;
        // Teléfono celular viene de la dirección de envío
        $document->customer_cellphone = $deliveryAddress?->phone_mobile ?? null;

        $document->save();

        // Importar productos del carrito
        $document->captureProducts();

        return true;
    }

    /**
     * Verifica la existencia de un documento para una orden específica
     *
     * @deprecated Use individual RESTful endpoints instead
     * @see verify() for verification
     * @see validation() for validation
     * @see store() for creating documents
     * @see uploadFiles() for uploading files
     * @see deleteFile() for deleting files
     */
    public function process(Request $request)
    {
        // Log uso de endpoint deprecated para monitoreo
        \Log::warning('Deprecated endpoint used: POST /api/documents with action parameter', [
            'action' => $request->input('action'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $action = $request->input('action');
        $data = $request->all();

        // Delegar a los nuevos métodos RESTful
        switch ($action) {
            case 'verification':
                return $this->verify($request);
            case 'validate':
                $uid = $request->input('uid') ?? $data['uid'] ?? null;

                return $this->validation($uid);
            case 'request':
                return $this->store($request);
            case 'upload':
                $uid = $request->input('uid') ?? $data['uid'] ?? null;

                return $this->uploadFiles($request, $uid);
            case 'delete':
                $uid = $request->input('uid') ?? $data['uid'] ?? null;
                $docType = $request->input('doc_type') ?? $data['doc_type'] ?? null;

                return $this->deleteFile($uid, $docType);
            default:
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Invalid action type. Use RESTful endpoints instead.',
                    'hint' => 'See API documentation for new endpoints: GET /verify, GET /{uid}/validation, POST /, POST /{uid}/files, DELETE /{uid}/files/{docType}',
                ], 400);
        }
    }

    /**
     * Verifica la existencia de un documento para una orden específica
     *
     * Endpoint RESTful: GET /api/documents/verify?order_id={order_id}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $orderId = $request->input('order_id') ?? $request->input('order');

        $document = Document::where('order_id', $orderId)->first();

        if (! $document) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No document found for this order',
                'data' => [
                    'order_id' => $orderId,
                ],
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document found',
            'data' => [
                'uid' => $document->uid,
                'reference' => $document->order_reference ?? $document->order_id,
                'type' => $document->type,
                'order_id' => $document->order_id,
            ],
        ], 200);
    }

    /**
     * Obtiene información de validación de un documento
     *
     * Endpoint RESTful: GET /api/documents/{uid}/validation
     *
     * Retorna estado actual, documentos requeridos, subidos y faltantes.
     * Valida si el documento puede recibir uploads según su estado.
     *
     * @param  string  $uid  UID del documento
     * @return \Illuminate\Http\JsonResponse
     */
    public function validation($uid)
    {
        if (! $uid) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing uid parameter',
            ], 400);
        }

        $document = Document::uid($uid)->first();

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found',
            ], 404);
        }

        // Determinar estado de validación y si puede cargar archivos
        $currentStatusKey = $document->status?->key;
        $blockedStatusKeys = ['approved', 'cancelled', 'completed'];
        $isComplete = $document->hasAllRequiredDocuments();

        // Determinar validation_status
        $validationStatus = 'pending';
        if ($isComplete) {
            $validationStatus = 'received';
        } elseif (in_array($currentStatusKey, $blockedStatusKeys)) {
            $validationStatus = 'error';
        }

        // Actualizar JSON de documentos requeridos si no existe
        if (empty($document->required_documents)) {
            $document->updateRequiredDocumentsJson();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document validation successful',
            'data' => [
                    'uid' => $document->uid,
                    'type' => $document->type,
                    'order_id' => $document->order_id,
                    'reference' => $document->order_reference,
                    'label' => $document->documentType?->getLabel() ?? 'N/A',
                    'can_upload' => ! $document->confirmed_at,
                    'required_documents' => $document->getRequiredDocumentsWithLabels(),
                    'uploaded_documents' => $document->getUploadedDocumentsWithDetails(),
                    'missing_documents' => $document->getMissingDocuments(),
            ],
        ], 200);
    }

    /**
     * Crea una nueva solicitud de documento
     *
     * Endpoint RESTful: POST /api/documents
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Delegar al método existente documentRequests (mantiene lógica existente)
        return $this->documentRequests($request->all());
    }

    public function documentRequests($data)
    {
        try {
            // Obtener order_id (compatible con múltiples formatos)
            $orderId = $data['order_id'] ?? $data['order'] ?? null;

            // Validar que existe order_id
            if (! $orderId) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Missing order_id parameter',
                ], 400);
            }

            // Validar que no existe un documento duplicado
            $existingDocument = Document::where('order_id', $orderId)->first();
            if ($existingDocument) {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Order {$orderId} already has a document request",
                    'data' => [
                        'uid' => $existingDocument->uid,
                    ],
                ], 409);  // Conflict
            }

            // ===== Language Mapping Logic =====
            $langId = null;

            // If iso_code is provided, map to Laravel lang_id
            if (isset($data['iso_code']) && ! empty($data['iso_code'])) {
                $isoCode = strtolower(trim($data['iso_code']));

                // Find Laravel language by iso_code
                $laravelLang = \App\Models\Lang::iso($isoCode);

                if ($laravelLang) {
                    $langId = $laravelLang->id;
                    \Log::info("Language mapped: PrestaShop iso_code '{$isoCode}' → Laravel lang_id {$langId}");
                } else {
                    // Log warning and fallback to default
                    \Log::warning("Language mapping failed: iso_code '{$isoCode}' not found in Laravel langs table");
                    $defaultLang = \App\Models\Lang::iso('es');
                    $langId = $defaultLang ? $defaultLang->id : null;
                }
            }

            $document = new Document;
            $document->order_id = $orderId;

            // Note: type_id will be detected from products after they're added
            // If no products or no blockades match, model will default to 'dni' type

            $document->lang_id = $langId;  // Assign language

            // Set lookup IDs for PrestaShop source
            $document->source_id = DocumentSource::where('key', 'prestashop')->first()?->id;
            $document->load_id = DocumentLoad::where('key', 'system')->first()?->id;
            $document->sync_id = DocumentSync::where('key', 'none')->first()?->id;
            $document->upload_id = DocumentUploadType::where('key', 'automatic')->first()?->id;

            if (isset($data['customer']) && is_array($data['customer'])) {
                $document->customer_id = $data['customer']['id_customer'] ?? $data['customer']['id'] ?? null;
                $document->customer_firstname = $data['customer']['firstname'] ?? null;
                $document->customer_lastname = $data['customer']['lastname'] ?? null;
                $document->customer_email = $data['customer']['email'] ?? null;
                // DNI/SIRET viene de la dirección en Prestashop
                $document->customer_dni = $data['customer']['siret'] ?? $data['customer']['document_type'] ?? null;
                $document->customer_company = $data['customer']['company'] ?? null;
                // Teléfono viene de la dirección de envío
                $document->customer_cellphone = $data['customer']['phone_mobile'] ?? $data['customer']['phone'] ?? null;
            }

            // Obtener datos de la orden
            $document->cart_id = $data['cart_id'] ?? $data['cart'] ?? null;
            $document->order_reference = $data['reference'] ?? null;
            $document->order_date = $data['date_add'] ?? null;

            // Set initial status to "pending"
            $document->status_id = DocumentStatus::where('key', 'pending')->first()?->id;

            // Save document FIRST (required for relationships)
            $document->save();

            // Process and create products AFTER document is saved
            // Accept both 'products' and 'inventaries' field names (PrestaShop compatibility)
            $productsCount = 0;
            $products = $data['products'] ?? $data['inventaries'] ?? null;
            if ($products && is_array($products)) {
                foreach ($products as $product) {
                    // Mapear campos de Prestashop correctamente
                    $productId = $product['product_id'] ?? $product['id'] ?? null;
                    $productName = $product['product_name'] ?? $product['name'] ?? null;
                    $productReference = $product['product_reference'] ?? $product['reference'] ?? null;
                    $quantity = (int) ($product['product_quantity'] ?? $product['quantity'] ?? 0);
                    $price = (float) ($product['unit_price_tax_incl'] ?? $product['price'] ?? 0);

                    if ($productId && $productName) {
                        $document->products()->create([
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'product_reference' => $productReference,
                            'quantity' => $quantity,
                            'price' => $price,
                        ]);
                        $productsCount++;
                    }
                }
            }

            // Detectar tipo de documento basado en los productos y document_product_blockades
            // La fuente de verdad es document_product_blockades, no PrestaShop
            if ($productsCount > 0) {
                $detectedTypeSlug = $document->detectDocumentType();
                $detectedTypeId = $this->resolveDocumentTypeId($detectedTypeSlug);

                if ($detectedTypeId && $detectedTypeId !== $document->type_id) {
                    $document->type_id = $detectedTypeId;
                    $document->save();
                }
            } else {
                // FALLBACK: If no products, use the type field from the request
                // This handles cases where PrestaShop sends type but no products
                $typeSlug = $data['type'] ?? null;
                if ($typeSlug) {
                    $typeId = $this->resolveDocumentTypeId($typeSlug);
                    if ($typeId) {
                        $document->type_id = $typeId;
                        $document->save();
                    }
                }
            }

            // Refresh para obtener los datos más recientes
            $document->refresh();

            // Disparar evento SOLO UNA VEZ al final después de toda la configuración
            Log::info('Dispatching DocumentCreated event', [
                'document_uid' => $document->uid,
                'document_id' => $document->id,
                'order_id' => $document->order_id,
                'method' => 'documentRequests',
            ]);
            DocumentCreated::dispatch($document);

            MailTemplateJob::dispatch($document, 'request');

            return response()->json([
                'status' => 'success',
                'message' => "Document request created successfully for order {$orderId}",
                'data' => [
                    'uid' => $document->uid,
                    'document_type' => $document->type,
                    'order_id' => $document->order_id,
                    'reference' => $document->order_reference,
                    'label' => $document->documentType?->getLabel() ?? 'N/A',
                    'can_upload' => ! $document->confirmed_at,
                    'required_documents' => $document->getRequiredDocuments(),
                    'uploaded_documents' => $document->getUploadedDocumentTypes(),
                    'missing_documents' => array_keys($document->getMissingDocuments()),
                ],
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating document request: '.$e->getMessage(), [
                'exception' => $e,
                'data' => $data,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Error creating document request: '.$e->getMessage(),
            ], 500);
        }
    }

    public function documentVerification($data)
    {

        $document = Document::orders($data['order']);

        return response()->json([
            'status' => 'success',
            'message' => 'You have document from general emails.',
            'data' => [
                'uid' => $document->uid,
                'reference' => $document->label,
                'type' => $document->type,
            ],

        ], 200);

    }

    public function documentValidates($data)
    {
        $uid = $data['uid'] ?? null;

        if (! $uid) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing uid parameter',
            ], 400);
        }

        $document = Document::uid($uid)->first();

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found',
            ], 404);
        }

        // Validar que el documento está en un estado que permite carga de archivos
        $allowedStatusKeys = ['incomplete', 'rejected', 'pending'];
        $currentStatusKey = $document->status?->key;

        if ($currentStatusKey && ! in_array($currentStatusKey, $allowedStatusKeys)) {
            return response()->json([
                'status' => 'failed',
                'message' => "Document cannot accept file uploads in '{$currentStatusKey}' status. Allowed statuses: ".implode(', ', $allowedStatusKeys),
                'data' => [
                    'uid' => $document->uid,
                    'current_status' => $currentStatusKey,
                    'allowed_statuses' => $allowedStatusKeys,
                ],
            ], 409);
        }

        if (empty($document->required_documents)) {
            $document->updateRequiredDocumentsJson();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Document validation successful',
            'data' => [
                'uid' => $document->uid,
                'type' => $document->type ?? 'general',
                'label' => $document->order_reference ?? $document->order_id,
                'current_status' => $currentStatusKey,
                'can_upload' => is_null($document->confirmed_at) && in_array($currentStatusKey, $allowedStatusKeys),
                'required_documents' => $document->getRequiredDocumentsWithLabels(),
                'uploaded_documents' => $document->getUploadedDocumentsWithDetails(),
                'missing_documents' => $document->getMissingDocuments(),
            ],
        ], 200);
    }

    /**
     * Sube archivos a un documento específico
     *
     * Endpoint RESTful: POST /api/documents/{uid}/files
     *
     * @param  string  $uid  UID del documento
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFiles(Request $request, $uid)
    {
        try {
            // UID viene del parámetro de ruta (no del request body)
            if (! $uid) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Missing uid parameter',
                ], 400);
            }

            $document = Document::uid($uid)->first();

            if (! $document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Document not found',
                ], 404);
            }

            // Validar que el documento está en un estado que permite carga de archivos
            // Bloquear solo estados finales (completado, aprobado, cancelado)
            $blockedStatusKeys = ['approved', 'cancelled', 'completed'];
            $currentStatusKey = $document->status?->key;

            if ($currentStatusKey && in_array($currentStatusKey, $blockedStatusKeys)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Document cannot accept file uploads in '{$currentStatusKey}' status. Upload is only allowed while document is in progress.",
                    'data' => [
                        'uid' => $document->uid,
                        'current_status' => $currentStatusKey,
                        'blocked_statuses' => $blockedStatusKeys,
                    ],
                ], 409);
            }

            // Obtener archivos y tipos de documento
            $files = $request->file('file');
            $documentTypes = $request->input('document_types', []);

            if (! $files) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No files provided',
                ], 400);
            }

            // Convertir a array si es archivo único
            if (! is_array($files)) {
                $files = [$files];
                $documentTypes = is_array($documentTypes) ? $documentTypes : [$documentTypes];
            }

            // Validar que hay tipos para cada archivo
            if (count($files) !== count($documentTypes)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Each file must have a document type specified',
                ], 400);
            }

            // Procesar cada archivo con su tipo
            foreach ($files as $index => $file) {
                // Validar que el archivo es válido
                if (! $file || ! $file->isValid()) {
                    $errorMessage = 'Invalid file provided at index '.$index;

                    // Agregar detalles del error para debugging
                    if ($file) {
                        $errorMessage .= ' - Error: '.$file->getError();
                        $errorMessage .= ' - Name: '.$file->getClientOriginalName();
                        $errorMessage .= ' - Size: '.$file->getSize();
                    } else {
                        $errorMessage .= ' - File is null';
                    }

                    return response()->json([
                        'status' => 'failed',
                        'message' => $errorMessage,
                    ], 400);
                }

                $docType = $documentTypes[$index] ?? 'documento';

                // Validar tamaño del archivo (máximo 10MB)
                $maxSize = 10 * 1024 * 1024; // 10MB
                if ($file->getSize() > $maxSize) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'File "'.$file->getClientOriginalName().'" is too large. Maximum size is 10MB.',
                    ], 400);
                }

                // Eliminar archivo previo del mismo tipo si existe
                $existingMedia = $document->getMedia('documents')
                    ->filter(fn ($media) => $media->getCustomProperty('document_type') === $docType)
                    ->first();

                if ($existingMedia) {
                    $existingMedia->delete();
                }

                // Subir nuevo archivo con tipo identificado
                $document->addMedia($file)
                    ->withCustomProperties(['document_type' => $docType])
                    ->toMediaCollection('documents');
            }

            // Refrescar el documento para obtener los medios recién subidos
            $document->refresh();

            // Actualizar JSON de documentos subidos
            $document->syncUploadedDocumentsJson();

            // Update status based on document completion
            if ($document->hasAllRequiredDocuments()) {
                // All documents uploaded - set to "received" status
                $document->status_id = DocumentStatus::where('key', 'received')->first()?->id;
                if (! $document->confirmed_at) {
                    $document->confirmed_at = Carbon::now()->setTimezone('Europe/Madrid');
                }
            }
            // If partial upload (some documents missing), keep current status

            // Set source_id to 'api', load_id to 'api', sync_id to 'automatic' and upload_id to 'automatic'
            // $apiSource = DocumentSource::where('key', 'api')->first();
            // $document->source_id = $apiSource?->id;

            // $apiLoad = DocumentLoad::where('key', 'api')->first();
            // $document->load_id = $apiLoad?->id;

            // $automaticSync = DocumentSync::where('key', 'automatic')->first();
            // $document->sync_id = $automaticSync?->id;

            $automaticUpload = DocumentUploadType::where('key', 'automatic')->first();
            $document->upload_id = $automaticUpload?->id;

            $document->save();

            // Refrescar nuevamente para obtener los datos actualizados
            $document->refresh();

            // Obtener documentos subidos para respuesta
            $uploadedDocs = $document->getUploadedDocumentsWithDetails();

            // Disparar evento SOLO cuando todos los documentos estén completos
            // Usa UPDATE atómico para prevenir múltiples disparos
            $isComplete = $document->hasAllRequiredDocuments();

            if ($isComplete) {
                // Usar UPDATE atómico para asegurar que solo se marca una vez
                $updated = \DB::table('documents')
                    ->where('id', $document->id)
                    ->whereNull('uploaded_confirmation_sent_at')
                    ->update([
                        'uploaded_confirmation_sent_at' => Carbon::now()->setTimezone('Europe/Madrid'),
                        'updated_at' => Carbon::now()->setTimezone('Europe/Madrid'),
                    ]);

                // Si se actualizó, procesar upload: enviar confirmación
                if ($updated === 1) {
                    $document->refresh();

                    // Capture adminId before releasing session
                    $adminId = auth()->id();

                    // Process upload with email service
                    $emailService = app(DocumentEmailService::class);
                    $sent = $emailService->sendUploadConfirmation($document, $adminId);

                    if ($sent) {
                        // Update document status to received
                        $receivedStatus = DocumentStatus::where('key', 'received')->first();
                        if ($receivedStatus) {
                            $document->update(['status_id' => $receivedStatus->id]);
                            Log::info('Document status set to Received', [
                                'document_uid' => $document->uid,
                                'status_id' => $receivedStatus->id,
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Document uploaded successfully',
                'data' => [
                    'uploaded_documents' => $uploadedDocs,
                    'missing_documents' => $document->getMissingDocuments(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error uploading documents: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un archivo específico de un documento
     *
     * Endpoint RESTful: DELETE /api/documents/{uid}/files/{docType}
     *
     * @param  string  $uid  UID del documento
     * @param  string  $docType  Tipo de documento a eliminar
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile($uid, $docType)
    {
        try {
            // UID y docType vienen de parámetros de ruta (no del request body)
            if (! $uid || ! $docType) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Missing uid or doc_type parameter',
                ], 400);
            }

            $document = Document::uid($uid)->first();

            if (! $document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Document not found',
                ], 404);
            }

            // Encontrar y eliminar archivo del tipo especificado
            $mediaToDelete = $document->getMedia('documents')
                ->filter(fn ($media) => $media->getCustomProperty('document_type') === $docType)
                ->first();

            if (! $mediaToDelete) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Document type not found',
                ], 404);
            }

            // Eliminar el archivo
            $mediaToDelete->delete();

            // Actualizar JSON de documentos subidos
            $document->syncUploadedDocumentsJson();

            // Cambiar estado a "awaiting_documents" cuando se elimina un archivo
            $document->status_id = DocumentStatus::where('key', 'awaiting_documents')->first()?->id;
            $document->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Document deleted successfully',
                'data' => [
                    'uploaded_documents' => $document->uploaded_documents,
                    'missing_documents' => $document->getMissingDocuments(),
                    'current_status' => $document->status?->key,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error deleting document: '.$e->getMessage(),
            ], 500);
        }
    }

    public function resendDocumentReminder(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
        ]);

        $document = Document::uid($request->input('uid'))->first();

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No document found with this UID.',
            ], 404);
        }

        MailTemplateJob::dispatch($document, 'reminder');

        $document->reminder_at = now();
        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document reminder email sent successfully.',
        ], 200);
    }

    public function confirmDocumentUpload(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
        ]);

        $document = Document::uid($request->input('uid'))->first();

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No document found with this UID.',
            ], 404);
        }

        if (! $document->confirmed_at || $document->media->count() === 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document has not been uploaded yet.',
            ], 400);
        }

        $document->confirmed_at = now();
        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document upload confirmed successfully.',
        ], 200);
    }

    /**
     * Obtiene datos de la orden y cliente para llenar el documento
     * Consulta los datos en Prestashop y los devuelve para desnormalización
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderData(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $orderId = $request->input('order_id');

        // Obtener la orden de Prestashop
        $order = PrestashopOrder::find($orderId);

        if (! $order) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Order not found in Prestashop.',
            ], 404);
        }

        // Obtener el cliente
        $customer = $order->customer;

        if (! $customer) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Customer associated with order not found.',
            ], 404);
        }

        // Retornar todos los datos necesarios para desnormalización
        return response()->json([
            'status' => 'success',
            'message' => 'Order data retrieved successfully.',
            'data' => [
                // Datos de la orden
                'order_id' => $order->id_order,
                'order_reference' => $order->reference,
                'order_date' => $order->date_add,
                'order_cart_id' => $order->id_cart,

                // Datos del cliente
                'customer_id' => $customer->id_customer,
                'customer_firstname' => $order->deliveryAddress?->firstname ?? $customer->firstname,
                'customer_lastname' => $order->deliveryAddress?->lastname ?? $customer->lastname,
                'customer_email' => $customer->email,
                'customer_dni' => $order->deliveryAddress?->dni ?? $order->deliveryAddress?->vat_number ?? null,
                'customer_company' => $order->deliveryAddress?->company ?? null,
                'customer_cellphone' => $order->deliveryAddress?->phone_mobile ?? null,
            ],
        ], 200);
    }

    /**
     * Llena automáticamente los datos desnormalizados de un documento
     * usando los datos de la orden y cliente de Prestashop
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fillDocumentWithOrderData(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'order_id' => 'required|integer',
        ]);

        $uid = $request->input('uid');
        $orderId = $request->input('order_id');

        // Obtener el documento
        $document = Document::uid($uid)->first();

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found.',
            ], 404);
        }

        // Obtener la orden
        $order = PrestashopOrder::find($orderId);

        if (! $order) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Order not found in Prestashop.',
            ], 404);
        }

        // Obtener el cliente
        $customer = $order->customer;

        if (! $customer) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Customer not found.',
            ], 404);
        }

        // Llenar los datos desnormalizados
        $document->order_reference = $order->reference;
        $document->order_id = $order->id_order;
        $document->order_date = $order->date_add;

        // Obtener dirección de envío
        $deliveryAddress = $order->deliveryAddress;
        $document->customer_id = $customer->id_customer;
        // Nombre y apellido vienen de la dirección de envío
        $document->customer_firstname = $deliveryAddress?->firstname ?? $customer->firstname;
        $document->customer_lastname = $deliveryAddress?->lastname ?? $customer->lastname;
        $document->customer_email = $customer->email;
        // DNI/SIRET vienen de la dirección de envío
        $document->customer_dni = $deliveryAddress?->dni ?? $deliveryAddress?->vat_number ?? null;
        // Empresa viene de la dirección de envío
        $document->customer_company = $deliveryAddress?->company ?? null;
        // Teléfono celular viene de la dirección de envío
        $document->customer_cellphone = $deliveryAddress?->phone_mobile ?? null;
        $document->save();

        MailTemplateJob::dispatch($document, 'request');

        return response()->json([
            'status' => 'success',
            'message' => 'Document filled with order data successfully.',
            'data' => [
                'uid' => $document->uid,
                'order_reference' => $document->order_reference,
                'customer_name' => "{$document->customer_firstname} {$document->customer_lastname}",
                'customer_email' => $document->customer_email,
            ],
        ], 200);
    }

    /**
     * Sincroniza todos los documentos existentes con los datos de sus órdenes
     * Busca documentos sin datos desnormalizados y los llena desde Prestashop
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAllDocumentsWithOrders()
    {
        try {
            $synced = 0;
            $failed = 0;
            $errors = [];

            // Obtener documentos sin datos desnormalizados
            $documents = Document::get();

            if ($documents->isEmpty()) {

                return response()->json([
                    'status' => 'success',
                    'message' => 'No documents need synchronization.',
                    'data' => [
                        'synced' => 0,
                        'failed' => 0,
                        'total' => 0,
                    ],
                ], 200);
            }

            foreach ($documents as $document) {
                try {
                    // Obtener la orden
                    $order = PrestashopOrder::find($document->order_id);

                    if (! $order) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'order_id' => $document->order_id,
                            'reason' => 'Order not found in Prestashop',
                        ];

                        continue;
                    }

                    // Sincronizar datos de la orden y productos usando el helper
                    if (! $this->syncDocumentWithOrder($document, $order)) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'order_id' => $document->order_id,
                            'reason' => 'Customer not found',
                        ];

                        continue;
                    }

                    $synced++;

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'uid' => $document->uid,
                        'order_id' => $document->order_id,
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Synchronization completed. {$synced} documents synced, {$failed} failed.",
                'data' => [
                    'synced' => $synced,
                    'failed' => $failed,
                    'total' => $documents->count(),
                    'errors' => $failed > 0 ? $errors : [],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Synchronization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza documentos de una orden específica por query parameter
     * Recibe order_id como parámetro query e importa todos los datos y productos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncDocumentsByOrderQuery(Request $request)
    {
        $orderId = $request->query('id_order');

        if (! $orderId) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Missing required query parameter: id_order',
            ], 400);
        }

        try {
            // Obtener documentos asociados a la orden
            $documents = Document::where('order_id', $orderId)
                ->get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No documents found for this order ID.',
                ], 404);
            }

            // Obtener la orden
            $order = PrestashopOrder::find($orderId);

            if (! $order) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Order not found in Prestashop.',
                ], 404);
            }

            $synced = 0;
            $failed = 0;
            $errors = [];

            // Sincronizar todos los documentos de esta orden
            foreach ($documents as $document) {
                try {
                    if (! $this->syncDocumentWithOrder($document, $order)) {
                        $failed++;
                        $errors[] = [
                            'uid' => $document->uid,
                            'reason' => 'Customer not found',
                        ];

                        continue;
                    }
                    $synced++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'uid' => $document->uid,
                        'reason' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced {$synced} document(s) for order {$orderId}.",
                'data' => [
                    'order_id' => $orderId,
                    'synced' => $synced,
                    'failed' => $failed,
                    'total' => $documents->count(),
                    'order_reference' => $order->reference,
                    'customer_name' => $order->customer ? "{$order->customer->firstname} {$order->customer->lastname}" : null,
                    'errors' => $failed > 0 ? $errors : [],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Synchronization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincroniza un documento específico con los datos de su orden
     * Busca por order_id y llena todos los datos desnormalizados
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncDocumentByOrderId(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
        ]);

        $orderId = $request->input('order_id');

        try {

            $documents = Document::where('order_id', $orderId)->get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No documents found for this order ID.',
                ], 404);
            }

            // Obtener la orden
            $order = PrestashopOrder::find($orderId);

            if (! $order) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Order not found in Prestashop.',
                ], 404);
            }

            // Obtener el cliente
            $customer = $order->customer;

            if (! $customer) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Customer not found.',
                ], 404);
            }

            $synced = 0;

            // Sincronizar todos los documentos de esta orden
            foreach ($documents as $document) {
                $document->order_reference = $order->reference ?? $document->order_reference;
                $document->order_date = $order->date_add ?? $document->order_date;

                // Obtener dirección de envío
                $deliveryAddress = $order->deliveryAddress;

                $document->customer_id = $customer->id_customer;
                // Nombre y apellido vienen de la dirección de envío
                $document->customer_firstname = $deliveryAddress?->firstname ?? $customer->firstname;
                $document->customer_lastname = $deliveryAddress?->lastname ?? $customer->lastname;
                $document->customer_email = $customer->email;
                // DNI/SIRET vienen de la dirección de envío
                $document->customer_dni = $deliveryAddress?->dni ?? $deliveryAddress?->vat_number ?? null;
                // Empresa viene de la dirección de envío
                $document->customer_company = $deliveryAddress?->company ?? null;
                // Teléfono celular viene de la dirección de envío
                $document->customer_cellphone = $deliveryAddress?->phone_mobile ?? null;

                $document->save();
                $synced++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Successfully synced {$synced} document(s) for order {$orderId}.",
                'data' => [
                    'order_id' => $orderId,
                    'synced' => $synced,
                    'order_reference' => $order->reference,
                    'customer_name' => "{$customer->firstname} {$customer->lastname}",
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Synchronization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function orderPaid(Request $request)
    {
        $payload = $request->validate([
            'order_id' => 'required|integer',
            'document_type' => 'nullable|string|max:100',
            'force_reminder' => 'sometimes|boolean',
        ]);

        $order = PrestashopOrder::find($payload['order_id']);

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found in Prestashop database.',
            ], 404);
        }

        $document = Document::firstOrNew(['order_id' => $order->id_order]);
        $document->customer_id = $document->customer_id ?? $order->id_customer;
        $document->cart_id = $document->cart_id ?? $order->id_cart;
        $document->reference = $order->reference ?? $document->reference;

        // Ensure document is saved to establish relationships
        if (! $document->exists) {
            $document->save();
        }

        // Capture products from PrestaShop order if not already captured
        if ($document->products()->count() === 0) {
            $document->captureProducts();
        }

        // Validate document type against product blockades (source of truth)
        // PrestaShop's document_type is only a suggestion, blockades are authoritative
        $detectedTypeSlug = $document->detectDocumentType();
        $detectedTypeId = $this->resolveDocumentTypeId($detectedTypeSlug ?? $payload['document_type'] ?? 'general');

        if ($detectedTypeId) {
            $document->type_id = $detectedTypeId;
            $document->save();
        }

        if ($document->confirmed_at) {
            return response()->json([
                'status' => 'success',
                'message' => 'Document already uploaded. No further action needed.',
                'data' => [
                    'uid' => $document->uid,
                    'order_id' => $document->order_id,
                ],
            ], 200);
        }

        $forceReminder = (bool) ($payload['force_reminder'] ?? false);

        if (! $forceReminder && $document->reminder_at) {
            return response()->json([
                'status' => 'success',
                'message' => 'Reminder already sent previously.',
                'data' => [
                    'uid' => $document->uid,
                    'order_id' => $document->order_id,
                ],
            ], 200);
        }

        MailTemplateJob::dispatch($document, 'reminder');

        $document->reminder_at = now();
        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document reminder queued successfully.',
            'data' => [
                'uid' => $document->uid,
                'order_id' => $document->order_id,
            ],
        ], 200);
    }

    /**
     * Sincroniza campos de documentos requeridos/subidos desde el tipo de documento
     */
    public function syncAllDocumentFields(Request $request)
    {
        try {
            $typeSlug = $request->query('type');  // Filter by type slug
            $force = $request->query('force', false);

            // Query documents, filtering by document type slug if provided
            $query = Document::query();
            if ($typeSlug) {
                // Filter by DocumentType slug through relationship
                $query->whereHas('documentType', function ($q) use ($typeSlug) {
                    $q->where('slug', $typeSlug);
                });
            }
            $documents = $query->get();

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
     * Obtiene el estado completo del documento en formato JSON
     */
    public function getDocumentState($uid)
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Obtener documentos requeridos y faltantes desde el modelo
            $requiredDocuments = $document->getRequiredDocuments();
            $missingDocuments = $document->getMissingDocuments();
            $uploadedDocuments = $document->uploaded_documents ?? [];
            $uploadedDocumentsDetails = $document->getUploadedDocumentsWithDetails();

            return response()->json([
                'success' => true,
                'document' => [
                    'uid' => $document->uid,
                    'type' => $document->type,
                    'confirmed_at' => $document->confirmed_at?->format('d/m/Y H:i'),
                ],
                'required_documents' => $requiredDocuments,
                'uploaded_documents' => $uploadedDocuments,
                'uploaded_documents_details' => $uploadedDocumentsDetails,
                'missing_documents' => $missingDocuments,
                'all_uploaded' => empty($missingDocuments),
                'stats' => [
                    'total_required' => count($requiredDocuments),
                    'total_uploaded' => count($uploadedDocuments),
                    'total_missing' => count($missingDocuments),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un archivo específico de un documento
     */
    public function deleteSingleDocument(Request $request, $uid)
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            $mediaId = $request->input('media_id');

            if (! $mediaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de media no proporcionado',
                ], 400);
            }

            $media = Media::find($mediaId);

            if (! $media || $media->model_id !== $document->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado o no pertenece a este documento',
                ], 404);
            }

            $media->delete();

            // Sincronizar JSON de documentos subidos
            $document->syncUploadedDocumentsJson();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente',
                'uploaded_documents' => $document->uploaded_documents,
                'missing_documents' => $document->getMissingDocuments(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sube un archivo al documento
     */
    public function storeFiles(Request $request, $uid)
    {
        // Validación de archivo
        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $document = Document::findByUid($uid);

        if (! $document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        $type = 'documents';

        $document->clearMediaCollection($type);

        // Sanitizar nombre de archivo
        $file = $request->file('file');
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        $media = $document->addMediaFromRequest('file')
            ->usingFileName($sanitizedName)
            ->toMediaCollection($type);

        // Asegurar que el archivo es accesible al servidor web
        $mediaPath = $media->getPath();
        if (file_exists($mediaPath)) {
            @chmod($mediaPath, 0644);
        }
        $mediaDir = dirname($mediaPath);
        if (is_dir($mediaDir)) {
            @chmod($mediaDir, 0755);
        }

        // Procesar upload: enviar confirmación
        app(DocumentEmailService::class)->processDocumentUpload($document);

        return response()->json([
            'status' => 'success',
            'statement_id' => $document->id,
            'media' => [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'file' => $media->file_name,
                'size' => $media->size,
                'path' => $media->getUrl(),
            ],
        ]);
    }

    /**
     * Obtiene los archivos de un documento
     */
    public function getFiles($id)
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json([], 404);
        }

        // Verificar que el media pertenece a un documento
        if ($media->model_type !== Document::class) {
            return response()->json([
                'status' => 'error',
                'message' => 'Acceso no autorizado.',
            ], 403);
        }

        return response()->json([[
            'id' => $media->id,
            'uuid' => $media->uuid,
            'file' => $media->file_name,
            'size' => $media->size,
            'path' => $media->getUrl(),
        ]]);
    }

    /**
     * Elimina un archivo por su ID
     */
    public function deleteFiles($id)
    {
        $media = Media::find($id);

        if (! $media) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Verificar que el media pertenece a un documento
        if ($media->model_type !== Document::class) {
            return response()->json([
                'status' => 'error',
                'message' => 'Acceso no autorizado.',
            ], 403);
        }

        // Verificar que el documento existe
        $document = Document::find($media->model_id);
        if (! $document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Documento asociado no encontrado.',
            ], 404);
        }

        $media->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Handle Prestashop webhook for paid orders
     * Creates or updates documents when an order is marked as paid in Prestashop
     */
    public function prestashopOrderPaid(Request $request)
    {
        try {

            $orderId = $request->input('order_id') ?? $request->input('id_order');

            if (! $orderId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required',
                ], 400);
            }

            // Fetch order from Prestashop
            $order = PrestashopOrder::find($orderId);
            if (! $order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found in Prestashop',
                ], 404);
            }

            // Create document for this order if it doesn't exist
            $document = Document::where('order_id', $orderId)->first();
            if (! $document) {
                $document = Document::create([
                    'order_id' => $orderId,
                    'customer_id' => $order->id_customer,
                    'document_status_id' => DocumentStatus::where('slug', 'pending')->first()?->id,
                    'source_id' => DocumentSource::where('slug', 'prestashop')->first()?->id,
                ]);
            }

            Log::info('Prestashop order paid webhook processed', [
                'order_id' => $orderId,
                'document_id' => $document->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document created/updated for paid order',
                'document_id' => $document->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing Prestashop order paid webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process webhook',
            ], 500);
        }
    }

    /**
     * Actualizar documento
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'uid' => 'required|exists:documents,uid',
            'data' => 'nullable|array',
        ]);

        try {
            $document = Document::where('uid', $validated['uid'])->firstOrFail();
            if ($validated['data'] ?? null) {
                $document->update($validated['data']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento actualizado',
                'document' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Upload de archivo de admin
     */
    public function adminUploadDocument(Request $request, $uid)
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'type' => 'nullable|string',
        ]);

        try {
            if ($request->hasFile('file')) {
                $media = $document->addMediaFromRequest('file')
                    ->toMediaCollection('documents');

                return response()->json([
                    'success' => true,
                    'message' => 'Archivo cargado exitosamente',
                    'media' => $media,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No file provided',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener adjuntos adicionales
     */
    public function getAdditionalAttachments($uid)
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        $attachments = $document->getMedia('attachments');

        return response()->json([
            'success' => true,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Subir adjunto adicional
     */
    public function uploadAdditionalAttachment(Request $request, $uid)
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        $validated = $request->validate([
            'attachment' => 'required|file|max:10240',
        ]);

        try {
            if ($request->hasFile('attachment')) {
                $media = $document->addMediaFromRequest('attachment')
                    ->toMediaCollection('attachments');

                return response()->json([
                    'success' => true,
                    'message' => 'Adjunto subido exitosamente',
                    'media' => $media,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No file provided',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar adjunto adicional
     */
    public function deleteAdditionalAttachment($uid, $attachmentId)
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        try {
            $media = $document->getMedia('attachments')->find($attachmentId);
            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment not found',
                ], 404);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Adjunto eliminado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Refrescar sección de documentos
     */
    public function refreshDocumentsSection($uid)
    {
        // Liberar la sesión inmediatamente para evitar bloqueos con peticiones concurrentes
        if (request()->hasSession()) {
            session()->save();
            session()->migrate(false);
        }

        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Obtener tipo de documento desde la relación
            $documentType = $document->documentType?->load('requirements');
            $requiredDocuments = $documentType?->getRequiredDocuments() ?? [];

            // Obtener documentos ya cargados organizados por tipo
            // IMPORTANTE: Solo obtener media de la colección 'documents' (documentos requeridos)
            $uploadedDocs = [];
            $allMedia = $document->media()->where('collection_name', 'documents')->get();

            foreach ($allMedia as $media) {
                $docType = $media->getCustomProperty('document_type', 'documento');
                $uploadedDocs[$docType] = $media;
            }

            // Calcular documentos faltantes
            $missingDocs = array_diff_key($requiredDocuments, $uploadedDocs);
            $allUploaded = empty($missingDocs);

            // Renderizar solo la sección de carga de documentos
            $html = view('documents::documents.documents.components.files.upload-section', [
                'document' => $document,
                'requiredDocuments' => $requiredDocuments,
                'uploadedDocs' => $uploadedDocs,
                'missingDocs' => $missingDocs,
                'allUploaded' => $allUploaded,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al refrescar sección de documentos', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar la sección: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refrescar sección de gestión del documento (document-management)
     */
    public function refreshManagementSection($uid)
    {
        if (request()->hasSession()) {
            session()->save();
            session()->migrate(false);
        }

        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Obtener datos necesarios para el componente
            $statuses = DocumentStatus::all();
            $documentSources = DocumentSource::all();
            $documentLoads = DocumentLoad::all();
            $documentSyncs = DocumentSync::all();
            $uploadTypes = DocumentUploadType::all();

            // Renderizar el componente actualizado
            $html = view('documents::documents.documents.components.management.document-management', [
                'document' => $document,
                'statuses' => $statuses,
                'documentSources' => $documentSources,
                'documentLoads' => $documentLoads,
                'documentSyncs' => $documentSyncs,
                'uploadTypes' => $uploadTypes,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al refrescar sección de gestión', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar la sección: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refreshActionHistory($uid)
    {
        // Liberar la sesión inmediatamente para evitar bloqueos con peticiones concurrentes
        if (request()->hasSession()) {
            session()->save();
            session()->migrate(false);
        }

        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // IMPORTANTE: La relación es 'performer', no 'user'
            $document->load(['actions' => fn ($q) => $q->with('performer')->orderBy('created_at', 'desc')]);

            // Renderizar el componente de historial de acciones
            $html = view('documents::documents.documents.components.management.action-history', [
                'document' => $document,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al refrescar historial de acciones', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar el historial: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy($uid)
    {
        $document = Document::where('uid', $uid)->firstOrFail();

        try {
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function resolveDocumentTypeId(?string $typeSlug): ?int
    {
        if (! $typeSlug) {
            return null;
        }

        $documentType = DocumentType::where('slug', $typeSlug)->first();

        return $documentType?->id;
    }
}
