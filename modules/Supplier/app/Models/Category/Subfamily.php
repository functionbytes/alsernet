<?php

namespace Modules\Supplier\Models\Category;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;

class Subfamily extends Model
{
    protected $table = 'supplier_subfamilies';

    protected $fillable = [
        'erp_id',
        'category_id',
        'erp_category_id',
        'name',
        'short_name',
        'available',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id'          => 'integer',
        'category_id'     => 'integer',
        'erp_category_id' => 'integer',
        'available'       => 'boolean',
        'last_sync_at'    => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'subfamily_id');
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class, 'subfamily_id');
    }

    /** Prompts que incluyen esta subfamilia en su array subfamily_ids */
    public function promptsMulti(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Prompt::class, 'subfamily_id');
    }

    /**
     * Todos los prompts asignados: los que tienen subfamily_id = $this->id
     * O los que tienen this->id dentro de su JSON subfamily_ids.
     */
    public function allPrompts(): \Illuminate\Support\Collection
    {
        return Prompt::where(function ($q) {
            $q->where('subfamily_id', $this->id)
              ->orWhereJsonContains('subfamily_ids', $this->id);
        })->get();
    }

    public function scopeActive($query)
    {
        return $query->where('available', true);
    }
}
