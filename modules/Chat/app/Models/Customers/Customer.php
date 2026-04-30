<?php

namespace Modules\Chat\Models\Customers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Chat\Database\Factories\CustomerFactory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Inbox\Inbox;

class Customer extends Model
{
    use BelongsToAccount, HasFactory, Searchable, SoftDeletes;

    protected $table = 'chat_customers';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CustomerFactory::new();
    }

    protected $fillable = [
        'account_id',
        'name',
        'last_name',
        'email',
        'phone_number',
        'avatar_url',
        'identifier',
        'company_name',
        'bio',
        'city',
        'country',
        'country_code',
        'social_profiles',
        'blocked',
        'custom_attributes',
        'additional_attributes',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_attributes' => 'array',
            'additional_attributes' => 'array',
            'social_profiles' => 'array',
            'blocked' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name ?? '',
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'company_name' => $this->company_name ?? '',
            'bio' => $this->bio ?? '',
            'city' => $this->city ?? '',
            'country' => $this->country ?? '',
        ];
    }

    /**
     * Get the account that owns the customer.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all customer inboxes for this customer.
     */
    public function customerInboxes(): HasMany
    {
        return $this->hasMany(CustomerInbox::class, 'customer_id');
    }

    /**
     * Get all conversations for this customer.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_id');
    }

    /**
     * Get all inboxes this customer is connected to.
     */
    public function inboxes(): HasManyThrough
    {
        return $this->hasManyThrough(Inbox::class, CustomerInbox::class, 'customer_id', 'id', 'id', 'inbox_id');
    }

    /**
     * Get all labels for this customer.
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ConversationLabel::class, 'chat_customer_label', 'customer_id', 'label_id')->withTimestamps();
    }

    /**
     * Get all notes for this customer.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'customer_id')->latest();
    }

    /**
     * Get all segments this customer belongs to.
     */
    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(CustomerSegment::class, 'chat_customer_segment', 'customer_id', 'customer_segment_id')
            ->withPivot('added_at');
    }

    /**
     * Get all messages sent by this customer.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'sender_id')
            ->where('sender_type', self::class);
    }

    /**
     * Get the PrestaShop sync record for this customer.
     */
    public function prestashopSync(): HasOne
    {
        return $this->hasOne(PrestashopCustomerSync::class, 'customer_id');
    }

    /**
     * Get all customer sessions for this customer.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'customer_id');
    }

    /**
     * Get the avatar for this customer.
     */
    public function avatar(): HasOne
    {
        return $this->hasOne(CustomerAvatar::class, 'customer_id');
    }

    /**
     * Get the latest customer session.
     */
    public function latestSession(): HasOne
    {
        return $this->hasOne(CustomerSession::class, 'customer_id')->latestOfMany();
    }

    /**
     * Get all page visits for this customer.
     */
    public function pageVisits(): HasMany
    {
        return $this->hasMany(PageVisit::class, 'customer_id');
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name.' '.($this->last_name ?? ''));
    }

    /**
     * Check if customer is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->blocked ?? false;
    }

    /**
     * Block this customer.
     */
    public function block(): void
    {
        $this->update(['blocked' => true]);
    }

    /**
     * Unblock this customer.
     */
    public function unblock(): void
    {
        $this->update(['blocked' => false]);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get avatar URL (uploaded or Gravatar fallback).
     */
    public function getAvatarUrlAttribute(?string $value): string
    {
        // Check for uploaded avatar via relationship
        if ($this->relationLoaded('avatar') && $this->avatar) {
            return $this->avatar->url;
        }

        // Check for direct avatar_url column value
        if ($value) {
            return $value;
        }

        return $this->getGravatarUrl();
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->relationLoaded('avatar') && $this->avatar && $this->avatar->thumbnail_url) {
            return $this->avatar->thumbnail_url;
        }

        return $this->getGravatarUrl(50);
    }

    /**
     * Get Gravatar URL.
     */
    public function getGravatarUrl(int $size = 200): string
    {
        $hash = md5(strtolower(trim($this->email ?? '')));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    /**
     * Get initials for avatar placeholder.
     */
    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->name ? strtoupper(substr($this->name, 0, 1)) : '';
        $lastInitial = $this->last_name ? strtoupper(substr($this->last_name, 0, 1)) : '';

        if ($firstInitial && $lastInitial) {
            return $firstInitial.$lastInitial;
        }

        if ($firstInitial) {
            $parts = explode(' ', $this->name);
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
            }

            return strtoupper(substr($this->name, 0, 2));
        }

        return '?';
    }

    /**
     * Scope to filter verified customers.
     * TODO: Add email_verified_at column to table
     */
    public function scopeVerified(Builder $query): Builder
    {
        // For now, return all customers
        // Later: return $query->whereNotNull('email_verified_at');
        return $query;
    }

    /**
     * Scope to filter banned customers.
     * TODO: Add is_banned or banned_at column to table
     */
    public function scopeBanned(Builder $query): Builder
    {
        // For now, return no customers
        // Later: return $query->where('is_banned', true);
        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope to filter active customers (activity within last 30 days).
     */
    public function scopeActive(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter customers created this month.
     */
    public function scopeCreatedThisMonth(Builder $query): Builder
    {
        return $query->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);
    }

    /**
     * Scope to search customers by name, email, or phone.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to find duplicate customers by email or phone.
     */
    public function scopeDuplicateContact(Builder $query, ?string $email, ?string $phoneNumber): Builder
    {
        return $query->where(function ($q) use ($email, $phoneNumber) {
            if ($email) {
                $q->where('email', $email);
            }
            if ($phoneNumber) {
                $q->orWhere('phone_number', $phoneNumber);
            }
        });
    }

    /**
     * Scope to order by most recent activity.
     */
    public function scopeRecentActivity(Builder $query): Builder
    {
        return $query->orderBy('last_activity_at', 'desc');
    }

    /**
     * Scope to filter by segment.
     */
    public function scopeInSegment(Builder $query, int $segmentId): Builder
    {
        return $query->whereHas('segments', function ($q) use ($segmentId) {
            $q->where('customer_segment_id', $segmentId);
        });
    }

    /**
     * Add labels to customer via pivot table.
     */
    public function addLabels(array $labelTitles): void
    {
        $labelIds = ConversationLabel::where('account_id', $this->account_id)
            ->whereIn('name', $labelTitles)
            ->pluck('id')
            ->toArray();

        if (! empty($labelIds)) {
            $this->labels()->syncWithoutDetaching($labelIds);
        }
    }

    /**
     * Remove labels from customer via pivot table.
     */
    public function removeLabels(array $labelTitles): void
    {
        $labelIds = ConversationLabel::where('account_id', $this->account_id)
            ->whereIn('name', $labelTitles)
            ->pluck('id')
            ->toArray();

        if (! empty($labelIds)) {
            $this->labels()->detach($labelIds);
        }
    }
}
