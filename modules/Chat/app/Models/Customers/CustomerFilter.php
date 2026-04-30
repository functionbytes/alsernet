<?php

namespace Modules\Chat\Models\Customers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class CustomerFilter extends Model
{
    protected $table = 'chat_customer_filters';

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
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope for user (includes public filters).
     */
    public function scopeForUser(Builder $query, int $userId, int $accountId): Builder
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
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
