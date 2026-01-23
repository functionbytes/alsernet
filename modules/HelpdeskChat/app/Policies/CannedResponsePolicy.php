<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\CannedResponse;

class CannedResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CannedResponse $cannedResponse): bool
    {
        return $user->account_id === $cannedResponse->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CannedResponse $cannedResponse): bool
    {
        return $user->account_id === $cannedResponse->account_id;
    }

    public function delete(User $user, CannedResponse $cannedResponse): bool
    {
        return $user->account_id === $cannedResponse->account_id;
    }

    public function restore(User $user, CannedResponse $cannedResponse): bool
    {
        return $user->account_id === $cannedResponse->account_id;
    }

    public function forceDelete(User $user, CannedResponse $cannedResponse): bool
    {
        return $user->account_id === $cannedResponse->account_id;
    }
}
