<?php

namespace App\Http\Controllers\Managers\Newsletters;

use App\Models\Newsletter\NewsletterList;
use App\Http\Controllers\Controller;
use App\Models\Newsletter\NewsletterLIstUser;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewslettersListsUserController extends Controller
{


    public function destroy($slack){
        $list = null;
        $item = NewsletterListUser::uid($slack);
        $list = $item->list->uid;
        $item->delete();
        return redirect()->route('manager.newsletters.lists.details', $list);
    }

}

