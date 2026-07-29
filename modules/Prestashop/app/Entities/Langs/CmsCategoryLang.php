<?php

namespace Modules\Prestashop\Entities\Langs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\CMSCategory;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;

class CmsCategoryLang extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cms_category_lang';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_cms_category',
        'id_lang',
        'id_shop',
        'name',
        'description',
        'link_rewrite',
        'meta_title',
        'meta_keywords',
        'meta_description',
    ];

    protected $casts = [
        'id_cms_category' => 'integer',
        'id_lang' => 'integer',
        'id_shop' => 'integer',
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('id_cms_category', $this->getAttribute('id_cms_category'))
            ->where('id_lang', $this->getAttribute('id_lang'))
            ->where('id_shop', $this->getAttribute('id_shop'));
    }

    public function cmsCategory(): BelongsTo
    {
        return $this->belongsTo(CMSCategory::class, 'id_cms_category');
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
