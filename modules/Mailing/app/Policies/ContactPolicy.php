<?php

namespace Modules\Mailing\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Mailing\Models\Contact;
use Modules\Mailing\Models\User;

class ContactPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Contact $item)
    {
        return ! isset($item->id) || $user->contact_id == $item->id;
    }
}
