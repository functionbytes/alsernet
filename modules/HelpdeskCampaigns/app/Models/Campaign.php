<?php

namespace Modules\HelpdeskCampaigns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskCampaigns\Database\Factories\CampaignFactory;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_campaigns';

    protected function casts(): array
    {
        return [
            'content' => 'array', // Store blocks/nodes for content editor
            'appearance' => 'array', // Colors, fonts, positioning
            'conditions' => 'array', // Targeting rules
            'metadata' => 'array', // Additional data
            'published_at' => 'datetime',
            'ends_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'type', // 'popup', 'banner', 'slide-in', 'full-screen'
        'status', // 'draft', 'scheduled', 'active', 'ended', 'paused'
        'content',
        'appearance',
        'conditions',
        'metadata',
        'published_at',
        'ends_at',
    ];

    // ==================== Relationships ====================

    /**
     * Campaign impressions (page views/interactions)
     */
    public function impressions(): HasMany
    {
        return $this->hasMany(CampaignImpression::class);
    }

    /**
     * Campaign templates (reusable content blocks)
     */
    public function templates(): HasMany
    {
        return $this->hasMany(CampaignTemplate::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('deleted_at');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('published_at', '>', now());
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', ['active', 'ended'])
            ->where('published_at', '<=', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Get the number of impressions
     */
    public function getImpressionsCountAttribute(): int
    {
        if (array_key_exists('impressions_count', $this->attributes)) {
            return (int) $this->attributes['impressions_count'];
        }

        return $this->impressions()->count();
    }

    /**
     * Get the number of clicks/conversions
     */
    public function getConversionsCountAttribute(): int
    {
        $row = $this->impressions()
            ->selectRaw('COUNT(*) as total, SUM(clicked_at IS NOT NULL) as clicks')
            ->first();

        return (int) ($row->clicks ?? 0);
    }

    /**
     * Get click-through rate (CTR)
     */
    public function getCtrAttribute()
    {
        $impressions = $this->getImpressionsCountAttribute();
        if ($impressions === 0) {
            return 0;
        }

        $conversions = $this->getConversionsCountAttribute();

        return round(($conversions / $impressions) * 100, 2);
    }

    /**
     * Check if campaign is currently active
     */
    public function getIsActiveAttribute()
    {
        if (! $this->published_at) {
            return false;
        }

        return $this->status === 'active' &&
               $this->published_at <= now() &&
               (is_null($this->ends_at) || $this->ends_at > now());
    }

    /**
     * Get campaign type label in Spanish
     */
    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'popup' => 'Pop-up',
            'banner' => 'Banner',
            'slide-in' => 'Slide-in',
            'full-screen' => 'Pantalla Completa',
            default => $this->type,
        };
    }

    /**
     * Get campaign status label in Spanish
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'scheduled' => 'Programada',
            'active' => 'Activa',
            'ended' => 'Finalizada',
            'paused' => 'Pausada',
            default => $this->status,
        };
    }

    /**
     * Get status color for Bootstrap badges
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'scheduled' => 'info',
            'active' => 'success',
            'ended' => 'danger',
            'paused' => 'warning',
            default => 'light',
        };
    }

    // ==================== Methods ====================

    /**
     * Publish the campaign
     */
    public function publish()
    {
        $this->update([
            'status' => 'active',
            'published_at' => now(),
        ]);

        return $this;
    }

    /**
     * Pause the campaign
     */
    public function pause()
    {
        if ($this->is_active) {
            $this->update(['status' => 'paused']);
        }

        return $this;
    }

    /**
     * Resume the campaign
     */
    public function resume()
    {
        if ($this->status === 'paused') {
            $this->update(['status' => 'active']);
        }

        return $this;
    }

    /**
     * End the campaign
     */
    public function end()
    {
        $this->update([
            'status' => 'ended',
            'ends_at' => now(),
        ]);

        return $this;
    }

    /**
     * Get targeting conditions as readable text
     */
    public function getConditionsDescriptionAttribute()
    {
        if (empty($this->conditions)) {
            return 'Sin condiciones (mostrar a todos)';
        }

        $descriptions = [];
        foreach ($this->conditions as $condition) {
            $descriptions[] = "{$condition['field']} {$condition['operator']} {$condition['value']}";
        }

        return implode(' AND ', $descriptions);
    }

    /**
     * Get appearance as CSS variables for preview
     */
    public function getAppearanceCssAttribute()
    {
        if (empty($this->appearance)) {
            return '';
        }

        $bgColor = $this->sanitizeCssColor($this->appearance['background_color'] ?? '', '#ffffff');
        $textColor = $this->sanitizeCssColor($this->appearance['text_color'] ?? '', '#000000');
        $primaryColor = $this->sanitizeCssColor($this->appearance['primary_color'] ?? '', '#90bb13');

        $css = ':root {';
        $css .= "--campaign-bg-color: {$bgColor};";
        $css .= "--campaign-text-color: {$textColor};";
        $css .= "--campaign-primary-color: {$primaryColor};";
        $css .= '}';

        return $css;
    }

    /**
     * Sanitize a CSS color value to prevent injection.
     */
    private function sanitizeCssColor(string $value, string $default): string
    {
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Get content blocks count
     */
    public function getContentBlocksCountAttribute()
    {
        return count($this->content ?? []);
    }

    /**
     * Get estimated impressions for a day based on previous data
     */
    public function getAverageDailyImpressionsAttribute()
    {
        if (! $this->published_at) {
            return 0;
        }

        if ($this->published_at > now()) {
            return 0;
        }

        $days = max(1, (int) now()->diffInDays($this->published_at));

        $impressions = $this->getImpressionsCountAttribute();

        return round($impressions / $days, 0);
    }
}
