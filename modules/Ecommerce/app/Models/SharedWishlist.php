<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedWishlist extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_shared_wishlists';

    protected $fillable = [
        'customer_id',
        'token',
        'title',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
