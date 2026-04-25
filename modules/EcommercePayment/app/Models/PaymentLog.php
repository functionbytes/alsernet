<?php

namespace Modules\EcommercePayment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_payment_logs';

    protected $fillable = [
        'payment_id',
        'payment_method',
        'request',
        'response',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }
}
