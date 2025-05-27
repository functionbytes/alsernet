<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnStatusLang extends Model
{
    protected $table = 'return_status_lang';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_return_status', 'id_lang', 'id_shop', 'name'
    ];

    public function returnStatus(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnStatus', 'id_return_status', 'id_return_status');
    }

    public function scopeByLanguage($query, $langId)
    {
        return $query->where('id_lang', $langId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('id_shop', $shopId);
    }

    public function scopeByStatusAndShopAndLang($query, $statusId, $shopId, $langId)
    {
        return $query->where('id_return_status', $statusId)
            ->where('id_shop', $shopId)
            ->where('id_lang', $langId);
    }

    /**
     * Obtener traducción por estado, idioma y tienda
     */
    public static function getTranslation($statusId, $langId = 1, $shopId = 1)
    {
        return static::where('id_return_status', $statusId)
            ->where('id_lang', $langId)
            ->where('id_shop', $shopId)
            ->first();
    }
}
