<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\AttributeGroup;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;

class AttributeGroupLang extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_attribute_group_lang';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_attribute_group',
        'id_lang',
        'id_shop',
        'name',
        'public_name',
    ];

    protected $casts = [
        'id_attribute_group' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_attribute_group', $this->getAttribute('id_attribute_group'))
            ->where('id_lang', $this->getAttribute('id_lang'))
            ->where('id_shop', $this->getAttribute('id_shop'));
    }

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'id_attribute_group');
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
