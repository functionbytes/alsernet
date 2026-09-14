<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStatusTransition extends Model
{
    protected $table = 'document_status_transitions';

    protected $fillable = [
        'from_status_id',
        'to_status_id',
        'permission',
        'requires_all_documents_uploaded',
        'auto_transition_after_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_all_documents_uploaded' => 'boolean',
            'is_active' => 'boolean',
            'auto_transition_after_days' => 'integer',
        ];
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'to_status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFromStatus($query, int $statusId)
    {
        return $query->where('from_status_id', $statusId);
    }

    public static function getValidTransitions(DocumentStatus $status): array
    {
        return static::where('from_status_id', $status->id)
            ->active()
            ->with('toStatus')
            ->get()
            ->pluck('toStatus')
            ->toArray();
    }

    public function canTransition(?int $userId = null): bool
    {
        if ($this->permission && $userId) {
            return \auth()->check() && \auth()->user()->can($this->permission);
        }

        return true;
    }
}
