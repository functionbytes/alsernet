<?php

namespace Modules\HelpdeskChat\Models\Customers;

use App\Models\Account;
use App\Models\ChatSession;
use App\Models\PrestashopCustomerSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;

class Customer extends Model
{
    protected $table = 'helpdesk_customers';

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'phone_number',
        'avatar_url',
        'identifier',
        'custom_attributes',
        'additional_attributes',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_attributes' => 'array',
            'additional_attributes' => 'array',
            'last_activity_at' => 'datetime',
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
     * Get all conversation for this customer.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'helpdesk_customer_id');
    }

    /**
     * Get all chat sessions for this customer.
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'helpdesk_customer_id');
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
        return $this->hasOne(PrestashopCustomerSync::class, 'helpdesk_customer_id');
    }

    /**
     * Get all customer sessions for this customer.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'customer_id');
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
     * Update last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get avatar URL or generate initials-based placeholder.
     */
    public function getAvatarUrlAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        return $this->getGravatarUrl();
    }

    /**
     * Get avatar URL (method version for explicit calls).
     */
    public function getAvatarUrl(): string
    {
        return $this->avatar_url;
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
        $parts = explode(' ', $this->name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Scope to filter verified customers.
     * TODO: Add email_verified_at column to table
     */
    public function scopeVerified($query)
    {
        // For now, return all customers
        // Later: return $query->whereNotNull('email_verified_at');
        return $query;
    }

    /**
     * Scope to filter banned customers.
     * TODO: Add is_banned or banned_at column to table
     */
    public function scopeBanned($query)
    {
        // For now, return no customers
        // Later: return $query->where('is_banned', true);
        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope to filter active customers (activity within last 30 days).
     */
    public function scopeActive($query)
    {
        return $query->where('last_activity_at', '>=', now()->subDays(30));
    }

    /**
     * Scope to search customers by name, email, or phone.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%");
        });
    }
}
