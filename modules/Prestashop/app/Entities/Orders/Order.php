<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Address;
use Modules\Prestashop\Entities\Carrier;
use Modules\Prestashop\Entities\Cart;
use Modules\Prestashop\Entities\Currency;
use Modules\Prestashop\Entities\Customer;
use Modules\Prestashop\Entities\Language;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class Order extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_orders';

    protected $primaryKey = 'id_order';

    public $timestamps = false;

    protected $fillable = [
        'id_address_delivery',
        'id_address_invoice',
        'id_shop_group',
        'id_shop',
        'id_cart',
        'id_currency',
        'id_lang',
        'id_customer',
        'id_carrier',
        'current_state',
        'secure_key',
        'payment',
        'module',
        'conversion_rate',
        'gift_message',
        'mobile_theme',
        'shipping_number',
        'total_discounts',
        'total_discounts_tax_incl',
        'total_discounts_tax_excl',
        'total_paid',
        'total_paid_tax_incl',
        'total_paid_tax_excl',
        'total_paid_real',
        'total_products',
        'total_products_wt',
        'total_shipping',
        'total_shipping_tax_incl',
        'total_shipping_tax_excl',
        'carrier_tax_rate',
        'total_wrapping',
        'total_wrapping_tax_incl',
        'total_wrapping_tax_excl',
        'invoice_number',
        'delivery_number',
        'invoice_date',
        'delivery_date',
        'valid',
        'date_add',
        'date_upd',
        'reference',
        'round_mode',
        'round_type',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'gift_message' => 'boolean',
        'mobile_theme' => 'boolean',
        'id_address_delivery' => 'integer',
        'id_address_invoice' => 'integer',
        'id_shop_group' => 'integer',
        'id_shop' => 'integer',
        'id_cart' => 'integer',
        'id_currency' => 'integer',
        'id_lang' => 'integer',
        'id_customer' => 'integer',
        'id_carrier' => 'integer',
        'conversion_rate' => 'float',
        'total_discounts' => 'float',
        'total_discounts_tax_incl' => 'float',
        'total_discounts_tax_excl' => 'float',
        'total_paid' => 'float',
        'total_paid_tax_incl' => 'float',
        'total_paid_tax_excl' => 'float',
        'total_paid_real' => 'float',
        'total_products' => 'float',
        'total_products_wt' => 'float',
        'total_shipping' => 'float',
        'total_shipping_tax_incl' => 'float',
        'total_shipping_tax_excl' => 'float',
        'carrier_tax_rate' => 'float',
        'total_wrapping' => 'float',
        'total_wrapping_tax_incl' => 'float',
        'total_wrapping_tax_excl' => 'float',
    ];

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'id_address_delivery');
    }

    public function invoiceAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'id_address_invoice');
    }

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'id_cart');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'id_carrier');
    }

    public function currentState(): BelongsTo
    {
        return $this->belongsTo(OrderState::class, 'current_state');
    }
}
