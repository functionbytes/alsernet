<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_affiliates';

    protected $fillable = [
        'customer_id',
        'code',
        'commission_rate',
        'status',
        'total_earned',
        'total_paid',
    ];

    public function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_paid' => 'decimal:2',
        ];
    }

    public static function generateCode(): string
    {
        do {
            $code = 'REF'.strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    public function pendingPayout(): float
    {
        return (float) $this->total_earned - (float) $this->total_paid;
    }
}
