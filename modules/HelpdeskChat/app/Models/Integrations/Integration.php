<?php

namespace Modules\HelpdeskChat\Models\Integrations;

use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Integration extends Model
{
    // Status Enums
    const STATUS_DISABLED = 0;

    const STATUS_ENABLED = 1;

    protected $table = 'helpdesk_integrations';

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
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox
     */
    public function inbox()
    {
        return $this->belongsTo(Inbox::class);
    }
}
