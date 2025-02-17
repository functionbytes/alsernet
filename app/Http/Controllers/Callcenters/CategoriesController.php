<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket\Category;
use App\Models\Ticket\Ticket;
use Auth;
use Illuminate\Support\Facades\DB;
use Mail;
use App\Mail\mailmailablesend;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Pages;
use App\Models\Projects;
use App\Models\Groups;
use DataTables;
use App\Models\User;
use App\Models\Subcategory;
use App\Models\Subcategorychild;
use App\Models\CategoryUser;
use Illuminate\Support\Facades\Validator;
use Response;
use Illuminate\Support\Str;
use Modules\Uhelpupdate\Entities\CategoryEnvato;
use App\Models\tickethistory;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('Category Access');

            $categories = DB::table('categories')->paginate();
            $data['categories'] = $categories;

            $title = Apptitle::first();
            $data['title'] = $title;

            $footertext = Footertext::first();
            $data['footertext'] = $footertext;

            $seopage = Seosetting::first();
            $data['seopage'] = $seopage;

            $post = Pages::all();
            $data['page'] = $post;

            $categories = Category::get();
            $data['categories'] = $categories;

            return view('admin.category.index')-> with($data)->with('i', (request()->input('page', 1) - 1) * 5);

    }

    public function status(Request $request, $id)
    {
          $calID = Category::find($id);
          $calID ->status = $request->status;
          $calID ->save();


        return response()->json(['code'=>200, 'success'=> lang('Updated successfully', 'alerts')], 200);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display' => 'required|in:both,ticket,knowledge',

        ]);

        if($validator->passes()){


            $testiId = $request->testimonial_id;
            $categoryfind = Category::find($testiId);
            if($categoryfind){
                if($categoryfind->categoryslug == null){

                    $testi =  [
                        'name' => $request->name,
                        'display' => $request->display,
                        'priority' => $request->priority,
                        'categoryslug' => Str::slug($request->name, '-'),
                        'status' => $request->status ?  '1' :  '0',
                    ];
                }
                if($categoryfind->categoryslug != null){

                    $testi =  [
                        'name' => $request->name,
                        'display' => $request->display,
                        'priority' => $request->priority,
                        'status' => $request->status ?  '1' :  '0',
                    ];
                }
            }
            if(!$categoryfind){
                $testi =  [
                    'name' => $request->name,
                    'display' => $request->display,
                    'priority' => $request->priority,
                    'categoryslug' => Str::slug($request->name, '-'),
                    'status' => $request->status ?  '1' :  '0',
                ];
            }



            $testimonial = Category::updateOrCreate(['id' => $testiId], $testi);


            return response()->json(['code'=>200, 'success'=> lang('The category was successfully updated.', 'alerts'),'data' => $testimonial], 200);
        }else{
            return Response::json(['errors' => $validator->errors()]);
        }



    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->authorize('Category Edit');
        $post = Category::find($id);

        $data = [
            'post' => $post,
        ];
        return response()->json($data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->authorize('Category Edit');
        $categories = Category::where('id', $id)->findOrFail();
        $data['categories'] = $categories;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $title = Apptitle::first();
        $data['title'] = $title;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        return view('admin.category.showcategory')-> with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'catogorynam' => 'required|string|max:255',

        ]);
        $Category = Category::findOrFail($id);

        $Category->name = $request->input('catogorynam');
        $Category->update();
        return redirect('/admin/categories')->with('success', lang('The category was successfully updated.', 'alerts'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('Category Delete');
        $Category = Category::findOrFail($id);

        $Category->delete();


        return response()->json(['success'=> lang('The category was successfully deleted.', 'alerts')]);
    }


    public function agentshow(Request $req, $id)
    {

        if($req->ajax()){
            $output = '';

            $assign = Category::find($id);

            $group =User::
            leftJoin('groups_users', 'users.id','groups_users.users_id')
            ->leftJoin('groups_categories','groups_users.groups_id','groups_categories.group_id')
            ->where('groups_categories.category_id', $id)->first();

            if($group != null){
                $data =User::
                leftJoin('groups_users', 'users.id','groups_users.users_id')
                ->leftJoin('groups_categories','groups_users.groups_id','groups_categories.group_id')
                ->whereNull('groups_categories.category_id')->get();
            }else{
                $data =User::
                leftJoin('groups_users', 'users.id','groups_users.users_id')
                ->leftJoin('groups_categories','groups_users.groups_id','groups_categories.group_id')
                ->whereNull('groups_categories.category_id')->get();
            }



            $total_row = $data->count();

            $cat = DB::table("category_category_user")->where("category_category_user.category_id",$id)
            ->pluck('category_category_user.category_user_id','category_category_user.category_user_id')
            ->all();

            if($total_row > 0){
                foreach($data as $row){


                    $output .= '

                        <option  value="'.$row->id.'" ' .( $row->id  ? in_array($row->id,$cat) ?  'selected' : '' : '').'>'.$row->name.' '.(!empty($row->getRoleNames()[0])? $row->getRoleNames()[0] : '').'</option>

                        ';

                }

            }
            $data = array(
                'assign_data'=> $assign,
                'table_data' => $output,
                'total_data' => $total_row,
                'data' => $cat
            );


        }

    }


    public function agentshowcreate(Request $request)
    {

        $data =  $request->assigned_id;
        $cat = Category::find($data);
        $cat->update($request->only(['assigned_name']));

        if($request->input('assigned_user_id')){
            foreach ($request->input('assigned_user_id') as $value) {
                $assigned_user_id[] = $value;
            }
        }

        $cat->catagent()->sync($request->get('assigned_user_id'));

        return response()->json(['success'=> lang('Updated successfully', 'alerts')]);

    }


    public function groupshow(Request $req, $id)
    {

        if($req->ajax()){
            $output = '';

            $assign = Category::find($id);

            $data = Groups::get();

            $total_row = $data->count();

            $cat = DB::table("groups_categories")->where("groups_categories.category_id",$id)
            ->pluck('groups_categories.group_id','groups_categories.group_id')
            ->all();

            if($total_row > 0){
                foreach($data as $row){


                    $output .= '


                        <option  value="'.$row->id.'" ' .( $row->id  ? in_array($row->id,$cat) ?  'selected' : '' : '').'>'.$row->groupname.'</option>

                    ';


                }

            }
            $data = array(
                'assign_data'=> $assign,
                'table_data' => $output,
                'total_data' => $total_row,
                'data' => $cat
            );


            return response()->json($data);
        }

    }

    public function categorygroupassign(Request $request)
    {

        $data =  $request->category_id;
        $cat = Category::find($data);
        $cat->update($request->only(['category_name']));

        if($request->input('group_id')){
            foreach ($request->input('group_id') as $value) {
                $group_id[] = $value;


            }
        }

        $cat->groupscategory()->sync($request->get('group_id'));


        return response()->json(['success'=> lang('Updated successfully', 'alerts')]);

    }


    /// category list

    public function categorylist(Request $req, $ticket_id)
    {

        if($req->ajax()){
            $output = '';
            $category = Category::whereIn('display',['ticket', 'both'])->where('status', '1')->get();

            $totalrow = $category->count();
            $ticket = DB::table('tickets')->where('ticket_id', $ticket_id)->first();
            if($totalrow > 0){
                $output .='<option label="Select Category"></option>';
                foreach($category as $categories){
                    $output .= '
                    <option  value="'.$categories->id.'"'.($categories->id == $ticket->category_id ? 'selected': '').'>'.$categories->name.'</option>
                    ';
                }
            }else{
                $output .= '
                <option label="No Data Found"></option>
                ';
            }

            $projectoutput = '';
            $projects = Projects::get();
            if($projects->count() > 0){
                foreach($projects as $project){
                    $projectoutput .= '<option  value="'.$project->name.'"'.($project->name == $ticket->project ? 'selected': '').'>'.$project->name.'</option>';
                }
            }else{
                $projectoutput .= '
                <option label="No Data Found"></option>
                ';
            }

            $subcategory = '';
            $ticket1 = DB::table('tickets')->where('ticket_id', $ticket_id)->first();
            $category1 = Subcategorychild::where('category_id',$ticket1->category_id)->get();

            $totalrow1 = $category1->count();
            if($totalrow1 > 0){
                $subcategory .='<option label="Select Category"></option>';
                foreach($category1 as $categories){

                    foreach ($categories->subcatlists()->where('status', '1')->get() as $subcategorylist) {
                       $subcategory .= '
                        <option  value="'.$subcategorylist->id.'"'.($subcategorylist->id == $ticket1->subcategory ? 'selected': '').'>'.$subcategorylist->subcategoryname.'</option>
                        ';
                    }

                }
            }

            $data = array(

                'table_data' => $output,
                'total_data' => $totalrow,
                'ticket' => $ticket,
                'projectop' => $projectoutput,
                'subcategoryt' => $subcategory,

            );
          return response()->json($data, 200);
        }


    }


    public function categorychange(Request $req)
    {

        $this->validate($req, [
            'category' => 'required',
        ]);

        $ticketcategory = Ticket::find($req->ticket_id);
        //old category name
        $oldcategory = Category::find($ticketcategory->category_id);
        $oldcategoryName = $oldcategory->name;
        $ticketcategory->category_id = $req->category;
        $ticketcategory->project = $req->project;
        $ticketcategory->subcategory = $req->subscategory;
        if($req->envato_id){
            $ticketcategory->purchasecode = encrypt($req->envato_id);
            $ticketcategory->purchasecodesupport = $req->envato_support;
        }




        $findcat = Category::find($req->category);
        // new category or changed category
        $changedcategory = $findcat->name;
        $ticketcategory->priority = $findcat->priority;
        $ticketcategory->update();

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $ticketcategory->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($ticketcategory->ticketnote->isEmpty()){
            if($ticketcategory->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketcategory->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticketcategory->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketcategory->status.'</span>
                ';
            }

        }else{
            if($ticketcategory->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketcategory->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticketcategory->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketcategory->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Category Updated)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.Auth::user()->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();

        $ticketData = [
            'ticket_username' => $ticketcategory->cust->username,
            'ticket_id' => $ticketcategory->ticket_id,
            'ticket_title' => $ticketcategory->subject,
            'ticket_description' => $ticketcategory->message,
            'ticket_status' => $ticketcategory->status,
            'ticket_oldcategory' => $oldcategoryName,
            'ticket_changedcategory' => $changedcategory,
            'ticket_customer_url' => route('loadmore.load_data', $ticketcategory->ticket_id),
            'ticket_admin_url' => url('/admin/ticket-view/'.$ticketcategory->ticket_id),
        ];

        try{

            $notificationcatss = $ticketcategory->category->groupscategoryc()->get();
            $icc = array();
            if($notificationcatss->isNotEmpty()){

                foreach($notificationcatss as $igc){

                    foreach($igc->groupsc->groupsuser()->get() as $user){
                        $icc[] .= $user->users_id;
                    }
                }

                if(!$icc){
                    $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($admins as $admin){
                        if($admin->getRoleNames()[0] != 'superadmin' && $admin->usetting->emailnotifyon == 1){
                            Mail::to($admin->email)
                            ->send( new mailmailablesend('send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                        }
                    }

                }else{
                    if($ticketcategory->myassignuser){
                        $assignee = $ticketcategory->ticketassignmutliples;
                        foreach($assignee as $assignees){
                            $user = User::where('id',$assignees->toassignuser_id)->get();
                            foreach($user as $users){
                                if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin' && $users->usetting->emailnotifyon == 1){
                                    Mail::to($users->email)
                                    ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                                }
                            }
                        }
                    }
                    else if ($ticketcategory->selfassignuser_id) {
                        $self = User::findOrFail($ticketcategory->selfassignuser_id);
                        if($self->getRoleNames()[0] != 'superadmin' && $self->usetting->emailnotifyon == 1){
                            Mail::to($self->email)
                            ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                        }
                    }
                    else if($icc ){
                        $user = User::whereIn('id', $icc)->get();
                        foreach($user as $users){
                            if($users->usetting->emailnotifyon == 1){
                                Mail::to($users->email)
                                ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                            }
                        }
                    }
                    else {
                        $users = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                        foreach($users as $user){
                            if($user->getRoleNames()[0] != 'superadmin' && $user->usetting->emailnotifyon == 1){
                                Mail::to($user->email)
                                ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                            }
                        }
                    }
                }
            }else{
                if($ticketcategory->myassignuser){
                    $assignee = $ticketcategory->ticketassignmutliples;
                    foreach($assignee as $assignees){
                        $user = User::where('id',$assignees->toassignuser_id)->get();
                        foreach($user as $users){
                            if($users->id == $assignees->toassignuser_id && $users->getRoleNames()[0] != 'superadmin' && $users->usetting->emailnotifyon == 1){
                                Mail::to($users->email)
                                ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                            }
                        }
                    }
                } else if ($ticketcategory->selfassignuser_id) {
                    $self = User::findOrFail($ticketcategory->selfassignuser_id);
                    if($self->getRoleNames()[0] != 'superadmin' && $self->usetting->emailnotifyon == 1){
                        Mail::to($self->email)
                        ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                    }
                } else {

                    $users = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($users as $user){
                        if($user->getRoleNames()[0] != 'superadmin' && $user->usetting->emailnotifyon == 1){
                            Mail::to($user->email)
                            ->send( new mailmailablesend( 'send_mail_admin_panel_users_when_category_changed', $ticketData ) );
                        }
                    }
                }
            }

            $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
            foreach($admins as $admin){
                if($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1){
                    Mail::to($admin->email)
                    ->send( new mailmailablesend('send_mail_admin_panel_users_when_category_changed', $ticketData) );
                }
            }

            Mail::to($ticketcategory->cust->email)
            ->send( new mailmailablesend('send_mail_customer_when_category_changed', $ticketData) );
        }
        catch(\Exception $e){
            return response()->json(['success' => lang('Updated successfully', 'alerts')]);
        }

        return response()->json(['success' => lang('Updated successfully', 'alerts')]);


    }

    public function categorylistshow(Request $request)
    {

        if($request->ajax()){

            $output = '';
            $category = Category::whereIn('display',['ticket', 'both'])->where('status', '1')->get();

            $categoryenvato = CategoryEnvato::pluck('category_id')->toArray();


            $totalrow = $category->count();

            if($totalrow > 0){
                $output .='<option label="Select Category"></option>';
                foreach($category as $categories){
                    $output .= '
                    <option  value="'.$categories->id.'" '.(in_array($categories->id, $categoryenvato) ? 'selected':'' ).'>'.$categories->name.'</option>
                    ';
                }
            }
            return response()->json($output, 200);
        }

    }

    public function categoryenvatoassign(Request $r)
    {

        if($r->input('categorys_id') != null){

            $categories = CategoryEnvato::get();

            foreach($categories as $category){

                $category->truncate();
            }
            foreach($r->input('categorys_id') as $value){

                $category = CategoryEnvato::create([
                    'category_id' => $value,
                    'envato_enable' => '1'
                ]);

            }
        }else{

            $categories = CategoryEnvato::get();

            foreach($categories as $category){

                $category->truncate();
            }
        }


        return response()->json(['success' => lang('Updated successfully', 'alerts'), 200]);


    }

    public function subcategoryindex()
    {
        $subcategory = Subcategory::get();
        $data['subcategory'] = $subcategory;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        return view('admin.category.subcategory')->with($data);
    }

    public function categorygetall(Request $request)
    {
        $categoryall = Category::get();

        // $categoryArray = Category::pluck('parent_id')->toArray();

        $output = '';
        if($categoryall != ''){
            $output .= '<option label="No Parent"></option>';
            foreach($categoryall as $catall){
                $output .= '<option value="'.$catall->id.'">'.$catall->name.' </option>';
            }
        }
        return response()->json($output);
    }

    public function subcategorystore(Request $request)
    {


        $validate = Subcategory::find($request->subcategory_id);

        if(!$validate){
            $validator = Validator::make($request->all(), [
                'subcategoryname' => 'required|string|max:255|unique:subcategorysd',

            ]);
        }
        if($validate){
            if($validate->subcategoryname == $request->subcategoryname){
                $validator = Validator::make($request->all(), [
                    'subcategoryname' => 'required|string|max:255',

                ]);
            }else{
                $validator = Validator::make($request->all(), [
                    'subcategoryname' => 'required|string|max:255|unique:subcategorysd',

                ]);
            }
        }

        if($validator->passes()){

            $testiId = $request->subcategory_id;
            $testi =  [
                'subcategoryname' => $request->subcategoryname,
                'status' => $request->status ?  '1' :  '0',
            ];
            $testimonial = Subcategory::updateOrCreate(['id' => $testiId], $testi);

            if($request->parent_id){
                foreach ($request->parent_id as $value) {
                    $parent_id[] = $value;
                }
            }

            $testimonial->subcategorysync()->sync($request->get('parent_id'));

            return response()->json(['code'=>200, 'success'=> lang('The subcategory was successfully updated.', 'alerts'),'data' => $testimonial], 200);
        }else{
            return Response::json(['errors' => $validator->errors()]);
        }
    }

    public function subcategoryshow($id)
    {
        $subcategory = Subcategory::find($id);

        if(request()->ajax()){
            $categoryall = Category::get();

            $categoryArray = Subcategorychild::where("subcategory_id",$id)->pluck('category_id')->toArray();


            $output = '';
            if($categoryall != ''){
                $output .= '<option label="No Parent"></option>';
                foreach($categoryall as $catall){
                    $output .= '<option value="'.$catall->id.'"'.($catall->id  ? in_array($catall->id,$categoryArray) ?  'selected' : '' : '').'>'.$catall->name.' </option>';
                }
            }
        }
        $data = [
            'subcategory' => $subcategory,
            'categorylist' => $output
        ];
        return response()->json($data);

    }

    public function subcategorystatusupdate(Request $request)
    {
        $subcategorystatus = Subcategory::find($request->id);
        $subcategorystatus->status = $request->status;
        $subcategorystatus->update();
        return response()->json([$subcategorystatus, 'success' => lang('Updated successfully', 'alerts')], 200);
    }

    public function subcategorydelete(Request $request)
    {
        $subcategorydelete = Subcategory::find($request->id);
        $subcategorydelete->delete();
        return response()->json([$subcategorydelete, 'success' => lang('Deleted successfully', 'alerts')], 200);
    }


    public function categorymassdestroy(Request $request)
    {
        $category_id_array = $request->input('id');

        $articles = Category::whereIn('id', $category_id_array)->get();

        foreach($articles as $article){

            $article->delete();
        }
        return response()->json(['success'=> lang('The category was successfully deleted.', 'alerts')]);

    }


    public function subcategorydeleteall(Request $request)
    {
        $subcategory_id_array = $request->input('id');

        $subcats = Subcategory::whereIn('id', $subcategory_id_array)->get();

        foreach($subcats as $subcat){

            $subcat->delete();
        }
        return response()->json(['success'=> lang('The subcategory was successfully deleted.', 'alerts')]);
    }


    public function selfassign(Request $request)
    {

        $ticketselfassign = Ticket::find($request->id);
        $ticketselfassign->selfassignuser_id = Auth::id();
        $ticketselfassign->myassignuser_id = null;
        $ticketselfassign->update();
        $ticketselfassign->ticketassignmutliple()->detach($request->assigned_userid);

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $ticketselfassign->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($ticketselfassign->ticketnote->isEmpty()){
            if($ticketselfassign->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketselfassign->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticketselfassign->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketselfassign->status.'</span>
                ';
            }

        }else{
            if($ticketselfassign->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketselfassign->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticketselfassign->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticketselfassign->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Self Assigned)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.Auth::user()->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();


        return response()->json(['success' => lang('Updated successfully', 'alerts'), 200]);

    }


}
