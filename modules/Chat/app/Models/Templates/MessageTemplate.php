<?php

namespace Modules\Chat\Models\Templates;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class MessageTemplate extends Model
{
    protected $table = 'chat_message_templates';

    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'content',
        'shortcut',
        'category',
        'is_public',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the account that owns the template.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user that created the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter templates by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter public templates.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter private templates for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_public', true);
        });
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search templates.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('shortcut', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to order by most used.
     */
    public function scopeMostUsed($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    /**
     * Scope to order by recently used.
     */
    public function scopeRecentlyUsed($query)
    {
        return $query->whereNotNull('last_used_at')
            ->orderBy('last_used_at', 'desc');
    }

    /**
     * Increment usage count.
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get available variables for templates.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'contact' => [
                'name' => 'Customers Name',
                'email' => 'Customers Email',
                'phone' => 'Customers Phone',
                'city' => 'Customers City',
                'country' => 'Customers Country',
            ],
            'agent' => [
                'name' => 'Agent Name',
                'email' => 'Agent Email',
            ],
            'conversation' => [
                'id' => 'Conversation ID',
                'status' => 'Conversation Status',
            ],
            'account' => [
                'name' => 'Account Name',
            ],
            'system' => [
                'date' => 'Current Date',
                'time' => 'Current Time',
                'datetime' => 'Current Date and Time',
            ],
        ];
    }

    /**
     * Get categories.
     */
    public static function getCategories(): array
    {
        return [
            'greeting' => 'Greetings',
            'closing' => 'Closings',
            'question' => 'Questions',
            'response' => 'Responses',
            'support' => 'Support',
            'sales' => 'Sales',
            'feedback' => 'Feedback',
            'other' => 'Other',
        ];
    }
}
