<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_invoice_items';

    protected $fillable = [
        'invoice_id',
        'reference_type',
        'reference_id',
        'name',
        'description',
        'image',
        'qty',
        'sub_total',
        'tax_amount',
        'discount_amount',
        'amount',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
