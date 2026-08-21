<?php

namespace Modules\Supplier\Models\Characteristic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Characteristic\VariantCharacteristicFactory;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductAttribute;

class VariantCharacteristic extends Model
{
    use HasFactory;

    protected $table = 'supplier_variant_characteristics';

    protected $fillable = [
        'erp_id',
        'erp_article_id',
        'erp_model_id',
        'characteristic_id',
        'value_id',
        'orden',
        'estado',
        'product_attribute_id',
        'product_id',
        'sync_status',
        'erp_response',
        'created_by',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'erp_article_id' => 'integer',
        'erp_model_id' => 'integer',
        'characteristic_id' => 'integer',
        'value_id' => 'integer',
        'orden' => 'integer',
        'estado' => 'boolean',
        'product_attribute_id' => 'integer',
        'product_id' => 'integer',
        'created_by' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): VariantCharacteristicFactory
    {
        return VariantCharacteristicFactory::new();
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(ErpCharacteristic::class, 'characteristic_id');
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(ErpCharacteristicValue::class, 'value_id');
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }
}
