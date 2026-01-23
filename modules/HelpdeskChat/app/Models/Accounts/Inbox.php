<?php

namespace Modules\HelpdeskChat\Models\Accounts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;

class Inbox extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_inboxes';

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

    protected $casts = [
        'greeting_enabled' => 'boolean',
        'working_hours_enabled' => 'boolean',
    ];

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
     * Get all contact inboxes for this inbox.
     */
    public function contactInboxes(): HasMany
    {
        return $this->hasMany(ContactInbox::class);
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
        return $this->hasMany(Message::class);
    }

    /**
     * Check if inbox is a specific channel type.
     */
    public function isChannelType(string $type): bool
    {
        return $this->channel_type === "App\\Models\\Channels\\{$type}";
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
}
