<?php

namespace Modules\Attention\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Attention\Database\Factories\AttentionSedeFactory;

/**
 * Sedes físicas donde se reciben peticiones
 */
class AttentionSede extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return AttentionSedeFactory::new();
    }

    protected $table = 'attention_sedes';

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Attentions received at this sede
     */
    public function attentions(): HasMany
    {
        return $this->hasMany(Attention::class, 'sede_id');
    }

    /**
     * Scope active sedes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
