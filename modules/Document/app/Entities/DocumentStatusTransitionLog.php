<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStatusTransitionLog extends Model
{
    protected $table = 'document_status_transition_logs';

    protected $fillable = [
        'document_id',
        'transition_id',
        'from_status_id',
        'to_status_id',
        'performed_by',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function transition(): BelongsTo
    {
        return $this->belongsTo(DocumentStatusTransition::class, 'transition_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'to_status_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
