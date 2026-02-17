<?php

namespace Modules\Menu\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'target',
        'icon',
        'css_class',
        'order',
        'has_child',
        'type',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'order' => 'integer',
        'has_child' => 'boolean',
    ];

    protected $appends = ['full_url'];

    /**
     * Get the menu that owns the item.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item.
     */
    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get the reference (polymorphic relation).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL for the menu item.
     */
    public function getFullUrlAttribute()
    {
        if ($this->type === 'custom') {
            return $this->url;
        }

        if ($this->reference) {
            // Asume que los modelos referenciados tienen un atributo 'url'
            return $this->reference->url ?? $this->url;
        }

        return $this->url;
    }

    /**
     * Check if the menu item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if the menu item is active.
     */
    public function isActive(): bool
    {
        return request()->url() === $this->full_url;
    }

    /**
     * Check if any child is active.
     */
    public function hasActiveChild(): bool
    {
        foreach ($this->children as $child) {
            if ($child->isActive() || $child->hasActiveChild()) {
                return true;
            }
        }

        return false;
    }
}
