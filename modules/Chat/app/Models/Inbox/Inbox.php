<?php

namespace Modules\Chat\Models\Inbox;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Database\Factories\InboxFactory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\CustomerInbox;

class Inbox extends Model
{
    use BelongsToAccount, HasFactory, SoftDeletes;

    protected $table = 'chat_inboxes';

    /**
     * Bootstrap the model by setting up event listeners to clear cache.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear inboxes cache when inbox is deleted, restored, or saved
        static::saved(fn ($inbox) => self::clearInboxCache($inbox->account_id));
        static::deleted(fn ($inbox) => self::clearInboxCache($inbox->account_id));
        static::restored(fn ($inbox) => self::clearInboxCache($inbox->account_id));
    }

    /**
     * Clear the cached inboxes for the given account.
     */
    private static function clearInboxCache(int $accountId): void
    {
        Cache::forget("chat:inboxes:{$accountId}");
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return InboxFactory::new();
    }

    protected $fillable = [
        'account_id',
        'channel_id',
        'channel_type',
        'name',
        'timezone',
        'greeting_enabled',
        'greeting_message',
        'working_hours_enabled',
        'out_of_office_message',
    ];

    protected function casts(): array
    {
        return [
            'greeting_enabled' => 'boolean',
            'working_hours_enabled' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the inbox.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the owning channel model (polymorphic).
     */
    public function channel(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all customer inboxes for this inbox.
     */
    public function customerInboxes(): HasMany
    {
        return $this->hasMany(CustomerInbox::class);
    }

    /**
     * Get all conversation for this inbox.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get all messages for this inbox.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Check if inbox is a specific channel type.
     */
    public function isChannelType(string $type): bool
    {
        return $this->channel_type === "Modules\\Chat\\Models\\Channels\\{$type}";
    }

    /**
     * Shorthand methods for channel type checking.
     */
    public function isWebWidget(): bool
    {
        return $this->isChannelType('Web');
    }

    public function isFacebook(): bool
    {
        return $this->isChannelType('Facebook');
    }

    public function isInstagram(): bool
    {
        return $this->isChannelType('Instagram');
    }

    public function isWhatsapp(): bool
    {
        return $this->isChannelType('Whatsapp');
    }

    public function isEmail(): bool
    {
        return $this->isChannelType('Email');
    }

    /**
     * Human-readable channel name for display in badges.
     */
    public function getChannelDisplayNameAttribute(): string
    {
        return match (true) {
            $this->isWhatsapp() => 'WhatsApp',
            $this->isFacebook() => 'Facebook',
            $this->isInstagram() => 'Instagram',
            $this->isEmail() => 'Email',
            default => $this->name,
        };
    }

    /**
     * Bootstrap badge CSS class for the channel type.
     */
    public function getChannelBadgeClassAttribute(): string
    {
        return match (true) {
            $this->isWhatsapp() => 'text-bg-success',
            $this->isFacebook() => 'text-bg-primary',
            $this->isInstagram() => 'text-bg-danger',
            $this->isEmail() => 'text-bg-warning',
            default => 'text-bg-secondary',
        };
    }

    /**
     * Get the business hours for this inbox.
     */
    public function inboxHours(): HasMany
    {
        return $this->hasMany(InboxHour::class);
    }
}
