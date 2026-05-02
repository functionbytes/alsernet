<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskSocial\Database\Factories\SocialCompetitorFactory;

class SocialCompetitor extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_competitors';

    protected $fillable = [
        'social_account_id',
        'name',
        'platform',
        'external_id',
        'username',
        'profile_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(SocialCompetitorMetric::class, 'social_competitor_id');
    }

    public function latestMetrics(): HasMany
    {
        return $this->metrics()
            ->orderByDesc('captured_at')
            ->limit(1);
    }

    protected static function newFactory(): SocialCompetitorFactory
    {
        return SocialCompetitorFactory::new();
    }
}
