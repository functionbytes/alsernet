<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;

class Referrer extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_referrer';

    protected $primaryKey = 'id_referrer';

    public $timestamps = false;

    protected $fillable = [
        'id_shop',
        'name',
        'passwd',
        'http_referer_regexp',
        'http_referer_like',
        'request_uri_regexp',
        'request_uri_like',
        'http_referer_regexp_not',
        'http_referer_like_not',
        'request_uri_regexp_not',
        'request_uri_like_not',
        'base_fee',
        'percent_fee',
        'click_fee',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_shop' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }
}
