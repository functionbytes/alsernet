<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'is_enabled',
        'backups',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'backups' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isEnabled(int $userId, string $channel, string $type): bool
    {
        $preference = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('notification_type', $type)
            ->first();

        return $preference ? $preference->is_enabled : true;
    }

    public static function toggle(int $userId, string $channel, string $type, bool $enabled): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'channel' => $channel,
                'notification_type' => $type,
            ],
            ['is_enabled' => $enabled]
        );
    }

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId)->get()->groupBy('channel')->toArray();
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }
}
