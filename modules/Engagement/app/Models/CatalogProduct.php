<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Inbox;

class CatalogProduct extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_catalog_products';

    protected $fillable = [
        'inbox_id',
        'platform_product_id',
        'name',
        'description',
        'price',
        'currency',
        'url',
        'image_url',
        'category',
        'metadata',
        'is_active',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function toRecommendation(): array
    {
        return [
            'productId' => $this->platform_product_id,
            'name' => $this->name,
            'url' => $this->url ?? '',
            'imageUrl' => $this->image_url,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'category' => $this->category,
        ];
    }
}
