<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomizationField extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customization_field';

    protected $primaryKey = 'id_customization_field';

    public $timestamps = false;

    protected $fillable = [
        'id_product',
        'type',
        'required',
        'is_module',
        'name',
        'is_deleted',
    ];

    protected $casts = [
        'is_module' => 'boolean',
        'is_deleted' => 'boolean',
        'id_product' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
