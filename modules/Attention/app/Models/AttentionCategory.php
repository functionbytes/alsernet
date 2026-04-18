<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Categorías temáticas para peticiones
 * Ejemplos: Atención al cliente, Facturación, Quejas de producto, etc.
 */
class AttentionCategory extends Model
{
    use SoftDeletes;

    protected $table = 'attention_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Attentions of this category
     */
    public function attentions(): HasMany
    {
        return $this->hasMany(Attention::class, 'category_id');
    }

    /**
     * Scope active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
