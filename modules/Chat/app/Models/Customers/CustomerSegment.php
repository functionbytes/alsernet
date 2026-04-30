<?php

namespace Modules\Chat\Models\Customers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Models\Accounts\Account;

class CustomerSegment extends Model
{
    use SoftDeletes;

    protected $table = 'chat_customer_segments';

    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'description',
        'filter_criteria',
        'is_dynamic',
        'customer_count',
    ];

    protected function casts(): array
    {
        return [
            'filter_criteria' => 'array',
            'is_dynamic' => 'boolean',
            'customer_count' => 'integer',
        ];
    }

    /**
     * Segment belongs to an account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Segment belongs to a user (creator).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Customers in this segment.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'chat_customer_segment', 'customer_segment_id', 'customer_id')
            ->withTimestamps()
            ->withPivot('added_at');
    }

    /**
     * Scope to filter segments by account.
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter dynamic segments.
     */
    public function scopeDynamic(Builder $query): Builder
    {
        return $query->where('is_dynamic', true);
    }

    /**
     * Update the customer count cache.
     */
    public function updateCustomerCount(): void
    {
        $this->update(['customer_count' => $this->customers()->count()]);
    }
}
