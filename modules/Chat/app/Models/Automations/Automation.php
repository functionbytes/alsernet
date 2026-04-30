<?php

namespace Modules\Chat\Models\Automations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Database\Factories\AutomationFactory;
use Modules\Chat\Models\Accounts\Account;

class Automation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_automations';

    protected static function newFactory()
    {
        return AutomationFactory::new();
    }

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
        'conditions' => '[]',
        'actions' => '[]',
    ];

    /**
     * Get the account that owns the automation rule
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
