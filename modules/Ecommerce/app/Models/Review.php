<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_reviews';

    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'star',
        'is_verified_buyer',
        'comment',
        'status',
        'images',
        'reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'star' => 'integer',
            'is_verified_buyer' => 'boolean',
            'images' => 'array',
            'replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class);
    }
}
