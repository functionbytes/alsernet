<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentProduct extends Model
{
    protected $table = 'document_products';

    protected $fillable = [
        'document_id',
        'product_id',
        'product_name',
        'product_reference',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    // =========================================================================
    // MUTATORS - Automatic field transformations
    // =========================================================================

    protected function productName(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $value !== null ? strtoupper($value) : null
        );
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

}
