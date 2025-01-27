<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Department;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Pages;

class DepartmentController extends Controller
{
    public function index()
    {

        $this->authorize('Department Access');

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $departments = Department::latest()->get();
        $data['departments'] = $departments;

        return view('admin.department.index')->with($data);
    }


    public function create(Request $request)
    {
        $validate = Department::find($request->id);
        if(!$validate){

            $request->validate([

                'departmentname'=> 'required|max:255|unique:departments',

            ]);


        }
        if($validate){
            if($request->faqcatname == $validate->faqcategoryname){

                $request->validate([

                    'departmentname'=> 'required|max:255',

                ]);

            }else{

                $request->validate([

                    'departmentname'=> 'required|max:255|unique:departments',

                ]);
            }
        }

          $department = Department::updateOrCreate( ['id' => $request->id], ['departmentname' => $request->departmentname, 'status' => $request->status ]);

          return response()->json(['code'=>200, 'success'=> lang('The Department has been successfully updated.', 'alerts'),'data' => $department], 200);
    }

    public function edit($id)
    {
        $this->authorize('Department Edit');
        $department = Department::find($id);
        return response()->json($department);
    }

    public function delete($id)
    {
        $this->authorize('Department Delete');
        $department = Department::find($id);
		$department->delete();

		return response()->json(['success'=>lang('The Department has been successfully deleted.', 'alerts')]);
    }

    public function deleteall(Request $request)
    {
        $this->authorize('Department Delete');
        $id_array = $request->input('id');

		$departments = Department::whereIn('id', $id_array)->get();

		foreach($departments as $department){
			$department->delete();

		}
		return response()->json(['success'=> lang('The Department has been successfully deleted.', 'alerts')]);
    }

    public function status(Request $request, $id)
    {
        $this->authorize('Department Edit');

        $calID = Department::find($id);
        $calID->status = $request->status;
        $calID->save();

        return response()->json(['code'=>200, 'success'=>lang('Updated Successfully', 'alerts')], 200);

    }

}
