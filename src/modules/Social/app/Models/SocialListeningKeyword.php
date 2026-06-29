<?php

namespace Modules\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;

class SocialListeningKeyword extends Model
{
    protected $fillable = [
        'account_id',
        'name',
        'type',
        'networks',
        'is_active',
        'sentiment_filter',
        'notify_on_match',
        'last_scanned_at',
        'mentions_count',
        'color',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'networks' => 'array',
            'is_active' => 'boolean',
            'notify_on_match' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class, 'listening_keyword_id');
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForNetwork($query, string $network)
    {
        return $query->whereJsonContains('networks', $network);
    }

    public function scopeNeedsScanning($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_scanned_at')
                    ->orWhere('last_scanned_at', '<', now()->subMinutes(15));
            });
    }

    public function scopeWithNotifications($query)
    {
        return $query->where('notify_on_match', true);
    }

    /**
     * Helper Methods
     */
    public function getFormattedNameAttribute(): string
    {
        return match ($this->type) {
            'hashtag' => '#'.$this->name,
            'mention' => '@'.$this->name,
            default => $this->name,
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'keyword' => 'ti-search',
            'hashtag' => 'ti-hash',
            'mention' => 'ti-at',
            'competitor' => 'ti-trophy',
            default => 'ti-info-circle',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'keyword' => 'Palabra Clave',
            'hashtag' => 'Hashtag',
            'mention' => 'Mención',
            'competitor' => 'Competidor',
            default => 'Desconocido',
        };
    }

    public function incrementMentionsCount(): void
    {
        $this->increment('mentions_count');
        $this->update(['last_scanned_at' => now()]);
    }

    public function markAsScanned(): void
    {
        $this->update(['last_scanned_at' => now()]);
    }

    public function toggleActive(): void
    {
        $this->update(['is_active' => ! $this->is_active]);
    }

    /**
     * Get search query based on type
     */
    public function getSearchQuery(): string
    {
        return match ($this->type) {
            'hashtag' => '#'.$this->name,
            'mention' => '@'.$this->name,
            default => $this->name,
        };
    }

    /**
     * Check if should scan this network
     */
    public function shouldScanNetwork(string $network): bool
    {
        return in_array($network, $this->networks);
    }

    /**
     * Get recent mentions (last 7 days)
     */
    public function getRecentMentionsAttribute()
    {
        return $this->mentions()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    /**
     * Get sentiment distribution
     */
    public function getSentimentDistribution(): array
    {
        $mentions = $this->mentions()->get();

        return [
            'positive' => $mentions->where('sentiment', 'positive')->count(),
            'negative' => $mentions->where('sentiment', 'negative')->count(),
            'neutral' => $mentions->where('sentiment', 'neutral')->count(),
        ];
    }
}
