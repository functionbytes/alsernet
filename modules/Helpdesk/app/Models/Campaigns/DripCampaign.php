<?php

namespace Modules\Helpdesk\Models\Campaigns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DripCampaign extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_drip_campaigns';

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const TRIGGER_TYPES = [
        'tag_added' => 'Etiqueta agregada',
        'conversation_closed' => 'Conversación cerrada',
        'csat_low' => 'CSAT bajo (≤ 2)',
        'manual' => 'Inicio manual',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(DripStep::class, 'campaign_id')->orderBy('step_order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(DripExecution::class, 'campaign_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTrigger(Builder $query, string $triggerType, ?string $triggerValue = null): Builder
    {
        return $query->active()
            ->where('trigger_type', $triggerType)
            ->when($triggerValue !== null, fn ($q) => $q->where('trigger_value', $triggerValue));
    }
}
