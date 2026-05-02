<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\Chat\Models\Accounts\Account;
use Modules\Social\Enums\AccountStatus;
use Modules\Social\Enums\SocialNetwork;

class SocialAccount extends Model
{
    use HasFactory;

    protected $table = 'social_accounts';

    protected $fillable = [
        'id_secure',
        'account_id',
        'network',
        'network_id',
        'channel_type',
        'category',
        'username',
        'name',
        'avatar',
        'proxy_id',
        'access_token',
        'token_expires_at',
        'refresh_token',
        'data',
        'profile_data',
        'status',
        'last_error',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'network' => SocialNetwork::class,
            'status' => AccountStatus::class,
            'data' => 'array',
            'profile_data' => 'array',
            'token_expires_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($socialAccount) {
            if (! $socialAccount->id_secure) {
                $socialAccount->id_secure = Str::random(32);
            }
        });
    }

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Accessors & Mutators
    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === AccountStatus::ACTIVE;
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function needsReconnection(): bool
    {
        return $this->status === AccountStatus::ERROR || $this->isTokenExpired();
    }

    /**
     * Get proxy URL if proxy is set
     */
    public function getProxyUrl(): ?string
    {
        return $this->proxy?->getProxyUrl();
    }

    /**
     * Scope: Filter by network
     */
    public function scopeNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }

    /**
     * Scope: Active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', AccountStatus::ACTIVE);
    }
}
