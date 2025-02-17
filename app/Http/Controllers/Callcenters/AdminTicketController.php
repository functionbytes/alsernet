<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Auth;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketComment;
use App\Models\Ticket\Category;
use App\Mail\AppMailer;
use App\Models\Customer;
use App\Models\User;
use App\Models\Role;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Pages;
use Illuminate\Support\Facades\DB;
use Mail;
use App\Mail\mailmailablesend;
use Hash;
use App\Models\Ticketnote;
use App\Models\Projects;
use App\Notifications\TicketCreateNotifications;
use App\Models\CustomerSetting;
use DataTables;
use App\Models\Groupsusers;
use App\Models\Groups;
use Illuminate\Support\Str;
use Modules\Uhelpupdate\Entities\Cannedmessages;
use Carbon\Carbon;
use App\Models\Customfield;
use App\Models\TicketCustomfield;
use App\Models\CCMAILS;
use Modules\Uhelpupdate\Entities\CategoryEnvato;
use App\Models\tickethistory;

class AdminTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tickets = Ticket::paginate(10);
        $categories = Category::all();

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        return view('admin.viewticket.showticket', compact('tickets', 'categories', 'title'))->with($data);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($ticket_id)
    {
        $this->authorize('Ticket Edit');
        $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
        $comments = $ticket->comments()->latest()->paginate(10);

        $custsimillarticket = Ticket::where('cust_id', $ticket->cust->id)->count();
        $data['custsimillarticket'] = $custsimillarticket;

        $category = $ticket->category;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $cannedmessage = Cannedmessages::tickedetails($ticket_id);
        $data['cannedmessages'] = $cannedmessage;

        $data['allowreply'] = false;

        $finalassigne = [];
        $assignee = $ticket->ticketassignmutliples;
        foreach($assignee as $assignees){
            array_push($finalassigne, $assignees->toassignuser_id);
        }

        if (Auth::user()->getRoleNames()[0] == 'superadmin' || in_array(Auth::user()->id, $finalassigne) || $ticket->selfassignuser_id == Auth::user()->id) {
            $data['allowreply'] = true;
        } else {
            $aa = $ticket->category->groupscategoryc()->get();
            if($aa->isNotEmpty()){
                $categoryArr = Category::with('groupscategoryc')->get();
                foreach ($categoryArr as $individualCategory) {
                    if ($individualCategory->id == $ticket->category->id) {
                        foreach ($individualCategory->groupscategoryc as $individualGroupc) {
                            $groupId = $individualGroupc->group_id;
                            $groupUser = Groups::with('groupsuser')->get();
                            foreach ($groupUser as $individualGroup) {
                                foreach ($individualGroup->groupsuser as $groups) {
                                    if ($groups->groups_id == $groupId) {
                                        if (($groups->users_id == Auth::user()->id)) {
                                            $data['allowreply'] = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }else{
                $admins = User::leftJoin('groups_users', 'groups_users.users_id', 'users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                foreach($admins as $admin) {
                    if($admin->id == Auth::user()->id){
                        $data['allowreply'] = true;
                    }
                }
            }
        }

        if (request()->ajax()) {

            $view = view('admin.viewticket.showticketdata',compact('ticket', 'category','comments'))->render();
            return response()->json(['html'=>$view]);
        }

        return view('admin.viewticket.showticket', compact('ticket','category', 'comments', 'title','footertext'))->with($data);
    }


    public function purchasedetailsverify(Request $request)
    {
        $ticket = Ticket::findOrFail($request->id);
        $ticket->usernameverify = 'verified';
        $ticket->update();

        return response()->json(['success'=>lang('The cutomer was verified successfully.', 'alerts')]);
    }

    public function wrongcustomer(Request $request)
    {
        $ticket = Ticket::findOrFail($request->id);
        $ticket->usernameverify = 'wrongcustomer';
        $ticket->update();

        return response()->json(['success'=>lang('The cutomer mentioned details are wrong.', 'alerts')]);
    }


    public function commentshow($ticket_id){

        if(request()->ajax()){
            $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
            if(request()->id > 0){
                $comments = $ticket->comments()->where('id', '<', request()->id)
                ->orderBy('id', 'DESC')
                ->limit(6)
                ->latest()
                ->get();
            }else{
                $comments = $ticket->comments()
                ->orderBy('id', 'DESC')
                ->limit(6)
                ->latest()
                ->get();
            }

            $output = '';
            $last_id = '';
            $i = 0;
            $len = count($comments);
            if(!$comments->isEmpty())
            {
            foreach($comments as $comment){
                if($comment->user_id != null){

                    if($i == 0){
                        $output .= '
                        <div class="card-body">
                            <div class="d-sm-flex">
                                <div class="d-flex me-3">
                                    <a href="#">';
                                        if($comment->user != null){
                                            if ($comment->user->image == null){
                                                $output .= '<img src="'.asset('uploads/profile/user-profile.png').'"  class="media-object brround avatar-lg" alt="default">';
                                            }else{
                                                $output .= '<img class="media-object brround avatar-lg" alt="'.$comment->user->image.'" src="'.asset('uploads/profile/'. $ticket->user->image).'">';
                                            }
                                        }else{
                                            $output .= '<img src="'.asset('uploads/profile/user-profile.png').'"  class="media-object brround avatar-lg" alt="default">';
                                        }
                                        $output .=
                                    '</a>
                                </div>
                                <div class="media-body">';
                                    if($comment->user != null){
                                        $output .= '<h5 class="mt-1 mb-1 font-weight-semibold">'.$comment->user->name.'<span class="badge badge-primary-light badge-md ms-2">'.$comment->user->getRoleNames()[0].'</span></h5>';
                                    }else{
                                        $output .= '<h5 class="mt-1 mb-1 font-weight-semibold text-muted">~</h5>';
                                    }
                                    $output .= '<small class="text-muted"><i class="feather feather-clock"></i> '.$comment->created_at->diffForHumans().'</small>
                                    <span class="fs-13 mb-0 mt-1" value="">
                                        '.$comment->comment.'
                                    </span>
                                    <div class="editsupportnote-icon animated" id="supportnote-icon-'.$comment->id.'">
                                        <form action="'.url('admin/ticket/editcomment/'.$comment->id).'" method="POST">
                                            '.csrf_field().'
                                            <textarea class="editsummernote" name="editcomment">'.$comment->comment.'</textarea>
                                            <div class="btn-list mt-1">
                                                <input type="submit" class="btn btn-secondary" onclick="this.disabled=true;this.form.submit();" value="Update">
                                            </div>
                                        </form>
                                    </div>
                                    ';
                                    if(Auth::id() == $comment->user_id){
                                        $output .= '<div class="row galleryopen">';
                                            foreach ($comment->getMedia('comments') as $commentss){
                                                $output .= '<div class="file-image-1  removespruko'.$commentss->id.'" id="imageremove'.$commentss->id.'">
                                                    <div class="product-image  ">
                                                        <a href="'.$commentss->getFullUrl().'" class="imageopen">
                                                            <img src="'.$commentss->getFullUrl().'" class="br-5" alt="'.$commentss->file_name.'">
                                                        </a>
                                                        <ul class="icons">
                                                            <li><a href="javascript:(0);" class="bg-danger " onclick="deleteticket(event.target)" data-id="'.$commentss->id.'"><i class="fe fe-trash" data-id="'.$commentss->id.'"></i>'.csrf_field().'</a></li>
                                                        </ul>
                                                    </div>
                                                    <span class="file-name-1">
                                                        '.Str::limit($commentss->file_name, 10, $end='.......').'
                                                    </span>
                                                </div>
                                                ';
                                            }
                                        $output .= '</div>';
                                    }else{
                                        $output .= '<div class="row galleryopen">';
                                            foreach ($comment->getMedia('comments') as $commentss){
                                                $output .= '<div class="file-image-1  removespruko'.$commentss->id.'" id="imageremove'.$commentss->id.'">
                                                    <div class="product-image">
                                                        <a href="'.$commentss->getFullUrl().'" class="imageopen">
                                                            <img src="'.$commentss->getFullUrl().'" class="br-5" alt="'.$commentss->file_name.'">
                                                        </a>
                                                    </div>
                                                    <span class="file-name-1">
                                                        '.Str::limit($commentss->file_name, 10, $end='.......').'
                                                    </span>
                                                </div>
                                                ';
                                            }
                                        $output .= '</div>';
                                    }
                                $output .= '</div>';

                                    if (Auth::id() == $comment->user_id){
                                        if($comment->display != null)
                                        $output .= '<div class="ms-auto">
                                        <span class="action-btns supportnote-icon" onclick="showEditForm('.$comment->id.')"><i class="feather feather-edit text-primary fs-16"></i></span>
                                    </div>';
                                    }


                            $output .= '</div>
                        </div>';
                    }else{

                        $output .= '<div class="card-body">
                            <div class="d-sm-flex">
                                <div class="d-flex me-3">
                                    <a href="#">';
                                        if($comment->user != null){
                                            if ($comment->user->image == null){
                                                $output .= '<img src="'.asset('uploads/profile/user-profile.png').'"  class="media-object brround avatar-lg" alt="default">';
                                            }else{
                                                $output .= '<img class="media-object brround avatar-lg" alt="'.$comment->user->image.'" src="'.asset('uploads/profile/'. $ticket->user->image).'">';
                                            }
                                        }else{
                                            $output .= '<img src="'.asset('uploads/profile/user-profile.png').'"  class="media-object brround avatar-lg" alt="default">';
                                        }
                                    $output .= '</a>
                                </div>
                                <div class="media-body">';
                                    if($comment->user != null){
                                        $output .= '<h5 class="mt-1 mb-1 font-weight-semibold">'.$comment->user->name.'<span class="badge badge-primary-light badge-md ms-2">'.$comment->user->getRoleNames()[0].'</span></h5>';
                                    }else{
                                        $output .= '<h5 class="mt-1 mb-1 font-weight-semibold text-muted">~</h5>';
                                    }
                                    $output .= '<small class="text-muted"><i class="feather feather-clock"></i>'.$comment->created_at->diffForHumans().'</small>
                                    <span class="fs-13 mb-0 mt-1" value="">
                                        '.$comment->comment.'
                                    </span>
                                    <div class="row galleryopen">';
                                        foreach ($comment->getMedia('comments') as $commentss){
                                            $output .= '<div class="file-image-1  removespruko'.$commentss->id.'" id="imageremove{{$commentss->id}}">
                                                <div class="product-image  ">
                                                    <a href="'.$commentss->getFullUrl().'" class="imageopen">
                                                        <img src="'.$commentss->getFullUrl().'" class="br-5" alt="'.$commentss->file_name.'">
                                                    </a>
                                                </div>
                                                <span class="file-name-1">
                                                    '.Str::limit($commentss->file_name, 10, $end='.......').'
                                                </span>
                                            </div>';
                                        }
                                    $output .= '</div>
                                </div>
                            </div>
                        </div>';

                    }
                }else{
                    $output .= '<div class="card-body">
                        <div class="d-sm-flex">
                            <div class="d-flex me-3">
                                <a href="#">';
                                    if ($comment->cust->image == null){
                                        $output .= ' <img src="'.asset('uploads/profile/user-profile.png').'"  class="media-object brround avatar-lg" alt="default">';
                                    }else{
                                        $output .= '<img class="media-object brround avatar-lg" alt="'.$comment->cust->image.'" src="'.asset('uploads/profile/'. $ticket->cust->image).'">';
                                    }
                                $output .= ' </a>
                            </div>
                            <div class="media-body">
                                <h5 class="mt-1 mb-1 font-weight-semibold">'.$comment->cust->username.'<span class="badge badge-primary-light badge-md ms-2">'.$comment->cust->userType.'</span></h5>
                                <small class="text-muted"><i class="feather feather-clock"></i>'.$comment->created_at->diffForHumans().'</small>
                                <span class="fs-13 mb-0 mt-1" value="">
                                    '.$comment->comment.'
                                </span>
                                <div class="row galleryopen">';
                                    foreach ($comment->getMedia('comments') as $commentss){
                                        $output .= '<div class="file-image-1  removespruko'.$commentss->id.'" id="imageremove'.$commentss->id.'">
                                            <div class="product-image">
                                                <a href="'.$commentss->getFullUrl().'" class="imageopen">
                                                    <img src="'.$commentss->getFullUrl().'" class="br-5" alt="'.$commentss->file_name.'">
                                                </a>
                                            </div>
                                            <span class="file-name-1">
                                                '.Str::limit($commentss->file_name, 10, $end='.......').'
                                            </span>
                                        </div>';
                                    }
                                $output .= '</div>
                            </div>
                        </div>
                    </div>';
                }
                $last_id = $comment->id;
                $i++;
            }

            $output .= '
       <div id="load_more">
        <button type="button" name="load_more_button" class="btn btn-success" data-id="'.$last_id.'" id="load_more_button">Load More</button>
       </div>
       ';
            }
            else
                {
                $output .= '
                <div id="load_more">
                    <button type="button" name="load_more_button" class="btn btn-info ">No Data Found</button>
                </div>
                ';
                }

            return response()->json(['html' => $output, 'coment' => $comments]);
        }
    }


    /**
     * Close the specified ticket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function close(Request $request,$ticket_id, AppMailer $mailer)
    {
        $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();

        $ticket->status = $request->input('status');

        $ticket->update();

        $ticketOwner = $ticket->user;

        $mailer->sendTicketStatusNotification($ticketOwner, $ticket);

        return redirect()->back()->with("warning", lang('The ticket has been closed.', 'alerts'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('Ticket Delete');
        $ticket = Ticket::findOrFail($id);
        // $ticket->myassignuser_id = null;
        // $ticket->save();

        $comment = $ticket->comments()->get();


        if (count($comment) > 0) {
            $media = $ticket->getMedia('ticket');

            foreach ($media as $media) {

                    $media->delete();

            }
            $medias = $ticket->comments()->get();

            foreach ($medias as $mediass) {
                foreach($mediass->getMedia('comments') as $mediasss){

                    $mediasss->delete();
                }

            }
            $comment->each->delete();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
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

            // $ticket->ticketassignmutliples()->delete();
            $ticket->delete();

            return response()->json(['success'=>lang('The ticket was successfully deleted.', 'alerts')]);
        }else{

            $media = $ticket->getMedia('ticket');

            foreach ($media as $media) {

                    $media->delete();

            }

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
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

            foreach($ticket->ticket_history as $deletetickethistory)
            {
                $deletetickethistory->delete();
            }

            // $ticket->ticketassignmutliples()->delete();
            $ticket->delete();

            return response()->json(['success'=>lang('The ticket was successfully deleted.', 'alerts')]);

        }
    }


    public function ticketmassdestroy(Request $request){

        $student_id_array = $request->input('id');

        $tickets = Ticket::whereIn('id', $student_id_array)->get();


        foreach($tickets as $ticket){

            $comment = $ticket->comments()->get();


            if (count($comment) > 0) {
                $media = $ticket->getMedia('ticket');

                foreach ($media as $media) {

                        $media->delete();

                }
                $medias = $ticket->comments()->get();

                foreach ($medias as $mediass) {
                    foreach($mediass->getMedia('comments') as $mediasss){

                        $mediasss->delete();
                    }

                }
                $comment->each->delete();

                $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
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
            foreach($ticket->ticket_history as $deletetickethistory)
            {
                $deletetickethistory->delete();
            }

                $tickets->each->delete();
                return response()->json(['success'=> lang('The ticket was successfully deleted.', 'alerts')]);
            }else{

                $media = $ticket->getMedia('ticket');

                foreach ($media as $media) {

                        $media->delete();

                }

                $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Ticket Deleted)</span></p>
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

            foreach($ticket->ticket_history as $deletetickethistory)
                {
                    $deletetickethistory->delete();
                }
                $tickets->each->delete();
            }
        }
        return response()->json(['success'=> lang('The ticket was successfully deleted.', 'alerts')]);

    }

    // Admin Ticket View
    public function createticket()
    {

        $this->authorize('Ticket Create');
            $title = Apptitle::first();
            $data['title'] = $title;

            $footertext = Footertext::first();
            $data['footertext'] = $footertext;

            $seopage = Seosetting::first();
            $data['seopage'] = $seopage;

            $post = Pages::all();
            $data['page'] = $post;

            $categories = Category::whereIn('display',['ticket', 'both'])->where('status', '1')->get();
            $data['categories'] = $categories;

            $customfields = Customfield::whereIn('displaytypes', ['both', 'createticket'])->where('status','1')->get();
            $data['customfields'] = $customfields;

        return view('admin.viewticket.createticket')->with($data);
    }

    // Admins Creating  Ticket

    public function gueststore(Request $request)
    {

        $this->authorize('Ticket Create');

        $categories = CategoryEnvato::where('category_id',$request->category)->first();

        if(setting('ENVATO_ON') == 'on' && $categories != null && $request->envato_id == 'undefined'){
            return response()->json(['message' => 'envatoerror', 'message' => lang('Please enter valid details to create a ticket.', 'alerts')], 200);
        }

        $email  = $request->email;
        $completeDomain = substr(strrchr($email, "@"), 1);
        $domain = explode(".",$completeDomain)[0];
        $emaildomainlist = setting('EMAILDOMAIN_LIST');
        $emaildomainlistArray = explode(",", $emaildomainlist);
        if(setting('EMAILDOMAIN_BLOCKTYPE') == 'blockemail'){
            if(setting('EMAILDOMAIN_LIST') == null){
                $ticket = $this->emailpassgueststore($request);
                return response()->json(['message' => 'createticket', 'success' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id], 200);
            }else{
                if(in_array($domain, $emaildomainlistArray)){

                    return response()->json(['message' => 'domainblock', 'message' => lang('Domain is Blocked List', 'alerts')], 200);
                }
                $ticket = $this->emailpassgueststore($request);
                return response()->json(['message' => 'createticket', 'success' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id], 200);
            }
        }
        if(setting('EMAILDOMAIN_BLOCKTYPE') == 'allowemail'){
            if(setting('EMAILDOMAIN_LIST') == null){
                $ticket =  $this->emailpassgueststore($request);
                return response()->json(['message' => 'createticket', 'success' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id], 200);
            }else{
                if(in_array($domain, $emaildomainlistArray))
                {
                    $ticket = $this->emailpassgueststore($request);
                    return response()->json(['message' => 'createticket', 'success' => lang('A ticket has been opened with the ticket ID', 'alerts') . $ticket->ticket_id], 200);
                }
                return response()->json(['message' => 'domainblock', 'message' => lang('Domain is Blocked List', 'alerts')], 200);

            }
        }

    }

    private function emailpassgueststore($request)
    {
        $this->authorize('Ticket Create');

        $this->validate($request, [
            'subject' => 'required|string|max:255',
            'category' => 'required',
            'message' => 'required',
            'email' => 'required|max:255',
        ]);

        if($request->ccemail)
        {
            $this->validate($request, [
                'ccmail' => 'email|indisposable'
            ]);
        }


        $userexits = Customer::where('email', $request->email)->count();
        if($userexits == 1){
            $guest = Customer::where('email', $request->email)->first();

        }else{
            $guest = Customer::create([

                'firstname' => '',
                'lastname' => '',
                'username' => 'GUEST',
                'email' => $request->email,
                'userType' => 'Guest',
                'password' => null,
                'country' => '',
                'timezone' => 'UTC',
                'status' => '1',
                'image' => null,

            ]);
            $customersetting = new CustomerSetting();
            $customersetting->custs_id = $guest->id;
            $customersetting->save();
        }
        $ticket = Ticket::create([
            'subject' => $request->input('subject'),
            'cust_id' => $guest->id,
            'category_id' => $request->input('category'),
            'priority' => $request->input('priority'),
            'message' => $request->input('message'),
            'project' => $request->input('project'),
            'status' => 'New',
        ]);
        $ticket = Ticket::find($ticket->id);
        $ticket->ticket_id = setting('CUSTOMER_TICKETID').'G-'.$ticket->id;
        $ticket->user_id = Auth::user()->id;
        if($request->input('envato_id')){

            $ticket->purchasecode = encrypt($request->input('envato_id'));
        }
        if($request->input('envato_support')){

            $ticket->purchasecodesupport = $request->input('envato_support');
        }

        $categoryfind = Category::find($request->category);
        $ticket->priority = $categoryfind->priority;
        $ticket->subcategory = $request->subscategory;

        $ticket->update();

        $customfields = Customfield::whereIn('displaytypes', ['both', 'createticket'])->get();

        foreach($customfields as $customfield){
            $ticketcustomfield = new TicketCustomfield();
            $ticketcustomfield->ticket_id = $ticket->id;
            $ticketcustomfield->fieldnames = $customfield->fieldnames;
            $ticketcustomfield->fieldtypes = $customfield->fieldtypes;
            if($customfield->fieldtypes == 'checkbox'){
                if($request->input('custom_'.$customfield->id) != null){

                    $string = implode(',', $request->input('custom_'.$customfield->id));
                    $ticketcustomfield->values = $string;
                }

            }
            if($customfield->fieldtypes != 'checkbox'){
                if($customfield->fieldprivacy == '1'){
                    $ticketcustomfield->privacymode  = $customfield->fieldprivacy;
                    $ticketcustomfield->values = encrypt($request->input('custom_'.$customfield->id));
                }else{

                    $ticketcustomfield->values = $request->input('custom_'.$customfield->id);
                }
            }
            $ticketcustomfield->save();

        }

        $ccmails = new CCMAILS();
        $ccmails->ticket_id = $ticket->id;
        $ccmails->ccemails = $request->ccmail;
        $ccmails->save();


        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $ticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($ticket->ticketnote->isEmpty()){
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                ';
            }

        }else{
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.$ticket->users->name.'<span class="fs-11 mx-1 text-muted">(Responded)</span></p>
        </div>
        <div class="ms-auto">
        <span class="float-end badge badge-primary-light">
            <span class="fs-11 font-weight-semibold">'.$ticket->users->getRoleNames()[0].'</span>
        </span>
        </div>

        </div>
        ';
        $tickethistory->ticketactions = $output;
        $tickethistory->save();


        foreach ($request->input('ticket', []) as $file) {
            $ticket->addMedia(public_path('uploads/guestticket/' . $file))->toMediaCollection('ticket');
        }

        // create ticket notification
        $notificationcat = $ticket->category->groupscategoryc()->get();
        $icc = array();
            if($notificationcat->isNotEmpty()){

                foreach($notificationcat as $igc){

                    foreach($igc->groupsc->groupsuser()->get() as $user){
                        $icc[] .= $user->users_id;
                    }
                }

                if(!$icc){
                    $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($admins as $admin){
                        $admin->notify(new TicketCreateNotifications($ticket));
                    }

                }else{

                    $user = User::whereIn('id', $icc)->get();
                    foreach($user as $users){
                        $users->notify(new TicketCreateNotifications($ticket));
                    }
                    $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($admins as $admin){
                        if($admin->getRoleNames()[0] == 'superadmin'){
                            $admin->notify(new TicketCreateNotifications($ticket));
                        }
                    }


                }
            }else{
                $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                foreach($admins as $admin){
                    $admin->notify(new TicketCreateNotifications($ticket));
                }
            }
        $cust = Customer::with('custsetting')->find($ticket->cust_id);
        $cust->notify(new TicketCreateNotifications($ticket));

        $ticketData = [
            'ticket_username' => $ticket->cust->username,
            'ticket_id' => $ticket->ticket_id,
            'ticket_title' => $ticket->subject,
            'ticket_status' => $ticket->status,
            'ticket_description' => $ticket->message,
            'ticket_customer_url' => route('guest.ticketdetailshow', $ticket->ticket_id),
            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
        ];

        try{

            $notificationcatss = $ticket->category->groupscategoryc()->get();
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
                        if($admin->usetting->emailnotifyon == 1){
                            Mail::to($admin->email)
                            ->send( new mailmailablesend( 'admin_send_email_ticket_created', $ticketData ) );
                        }
                    }

                }else{

                    $user = User::whereIn('id', $icc)->get();
                    foreach($user as $users){
                        if($users->usetting->emailnotifyon == 1){
                            Mail::to($users->email)
                            ->send( new mailmailablesend( 'admin_send_email_ticket_created', $ticketData ) );
                        }
                    }
                    $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                    foreach($admins as $admin){
                        if($admin->getRoleNames()[0] == 'superadmin' && $admin->usetting->emailnotifyon == 1){
                            Mail::to($admin->email)
                            ->send( new mailmailablesend( 'admin_send_email_ticket_created', $ticketData ) );
                        }
                    }


                }
            }else{
                $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
                foreach($admins as $admin){
                    if($admin->usetting->emailnotifyon == 1){
                        Mail::to($admin->email)
                        ->send( new mailmailablesend( 'admin_send_email_ticket_created', $ticketData ) );
                    }
                }
            }

            Mail::to($ticket->cust->email)
            ->send( new mailmailablesend('customer_send_guestticket_created', $ticketData ) );

            Mail::to($ccemailsend->ccemails )
                ->send( new mailmailablesend('customer_send_guestticket_created', $ticketData ) );

        }catch(\Exception $e){
            return $ticket;
        }
        return $ticket;

    }


     public function employeesreplyingstore(Request $request)
    {
        $this->authorize('Ticket Edit');
        $ticket = Ticket::findOrFail($request->ticketId);
        $oldemp = $ticket->employeesreplying;
        $oldempArray = explode(",", $oldemp);
        array_push($oldempArray,$request->userID);
        $oldempArray = implode(",", $oldempArray);
        $ticket->employeesreplying = $oldempArray;
        $ticket->save();
    }

    public function employeesreplyingremove(Request $request)
    {
        $this->authorize('Ticket Edit');
        $id = $request->userID;
        $ticket = Ticket::findOrFail($request->ticketId);
        $oldemp = $ticket->employeesreplying;
        $oldempArray = explode(",", $oldemp);

        $newArr = [];
        foreach($oldempArray as $new){
            if($new != $id){
                array_push($newArr, $new);
            }
        }
        $newArr = implode(",", $newArr);
        $ticket->employeesreplying = $newArr;
        $ticket->save();
    }

    public function getemployeesreplying($ticket_id)
    {
        $this->authorize('Ticket Edit');
        $ticket = Ticket::findOrFail($ticket_id);
        $empList = explode(",", $ticket->employeesreplying);

        $employee = User::get();

        $employees = [];
        $empnames = 'empnames';
        forEach($employee as $emp){
            if(in_array($emp->id , $empList) && $emp->id != Auth::id()){
                array_push($employees, $emp);
            }
        }

        return response()->json(['employees' => $employees, 'empnames' => $empnames]);
    }



    public function guestmedia(Request $request)
    {
        $path = public_path('uploads/guestticket/');

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file = $request->file('file');

        $name = uniqid() . '_' . trim($file->getClientOriginalName());

        $file->move($path, $name);

        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }

    public function note(Request $request){

        $ticketnote = Ticketnote::create([
            'ticket_id' => $request->input('ticket_id'),
            'user_id' => Auth::user()->id,
            'ticketnotes' => $request->input('ticketnote')
        ]);

        $ticket = Ticket::where('id', $request->input('ticket_id'))->firstOrFail();

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $ticket->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($ticket->ticketnote->isEmpty()){
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                ';
            }

        }else{
            if($ticket->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Note Created)</span></p>
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

        $user = User::findOrFail($ticketnote->user_id);
        $ticketData = [
            'ticket_id' => $ticket->ticket_id,
            'note_username' => $user->name,
            'ticket_note' => $ticketnote->ticketnotes,
            'ticket_admin_url' => url('/admin/ticket-view/'.$ticket->ticket_id),
        ];

        try{
            $admins = User::leftJoin('groups_users','groups_users.users_id','users.id')->whereNull('groups_users.groups_id')->whereNull('groups_users.users_id')->get();
            foreach($admins as $admin){
                if($admin->usetting->emailnotifyon == 1 && $admin->getRoleNames()[0] == 'superadmin' && setting('NOTE_CREATE_MAILS') == 'on' && $ticketnote->user_id != $admin->id){
                    // $admin->notify(new TicketCreateNotifications($ticketcategory));
                    Mail::to($admin->email)
                    ->send( new mailmailablesend('send_mail_to_admin_when_ticket_note_created', $ticketData) );
                }
            }
        }
        catch(\Exception $e){
            return response()->json(['success'=> lang('The note was successfully submitted.', 'alerts')]);
        }


        return response()->json(['success'=> lang('The note was successfully submitted.', 'alerts')]);
    }

    public function noteshow($ticket_id)
    {
        $ticket = Ticket::where('ticket_id', $ticket_id)->firstOrFail();
        $comments = $ticket->comments;
        $category = $ticket->category;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;


        return view('admin.viewticket.note', compact('ticket','category', 'comments', 'title','footertext'))->with($data);
    }

    public function notedestroy($id)
    {
        $ticketnotedelete = Ticketnote::find($id);



        $ticket = Ticket::where('id', $ticketnotedelete->ticket_id)->firstOrFail();

            $tickethistory = new tickethistory();
            $tickethistory->ticket_id = $ticket->id;

            $output = '<div class="d-flex align-items-center">
                <div class="mt-0">
                    <p class="mb-0 fs-12 mb-1">Status
                ';
            if($ticket->ticketnote->isEmpty()){
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    ';
                }

            }else{
                if($ticket->overduestatus != null){
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-danger font-weight-semibold mx-1">'.$ticket->overduestatus.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }else{
                    $output .= '
                    <span class="text-burnt-orange font-weight-semibold mx-1">'.$ticket->status.'</span>
                    <span class="text-warning font-weight-semibold mx-1">Note</span>
                    ';
                }
            }

            $output .= '
                <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Note Deleted)</span></p>
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


        $ticketnotedelete->delete();

        return response()->json(['success'=> lang('The note was successfully deleted.', 'alerts')]);


    }

    public function sublist(Request $request){

        $parent_id = $request->cat_id;

        $subcategories =Projects::select('projects.*','projects_categories.category_id')->join('projects_categories','projects_categories.projects_id', 'projects.id')
        ->where('projects_categories.category_id',$parent_id)
        ->get();

        return response()->json([
            'subcategories' => $subcategories
        ]);

    }


    public function changepriority(Request $req){

        $this->validate($req, [
            'priority_user_id' => 'required',
        ]);

        $priority = Ticket::find($req->priority_id);
        $priority->priority = $req->priority_user_id;
        $priority->update();

        $tickethistory = new tickethistory();
        $tickethistory->ticket_id = $priority->id;

        $output = '<div class="d-flex align-items-center">
            <div class="mt-0">
                <p class="mb-0 fs-12 mb-1">Status
            ';
        if($priority->ticketnote->isEmpty()){
            if($priority->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$priority->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$priority->overduestatus.'</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$priority->status.'</span>
                ';
            }

        }else{
            if($priority->overduestatus != null){
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$priority->status.'</span>
                <span class="text-danger font-weight-semibold mx-1">'.$priority->overduestatus.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }else{
                $output .= '
                <span class="text-burnt-orange font-weight-semibold mx-1">'.$priority->status.'</span>
                <span class="text-warning font-weight-semibold mx-1">Note</span>
                ';
            }
        }

        $output .= '
            <p class="mb-0 fs-17 font-weight-semibold text-dark">'.Auth::user()->name.'<span class="fs-11 mx-1 text-muted">(Priority Updated)</span></p>
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

        $priorityname = $priority->priority;
        return response()->json(['priority' => $priorityname,'success' => lang('Updated successfully', 'alerts')], 200);
    }

    public function alltickets()
    {

        if(Auth::user()->role == 'manager'){
            return $this->adminalltickets();
        }
        if(Auth::user()->role == 'Employee' || Auth::user()->dashboard == null){
            return $this->employeealltickets();
        }


    }

    public function adminalltickets()
    {
        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $alltickets = Ticket::latest('updated_at')->get();
        $data['alltickets'] = $alltickets;

        $ticketnote = DB::table('ticketnotes')->pluck('ticketnotes.ticket_id')->toArray();
        $data['ticketnote'] = $ticketnote;

        return view('admin.superadmindashboard.alltickets')->with($data);

    }

    public function employeealltickets()
    {

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $post = Pages::all();
        $data['page'] = $post;

        $agent = User::count();
        $data['agent'] = $agent;

        $customer = Customer::count();
        $data['customers'] = $customer;

        $groupexists = Groupsusers::where('users_id', Auth::id())->exists();

        // if there in group get group tickets
        if($groupexists){

            $gticket = Ticket::select('tickets.*',"groups_categories.group_id","groups_users.users_id")
            ->leftJoin('groups_categories','groups_categories.category_id','tickets.category_id')
            ->leftJoin('groups_users','groups_users.groups_id','groups_categories.group_id')
            ->whereNotNull('groups_users.users_id')
            ->where('groups_users.users_id', Auth::id())
            ->latest('tickets.updated_at')
            ->get();
            $data['gtickets'] = $gticket;

        $ticketnote = DB::table('ticketnotes')->pluck('ticketnotes.ticket_id')->toArray();
        $data['ticketnote'] = $ticketnote;
        }
        // If no there in group we get the all tickets
        else{


            $gtickets = Ticket::select('tickets.*',"groups_categories.group_id","groups_users.users_id")
            ->leftJoin('groups_categories','groups_categories.category_id','tickets.category_id')
            ->leftJoin('groups_users','groups_users.groups_id','groups_categories.group_id')
            ->whereNull('groups_users.users_id')
            ->latest('tickets.updated_at')
            ->get();;
            $data['gtickets'] = $gtickets;

            $ticketnote = DB::table('ticketnotes')->pluck('ticketnotes.ticket_id')->toArray();
            $data['ticketnote'] = $ticketnote;
        }

        return view('admin.viewticket.alltickets')->with($data);
    }
}
