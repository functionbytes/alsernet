<?php

namespace Modules\Chat\Models\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Inbox\Inbox;

class CustomerInbox extends Model
{
    protected $table = 'chat_customer_inboxes';

    protected $fillable = [
        'customer_id',
        'inbox_id',
        'source_id',
        'hmac_verified',
        'pubsub_token',
    ];

    protected $casts = [
        'hmac_verified' => 'boolean',
    ];

    /**
     * Get the customer that owns this customer inbox.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the inbox that owns this contact inbox.
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * Get all conversation for this customer inbox.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_inbox_id');
    }

    /**
     * Get the current (latest) conversation.
     */
    public function currentConversation()
    {
        return $this->conversations()->latest()->first();
    }
}
