<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Pages;
use DataTables;
use App\Models\User;
use App\Models\CategoryUser;
use App\Models\Groups;
use Illuminate\Support\Str;


class GroupCreateController extends Controller
{
    public function index()
    {

        $this->authorize('Groups List Access');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $groups = Groups::get();
        $data['groups'] = $groups;

        return view('admin.groups.index')->with($data)->with('i', (request()->input('page', 1) - 1) * 5);;
    }

    public function create()
    {
        $this->authorize('Groups Create');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $users = User::get();
        $data['users'] = $users;


        return view('admin.groups.create')->with($data);
    }


    public function store(Request $request)
    {
        $this->authorize('Groups Create');
        $request->validate([
            'groupname' => 'required|string|max:255|unique:groups',

        ]);
        $grop = new Groups;

        $grop->groupname = $request->input('groupname');
        $grop->groupstatus = 1;
        $grop->save();

        if($request->input('user_id')){
            foreach ($request->input('user_id') as $value) {
                $user_id[] = $value;


            }
        }

        $grop->groupsusers()->sync($request->get('user_id'));

        return redirect('admin/groups')->with('success', lang('A group was successfully created.', 'alerts'));
    }

    public function show($id)
    {
        $this->authorize('Groups Edit');
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $grop = Groups::find($id);
        $data['group'] = $grop;

        $group = DB::table("groups_users")->where("groups_users.groups_id",$id)
            ->pluck('groups_users.users_id','groups_users.users_id')
            ->all();
        $data['grop'] = $group;

        $users = User::get();
        $data['users'] = $users;

        return view('admin.groups.edit')->with($data);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('Groups Edit');

        $grop = Groups::find($id);
        if($grop->groupname == $request->groupname){
            $grop->groupstatus = 1;
            $grop->update();
            if($request->input('user_id')){
                foreach ($request->input('user_id') as $value) {
                    $user_id[] = $value;


                }
            }

            $grop->groupsusers()->sync($request->get('user_id'));
        }else{
            $request->validate([
                'groupname' => 'required|string|max:255|unique:groups',
            ]);

            $grop->groupname = $request->input('groupname');
            $grop->groupstatus = 1;
            $grop->update();

            if($request->input('user_id')){
                foreach ($request->input('user_id') as $value) {
                    $user_id[] = $value;
                }
            }
            $grop->groupsusers()->sync($request->get('user_id'));
        }

        return redirect('admin/groups')->with('success', lang('The group updated successfully.', 'alerts'));
    }

    public function destroy($id)
    {
        $groupdelete = Groups::find($id);
        $groupdelete->delete();

        return response()->json(['success'=> lang('The group deleted successfully.', 'alerts')]);
    }


    public function destroyall(Request $request)
    {
        $id_array = $request->input('id');

		$groups = Groups::whereIn('id', $id_array)->get();

		foreach($groups as $group){
			$group->delete();

		}
		return response()->json(['success'=> lang('The group deleted successfully.', 'alerts')]);
    }

    public function statuschange(Request $request, $id)
    {
            $id = $request->id;
            $groupstatuschange = Groups::find($id);
            $groupstatuschange->groupstatus = $request->status;
            $groupstatuschange->update();

        return response()->json(['success'=> lang('The group status updated successfully.', 'alerts')]);

    }


}
