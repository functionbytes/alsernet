<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentUploadType extends Model
{
    protected $table = 'document_upload_types';

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

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'upload_id');
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
