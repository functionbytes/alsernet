<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Accounts\Account;

class ContactFilter extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'filters',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the filter.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the filter.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope for user (includes public filters).
     */
    public function scopeForUser($query, int $userId, int $accountId)
    {
        return $query->where('account_id', $accountId)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('is_public', true);
            });
    }

    /**
     * Scope for public filters only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
