<?php

namespace Modules\Role\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Models\Role;

class UserRoleChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Role $role,
        public readonly string $action, // 'assigned' or 'removed'
        public readonly ?User $actor = null,
    ) {}
}
