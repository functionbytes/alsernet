<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Accounts\Account;

class ContactNote extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',
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
     * Get the contact that owns the note.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user who created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
