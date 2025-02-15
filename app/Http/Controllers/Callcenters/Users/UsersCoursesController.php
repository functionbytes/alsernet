<?php

namespace App\Http\Controllers\Callcenters\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inscription;
use App\Models\User;

class UsersCoursesController extends Controller
{
    public function index(Request $request,$uid){

        $user = User::uid($uid);
        $searchKey = null ?? $request->search;

        $inscriptions = $user->inscriptions();

        if ($searchKey != null) {
            $inscriptions = $inscriptions->where('title', 'like', '%' . $searchKey . '%');
        }

        $inscriptions = $inscriptions->paginate(paginationNumber());

        return view('callcenters.views.users.courses.index')->with([
            'inscriptions' => $inscriptions,
        ]);

    }
    public function postpone($uid)
    {
        $inscription = Inscription::uid($uid);
        $user = $inscription->user;
        $enterprise = $user->enterprise;
        $course = $inscription->course;

        return view('callcenters.views.users.courses.postpone')->with([
            'user' => $user,
            'course' => $course,
            'inscription' => $inscription,
            'enterprise' => $enterprise,
        ]);

    }
    public function destroy($uid)
    {
        $inscription = Inscription::uid($uid);
        $inscription->delete();

        return back();
    }

}
