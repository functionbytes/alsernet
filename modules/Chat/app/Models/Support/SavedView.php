<?php

namespace Modules\Chat\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class SavedView extends Model
{
    protected $table = 'chat_saved_views';

    protected $fillable = [
        'user_id',
        'account_id',
        'name',
        'description',
        'filters',
        'columns',
        'sort_by',
        'sort_order',
        'is_default',
        'is_public',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
