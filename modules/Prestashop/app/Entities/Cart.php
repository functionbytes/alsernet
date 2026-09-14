<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class Cart extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cart';

    protected $primaryKey = 'id_cart';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_shop_group',
        'id_shop',
        'id_address_delivery',
        'id_address_invoice',
        'id_currency',
        'id_customer',
        'id_guest',
        'id_lang',
        'gift_message',
        'mobile_theme',
        'date_add',
        'secure_key',
        'date_upd',
        'pictures',
        'delivery_option',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'gift_message' => 'boolean',
        'mobile_theme' => 'boolean',
        'id_shop_group' => 'integer',
        'id_shop' => 'integer',
        'id_address_delivery' => 'integer',
        'id_address_invoice' => 'integer',
        'id_currency' => 'integer',
        'id_customer' => 'integer',
        'id_guest' => 'integer',
        'id_lang' => 'integer',
    ];

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'id_address_delivery');
    }

    public function invoiceAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'id_address_invoice');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function guest()
    {
        return $this->belongsTo('App\Models\Prestashop\Guest', 'id_guest', 'id_guest');
    }

    public function products()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\CartProduct', 'id_cart', 'id_cart');
    }

    public function carrier()
    {
        return $this->belongsTo('App\Models\Prestashop\Carrier', 'id_carrier', 'id_carrier');
    }
}
