<?php

namespace Modules\HelpdeskChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Contacts\Contact;

class Label extends Model
{
    protected $table = 'helpdesk_labels';

    protected $fillable = [
        'title',
        'description',
        'color',
        'show_on_sidebar',
        'account_id',
    ];

    protected $casts = [
        'show_on_sidebar' => 'boolean',
    ];

    /**
     * Get the account that owns the label
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all contacts with this label.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withTimestamps();
    }
}
