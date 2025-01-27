<?php

namespace App\Http\Controllers\Callcenters\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inscription;
use App\Models\User;

class UsersCoursesController extends Controller
{
    public function index(Request $request,$slack){

        $user = User::uid($slack);
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
    public function postpone($slack)
    {
        $inscription = Inscription::uid($slack);
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
    public function destroy($slack)
    {
        $inscription = Inscription::uid($slack);
        $inscription->delete();

        return back();
    }

}
