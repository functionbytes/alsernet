<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskSocial\Database\Factories\SocialSlaPolicyFactory;

class SocialSlaPolicy extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_sla_policies';

    protected $fillable = [
        'name',
        'response_time_minutes',
        'resolution_time_minutes',
        'platform',
        'priority',
        'is_active',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'response_time_minutes' => 'integer',
            'resolution_time_minutes' => 'integer',
            'is_active' => 'boolean',
            'conditions' => 'array',
        ];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'social_sla_policy_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): SocialSlaPolicyFactory
    {
        return SocialSlaPolicyFactory::new();
    }
}
