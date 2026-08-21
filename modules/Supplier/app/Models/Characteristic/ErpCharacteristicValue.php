<?php

namespace Modules\Supplier\Models\Characteristic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Characteristic\ErpCharacteristicValueFactory;

class ErpCharacteristicValue extends Model
{
    use HasFactory;

    protected $table = 'supplier_erp_characteristic_values';

    protected $fillable = [
        'erp_id',
        'characteristic_id',
        'nombre',
        'estado',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'characteristic_id' => 'integer',
        'estado' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): ErpCharacteristicValueFactory
    {
        return ErpCharacteristicValueFactory::new();
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(ErpCharacteristic::class, 'characteristic_id');
    }

    public function variantLinks(): HasMany
    {
        return $this->hasMany(VariantCharacteristic::class, 'value_id');
    }

    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }
}
