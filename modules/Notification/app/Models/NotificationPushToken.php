<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_id',
        'active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function register(int $userId, string $token, array $metadata = []): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'token' => $token,
            ],
            [
                'device_type' => $metadata['device_type'] ?? null,
                'device_id' => $metadata['device_id'] ?? null,
                'active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    public function activate(): void
    {
        $this->update([
            'active' => true,
            'last_used_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    public static function activeForUser(int $userId): Collection
    {
        return static::where('user_id', $userId)
            ->where('active', true)
            ->get();
    }

    public static function cleanup(int $daysInactive = 90): int
    {
        return static::where('active', false)
            ->orWhere('last_used_at', '<', now()->subDays($daysInactive))
            ->delete();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('active', false);
    }

    public function scopeRecentlyUsed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }
}
