<?php

namespace Modules\Role\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Models\Role;

class RoleUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Role $role,
        public readonly ?User $actor = null,
    ) {}
}
