<?php

namespace Modules\HelpdeskCampaigns\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskCampaigns\Database\Factories\CampaignFactory;
use Modules\HelpdeskCampaigns\Events\CampaignEnded;
use Modules\HelpdeskCampaigns\Events\CampaignPaused;
use Modules\HelpdeskCampaigns\Events\CampaignPublished;
use Modules\HelpdeskCampaigns\Events\CampaignResumed;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ENDED = 'ended';

    /**
     * Máquina de estados del ciclo de vida. Cada clave es un estado origen y
     * el valor los estados destino permitidos. `ended` es terminal: una
     * campaña finalizada no puede reanudarse ni republicarse (los endpoints
     * de la API devolvían 200 y la reactivaban silenciosamente).
     *
     *   draft → pending_approval | scheduled | active
     *   pending_approval → draft (reject) | scheduled | active (approve)
     *   scheduled → active | ended (cancelación anticipada)
     *   active ⇄ paused, y ambos → ended
     *   ended → ∅
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_PENDING_APPROVAL, self::STATUS_SCHEDULED, self::STATUS_ACTIVE],
        self::STATUS_PENDING_APPROVAL => [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_ACTIVE],
        self::STATUS_SCHEDULED => [self::STATUS_ACTIVE, self::STATUS_ENDED],
        self::STATUS_ACTIVE => [self::STATUS_PAUSED, self::STATUS_ENDED],
        self::STATUS_PAUSED => [self::STATUS_ACTIVE, self::STATUS_ENDED],
        self::STATUS_ENDED => [],
    ];

    /**
     * Whether the campaign may move from its current status to $status.
     * Unknown current statuses (legacy data) allow nothing — fail closed.
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_campaigns';

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'appearance' => 'array',
            'conditions' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'approval_required' => 'boolean',
            'impressions_count' => 'integer',
            'clicks_count' => 'integer',
            'max_impressions_per_user' => 'integer',
            'cooldown_minutes' => 'integer',
            'goal_value' => 'integer',
            'approved_by_user_id' => 'integer',
        ];
    }

    protected $fillable = [
        'name',
        'description',
        'template_id',
        'type',
        'status',
        'content',
        'appearance',
        'conditions',
        'metadata',
        'published_at',
        'ends_at',
        'max_impressions_per_user',
        'cooldown_minutes',
        'goal_type',
        'goal_value',
        'approval_required',
        'approved_at',
        'approved_by_user_id',
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
     * Campaign variants for A/B testing
     */
    public function variants(): HasMany
    {
        return $this->hasMany(CampaignVariant::class);
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
     * Get the number of clicks/conversions from the denormalized counter.
     */
    public function getConversionsCountAttribute(): int
    {
        return (int) ($this->attributes['clicks_count'] ?? 0);
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
     * Publish the campaign.
     *
     * Lifecycle events are dispatched here (the single source of truth) so every
     * caller — panel, API and scheduler — fires them consistently. Previously the
     * panel called these methods without dispatching, so activity logging,
     * notifications and webhooks never ran for panel-initiated changes.
     */
    public function publish(): static
    {
        // No publicar una campaña que aún requiere aprobación: la ruta manual
        // de publish (panel/API) solo exige el permiso `update`, así que sin
        // este guard un usuario podía activar el envío masivo saltándose la
        // revisión que sí respeta el scheduler.
        if ($this->requiresPendingApproval()) {
            throw new \RuntimeException('La campaña requiere aprobación antes de publicarse.');
        }

        $this->update([
            'status' => 'active',
            'published_at' => now(),
        ]);

        CampaignPublished::dispatch($this);

        return $this;
    }

    /**
     * Whether the campaign still needs approval before it can go live.
     */
    public function requiresPendingApproval(): bool
    {
        return (bool) $this->approval_required && $this->approved_at === null;
    }

    /**
     * Pause the campaign
     */
    public function pause(): static
    {
        if ($this->is_active) {
            $this->update(['status' => 'paused']);
            CampaignPaused::dispatch($this);
        }

        return $this;
    }

    /**
     * Resume the campaign
     */
    public function resume(): static
    {
        if ($this->status === 'paused') {
            $this->update(['status' => 'active']);
            CampaignResumed::dispatch($this);
        }

        return $this;
    }

    /**
     * End the campaign
     */
    public function end(): static
    {
        $this->update([
            'status' => 'ended',
            'ends_at' => now(),
        ]);

        CampaignEnded::dispatch($this);

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
