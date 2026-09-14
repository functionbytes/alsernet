<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\Order;
use Modules\Prestashop\Entities\Shop\Shop;

class CustomerThread extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customer_thread';

    protected $primaryKey = 'id_customer_thread';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_shop',
        'id_lang',
        'id_contact',
        'id_customer',
        'id_order',
        'id_product',
        'status',
        'email',
        'token',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_shop' => 'integer',
        'id_lang' => 'integer',
        'id_contact' => 'integer',
        'id_customer' => 'integer',
        'id_order' => 'integer',
        'id_product' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'id_contact');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
