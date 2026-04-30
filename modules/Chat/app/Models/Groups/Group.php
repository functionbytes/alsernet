<?php

namespace Modules\Chat\Models\Groups;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Concerns\BelongsToAccount;
use Modules\Chat\Models\Conversations\Conversation;

class Group extends Model
{
    use BelongsToAccount;

    protected $table = 'chat_groups';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'is_active',
        'assignment_mode',
        'default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default' => 'boolean',
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
            'chat_group_user',
            'group_id',
            'user_id'
        )
            ->withPivot('conversation_priority')
            ->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'group_id');
    }
}
