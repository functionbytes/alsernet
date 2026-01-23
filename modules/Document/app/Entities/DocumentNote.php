<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentNote extends Model
{
    protected $table = 'document_notes';

    protected $fillable = [
        'document_id',
        'created_by',
        'content',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
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
     * Relación con el usuario que creó la nota
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Crear una nota
     */
    public static function addNote(
        int $documentId,
        int $createdBy,
        string $content,
        bool $isInternal = true
    ): self {
        return self::create([
            'document_id' => $documentId,
            'created_by' => $createdBy,
            'content' => $content,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Obtener notas ordenadas por fecha descendente
     */
    public static function getDocumentNotes(int $documentId, bool $onlyInternal = false)
    {
        $query = self::where('document_id', $documentId);

        if ($onlyInternal) {
            $query->where('is_internal', true);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }


}
