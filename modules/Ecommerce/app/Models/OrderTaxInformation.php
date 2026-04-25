<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTaxInformation extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_tax_information';

    protected $fillable = [
        'order_id',
        'company_name',
        'company_address',
        'company_email',
        'company_phone',
        'company_tax_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
