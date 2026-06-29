<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAction extends Model
{
    protected $table = 'document_actions';

    protected $fillable = [
        'document_id',
        'action_type',
        'action_name',
        'description',
        'metadata',
        'performed_by',
        'performed_by_type',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el documento
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Registrar una acción
     */
    public static function logAction(
        int $documentId,
        string $actionType,
        string $actionName,
        ?string $description = null,
        ?array $metadata = null,
        ?int $performedBy = null,
        string $performedByType = 'system'
    ): self {
        return self::create([
            'document_id' => $documentId,
            'action_type' => $actionType,
            'action_name' => $actionName,
            'description' => $description,
            'metadata' => $metadata,
            'performed_by' => $performedBy,
            'performed_by_type' => $performedByType,
        ]);
    }

    /**
     * Obtener acciones ordenadas por fecha descendente
     */
    public static function getDocumentHistory(int $documentId)
    {
        return self::where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
