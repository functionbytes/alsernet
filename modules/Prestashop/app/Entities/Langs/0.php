<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Language;

class 0 extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_theme_lang';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_theme',
        'id_lang',
        'id_shop',
        'name',
    ];

    protected $casts = [
        'id_theme' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];


    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_lang', $this->getAttribute('id_lang'))
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
