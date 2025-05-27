<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnReason extends Model
{
    protected $table = 'return_reasons';
    protected $primaryKey = 'id_return_reason';

    protected $fillable = [
        'return_type', 'active'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnReasonLang', 'id_return_reason', 'id_return_reason');
    }

    public function requests(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequest', 'id_return_reason', 'id_return_reason');
    }

    public function getTranslation($langId = 1, $shopId = 1)
    {
        return $this->translations()
            ->where('id_lang', $langId)
            ->where('id_shop', $shopId)
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByReturnType($query, $returnType)
    {
        return $query->where('return_type', $returnType);
    }

    // Constantes para tipos de motivo
    const TYPE_REFUND = 'refund';       // Para reembolsos
    const TYPE_REPLACEMENT = 'replacement'; // Para reemplazos
    const TYPE_REPAIR = 'repair';       // Para reparaciones
    const TYPE_ALL = 'all';            // Para todos los tipos

    /**
     * Obtener motivos por tipo de devolución
     */
    public static function getByReturnType($returnType, $langId = 1, $shopId = 1)
    {
        return static::where('active', true)
            ->where(function($query) use ($returnType) {
                $query->where('return_type', $returnType)
                    ->orWhere('return_type', self::TYPE_ALL);
            })
            ->with(['translations' => function($q) use ($langId, $shopId) {
                $q->where('id_lang', $langId)
                    ->where('id_shop', $shopId);
            }])
            ->get();
    }

    /**
     * Verificar si el motivo es válido para un tipo de devolución
     */
    public function isValidForReturnType($returnType): bool
    {
        return $this->return_type === $returnType || $this->return_type === self::TYPE_ALL;
    }
}
