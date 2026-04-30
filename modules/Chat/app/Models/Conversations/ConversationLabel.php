<?php

namespace Modules\Chat\Models\Conversations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\Customers\Customer;

class ConversationLabel extends Model
{
    use BelongsToAccount, SoftDeletes;

    protected $table = 'chat_conversation_labels';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'show_on_sidebar',
        'account_id',
    ];

    protected function casts(): array
    {
        return [
            'show_on_sidebar' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the label
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all customers with this label.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'chat_customer_label', 'label_id', 'customer_id')->withTimestamps();
    }
}
