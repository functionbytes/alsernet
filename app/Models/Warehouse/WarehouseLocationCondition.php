<?php

namespace App\Models\Warehouse;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocationCondition extends Model
{
    use HasFactory, HasUid;

    protected $table = "warehouse_location_conditions";

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'description',
        'color',
        'badge_class',
        'available',
    ];

    protected $casts = [
        'available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ===============================================
     * RELACIONES
     * ===============================================
     */

    /**
     * Una condición puede aplicarse a muchas posiciones de inventario
     */
    public function slots(): HasMany
    {
        return $this->hasMany('App\Models\Warehouse\WarehouseInventorySlot', 'condition_id', 'id');
    }

    /**
     * ===============================================
     * SCOPES
     * ===============================================
     */

    public function scopeId($query, $id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeSlug($query, $slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    /**
     * ===============================================
     * MÉTODOS HELPERS
     * ===============================================
     */

    /**
     * Obtener información para UI
     */
    public function getUiData(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'title' => $this->title,
            'slug' => $this->slug,
            'color' => $this->color ?? '#999999',
            'badge_class' => $this->badge_class ?? 'badge-secondary',
            'description' => $this->description,
            'available' => $this->available,
        ];
    }

    /**
     * Obtener estilo CSS inline
     */
    public function getStyleAttribute(): string
    {
        return "style='background-color: {$this->color}; color: white;'";
    }

    /**
     * Obtener clase de badge Bootstrap
     */
    public function getBadgeClass(): string
    {
        return $this->badge_class ?? 'badge-secondary';
    }

    /**
     * Obtener HTML del badge
     */
    public function getBadgeHtml(): string
    {
        return "<span class='badge {$this->getBadgeClass()}'>{$this->title}</span>";
    }
}
