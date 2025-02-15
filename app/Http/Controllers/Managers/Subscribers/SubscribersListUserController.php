<?php

namespace App\Http\Controllers\Managers\Subscribers;

use App\Models\Subscriber\SubscriberList;
use App\Http\Controllers\Controller;
use App\Models\Subscriber\NewsletterLIstUser;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscribersListUserController extends Controller
{


    public function destroy($uid){
        $list = null;
        $item = NewsletterListUser::uid($uid);
        $list = $item->list->uid;
        $item->delete();
        return redirect()->route('manager.subscribers.lists.details', $list);
    }

}

