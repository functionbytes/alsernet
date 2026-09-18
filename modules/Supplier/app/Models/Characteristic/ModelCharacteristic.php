<?php

namespace Modules\Supplier\Models\Characteristic;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Characteristic\ModelCharacteristicFactory;
use Modules\Supplier\Models\Product\Product;

class ModelCharacteristic extends Model
{
    use HasFactory;

    protected $table = 'supplier_model_characteristics';

    protected $fillable = [
        'erp_id',
        'erp_model_id',
        'characteristic_id',
        'orden',
        'estado',
        'product_id',
        'sync_status',
        'erp_response',
        'created_by',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'erp_model_id' => 'integer',
        'characteristic_id' => 'integer',
        'orden' => 'integer',
        'estado' => 'boolean',
        'product_id' => 'integer',
        'created_by' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): ModelCharacteristicFactory
    {
        return ModelCharacteristicFactory::new();
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(ErpCharacteristic::class, 'characteristic_id');
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
