<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class PrestaShopLogger extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_log';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'id_log',
        'severity',
        'error_code',
        'message',
        'object_type',
        'object_id',
        'id_employee',
        'date_add',
        'date_upd',
        'id_shop',
        'id_shop_group',
        'id_lang',
        'in_all_shops',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_log' => 'integer',
        'id_employee' => 'integer',
        'id_shop' => 'integer',
        'id_shop_group' => 'integer',
        'id_lang' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }
}
