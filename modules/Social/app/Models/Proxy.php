<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Chat\Models\Accounts\Account;

class Proxy extends Model
{
    protected $fillable = [
        'id_secure',
        'account_id',
        'name',
        'host',
        'port',
        'username',
        'password',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'port' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proxy) {
            if (! $proxy->id_secure) {
                $proxy->id_secure = Str::random(32);
            }
        });
    }

    /**
     * Get the account that owns the proxy
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get social accounts using this proxy
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get full proxy URL
     */
    public function getProxyUrl(): string
    {
        $auth = '';
        if ($this->username && $this->password) {
            $auth = "{$this->username}:{$this->password}@";
        }

        return "{$this->type}://{$auth}{$this->host}:{$this->port}";
    }

    /**
     * Check if proxy is active
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Scope active proxies
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
