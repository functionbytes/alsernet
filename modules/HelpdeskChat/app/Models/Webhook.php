<?php

namespace Modules\HelpdeskChat\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Webhook extends Model
{
    protected $table = 'helpdesk_webhooks';

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
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox (if webhook is inbox-level)
     */
    public function inbox()
    {
        return $this->belongsTo(Inbox::class);
    }
}
