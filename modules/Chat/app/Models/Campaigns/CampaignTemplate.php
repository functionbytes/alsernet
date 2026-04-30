<?php

namespace Modules\Chat\Models\Campaigns;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_campaign_templates';

    protected $casts = [
        'content' => 'array',
        'appearance' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'category',
        'content',
        'appearance',
        'metadata',
        'thumbnail_url',
        'is_featured',
    ];

    // ==================== Relationships ====================

    /**
     * Template belongs to a campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ==================== Scopes ====================

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ==================== Accessors ====================

    /**
     * Get category label in Spanish
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'announcement' => 'Anuncio',
            'promotion' => 'Promoción',
            'newsletter' => 'Newsletter',
            'survey' => 'Encuesta',
            'notification' => 'Notificación',
            default => $this->category,
        };
    }
}
