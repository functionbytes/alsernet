<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignTemplate extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_campaign_templates';

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
        'appearance' => 'array',
        'conditions' => 'array',
        'metadata' => 'array',
        'is_premium' => 'boolean',
    ];

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'category',
        'type',
        'content',
        'appearance',
        'conditions',
        'thumbnail_url',
        'preview_gradient',
        'is_premium',
        'metadata',
    ];

    // ==================== Relationships ====================

    /**
     * The campaign this template belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ==================== Scopes ====================

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    // ==================== Accessors ====================

    /**
     * Get the category label in Spanish
     */
    public function getCategoryLabelAttribute()
    {
        return match ($this->category) {
            'newsletter' => 'Boletín',
            'promotion' => 'Promoción',
            'announcement' => 'Anuncio',
            'survey' => 'Encuesta',
            'feedback' => 'Retroalimentación',
            'custom' => 'Personalizado',
            default => $this->category,
        };
    }
}
