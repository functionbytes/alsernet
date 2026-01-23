<?php

namespace Modules\Subscriber\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Subscriber\Models\NewsletterLIstUser;

class SubscribersListUserController extends Controller
{
    public function destroy($uid)
    {
        $list = null;
        $item = NewsletterListUser::uid($uid);
        $list = $item->list->uid;
        $item->delete();

        return redirect()->route('manager.subscribers.lists.details', $list);
    }
}
