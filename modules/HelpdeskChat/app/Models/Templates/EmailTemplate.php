<?php

namespace Modules\HelpdeskChat\Models\Templates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Accounts\Account;

class EmailTemplate extends Model
{
    protected $table = 'helpdesk_teams';

    protected $fillable = [
        'account_id',
        'name',
        'subject',
        'body_html',
        'body_text',
        'event_type',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the template.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to get only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get templates by event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Get the template for a specific event and account.
     */
    public static function getForEvent(int $accountId, string $eventType): ?self
    {
        return self::where('account_id', $accountId)
            ->where('event_type', $eventType)
            ->where('active', true)
            ->first();
    }
}
