<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Reviews\Enums\ConnectionStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReviewGoogleConnection extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'review_google_connections';

    protected $fillable = [
        'user_id',
        'name',
        'google_account_id',
        'google_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'status',
        'last_error',
        'connected_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
            'status' => ConnectionStatus::class,
            'connected_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'google_email', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ReviewGoogleLocation::class, 'connection_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ConnectionStatus::ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', ConnectionStatus::EXPIRED);
    }

    public function isActive(): bool
    {
        return $this->status === ConnectionStatus::ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function markAsActive(): void
    {
        $this->update([
            'status' => ConnectionStatus::ACTIVE,
            'last_error' => null,
        ]);
    }

    public function markAsExpired(?string $error = null): void
    {
        $this->update([
            'status' => ConnectionStatus::EXPIRED,
            'last_error' => $error,
        ]);
    }

    public function markAsRevoked(): void
    {
        $this->update([
            'status' => ConnectionStatus::REVOKED,
            'revoked_at' => now(),
        ]);
    }
}
