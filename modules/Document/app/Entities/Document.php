<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Document\Services\DocumentMailService;
use Modules\Document\Traits\HasUid;
use Modules\Document\Traits\HasValidationWorkflow;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasFactory, HasUid, HasValidationWorkflow, InteractsWithMedia;

    protected $table = 'documents';

    protected $casts = [
        'confirmed_at' => 'datetime',
        'uploaded_confirmation_sent_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'order_date' => 'datetime',
        'required_documents' => 'array',
        'uploaded_documents' => 'array',
        'additional_attachments' => 'array',
        'upload_type' => 'string',
        'requires_financing' => 'boolean',
        'current_stage' => 'integer',
        'total_stages' => 'integer',
        'validation_started_at' => 'datetime',
        'validation_completed_at' => 'datetime',
    ];

    protected $fillable = [
        'uid',
        'type_id',
        'source_id',
        'load_id',
        'sync_id',
        'upload_id',
        'lang_id',
        'confirmed_at',
        'uploaded_confirmation_sent_at',
        'reminder_at',
        'reminder_sent_at',
        'order_id',
        'customer_id',
        'cart_id',
        'order_reference',
        'order_date',
        'customer_firstname',
        'customer_lastname',
        'customer_email',
        'customer_cellphone',
        'customer_dni',
        'customer_company',
        'required_documents',
        'uploaded_documents',
        'additional_attachments',
        'status_id',
        'sla_policy_id',
        'requires_financing',
        'validation_status',
        'current_stage',
        'total_stages',
        'current_validator_group',
        'assigned_user_id',
        'validation_started_at',
        'validation_completed_at',
        'created_at',
        'updated_at',
    ];

    // =========================================================================
    // MUTATORS - Automatic field transformations
    // =========================================================================

    protected function customerFirstname(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    protected function customerLastname(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    protected function customerCompany(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeOrder($query, $order)
    {
        return $query->where('order_id', $order);
    }

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid);
    }

    /**
     * Filtra documentos por estado de carga (con o sin media)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $hasMedia  1 = con media, 0 = sin media, null = todos
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByUploadStatus($query, $hasMedia = null)
    {
        if ($hasMedia === null) {
            return $query;
        }

        return $query->whereRaw('EXISTS (
            SELECT 1 FROM media
            WHERE media.model_id = documents.id
              AND media.model_type = ?
        ) = ?', [self::class, $hasMedia === 1 ? 1 : 0]);
    }

    /**
     * Busca documentos por nombre de cliente, ID de orden u orden reference
     * Busca tanto en datos denormalizados como en relaciones
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search  Término de búsqueda
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByCustomerOrOrder($query, $search = '')
    {
        if (empty($search)) {
            return $query;
        }

        $search = strtolower($search);

        return $query
            ->where(function ($q) use ($search) {
                // Buscar en datos denormalizados del cliente
                $q->whereRaw('LOWER(customer_firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(customer_lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(customer_email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(customer_dni) LIKE ?', ["%{$search}%"])
                    // Buscar por ID de orden
                    ->orWhereRaw('CAST(order_id AS CHAR) LIKE ?', ["%{$search}%"])
                    // Buscar por referencia de orden denormalizada
                    ->orWhereRaw('LOWER(order_reference) LIKE ?', ["%{$search}%"]);
            });
    }

    /**
     * Ordena documentos por prioridad (sin carga primero), fecha de creación y agrupa por día
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByUploadPriority($query)
    {
        return $query
            ->orderByRaw('CASE WHEN EXISTS (
                SELECT 1 FROM media
                WHERE media.model_id = documents.id
                AND media.model_type = ?
            ) THEN 1 ELSE 0 END ASC', [self::class])
            ->orderBy('documents.created_at', 'desc');
    }

    /**
     * Filtra documentos por rango de fechas
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $dateFrom  Fecha inicial en formato Y-m-d
     * @param  string|null  $dateTo  Fecha final en formato Y-m-d
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByDateRange($query, $dateFrom = null, $dateTo = null)
    {
        if ($dateFrom) {
            try {
                $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
                $query->whereDate('created_at', '>=', $startDate);
            } catch (\Exception $e) {
                // Si la fecha es inválida, ignorar el filtro
            }
        }

        if ($dateTo) {
            try {
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();
                $query->whereDate('created_at', '<=', $endDate);
            } catch (\Exception $e) {
                // Si la fecha es inválida, ignorar el filtro
            }
        }

        return $query;
    }

    /**
     * Consulta optimizada para listar documentos en admin
     * Combina filtrado, búsqueda, filtrado por fechas y ordenamiento
     * Nota: Usa datos denormalizados sin cargar relaciones para mejor performance
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search  Término de búsqueda
     * @param  int|null  $uploadStatus  1 = con media, 0 = sin media, null = todos
     * @param  string|null  $dateFrom  Fecha inicial en formato Y-m-d
     * @param  string|null  $dateTo  Fecha final en formato Y-m-d
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterListing($query, $search = '', $uploadStatus = null, $dateFrom = null, $dateTo = null)
    {
        return $query
            ->filterByUploadStatus($uploadStatus)
            ->searchByCustomerOrOrder($search)
            ->filterByDateRange($dateFrom, $dateTo)
            ->orderByUploadPriority();
    }

    public function getAllDocumentsUrls(): array
    {
        return $this->getMedia('documents')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }

    public function getDocumentUrl(): ?string
    {
        $media = $this->getFirstMedia('documents');

        return $media ? $media->getUrl() : null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo('Modules\Prestashop\Entities\Orders\Order', 'order_id', 'id_order');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('Modules\Prestashop\Entities\Customer', 'customer_id', 'id_customer');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo('Modules\Prestashop\Entities\Cart', 'cart_id', 'id_cart');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('Modules\Document\Entities\DocumentLang', 'lang_id', 'id');
    }

    /**
     * Usuario asignado específicamente para validar el documento
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'assigned_user_id');
    }

    /**
     * Relación con los productos del documento
     */
    public function products()
    {
        return $this->hasMany(DocumentProduct::class, 'document_id');
    }

    /**
     * Relación con el historial de acciones
     */
    public function actions()
    {
        return $this->hasMany(DocumentAction::class, 'document_id');
    }

    /**
     * Relación con las notas del documento
     */
    public function notes()
    {
        return $this->hasMany(DocumentNote::class, 'document_id');
    }

    /**
     * Relación con el estado del documento
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'status_id');
    }

    /**
     * Relación con la fuente/origen del documento
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(DocumentSource::class, 'source_id');
    }

    /**
     * Relación con el tipo de carga (automatic, manual)
     */
    public function uploadType(): BelongsTo
    {
        return $this->belongsTo(DocumentUploadType::class, 'upload_id');
    }

    /**
     * Relación con el origen de carga (manual, email, api, erp)
     */
    public function documentLoad(): BelongsTo
    {
        return $this->belongsTo(DocumentLoad::class, 'load_id');
    }

    /**
     * Relación con el tipo de sincronización (manual, automatic)
     */
    public function sync(): BelongsTo
    {
        return $this->belongsTo(DocumentSync::class, 'sync_id');
    }

    /**
     * Relación con el historial de cambios de estado
     */
    public function statusHistories()
    {
        return $this->hasMany(DocumentStatusHistory::class, 'document_id');
    }

    /**
     * Relación con la política SLA del documento
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(DocumentSlaPolicy::class, 'sla_policy_id');
    }

    /**
     * Relación con los incumplimientos SLA del documento
     */
    public function slaBreaches()
    {
        return $this->hasMany(DocumentSlaBreach::class, 'document_id');
    }

    /**
     * Relación con los emails enviados para este documento
     */
    public function mails()
    {
        return $this->hasMany(DocumentMail::class, 'document_id');
    }

    /**
     * Relación con el tipo de documento
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id');
    }

    // =========================================================================
    // ACCESSORS - Map type_id to readable attributes
    // =========================================================================

    /**
     * Obtiene el slug del tipo de documento desde la relación
     * Permite acceder como $document->type en lugar de $document->documentType->slug
     *
     * @return string|null El slug del tipo de documento (ej: 'dni', 'corta', etc.)
     */
    public function getTypeAttribute(): ?string
    {
        return $this->documentType?->slug;
    }

    // =========================================================================
    // VALIDATION WORKFLOW CONFIGURATION
    // =========================================================================

    /**
     * Define the validation workflow stages for documents.
     * Retrieves stages from DocumentType configuration and evaluates conditions.
     *
     * @return array<string> Array of ValidatorGroup keys in order (only stages that pass conditions)
     */
    public function getValidationWorkflowStages(): array
    {
        // Try to get stages from DocumentType configuration
        if (! empty($this->type_id)) {
            $documentType = $this->documentType;

            if ($documentType && ! empty($documentType->validation_stages)) {
                // Get stages with conditions
                $stagesWithConditions = $documentType->getValidationStagesWithConditions();

                // Filter stages based on conditions
                $validStages = [];
                foreach ($stagesWithConditions as $stage) {
                    if ($this->evaluateStageConditions($stage['conditions'] ?? [])) {
                        $validStages[] = $stage['key'];
                    }
                }

                // Return filtered stages if any passed conditions, otherwise fallback
                if (! empty($validStages)) {
                    return $validStages;
                }
            }
        }

        // Fallback: If no DocumentType found or no stages configured,
        // use legacy logic based on product type and financing
        return $this->getLegacyValidationStages();
    }

    /**
     * Evaluate if a stage's conditions are met for this document
     *
     * Supported conditions:
     * - 'requires_financing' => true/false - Document requires financing
     * - 'is_weapon' => true/false - Document is for weapon sale (escopeta/rifle/corta)
     * - 'is_dni_only' => true/false - Document only requires DNI (no weapons)
     * - Custom conditions can be added here
     *
     * @param  array  $conditions  Key-value pairs of conditions to check
     * @return bool True if all conditions pass (empty conditions = always pass)
     */
    protected function evaluateStageConditions(array $conditions): bool
    {
        // Empty conditions = stage always applies
        if (empty($conditions)) {
            return true;
        }

        // Check each condition
        foreach ($conditions as $key => $expectedValue) {
            $actualValue = $this->getConditionValue($key);

            // Normalize both values to boolean for comparison
            // This handles JSON storing 1/0 while PHP returns true/false
            $actualBool = (bool) $actualValue;
            $expectedBool = (bool) $expectedValue;

            // If expected value doesn't match actual value, condition fails
            if ($actualBool !== $expectedBool) {
                return false;
            }
        }

        // All conditions passed
        return true;
    }

    /**
     * Get a condition value dynamically from database or document attributes
     * Completely dynamic - supports any condition added to DocumentValidationCondition
     * No hardcoded condition names - fully extensible
     *
     * @param  string  $key  The condition key (e.g., 'requires_financing', 'is_weapon')
     * @return mixed Boolean for evaluated conditions, null if not found
     */
    protected function getConditionValue(string $key): mixed
    {
        // Check if this is a built-in document attribute
        if ($key === 'requires_financing') {
            return (bool) $this->requires_financing;
        }

        // For all other conditions, dynamically query DocumentValidationCondition
        // This makes the system completely extensible - any new condition can be added to database
        $condition = DocumentValidationCondition::getByKey($key);

        if (! $condition) {
            // Condition not found in database
            return null;
        }

        // Evaluate the condition against document type
        if ($this->documentType) {
            return $condition->matches($this->documentType->slug);
        }

        // Fallback: evaluate against sale type from blockades
        $saleType = $this->getSaleType();
        if ($saleType) {
            return $condition->matches($saleType);
        }

        // No way to evaluate - return false
        return false;
    }

    /**
     * Determine if this document is for a weapon sale
     * Dynamically checks DocumentValidationCondition configuration
     */
    protected function isWeapon(): bool
    {
        // Primary: check DocumentType slug
        if ($this->documentType) {
            $documentTypeSlug = $this->documentType->slug;

            // Check centralized validation condition (cached)
            $condition = DocumentValidationCondition::getByKey('is_weapon');

            if ($condition) {
                return $condition->matches($documentTypeSlug);
            }

            // No condition configured - return false
            return false;
        }

        // Fallback: check blockade/sale type if no document type
        $saleType = $this->getSaleType();

        if (empty($saleType)) {
            return false;
        }

        // Check centralized validation condition (cached)
        $condition = DocumentValidationCondition::getByKey('is_weapon');

        if ($condition) {
            return $condition->matches($saleType);
        }

        // No condition configured - return false
        return false;
    }

    /**
     * Determine if this document only requires DNI (no weapons)
     * Dynamically checks DocumentValidationCondition configuration
     */
    protected function isDniOnly(): bool
    {
        // Primary: check DocumentType slug
        if ($this->documentType) {
            $documentTypeSlug = $this->documentType->slug;

            // Check centralized validation condition (cached)
            $condition = DocumentValidationCondition::getByKey('is_dni_only');

            if ($condition) {
                return $condition->matches($documentTypeSlug);
            }

            // No condition configured - return false
            return false;
        }

        // Fallback: check blockade/sale type if no document type
        $saleType = $this->getSaleType();

        if (empty($saleType)) {
            return false;
        }

        // Check centralized validation condition (cached)
        $condition = DocumentValidationCondition::getByKey('is_dni_only');

        if ($condition) {
            return $condition->matches($saleType);
        }

        // No condition configured - return false
        return false;
    }

    /**
     * Legacy validation stages logic (fallback) - NOW COMPLETELY DYNAMIC
     * Used when getValidationWorkflowStages() returns empty
     * Gets stages from DocumentType, evaluating conditions dynamically
     * Zero hardcoded stage names - all from database
     *
     * @return array<string>
     */
    protected function getLegacyValidationStages(): array
    {
        // Get all available stages from DocumentType (if exists)
        if ($this->type_id && $this->documentType) {
            $stagesWithConditions = $this->documentType->getValidationStagesWithConditions();

            // Filter stages based on document conditions
            $validStages = [];
            foreach ($stagesWithConditions as $stage) {
                if ($this->evaluateStageConditions($stage['conditions'] ?? [])) {
                    $validStages[] = $stage['key'];
                }
            }

            // Return filtered stages from DocumentType
            if (! empty($validStages)) {
                return $validStages;
            }
        }

        // Final fallback: use general DocumentType stages
        $generalType = DocumentType::where('slug', 'general')->first();
        if ($generalType) {
            $stagesWithConditions = $generalType->getValidationStagesWithConditions();

            $validStages = [];
            foreach ($stagesWithConditions as $stage) {
                if ($this->evaluateStageConditions($stage['conditions'] ?? [])) {
                    $validStages[] = $stage['key'];
                }
            }

            if (! empty($validStages)) {
                return $validStages;
            }
        }

        // Last resort: return empty array
        // The system should always have a DocumentType configured
        return [];
    }

    /**
     * Initialize the document validation workflow based on its type.
     * Alias for initializeWorkflow() for backwards compatibility.
     */
    public function setupValidationWorkflow(): bool
    {
        return $this->initializeWorkflow();
    }

    /**
     * Detecta el tipo de documento basándose en blockades desde document_product_blockades
     * Ahora completamente dinámico - usa la configuración de database, no hardcoded
     * Retorna el slug del DocumentType asociado al primer blockade encontrado
     */
    public function detectDocumentType(): ?string
    {
        try {
            // Obtener el tipo de venta del documento desde blockades
            $saleType = $this->getSaleType();

            if (empty($saleType)) {
                return 'general';
            }

            // Return the detected sale type (which is now the DocumentType slug)
            return $saleType;

        } catch (\Exception $e) {
            Log::error('Error detectando tipo de documento: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return 'general';
        }
    }

    /**
     * Envía email de notificación inicial pidiendo carga de documentación
     * Envío síncrono (directo, sin pasar por la cola)
     */
    public function sendUploadNotification(): bool
    {
        return DocumentMailService::sendUploadNotification($this);
    }

    /**
     * Envía email de recordatorio para cargar documentación
     * Envío síncrono (directo, sin pasar por la cola)
     */
    public function sendReminder(): bool
    {
        return DocumentMailService::sendReminder($this);
    }

    /**
     * Envía email de confirmación cuando la documentación es cargada
     * Envío síncrono (directo, sin pasar por la cola)
     */
    public function sendUploadedConfirmation(): bool
    {
        return DocumentMailService::sendUploadedConfirmation($this);
    }

    /**
     * Captura y sincroniza los productos de la orden Prestashop
     * Importa los productos de la tabla order_detail de Prestashop
     */
    public function captureProducts()
    {
        try {
            // Limpiar productos previos
            $this->products()->delete();

            // Obtener productos directamente de la BD de Prestashop
            $orderProducts = DB::connection('prestashop')
                ->table('aalv_order_detail')
                ->where('id_order', $this->order_id)
                ->get();

            if ($orderProducts->isEmpty()) {
                return false;
            }

            // Insertar productos en el documento
            foreach ($orderProducts as $orderProduct) {
                DocumentProduct::create([
                    'document_id' => $this->id,
                    'product_id' => $orderProduct->product_id ?? null,
                    'product_name' => $orderProduct->product_name ?? null,
                    'product_reference' => $orderProduct->product_reference ?? null,
                    'quantity' => $orderProduct->product_quantity ?? 0,
                    'price' => $orderProduct->unit_price_tax_incl ?? 0,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error capturando productos del documento: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Obtiene solo las keys de documentos requeridos (estructura simplificada)
     * Retorna array simple: ["dni_frontal", "dni_trasera", "licencia"]
     *
     * @return array Array de keys de documentos requeridos
     */
    public function getRequiredDocuments(): array
    {
        $documentsWithLabels = $this->getRequiredDocumentsWithLabels();

        return array_keys($documentsWithLabels);
    }

    /**
     * Obtiene documentos requeridos con sus labels descriptivos
     * Retorna array asociativo: {"dni_frontal": "DNI - Cara delantera", ...}
     *
     * @return array Array con keys y labels
     */
    public function getRequiredDocumentsWithLabels(): array
    {
        // Obtener configuración del tipo de documento desde la relación type_id
        $documentType = $this->documentType;

        if ($documentType) {
            // Pass the document's language ID to get translated labels
            return $documentType->getRequiredDocuments($this->lang_id);
        }

        return $this->getDefaultDocuments();
    }

    /**
     * Obtiene documentos requeridos por defecto del DocumentType configurado
     * Ahora completamente dinámico - lee la configuración de DocumentType
     * NO usar valores hardcodeados - todo debe venir de database
     */
    private function getDefaultDocuments(): array
    {
        // If document has a type_id, get defaults from DocumentType
        if ($this->type_id && $this->documentType) {
            $defaults = $this->documentType->required_documents ?? [];

            if (! empty($defaults)) {
                return $defaults;
            }
        }

        // Fallback: retornar array vacío si no hay configuración
        // El sistema debe confiar en la configuración de DocumentType, no en defaults hardcodeados
        return [];
    }

    /**
     * Obtiene el estado de los documentos (cargados vs faltantes)
     * Retorna un array con información de documentos cargados y pendientes
     */
    public function getDocumentStatus(): array
    {
        $requiredDocuments = $this->getRequiredDocuments();
        $uploadedDocuments = $this->getUploadedDocumentTypes();
        $missingDocuments = $this->getMissingDocuments();

        return [
            'total_required' => count($requiredDocuments),
            'total_uploaded' => count($uploadedDocuments),
            'required_documents' => $requiredDocuments,
            'uploaded_documents' => $uploadedDocuments,
            'missing_documents' => $missingDocuments,
            'is_complete' => empty($missingDocuments),
        ];
    }

    /**
     * Actualiza el estado JSON de documentos requeridos
     * Se llama cuando se detecta el tipo de documento
     */
    public function updateRequiredDocumentsJson(): void
    {
        $this->required_documents = $this->getRequiredDocuments();
        $this->save();
    }

    /**
     * Actualiza el estado JSON de documentos cargados (DEPRECATED)
     * Usar syncUploadedDocumentsJson() en su lugar
     */
    public function updateUploadedDocumentsJson(): void
    {
        $this->syncUploadedDocumentsJson();
    }

    /**
     * Obtiene documentos faltantes comparando requeridos vs subidos
     * Retorna array asociativo con estructura: {"dni_trasera": "DNI - Cara trasera"}
     *
     * @return array Array de documentos faltantes con labels
     */
    public function getMissingDocuments(): array
    {
        $requiredWithLabels = $this->getRequiredDocumentsWithLabels();
        $uploadedKeys = $this->getUploadedDocumentTypes();

        // Usar array_diff_key para obtener solo los documentos requeridos que NO están subidos
        return array_diff_key($requiredWithLabels, array_flip($uploadedKeys));
    }

    /**
     * Obtiene los tipos de documentos ya cargados (solo keys)
     * La información completa de archivos está en media
     *
     * @return array Array de doc_types que han sido cargados: ["doc_1", "doc_2"]
     */
    public function getUploadedDocumentTypes(): array
    {
        $uploadedTypes = [];

        foreach ($this->getMedia('documents') as $media) {
            $docType = $media->getCustomProperty('document_type');
            if ($docType && ! in_array($docType, $uploadedTypes)) {
                $uploadedTypes[] = $docType;
            }
        }

        return $uploadedTypes;
    }

    /**
     * Obtiene los documentos cargados con detalles completos de archivos
     * Combina keys de uploaded_documents con información de media
     *
     * @return array Documentos con detalles: ["doc_1" => ["id", "url", "size", ...]]
     */
    public function getUploadedDocumentsWithDetails(): array
    {
        $uploadedWithDetails = [];

        foreach ($this->getMedia('documents') as $media) {
            $docType = $media->getCustomProperty('document_type');
            if ($docType) {
                $uploadedWithDetails[$docType] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $uploadedWithDetails;
    }

    /**
     * Verifica si todos los documentos requeridos están subidos
     *
     * @return bool True si todos los documentos requeridos están presentes
     */
    public function hasAllRequiredDocuments(): bool
    {
        return empty($this->getMissingDocuments());
    }

    /**
     * Sincroniza el campo JSON uploaded_documents con los archivos media actuales
     * Debe llamarse después de cada operación de carga/eliminación de archivos
     */
    public function syncUploadedDocumentsJson(): void
    {
        $this->uploaded_documents = $this->getUploadedDocumentTypes();
        $this->save();
    }

    /**
     * Obtiene los adjuntos adicionales con detalles completos
     * Estos son documentos extras cargados por administrativos
     *
     * @return array Adjuntos con detalles: [["id", "name", "url", "size", "uploaded_at", "uploaded_by"]]
     */
    public function getAdditionalAttachmentsWithDetails(): array
    {
        $attachments = [];

        foreach ($this->getMedia('additional_attachments') as $media) {
            $attachments[] = [
                'id' => $media->id,
                'name' => $media->getCustomProperty('original_name', $media->file_name),
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'url' => parse_url($media->getUrl(), PHP_URL_PATH),
                'uploaded_at' => $media->created_at->format('Y-m-d H:i:s'),
                'uploaded_by' => $media->getCustomProperty('uploaded_by'),
                'notes' => $media->getCustomProperty('notes'),
            ];
        }

        return $attachments;
    }

    /**
     * Sincroniza el campo JSON additional_attachments con los archivos media actuales
     */
    public function syncAdditionalAttachmentsJson(): void
    {
        $this->additional_attachments = $this->getAdditionalAttachmentsWithDetails();
        $this->save();
    }

    /**
     * Verifica si tiene adjuntos adicionales
     */
    public function hasAdditionalAttachments(): bool
    {
        return $this->getMedia('additional_attachments')->isNotEmpty();
    }

    /**
     * Static helper to safely retrieve a document by UID
     * Encapsulates the uid() scope to ensure explicit retrieval of a single model instance
     */
    public static function findByUid(?string $uid): ?self
    {
        if (! $uid) {
            return null;
        }

        return self::uid($uid)->first();
    }

    /**
     * Check if any product in this document requires DNI
     * Uses the new DocumentProductBlockade system instead of PrestaShop features
     */
    public function hasDniRequiredProducts(): bool
    {
        $productIds = $this->products()->pluck('product_id')->filter()->toArray();

        if (empty($productIds)) {
            return false;
        }

        // Check for DNI blockade in both simple products and combinations
        return DocumentProductBlockade::where(function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)
                ->orWhereIn('product_attribute_id', $productIds);
        })
            ->whereHas('documentType', function ($query) {
                $query->where('slug', 'dni');
            })
            ->exists();
    }

    /**
     * Get the primary sale type (blockade type) for this document's products
     * Returns the DocumentType slug, prioritizing weapon types over dni
     */
    public function getSaleType(): ?string
    {
        $productIds = $this->products()->pluck('product_id')->filter()->toArray();

        if (empty($productIds)) {
            return null;
        }

        // Get ALL blockade types for these products
        $blockadeTypes = DocumentProductBlockade::where(function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)
                ->orWhereIn('product_attribute_id', $productIds);
        })
            ->with('documentType:id,slug')
            ->get()
            ->pluck('documentType.slug')
            ->filter()
            ->unique()
            ->toArray();

        if (empty($blockadeTypes)) {
            return null;
        }

        // Prioritize weapon types over 'dni'
        // Weapons are more restrictive than dni-only documents
        $weaponTypes = ['escopeta', 'rifle', 'corta'];
        foreach ($weaponTypes as $weaponType) {
            if (in_array($weaponType, $blockadeTypes)) {
                return $weaponType;
            }
        }

        // If no weapon types found, return the first blockade type
        return $blockadeTypes[0];
    }

    /**
     * Get all unique blockade types for this document's products
     * Return array of blockade type strings (e.g., ['dni', 'escopeta'])
     */
    public function getAllBlockadeTypes(): array
    {
        $productIds = $this->products()->pluck('product_id')->filter()->toArray();

        if (empty($productIds)) {
            return [];
        }

        // Get all blockade types for these products (simple products or combinations)
        return DocumentProductBlockade::where(function ($query) use ($productIds) {
            $query->whereIn('product_id', $productIds)
                ->orWhereIn('product_attribute_id', $productIds);
        })
            ->with('documentType:id,slug')
            ->get()
            ->pluck('documentType.slug')
            ->filter()
            ->unique()
            ->toArray();
    }

    /**
     * Initialize the validation workflow for this document
     * Uses the HasValidationWorkflow trait
     */
    public function initializeWorkflow(): bool
    {
        $stages = $this->getValidationWorkflowStages();

        return $this->initializeValidationWorkflow($stages);
    }

    /**
     * Recalculate workflow stages when document conditions change (e.g., requires_financing)
     * This preserves the current progress without resetting the workflow to pending
     */
    public function recalculateWorkflowStages(): bool
    {
        try {
            $stages = $this->getValidationWorkflowStages();
            $newTotalStages = count($stages);

            if (empty($stages)) {
                return false;
            }

            // If stage count changed
            if ($newTotalStages !== $this->total_stages) {
                $oldTotalStages = $this->total_stages;
                $this->total_stages = $newTotalStages;

                // If adding stages, keep current progress
                if ($newTotalStages > $oldTotalStages) {
                    // Just update total_stages, keep current stage and status
                }
                // If removing stages, adjust if needed
                elseif ($newTotalStages < $oldTotalStages) {
                    if ($this->current_stage > $newTotalStages) {
                        $this->current_stage = $newTotalStages;
                        $this->current_validator_group = $stages[$newTotalStages - 1] ?? null;

                        // Only change status if not already approved
                        if ($this->validation_status !== self::VALIDATION_STATUS_APPROVED) {
                            $this->validation_status = self::VALIDATION_STATUS_IN_VALIDATION;
                        }
                    }
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Error recalculating workflow stages for document '.$this->uid.': '.$e->getMessage());
            return false;
        }
    }

    /**
     * Boot method to initialize required_documents when creating a document
     * Uses getRequiredDocuments() to fetch keys based on document type
     * Laravel automatically calls bootHasUid() from the trait
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Document $document) {
            // If no type_id is set, default to 'dni' type (fallback when no products or blockades)
            if (! $document->type_id) {
                $dniType = DocumentType::where('slug', 'dni')->first();
                if ($dniType) {
                    $document->type_id = $dniType->id;
                }
            }

            // Initialize required_documents with keys only if not already set
            if (empty($document->required_documents)) {
                $document->required_documents = $document->getRequiredDocuments();
            }
        });

        static::updating(function (Document $document) {
            // If type_id changed and required_documents is not explicitly set, update it
            if ($document->isDirty('type_id') && empty($document->required_documents)) {
                $document->required_documents = $document->getRequiredDocuments();
            }

            // If requires_financing or type_id changed, recalculate workflow stages
            if ($document->isDirty('requires_financing') || $document->isDirty('type_id')) {
                try {
                    // Get new stages based on updated attributes
                    $stages = $document->getValidationWorkflowStages();
                    $newTotalStages = count($stages);

                    // Only update if stages count actually changed
                    if (! empty($stages) && $newTotalStages !== $document->total_stages) {
                        $oldTotalStages = $document->total_stages;

                        // Update total_stages
                        $document->total_stages = $newTotalStages;

                        if ($newTotalStages > $oldTotalStages) {
                            // ADDING stages: Keep current progress, just update total
                            // Don't reset current_stage, validation_status, or history
                            // New stages will be handled naturally when approving current stage
                        } elseif ($newTotalStages < $oldTotalStages) {
                            // REMOVING stages: Only adjust if current_stage exceeds new total
                            if ($document->current_stage > $newTotalStages) {
                                // Current stage no longer exists, move to last valid stage
                                $document->current_stage = $newTotalStages;
                                $document->current_validator_group = $stages[$newTotalStages - 1] ?? null;

                                // If we were completed and stages were removed, stay completed
                                // Otherwise, set to in_validation since we're now at an incomplete stage
                                if ($document->validation_status !== Document::VALIDATION_STATUS_APPROVED) {
                                    $document->validation_status = Document::VALIDATION_STATUS_IN_VALIDATION;
                                }
                            }
                            // If current_stage <= newTotalStages, keep everything as is
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error recalculating workflow stages for document '.$document->uid.': '.$e->getMessage());
                }
            }
        });

        static::created(function (Document $document) {
            // Automatically initialize validation workflow when document is created
            try {
                $document->initializeWorkflow();

                // Notify first stage validator group after successful initialization
                if ($document->current_validator_group && $document->current_stage === 1) {
                    // previousStage=0 indicates this is the initial creation (no previous stage)
                    $document->notifyValidatorGroup(0, 1, $document->current_validator_group);
                }
            } catch (\Exception $e) {
                Log::error('Error initializing workflow for document '.$document->uid.': '.$e->getMessage());
            }
        });
    }
}
