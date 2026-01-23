<?php

namespace Modules\Notification\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'browser',
        'platform',
        'is_active',
        'last_used_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
                'browser' => $metadata['browser'] ?? null,
                'platform' => $metadata['platform'] ?? null,
                'ip_address' => $metadata['ip_address'] ?? request()->ip(),
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'last_used_at' => now(),
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public static function activeForUser(int $userId)
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    public static function cleanup(int $daysInactive = 90): int
    {
        return static::where('is_active', false)
            ->orWhere('last_used_at', '<', now()->subDays($daysInactive))
            ->delete();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }
}
