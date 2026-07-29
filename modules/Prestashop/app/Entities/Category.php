<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;

class Category extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_category';

    protected $primaryKey = 'id_category';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'position',
        'description',
        'id_parent',
        'id_category_default',
        'level_depth',
        'nleft',
        'nright',
        'link_rewrite',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'date_add',
        'date_upd',
        'is_root_category',
        'id_shop_default',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'is_root_category' => 'boolean',
        'position' => 'integer',
        'id_parent' => 'integer',
        'id_category_default' => 'integer',
        'level_depth' => 'integer',
        'nleft' => 'integer',
        'nright' => 'integer',
        'id_shop_default' => 'integer',
    ];

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_parent');
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category_default');
    }

    public function defaultShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop_default');
    }
}
