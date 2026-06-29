<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentSource extends Model
{
    protected $table = 'document_sources';

    protected $fillable = [
        'key',
        'label',
        'description',
        'icon',
        'color',
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

    /**
     * Relation: Document using this source
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'document_source_id');
    }

    /**
     * Scope: Active sources only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get source by key
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
