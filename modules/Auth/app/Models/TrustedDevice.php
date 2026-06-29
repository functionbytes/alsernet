<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    protected $table = 'trusted_devices';

    protected $fillable = [
        'user_id',
        'fingerprint',
        'label',
        'platform',
        'browser',
        'device_type',
        'ip_address',
        'country',
        'city',
        'trusted',
        'first_seen_at',
        'last_seen_at',
        'trusted_until',
    ];

    protected function casts(): array
    {
        return [
            'trusted' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'trusted_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTrustValid(): bool
    {
        if (! $this->trusted) {
            return false;
        }

        return $this->trusted_until === null || $this->trusted_until->isFuture();
    }
}
