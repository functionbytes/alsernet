<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Stock\StockMvtReason;

class StockMvtReasonLang extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_stock_mvt_reason_lang';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_stock_mvt_reason',
        'id_lang',
        'id_shop',
        'name',
    ];

    protected $casts = [
        'id_stock_mvt_reason' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_stock_mvt_reason', $this->getAttribute('id_stock_mvt_reason'))
            ->where('id_lang', $this->getAttribute('id_lang'))
            ->where('id_shop', $this->getAttribute('id_shop'));
    }

    public function stockMvtReason(): BelongsTo
    {
        return $this->belongsTo(StockMvtReason::class, 'id_stock_mvt_reason');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }
}
