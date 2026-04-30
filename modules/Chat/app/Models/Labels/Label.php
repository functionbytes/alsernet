<?php

namespace Modules\Chat\Models\Labels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class Label extends Model
{
    protected $table = 'chat_labels';

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
