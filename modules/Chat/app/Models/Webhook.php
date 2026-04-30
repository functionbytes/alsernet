<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Inbox\Inbox;

class Webhook extends Model
{
    protected $table = 'chat_webhooks';

    // Webhook Type Enums
    const TYPE_ACCOUNT = 0;

    const TYPE_INBOX = 1;

    protected $fillable = [
        'account_id',
        'inbox_id',
        'name',
        'url',
        'webhook_type',
        'subscriptions',
    ];

    protected $casts = [
        'subscriptions' => 'array',
        'webhook_type' => 'integer',
    ];

    protected $attributes = [
        'subscriptions' => '[]',
    ];

    /**
     * Get the account that owns the webhook
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox (if webhook is inbox-level)
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }
}
