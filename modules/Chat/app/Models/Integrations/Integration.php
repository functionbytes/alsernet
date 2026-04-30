<?php

namespace Modules\Chat\Models\Integrations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Inbox\Inbox;

class Integration extends Model
{
    // Status Enums
    const STATUS_DISABLED = 0;

    const STATUS_ENABLED = 1;

    protected $table = 'chat_integrations';

    protected $fillable = [
        'status',
        'inbox_id',
        'account_id',
        'app_id',
        'hook_type',
        'reference_id',
        'access_token',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => 'integer',
        'hook_type' => 'integer',
    ];

    protected $attributes = [
        'settings' => '{}',
    ];

    /**
     * Get the account that owns the integration hook
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }
}
