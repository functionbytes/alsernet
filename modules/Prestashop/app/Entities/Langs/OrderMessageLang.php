<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;

class OrderMessageLang extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_message_lang';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_order_message',
        'id_lang',
        'id_shop',
        'name',
        'message',
    ];

    protected $casts = [
        'id_order_message' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_order_message', $this->getAttribute('id_order_message'))
            ->where('id_lang', $this->getAttribute('id_lang'))
            ->where('id_shop', $this->getAttribute('id_shop'));
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
