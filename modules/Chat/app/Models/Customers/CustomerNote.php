<?php

namespace Modules\Chat\Models\Customers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class CustomerNote extends Model
{
    protected $table = 'chat_customer_notes';

    protected $fillable = [
        'account_id',
        'customer_id',
        'user_id',
        'content',
    ];

    /**
     * Get the account that owns the note.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the customer that owns the note.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the user who created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
