<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\NewsletterSubscriber;

class NewsletterSubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('newsletter.view');
    }

    public function view(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->hasPermissionTo('newsletter.view');
    }

    public function delete(User $user, NewsletterSubscriber $subscriber): bool
    {
        return $user->hasPermissionTo('newsletter.delete');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('newsletter.export');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('newsletter.manage');
    }
}
