<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class ContactInbox extends Model
{
    protected $table = 'helpdesk_contact_inboxes';

    protected $fillable = [
        'contact_id',
        'inbox_id',
        'source_id',
        'hmac_verified',
        'pubsub_token',
    ];

    protected $casts = [
        'hmac_verified' => 'boolean',
    ];

    /**
     * Get the contact that owns this contact inbox.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the inbox that owns this contact inbox.
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * Get all conversation for this contact inbox.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the current (latest) conversation.
     */
    public function currentConversation()
    {
        return $this->conversations()->latest()->first();
    }
}
