<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipos de PQRSF
 * P - Petición
 * Q - Queja
 * R - Reclamo
 * S - Sugerencia
 * F - Felicitación
 */
class AttentionType extends Model
{
    protected $table = 'attention_types';

    protected $fillable = [
        'code',
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
     * Attentions of this type
     */
    public function attentions(): HasMany
    {
        return $this->hasMany(Attention::class, 'type_id');
    }

    /**
     * Scope active types
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
