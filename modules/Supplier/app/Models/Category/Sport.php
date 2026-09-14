<?php

namespace Modules\Supplier\Models\Category;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Category\SportFactory;

class Sport extends Model
{
    use HasFactory;

    protected $table = 'supplier_sports';

    protected $fillable = [
        'erp_id',
        'name',
        'short_name',
        'available',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'available' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): SportFactory
    {
        return SportFactory::new();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'sport_id');
    }

    public function scopeActive($query)
    {
        return $query->where('available', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
            ->orWhere('short_name', 'LIKE', "%{$search}%");
    }
}
