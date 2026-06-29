<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ecommerce_gift_cards';

    protected $fillable = [
        'code',
        'amount',
        'balance',
        'buyer_customer_id',
        'recipient_email',
        'recipient_name',
        'message',
        'status',
        'expires_at',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public static function generateCode(): string
    {
        do {
            $code = 'GIFT-'.strtoupper(Str::random(10));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isUsable(): bool
    {
        return $this->status === 'active'
            && (float) $this->balance > 0
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function redeem(float $amount): float
    {
        $applied = min($amount, (float) $this->balance);
        $this->balance = (float) $this->balance - $applied;

        if ((float) $this->balance <= 0) {
            $this->status = 'used';
        }

        $this->save();

        return $applied;
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'buyer_customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
