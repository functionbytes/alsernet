<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnReasonLang extends Model
{
    protected $table = 'return_reason_lang';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'id_return_reason', 'id_lang', 'id_shop', 'name'
    ];

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnReason', 'id_return_reason', 'id_return_reason');
    }

    public function scopeByLanguage($query, $langId)
    {
        return $query->where('id_lang', $langId);
    }

    public function scopeByShop($query, $shopId)
    {
        return $query->where('id_shop', $shopId);
    }

    public function scopeByReasonAndShopAndLang($query, $reasonId, $shopId, $langId)
    {
        return $query->where('id_return_reason', $reasonId)
            ->where('id_shop', $shopId)
            ->where('id_lang', $langId);
    }
}
