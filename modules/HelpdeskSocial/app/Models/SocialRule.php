<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskSocial\Database\Factories\SocialRuleFactory;

class SocialRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_social_rules';

    protected $fillable = [
        'name',
        'description',
        'platform',
        'conditions',
        'actions',
        'priority',
        'is_active',
        'stop_processing',
        'trigger_count',
        'last_triggered_at',
        'valid_from',
        'valid_until',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'stop_processing' => 'boolean',
            'last_triggered_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, ?string $platform)
    {
        return $query->when($platform, fn ($q) => $q->where(function ($sq) use ($platform) {
            $sq->whereNull('platform')->orWhere('platform', $platform);
        }));
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('id');
    }

    public function scopeValidNow($query)
    {
        $today = now()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
        });
    }

    public function incrementTrigger(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);
    }

    protected static function newFactory(): SocialRuleFactory
    {
        return SocialRuleFactory::new();
    }
}
