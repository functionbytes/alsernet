<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class WarehouseLocationSection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'location_id',
        'code',
        'barcode',
        'level',
        'face',
        'weight_max',
        'max_quantity',
        'available',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available' => 'boolean',
        'weight_max' => 'decimal:2',
        'max_quantity' => 'integer',
        'level' => 'integer',
    ];



    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeBarcode($query ,$barcode)
    {
        return $query->where('barcode', $barcode)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uid && Schema::hasColumn('warehouse_location_sections', 'uid')) {
                $model->uid = Str::uuid();
            }
        });
    }

    /**
     * Get the location that owns this section.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    /**
     * Get the inventory slots in this section.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(WarehouseInventorySlot::class, 'section_id');
    }

    /**
     * Get total slots (unique products) in this section.
     */
    public function getTotalSlots(): int
    {
        return $this->slots()->count();
    }

    /**
     * Get occupied slots count (products with quantity > 0).
     */
    public function getOccupiedSlots(): int
    {
        return $this->slots()->occupied()->count();
    }

    /**
     * Get available slots count (products with quantity = 0).
     */
    public function getAvailableSlots(): int
    {
        return $this->getTotalSlots() - $this->getOccupiedSlots();
    }

    /**
     * Get occupancy percentage.
     */
    public function getOccupancyPercentage(): float
    {
        $total = $this->getTotalSlots();
        if ($total === 0) {
            return 0;
        }

        return round(($this->getOccupiedSlots() / $total) * 100, 2);
    }

    /**
     * Get total quantity in this section.
     */
    public function getTotalQuantity(): int
    {
        return (int) $this->slots()
            ->selectRaw('SUM(quantity) as total')
            ->value('total') ?? 0;
    }

    /**
     * Check if section is near capacity (based on max_quantity).
     */
    public function isNearCapacity(float $threshold = 0.85): bool
    {
        if (!$this->max_quantity) {
            return false;
        }

        $current = $this->getTotalQuantity();
        return $current >= ($this->max_quantity * $threshold);
    }

    /**
     * Get summary information.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'code' => $this->code,
            'barcode' => $this->barcode,
            'level' => $this->level,
            'max_quantity' => $this->max_quantity,
            'available' => $this->available,
            'total_slots' => $this->getTotalSlots(),
            'occupied_slots' => $this->getOccupiedSlots(),
            'available_slots' => $this->getAvailableSlots(),
            'occupancy_percentage' => $this->getOccupancyPercentage(),
            'total_quantity' => $this->getTotalQuantity(),
            'is_near_capacity' => $this->isNearCapacity(),
        ];
    }

    /**
     * Get full detailed information.
     */
    public function getFullInfo(): array
    {
        return array_merge(
            $this->getSummary(),
            [
                'location_id' => $this->location?->id,
                'location_code' => $this->location?->code,
                'notes' => $this->notes,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]
        );
    }
}
