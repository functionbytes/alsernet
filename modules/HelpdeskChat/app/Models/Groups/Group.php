<?php

namespace Modules\HelpdeskChat\Models\Groups;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class Group extends Model
{
    protected $table = 'helpdesk_groups';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'helpdesk_group_user',
            'helpdesk_group_id',
            'user_id'
        )->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'helpdesk_group_id');
    }
}
