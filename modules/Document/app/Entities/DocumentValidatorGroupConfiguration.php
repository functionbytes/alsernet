<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentValidatorGroupConfiguration extends Model
{
    protected $table = 'document_validator_group_configurations';

    protected $fillable = [
        'validator_group_id',
        'key',
        'label',
        'description',
        'value',
        'category',
        'order',
        'is_active',
    ];

    protected $casts = [
        'value' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function validatorGroup(): BelongsTo
    {
        return $this->belongsTo(DocumentValidatorGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function getGroupConfigurations(int $groupId, ?string $category = null)
    {
        $query = static::where('validator_group_id', $groupId)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('order');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    public static function isEnabled(int $groupId, string $key): bool
    {
        return static::where('validator_group_id', $groupId)
            ->where('key', $key)
            ->where('is_active', true)
            ->value('value') ?? false;
    }
}
