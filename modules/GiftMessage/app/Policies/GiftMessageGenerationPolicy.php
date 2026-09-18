<?php

namespace Modules\GiftMessage\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\GiftMessage\Models\GiftMessageGeneration;

class GiftMessageGenerationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('giftmessage.view');
    }

    public function view(User $user, GiftMessageGeneration $generation): bool
    {
        return $user->can('giftmessage.view');
    }

    public function delete(User $user, ?GiftMessageGeneration $generation = null): bool
    {
        return $user->can('giftmessage.delete');
    }
}
