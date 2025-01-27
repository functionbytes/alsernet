<?php

namespace App\Http\Controllers\Managers\Tickets;

use App\Models\Ticket\TicketCategorie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Group\Group;
use App\Models\User;

class GroupsController extends Controller
{

    public function index(Request $request){

            $searchKey = null ?? $request->search;
            $available = null ?? $request->available;

            $groups = Group::descending();

            if ($searchKey) {
                $groups = $groups->where('title', 'like', '%' . $searchKey . '%');
            }

            if ($request->available != null) {
                $groups = $groups->where('available', $available);
            }

            $groups = $groups->paginate(paginationNumber());

            return view('managers.views.tickets.groups.index')->with([
                'groups' => $groups,
                'available' => $available,
                'searchKey' => $searchKey,
            ]);

    }

    public function create(){

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $users = User::latest()->available()->whereIn('role', ['manager','support'])->get();
        $users = $users->pluck('email','id');

        $categories = TicketCategorie::latest()->available()->get();
        $categories = $categories->pluck('title','id');

        return view('managers.views.tickets.groups.create')->with([
            'availables' => $availables,
            'users' => $users,
            'categories' => $categories,
        ]);

    }

    public function edit($slack){

        $group = Group::uid($slack);

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        $users = User::latest()->available()->whereIn('role', ['manager','support'])->get();
        $users = $users->pluck('email','id');

        $categories = TicketCategorie::latest()->available()->get();
        $categories = $categories->pluck('title','id');

        return view('managers.views.tickets.groups.edit')->with([
            'group' => $group,
            'users' => $users,
            'availables' => $availables,
            'categories' => $categories,
        ]);

    }

    public function update(Request $request){

        $group = Group::uid($request->uid);
        $group->title = $request->title;
        $group->slug  = Str::slug($request->title, '-');
        $group->available = $request->available;
        $group->update();

        $group->categories()->detach();
        $group->users()->detach();

        if ($request->categories!= null) {
            foreach ( explode(',',$request->categories) as $key => $id) {
                $group->categories()->attach($key, ['category_id' => $id]);
            }
        }

        if ($request->users!= null) {
            foreach ( explode(',',$request->users) as $key => $id) {
                $group->users()->attach($key, ['user_id' => $id]);
            }
        }

        return response()->json([
            'success' => true,
            'slack' => $group->uid,
            'message' => 'Se actualiza el grupo correctamente',
        ]);

    }

    public function store(Request $request){

        $group = new Group;
        $group->uid = $this->generate_uid('groups');
        $group->title = $request->title;
        $group->slug  = Str::slug($request->title, '-');
        $group->available = $request->available;
        $group->save();

        if ($request->categories!= null) {
            $group->categories()->sync(explode(',',$request->categories));
        }

        if ($request->users!= null) {
            $group->users()->sync(explode(',',$request->users));
        }

        return response()->json([
            'success' => true,
            'slack' => $group->uid,
            'message' => 'Se creado el grupo correctamente',
        ]);

    }

    public function destroy($slack){

        $categorie = Group::uid($slack);
        $categorie->delete();

        return redirect()->route('manager.tickets.groups');

    }

}
