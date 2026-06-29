<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\PersonalizationRuleFactory;
use Modules\Helpdesk\Models\Inbox;

class PersonalizationRule extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_personalization_rules';

    protected $fillable = [
        'inbox_id',
        'name',
        'selector',
        'conditions',
        'mutation',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'mutation' => 'array',
            'is_active' => 'boolean',
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

    protected static function newFactory(): PersonalizationRuleFactory
    {
        return PersonalizationRuleFactory::new();
    }
}
