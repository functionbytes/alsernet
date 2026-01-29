<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\User;
use Modules\Mailing\Models\Website;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebsitePolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return true;
    }

    public function create(User $user)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return true;
    }

    public function read(User $user, Website $website)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return $user->customer->id == $website->customer_id;
    }

    public function update(User $user, Website $website)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return $user->customer->id == $website->customer_id;
    }

    public function delete(User $user, Website $website)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return $user->customer->id == $website->customer_id;
    }

    public function connect(User $user, Website $website)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return $user->customer->id == $website->customer_id && $website->status == Website::STATUS_INACTIVE;
    }

    public function disconnect(User $user, Website $website)
    {
        if (app_profile('website.disable') === true) {
            return false;
        }

        return $user->customer->id == $website->customer_id && $website->status == Website::STATUS_CONNECTED;
    }
}
