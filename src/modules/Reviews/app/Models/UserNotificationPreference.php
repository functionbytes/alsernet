<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'channels',
        'is_enabled',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'is_enabled' => 'boolean',
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    public function shouldNotify(array $context = []): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if (empty($this->filters)) {
            return true;
        }

        foreach ($this->filters as $key => $value) {
            if ($key === 'min_rating' && isset($context['rating'])) {
                if ($context['rating'] < $value) {
                    return false;
                }
            }

            if ($key === 'max_rating' && isset($context['rating'])) {
                if ($context['rating'] > $value) {
                    return false;
                }
            }

            if ($key === 'location_ids' && isset($context['location_id'])) {
                if (! in_array($context['location_id'], (array) $value)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getChannels(): array
    {
        return $this->channels ?? [];
    }

    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->getChannels());
    }
}
