<?php

namespace Modules\Supplier\Models\Characteristic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Characteristic\ErpCharacteristicFactory;

class ErpCharacteristic extends Model
{
    use HasFactory;

    protected $table = 'supplier_erp_characteristics';

    protected $fillable = [
        'erp_id',
        'nombre',
        'estado',
        'orden',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'orden' => 'integer',
        'estado' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): ErpCharacteristicFactory
    {
        return ErpCharacteristicFactory::new();
    }

    public function values(): HasMany
    {
        return $this->hasMany(ErpCharacteristicValue::class, 'characteristic_id');
    }

    public function modelLinks(): HasMany
    {
        return $this->hasMany(ModelCharacteristic::class, 'characteristic_id');
    }

    public function variantLinks(): HasMany
    {
        return $this->hasMany(VariantCharacteristic::class, 'characteristic_id');
    }

    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }
}
