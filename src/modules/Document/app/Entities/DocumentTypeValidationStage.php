<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTypeValidationStage extends Model
{
    protected $table = 'document_type_validation_stages';

    protected $fillable = [
        'document_type_id',
        'key',
        'order',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
