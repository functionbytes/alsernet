<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentStatus extends Model
{
    protected $table = 'document_statuses';

    protected $fillable = [
        'key',
        'label',
        'description',
        'color',
        'icon',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'status_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DocumentStatusHistory::class, 'to_status_id');
    }

    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(DocumentStatusTransition::class, 'from_status_id');
    }

    public function transitionsTo(): HasMany
    {
        return $this->hasMany(DocumentStatusTransition::class, 'to_status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
