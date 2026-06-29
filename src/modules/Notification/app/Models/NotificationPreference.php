<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
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

        return $preference ? $preference->enabled : true;
    }

    public static function toggle(int $userId, string $channel, string $type, bool $enabled): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'channel' => $channel,
                'notification_type' => $type,
            ],
            ['enabled' => $enabled]
        );
    }

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId)->get()->groupBy('channel')->toArray();
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('notification_type', $type);
    }
}
