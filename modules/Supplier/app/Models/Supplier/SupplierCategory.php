<?php

namespace Modules\Supplier\Models\Supplier;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Supplier\Models\Category\Category;

class SupplierCategory extends Pivot
{
    use HasUid;

    protected $table = 'supplier_supplier_categories';

    public $incrementing = true;

    protected $fillable = [
        'supplier_id',
        'category_id',
        'is_primary',
        'is_active',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'category_id' => 'integer',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
