<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Tax\TaxRulesGroup;

class Product extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_product';

    protected $primaryKey = 'id_product';

    public $timestamps = false;

    protected $fillable = [
        'id_manufacturer',
        'id_supplier',
        'id_category_default',
        'id_shop_default',
        'name',
        'description',
        'description_short',
        'available_now',
        'available_later',
        'reference',
        'supplier_reference',
        'isbn',
        'upc',
        'mpn',
        'link_rewrite',
        'meta_description',
        'meta_keywords',
        'meta_title',
        'customizable',
        'uploadable_files',
        'text_fields',
        'condition',
        'visibility',
        'date_add',
        'date_upd',
        'tags',
        'base_price',
        'id_tax_rules_group',
        'depends_on_stock',
        'cache_is_pack',
        'cache_has_attachments',
        'is_virtual',
        'id_pack_product_attribute',
        'cache_default_attribute',
        'category',
        'delivery_in_stock',
        'delivery_out_stock',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'cache_is_pack' => 'boolean',
        'is_virtual' => 'boolean',
        'id_manufacturer' => 'integer',
        'id_supplier' => 'integer',
        '
        ' => 'integer',
        'id_shop_default' => 'integer',
        'id_tax_rules_group' => 'integer',
        'id_pack_product_attribute' => 'integer',
        'base_price' => 'float',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class, 'id_manufacturer');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category_default');
    }

    public function defaultShop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop_default');
    }

    public function taxRulesGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRulesGroup::class, 'id_tax_rules_group');
    }

    public function newCollection(array $models = [])
    {
        return new class($models) extends Collection
        {
            public function __construct($models)
            {
                parent::__construct($models);

                $this->load('lang');
            }
        };
    }

    public function newQuery($excludeDeleted = true)
    {
        return parent::newQuery($excludeDeleted)->with('lang');
    }

    public function scopeName($query, $name)
    {
        return $query->whereHas('lang', function ($q) use ($name) {
            $q->where('name', 'like', '%'.$name.'%');
        });
    }

    public function getNameAttribute()
    {
        return $this->lang ? $this->lang->name : null;
    }

    public function combinations(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Product\ProductAttribute', 'id_product', 'id_product');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsToMany('App\Models\Prestashop\Shop', 'ps_product_shop', 'id_product', 'id_shop');
    }

    public function lang(): HasOne
    {
        return $this->hasOne('App\Models\Prestashop\Product\ProductLang', 'id_product', 'id_product')->where('id_lang', 1);
    }
}
