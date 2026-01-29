<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Source;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SourcePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sources.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the source.
     *
     * @param  \Acelle\Source  $source
     * @return mixed
     */
    public function view(User $user, Source $source)
    {
        //
    }

    /**
     * Determine whether the user can create sources.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the source.
     *
     * @param  \Acelle\Source  $source
     * @return mixed
     */
    public function update(User $user, Source $source)
    {
        //
    }

    /**
     * Determine whether the user can delete the source.
     *
     * @param  \Acelle\Source  $source
     * @return mixed
     */
    public function delete(User $user, Source $source)
    {
        return $user->customer->id == $source->customer_id;
    }

    /**
     * Determine whether the user can restore the source.
     *
     * @param  \Acelle\Source  $source
     * @return mixed
     */
    public function restore(User $user, Source $source)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the source.
     *
     * @param  \Acelle\Source  $source
     * @return mixed
     */
    public function forceDelete(User $user, Source $source)
    {
        //
    }
}
