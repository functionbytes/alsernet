<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Subscriber;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriberPolicy
{
    use HandlesAuthorization;

    public function read(User $user, Subscriber $item)
    {
        $customer = $user->customer;

        return $item->mailList->customer_id == $customer->id;
    }

    public function create(User $user)
    {
        // constraints are checked in MailListPolicy
        return true;
    }

    public function update(User $user, Subscriber $item)
    {
        $customer = $user->customer;

        return $item->mailList->customer_id == $customer->id;
    }

    public function delete(User $user, Subscriber $item)
    {
        $customer = $user->customer;

        return $item->mailList->customer_id == $customer->id;
    }

    public function subscribe(User $user, Subscriber $subscriber)
    {
        $customer = $user->customer;

        return $subscriber->mailList->customer_id == $customer->id;
    }

    public function unsubscribe(User $user, Subscriber $item)
    {
        $customer = $user->customer;

        return $item->mailList->customer_id == $customer->id;
    }
}
