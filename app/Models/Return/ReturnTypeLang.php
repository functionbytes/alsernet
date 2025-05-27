<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnTypeLang extends Model
{
    protected $table = 'return_type_lang';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_return_type', 'id_lang', 'id_shop', 'name', 'day', 'return_color', 'active'
    ];

    protected $casts = [
        'day' => 'integer',
        'active' => 'boolean',
    ];

    public function returnType(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnType', 'id_return_type', 'id_return_type');
    }

    public function scopeByLanguage($query, $langId)
    {
        return $query->where('id_lang', $langId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('id_shop', $shopId);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByTypeAndShopAndLang($query, $typeId, $shopId, $langId)
    {
        return $query->where('id_return_type', $typeId)
            ->where('id_shop', $shopId)
            ->where('id_lang', $langId);
    }

    /**
     * Obtener traducción por tipo, idioma y tienda
     */
    public static function getTranslation($typeId, $langId = 1, $shopId = 1)
    {
        return static::where('id_return_type', $typeId)
            ->where('id_lang', $langId)
            ->where('id_shop', $shopId)
            ->first();
    }

    /**
     * Verificar si el tipo está dentro del período de devolución
     */
    public function isWithinReturnPeriod($orderDate): bool
    {
        if (!$this->day || !$orderDate) {
            return false;
        }

        $orderDate = is_string($orderDate) ? \Carbon\Carbon::parse($orderDate) : $orderDate;
        $daysSinceOrder = $orderDate->diffInDays(now());

        return $daysSinceOrder <= $this->day;
    }
}
