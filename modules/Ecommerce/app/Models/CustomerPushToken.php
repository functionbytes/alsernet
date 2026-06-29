<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\CustomerPushTokenFactory;

class CustomerPushToken extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_customer_push_tokens';

    protected $fillable = [
        'customer_id',
        'token',
        'platform',
        'device_id',
        'app_version',
        'locale',
        'last_used_at',
        'is_active',
    ];

    protected static function newFactory(): CustomerPushTokenFactory
    {
        return CustomerPushTokenFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
