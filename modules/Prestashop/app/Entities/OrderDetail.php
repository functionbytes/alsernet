<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\Order;
use Modules\Prestashop\Entities\Orders\OrderInvoice;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Stock\Warehouse;
use Modules\Prestashop\Entities\Tax\TaxRulesGroup;

class OrderDetail extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_detail';

    protected $primaryKey = 'id_order_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_order_detail',
        'id_order',
        'id_order_invoice',
        'product_id',
        'id_shop',
        'product_attribute_id',
        'id_customization',
        'product_name',
        'product_quantity',
        'product_quantity_in_stock',
        'product_quantity_return',
        'product_quantity_refunded',
        'product_quantity_reinjected',
        'product_price',
        'original_product_price',
        'unit_price_tax_incl',
        'unit_price_tax_excl',
        'total_price_tax_incl',
        'total_price_tax_excl',
        'reduction_percent',
        'reduction_amount',
        'reduction_amount_tax_excl',
        'reduction_amount_tax_incl',
        'group_reduction',
        'product_quantity_discount',
        'product_ean13',
        'product_isbn',
        'product_upc',
        'product_mpn',
        'product_reference',
        'product_supplier_reference',
        'product_weight',
        'ecotax',
        'ecotax_tax_rate',
        'discount_quantity_applied',
        'download_hash',
        'download_nb',
        'download_deadline',
        'tax_name',
        'tax_rate',
        'tax_computation_method',
        'id_tax_rules_group',
        'id_warehouse',
        'total_shipping_price_tax_excl',
        'total_shipping_price_tax_incl',
        'purchase_supplier_price',
        'original_wholesale_price',
        'total_refunded_tax_excl',
        'total_refunded_tax_incl',
    ];

    protected $casts = [
        'id_order_detail' => 'integer',
        'id_order' => 'integer',
        'id_order_invoice' => 'integer',
        'id_shop' => 'integer',
        'id_customization' => 'integer',
        'id_tax_rules_group' => 'integer',
        'id_warehouse' => 'integer',
        'product_price' => 'float',
        'original_product_price' => 'float',
        'unit_price_tax_incl' => 'float',
        'unit_price_tax_excl' => 'float',
        'total_price_tax_incl' => 'float',
        'total_price_tax_excl' => 'float',
        'reduction_percent' => 'float',
        'reduction_amount' => 'float',
        'reduction_amount_tax_excl' => 'float',
        'reduction_amount_tax_incl' => 'float',
        'group_reduction' => 'float',
        'product_weight' => 'float',
        'ecotax' => 'float',
        'ecotax_tax_rate' => 'float',
        'tax_rate' => 'float',
        'total_shipping_price_tax_excl' => 'float',
        'total_shipping_price_tax_incl' => 'float',
        'purchase_supplier_price' => 'float',
        'original_wholesale_price' => 'float',
        'total_refunded_tax_excl' => 'float',
        'total_refunded_tax_incl' => 'float',
    ];

    // BelongsTo Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Order',
            'id_order',
            'id_order'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Product',
            'product_id',
            'id_product'
        );
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Combination',
            'product_attribute_id',
            'id_product_attribute'
        );
    }

    public function orderInvoice(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\OrderInvoice',
            'id_order_invoice',
            'id_order_invoice'
        );
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Shop',
            'id_shop',
            'id_shop'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\Warehouse',
            'id_warehouse',
            'id_warehouse'
        );
    }

    public function taxRulesGroup(): BelongsTo
    {
        return $this->belongsTo(
            'App\Models\Prestashop\TaxRulesGroup',
            'id_tax_rules_group',
            'id_tax_rules_group'
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function orderInvoice(): BelongsTo
    {
        return $this->belongsTo(OrderInvoice::class, 'id_order_invoice');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function taxRulesGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRulesGroup::class, 'id_tax_rules_group');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
}
