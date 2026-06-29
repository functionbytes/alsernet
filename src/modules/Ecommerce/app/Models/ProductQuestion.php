<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuestion extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_questions';

    protected $fillable = [
        'product_id',
        'customer_id',
        'author_name',
        'author_email',
        'question',
        'answer',
        'answered_by',
        'answered_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'is_published' => 'boolean',
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
}
