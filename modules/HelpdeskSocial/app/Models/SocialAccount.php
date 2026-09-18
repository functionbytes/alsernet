<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Modules\HelpdeskSocial\Database\Factories\SocialAccountFactory;

class SocialAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_social_accounts';

    protected $fillable = [
        'name',
        'platform',
        'account_type',
        'external_id',
        'username',
        'profile_url',
        'page_access_token',
        'user_access_token',
        'token_expires_at',
        'permissions',
        'settings',
        'is_active',
        'comments_enabled',
        'messages_enabled',
        'auto_reply_enabled',
        'last_synced_at',
        'last_error_at',
        'last_error_message',
        'consecutive_failures',
        'circuit_breaker_active',
        'crisis_mode_active',
        'connected_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'comments_enabled' => 'boolean',
            'messages_enabled' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'crisis_mode_active' => 'boolean',
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'last_error_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    protected function pageAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    protected function userAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'social_account_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(SocialMetrics::class, 'social_account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SocialConversation::class, 'social_account_id');
    }

    public function crisisModes(): HasMany
    {
        return $this->hasMany(SocialCrisisMode::class, 'social_account_id');
    }

    public function activeCrisisMode(): ?SocialCrisisMode
    {
        return $this->crisisModes()->active()->first();
    }

    public function isInCrisisMode(): bool
    {
        return $this->crisis_mode_active;
    }

    public function enterCrisisMode(string $reason, int $userId): SocialCrisisMode
    {
        $this->update(['crisis_mode_active' => true]);

        return $this->crisisModes()->create([
            'reason' => $reason,
            'started_by_user_id' => $userId,
            'started_at' => now(),
        ]);
    }

    public function exitCrisisMode(): void
    {
        $this->update(['crisis_mode_active' => false]);
        $this->crisisModes()->active()->update(['ended_at' => now()]);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function needsTokenRefresh(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->diffInDays(now()) < 7;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeStale($query, int $days = 7)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<=', now()->subDays($days));
        });
    }

    protected static function newFactory(): SocialAccountFactory
    {
        return SocialAccountFactory::new();
    }

    public function recordFailure(): void
    {
        $this->increment('consecutive_failures');

        // Circuit breaker: disable after 5 consecutive failures
        if ($this->consecutive_failures >= 5) {
            $this->update([
                'is_active' => false,
                'last_error_message' => 'Desactivado automáticamente tras 5 fallos consecutivos.',
            ]);
        }
    }

    public function recordSuccess(): void
    {
        if ($this->consecutive_failures > 0) {
            $this->update(['consecutive_failures' => 0]);
        }
    }

    public function isTokenExpiringSoon(int $days = 7): bool
    {
        return $this->token_expires_at && $this->token_expires_at->diffInDays(now()) < $days;
    }
}
