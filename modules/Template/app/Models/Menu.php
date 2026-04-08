<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Template\Database\Factories\MenuFactory;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): MenuFactory
    {
        return MenuFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'location',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the items for the menu (only root items).
     */
    public function items()
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('order')->orderBy('id');
    }

    /**
     * Get all items for the menu.
     */
    public function allItems()
    {
        return $this->hasMany(MenuItem::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Scope a query to only include active menus.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to filter by location.
     */
    public function scopeByLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }
}
