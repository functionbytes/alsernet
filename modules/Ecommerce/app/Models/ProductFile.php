<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFile extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_files';

    protected $fillable = [
        'product_id',
        'name',
        'description',
        'file',
        'file_size',
        'file_ext',
        'is_free',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
