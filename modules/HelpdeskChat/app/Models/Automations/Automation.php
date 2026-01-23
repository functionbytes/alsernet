<?php

namespace Modules\HelpdeskChat\Models\Automations;

use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskChat\Models\Accounts\Account;

class Automation extends Model
{
    protected $table = 'helpdesk_automations';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'event_name',
        'conditions',
        'actions',
        'active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'conditions' => '{}',
        'actions' => '{}',
    ];

    /**
     * Get the account that owns the automation rule
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
