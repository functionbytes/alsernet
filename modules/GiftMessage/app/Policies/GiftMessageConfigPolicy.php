<?php

namespace Modules\GiftMessage\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\GiftMessage\Models\GiftMessageConfig;

class GiftMessageConfigPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?GiftMessageConfig $config = null): bool
    {
        return $user->can('giftmessage.view');
    }

    public function update(User $user, ?GiftMessageConfig $config = null): bool
    {
        return $user->can('giftmessage.update');
    }

    public function manage(User $user, ?GiftMessageConfig $config = null): bool
    {
        return $user->can('giftmessage.manage');
    }
}
