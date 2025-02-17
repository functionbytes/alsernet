<?php

namespace App\Http\Controllers\Managers\Livechat;

use App\Events\AgentMessageEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Livechat\LiveChatCustomers;
use App\Models\Livechat\LiveChatConversations;
use App\Models\Livechat\LiveChatFlow;
use App\Models\Livechat\LiveChatReviews;
use App\Models\Livechat\Cannedmessages;


use App\Models\User;
use App\Events\ChatMessageEvent;
use App\Jobs\SocketWorker;
use App\Models\Agent\AgentConversation;
use App\Models\Agent\AgentGroupConversation;
use App\Models\Setting;
use App\Models\Ticket\TicketCategorie;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use App\Models\Ticket\Ticket;
use Auth;
use GeoIP;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Tickets;


use Exception;
use Illuminate\Support\Facades\Mail;
use Spatie\SslCertificate\SslCertificate;
use Symfony\Component\Process\Process;

class LivechatController extends Controller
{
    public function index(Request $request)
    {

        $user = User::where('id','!=',Auth::id())->get();

        $livecust = LiveChatCustomers::latest('updated_at')->get();

        $filteredLiveCust = $livecust->filter(function ($item) {
            return $item->engage_conversation === null || $item->engage_conversation === '' && $item->status != 'solved';
        });

        if($request->operatorID){
            $filteredLiveCust = $livecust->filter(function ($customer) use ($request) {
                $engageConversation = json_decode($customer->engage_conversation, true);
                return collect($engageConversation)->contains('id', $request->operatorID);
            });
        }

        $operatorID = $request->operatorID;

        foreach ($filteredLiveCust as $customer) {
            $livechatdata = LiveChatConversations::where('unique_id', $customer->cust_unique_id)->get();
            $unreadIndexNumber = 0;
            foreach ($livechatdata as $message) {
                if ($message->livechat_cust_id !== null && $message->status != "seen" && !$message->delete) {
                    $unreadIndexNumber = $unreadIndexNumber + 1;
                }
            }
            $customer->unreadIndexNumber = $unreadIndexNumber;
            $customer->lastMessage = $livechatdata->isNotEmpty() ? $livechatdata->last() : null;
        }

        $filteredLiveCust = $filteredLiveCust->sortByDesc(function ($customer) {
            return optional($customer->lastMessage)->created_at ? $customer->lastMessage->created_at->timestamp : null;
        });


        //$categories = TicketCategorie::whereIn('display', ['ticket', 'both'])->where('status', '1')->get();


        return view('managers.views.livechat.livechat.index')->with([
            //'categories' => $categories,
            'filteredLiveCust' => $filteredLiveCust,
            'operatorID' => $operatorID,
            'user' => $user,
        ]);

    }

    public function customerdata(Request $request)
    {
        $cust = LiveChatCustomers::where('email',$request->email)->first();
        if($cust && $cust->count()){
            return response()->json(['success' => 'your live chat process is started.','custdata' => $cust]);
        }else{
            $cust = new LiveChatCustomers();
            $cust->cust_unique_id = Str::random(9);
            $cust->username = $request->name;
            $cust->email = $request->email;
            $cust->chat_flow_messages = $request->flowChatMessages;
            $cust->mobile_number = $request->mobilenumber;
            $cust->userType = 'livechat';
            $cust->login_at = now();
            $cust->browser_info = $request->browserAndOSInfo;
            $cust->login_ip = $request->loginIp;
            $cust->city = $request->city;
            $cust->state = $request->state;
            $cust->timezone = $request->timezone;
            $cust->country = $request->country;
            $cust->full_address = $request->fullAddress;
            $cust->save();

            event(new ChatMessageEvent($request->name,'newUser',$cust->id,null,null,null,null,null));
            return response()->json(['success' => 'your live chat process is started.','custdata' => $cust]);
        }

    }

    public function singlecustdata(Request $request, $id)
    {
        $livecust = LiveChatCustomers::find($id);
        if($livecust == null){
            return response()->json(['nocustomerdatafound' => true]);
        }
        if(LiveChatConversations::where('unique_id',$livecust->cust_unique_id)->first()->delete != null){
            $livecust->chat_flow_messages = null;
        }

        $livechatdata = LiveChatConversations::where('unique_id',$livecust->cust_unique_id)->get();
        $currentUserId = Auth::id();
        $filteredLiveChatData = $livechatdata->reject(function ($message) use ($currentUserId){
            return $message->delete != null;
        });

        if($currentUserId && strpos($request->header('referer'), 'livechat-flow/test-it-out') == false && $request->author == 'agent'){
            foreach ($filteredLiveChatData as $message) {
                if ($message->livechat_cust_id !== null && $message->status != "comment") {
                    $message->status = 'seen';
                    $message->save();
                }
            }
        }

        if($filteredLiveChatData->count() != $livechatdata->count()){
            $livecust->deletedMessage = true;
        }
        $filteredLiveChatData = $filteredLiveChatData->sortBy('created_at')->values();
        $livecust->onlineUsers = Setting::where("key",'All_Online_Users')->first();
        $livecust->livechatMaxFileUpload = setting('livechatMaxFileUpload');
        $livecust->livechatFileUploadMax = setting('livechatFileUploadMax');
        $livecust->livechatFileUploadTypes = setting('livechatFileUploadTypes');
        $livecust->liveChatFlowload = setting('liveChatFlowload');
        $livecust->livechatIconSize = setting('livechatIconSize');
        $livecust->livechatPosition = setting('livechatPosition');
        $livecust->isonlineoroffline = $this->isonlineoroffline();
        $livecust->OfflineMessage = setting('OfflineMessage');
        $livecust->OfflineStatusMessage = setting('OfflineStatusMessage');
        $livecust->OnlineStatusMessage = setting('OnlineStatusMessage');
        $livecust->offlineDisplayLiveChat = setting('offlineDisplayLiveChat');
        $livecust->liveChatHidden = setting('liveChatHidden');
        $livecust->liveChatCustomerOnlineUsers = setting('liveChatCustomerOnlineUsers');
        $livecust->livechatFeedbackDropdown = setting('livechatFeedbackDropdown');
        $livecust->LivechatCustFeedbackQuestion = setting('LivechatCustFeedbackQuestion');
        $livecust->LivechatCustWelcomeMsg = setting('LivechatCustWelcomeMsg');

        $customer = User::where('email', $livecust->email)->first();
        if($customer){
            $livecust->gustId = $customer->id;
            $livecust->livechatTickets = Ticket::where('cust_id',$customer->id)->get()->count();
        }


        return response()->json(['livechatcust' => $livecust,'livechatdata' => $filteredLiveChatData]);
    }

    public function broadcastMessage(Request $request) {
        if($request->cust_id != null){
            $user = Auth::user();
            $cust = LiveChatCustomers::find($request->cust_id);
            if($cust->status == 'solved'){
                $cust->status = "";
                $cust->save();
            }

            $newconversation = new LiveChatConversations();
            $newconversation->unique_id = $cust->cust_unique_id;
            $newconversation->livechat_user_id = $user->id;
            $newconversation->livechat_username = $user->name;
            $newconversation->message = $request->message;
            $newconversation->sender_image = $user->image;
            if($request->messageType){
                $newconversation->message_type = $request->messageType;
                event(new ChatMessageEvent($user->name, $request->message,$user->id,$request->customerId,null,null,null,Auth::user(),null,null,$request->messageType));
            }else{
                event(new ChatMessageEvent($user->name, $request->message,$user->id,$request->customerId,null,null,null,Auth::user()));
            };
            $newconversation->save();

        }else{

            $cust = LiveChatCustomers::find($request->id);
            if($request->messageType == 'welcomeMessage'){
                // return $cust;

                if($cust->status == 'solved'){
                    $cust->status = "";
                    $cust->save();
                }

                $newconversation = new LiveChatConversations();
                $newconversation->unique_id = $cust->cust_unique_id;
                $newconversation->livechat_user_id = $request->id;
                $newconversation->livechat_username = 'chatBot';
                $newconversation->message = $request->message;
                $newconversation->message_type = 'welcomeMessage';
                $newconversation->save();
                event(new ChatMessageEvent('chatBot', $request->message,$request->id,$request->customerId,null,null,null,null,null,null,'welcomeMessage'));

            }else{
                if($cust->status == 'solved'){
                    $cust->status = "";
                    event(new ChatMessageEvent($request->username,'newUser',null,null,null,null,null,null));
                }
                $cust->updated_at = now();
                $cust->save();

                $newconversation = new LiveChatConversations();
                $newconversation->unique_id = $cust->cust_unique_id;
                $newconversation->livechat_cust_id = $request->id;
                $newconversation->livechat_username = $request->username;
                $newconversation->message = $request->message;
                if($request->messageType){
                    $newconversation->message_type = $request->messageType;
                    event(new ChatMessageEvent($request->username, $request->message,$request->id,$request->customerId,null,null,null,null,null,null,$request->messageType));
                }else{
                    event(new ChatMessageEvent($request->username, $request->message,$request->id,$request->customerId));
                }
                $newconversation->save();
            }


        }

        if($request->messageType == "feedBack"){
            $cust = LiveChatCustomers::find($request->id);
            $liveChatEngageConversation = json_decode($cust->engage_conversation, true);
            foreach($liveChatEngageConversation as $EngageUser) {
                $feedBackRating = json_decode($request->message, true);
                $ratingModel = new livechatReviews();
                $ratingModel->users_id = $EngageUser['id'];
                $ratingModel->cust_id = $request->customerId;
                $ratingModel->starRating = $feedBackRating['starRating'];
                $ratingModel->problemRectified = $feedBackRating['problemRectified'];
                $ratingModel->feedBackData = $feedBackRating['feedBackData'];
                $ratingModel->save();
            }

            // For Left chat
            if($cust->engage_conversation){
                foreach(json_decode($cust->engage_conversation) as $engageusers ){
                    // For the Left User comment
                    $user = $engageusers;
                    $newconversation = new LiveChatConversations();
                    $newconversation->unique_id = $cust->cust_unique_id;
                    $newconversation->livechat_user_id = $user->id;
                    $newconversation->livechat_username = $user->name;
                    $newconversation->message = $user->name. ' left the discussion at';
                    $newconversation->status = 'comment';
                    $newconversation->save();
                    event(new ChatMessageEvent(null,null,$user->id,$cust->id,null,null,true,$cust->engage_conversation,$newconversation->message));
                }
            }

            // For Slove

            $solvedUser = User::find(1);
            $newconversation = new LiveChatConversations();
            $newconversation->unique_id = $cust->cust_unique_id;
            $newconversation->livechat_user_id = $solvedUser->id;
            $newconversation->livechat_username = $solvedUser->name;
            $newconversation->message = 'The customers given feedback to the chat, and as a result, the discussion was automatically marked as solved at';
            $newconversation->status = 'comment';
            $newconversation->save();
            event(new ChatMessageEvent(null,null,$solvedUser->id,$cust->id,null,null,true,$cust->engage_conversation,$newconversation->message));

            $cust->engage_conversation = '';
            $cust->status = 'solved';
            $cust->save();


        }

    }

    public function liveChatFlow($id)
    {

        $flow = LiveChatFlow::find($id);
        if($flow && $flow->active == 1 && $flow->active_draft){
            $flow->liveChatFlow = $flow->active_draft;
        }


        $flowChatId = $id;

        return view('managers.views.livechat.livechat.flow')->with([
            'flowChatId' => $flowChatId,
            'flow' => $flow,
        ]);

    }

    public function operators(Request $request)
    {

        $livecust = LiveChatCustomers::latest('updated_at')->get();

        $user = User::where('id','!=',Auth::id())->get();

        $userId = AgentConversation::where('sender_user_id',Auth::id())->first();

        if($userId != null){
            $userconversation = AgentConversation::where('unique_id',$userId->unique_id)->get();
        }

        $agenconver = AgentConversation::where(function ($query) {
            $query->where('sender_user_id', Auth::id())
                  ->orWhere('receiver_user_id', Auth::id());
        })->latest('created_at')->get()->groupBy(function($group) {
            return $group->unique_id;
        });


        $group_conver = AgentGroupConversation::get()->groupBy(function($group) {
            return $group->unique_id;
        });

        $filteredGroups = $group_conver->map(function ($conversationGroup) {
            $filteredConversations = $conversationGroup->filter(function ($conversation) {
                // To make the seen staus
                $markAsUnread = json_decode($conversation->mark_as_unread);
                $receiverUserId = json_decode($conversation->receiver_user_id);
                if(count(array_intersect($markAsUnread, $receiverUserId)) === count($receiverUserId)){
                    $conversation->update(['message_status' => 'seen']);
                };

                // To remove the array which not include the auth id in the receiver_user_id
                $authUserId = Auth::id();
                return in_array($authUserId, json_decode($conversation->receiver_user_id));
            });
            $conversationGroup = $filteredConversations;
            return $conversationGroup;
        });

        $finalFilteredGrouparray = $filteredGroups->filter(function ($item) {
            return !empty($item->all());
        });


        $sortedCollection = new Collection(array_merge($agenconver->toArray(), $finalFilteredGrouparray->toArray()));

        $allconver = $sortedCollection->sortByDesc(function ($item) {
            return isset($item[0]['created_user_id']) ? $item[count($item)-1]['created_at'] : $item[0]['created_at'];
        });


        return view('managers.views.livechat.livechat.operator')->with([
            'allconver' => $allconver,
            'user' => $user,
            'livecust' => $livecust,
            'userconversation' => $userconversation,
        ]);

    }

    public function groupconversion(Request $request, $id)
    {
        $group_conver = AgentGroupConversation::where('unique_id',$id)->get();

        foreach ($group_conver as $group_convers){
            if(!$group_convers->mark_as_unread){
                $group_convers->mark_as_unread = [Auth::user()->id];
                $group_convers->save();
            }else{
                if(!in_array(Auth::id(), json_decode($group_convers->mark_as_unread))){
                    $existingData = [];
                    $existingData = json_decode($group_convers->mark_as_unread, true);

                    $newUserId = Auth::user()->id;
                    $existingData[] = $newUserId;
                    $group_convers->mark_as_unread = json_encode($existingData);

                    $group_convers->save();
                }

            }
        }

        $receiverUsersInfo = null;

       foreach($group_conver as $group){
        $receiverUsersInfo = $group->reciever_username;
       }

        // Filter the remove the messages
        $filteredConversations = $group_conver->filter(function ($conversation) {
            $authUserId = Auth::id();
            $receiverUserIds = json_decode($conversation->receiver_user_id);
            $deleteStatus = json_decode($conversation->delete_status);
            return in_array($authUserId, $receiverUserIds) && (empty($deleteStatus) || !in_array($authUserId, $deleteStatus));
        });

        $conversationGroup = $filteredConversations->values()->all();

        event(new AgentMessageEvent(null,null,Auth::id(),null,null,$id));


        return response()->json(['groupconversion' => $conversationGroup,'receiverUsersInfo' => $receiverUsersInfo]);

    }


    public function singleoperator(Request $request, $id)
    {
        $seenconversation = AgentConversation::where('receiver_user_id',Auth::id())->where('sender_user_id',$id)->get();
        foreach($seenconversation as $seenconversations){
            if($seenconversations->message_status != 'seen'){
                $seenconversations->message_status = 'seen';
                $seenconversations->save();
            }
        }

        $userId = AgentConversation::where('sender_user_id',Auth::id())->where('receiver_user_id',$id)->first();
        if($userId == null){
            $userId = AgentConversation::where('receiver_user_id',Auth::id())->where('sender_user_id',$id)->first();
        }

        if($userId != null){
            $userconversation = AgentConversation::where('unique_id', $userId->unique_id)
            ->where(function ($query) {
                $query->whereJsonDoesntContain('delete_status', Auth::id())
                    ->orWhereNull('delete_status');
            })
            ->get();
        }else{
            $userconversation = null;
        }

        $user = Auth::user();
        $receiverdata = User::find($id);
        event(new AgentMessageEvent(null,null,$user->id,null,null,$id));

        if ($userId != null) {
            $this->markasread($userId->unique_id);
        }

        return response()->json(['senderdata' => $user,'receiverdata' => $receiverdata,'userconversation' => $userconversation]);
    }

    public function conversationdelete(Request $request, $id)
    {
        $allconver = AgentConversation::where('unique_id',$id)->get();
        foreach($allconver as $allconvers){

            if(!$allconvers->delete_status){
                $allconvers->delete_status = [Auth::user()->id];
                $allconvers->save();
            }else{
                if(!in_array(Auth::id(), json_decode($allconvers->delete_status))){
                    $existingData = [];
                    $existingData = json_decode($allconvers->delete_status, true);

                    $newUserId = Auth::user()->id;
                    $existingData[] = $newUserId;
                    $allconvers->delete_status = json_encode($existingData);


                    $allconvers->save();
                }

            }
        }

        $this->markasread($id);


        return redirect()->route('admin.operators')->with('success', lang('Conversations deleted successfully.', 'alerts'));
    }

    public function broadcastoperator(Request $request) {
        $user = Auth::user();

        $receiverdata = User::find($request->receiverId);

        $uniqueId = AgentConversation::where('sender_user_id',$user->id)->where('receiver_user_id',$request->receiverId)->first();

        if($uniqueId == null){
            $uniqueId = AgentConversation::where('sender_user_id',$request->receiverId)->where('receiver_user_id',$user->id)->first();
        }

        // event(new AgentMessageEvent($request->message,$request->receiverId,$user->id,$user->name));

        $newconversation = new AgentConversation();
        $newconversation->unique_id = $uniqueId != null ? $uniqueId->unique_id : Str::random(9);
        $newconversation->sender_username = $user->name;
        $newconversation->reciever_username = $receiverdata->name;
        $newconversation->sender_user_id = $user->id;
        $newconversation->receiver_user_id = $request->receiverId;
        $newconversation->message = $request->message;
        $newconversation->message_status = $request->messageStatus;
        if($request->messageType){
            $newconversation->message_type = $request->messageType;
            event(new AgentMessageEvent($request->message,$request->receiverId,$user->id,$user->name,null,null,null,null,'image'));
        }else{
            event(new AgentMessageEvent($request->message,$request->receiverId,$user->id,$user->name));
        }
        $newconversation->save();

        if($uniqueId){
            $this->markasread($uniqueId->unique_id);
        }

        return response()->json(['MessageSent' => $newconversation,]);

    }

    public function markasunread(Request $request, $id){
        $agentconver =  AgentConversation::where('unique_id',$id)->latest('updated_at')->latest('id')->first();
        if(!$agentconver->mark_as_unread){
            $agentconver->mark_as_unread = [Auth::id()];
            $agentconver->save();
        }else{
            if(!in_array(Auth::id(), json_decode($agentconver->mark_as_unread))){
                    $existingData = [];
                    $existingData = json_decode($agentconver->mark_as_unread, true);

                    $newUserId = Auth::id();
                    $existingData[] = $newUserId;
                    $agentconver->mark_as_unread = json_encode($existingData);

                    $agentconver->save();
            }
        }

       return redirect()->route('admin.operators')->with('success', lang('Mark As Unread.', 'alerts'));
    }

    public function markasread($id){
        $agentconver =  AgentConversation::where('unique_id',$id)->get();
        $agentconver->each(function ($message) {
            if (is_array(json_decode($message->mark_as_unread))) {
                $message->update([
                    'mark_as_unread' => array_diff(json_decode($message->mark_as_unread), [auth()->id()])
                ]);
            }
        });

           return redirect()->route('admin.operators');
    }

    public function groupbroadcastoperator (Request $request){

        $group = new AgentGroupConversation();
        $group->unique_id = Str::random(9);
        $group->message = 'Group created by'.' '.Auth::user()->name;
        $group->sender_username = Auth::user()->name;
        $group->reciever_username = $request->recieverUsersNames;
        $group->receiver_user_id = $request->usersId;
        $group->sender_user_id = Auth::id();
        $group->created_user_id = Auth::id();
        $group->mark_as_unread = "[".Auth::user()->id."]";
        $group->save();

        event(new AgentMessageEvent($group->message,$group->unique_id,Auth::id(),Auth::user()->name,null,null,Auth::user()->image,$request->usersId));
        return response()->json(['group' => $group,]);

    }

    public function groupconversionstore(Request $request, $id)
    {
        $createdId = AgentGroupConversation::where('unique_id', $id)->first();
        if($createdId->receiver_user_id != $createdId->mark_as_unread){
            $createdId->mark_as_unread = $createdId->receiver_user_id;
            $createdId->save();
        }

        $group = new AgentGroupConversation();
        $group->unique_id = $id;
        $group->sender_username = Auth::user()->name;
        $group->sender_image = Auth::user()->image;
        $group->reciever_username = $request->recieverUsersNames;
        $group->message = $request->message;
        $group->sender_user_id = Auth::id();
        $group->receiver_user_id = $request->usersId;
        $group->created_user_id = $createdId->created_user_id;
        $group->mark_as_unread = $request->seenUserIds;
        $group->message_status = $request->messageStatus;
        if($request->messageType){
            $group->message_type = $request->messageType;
            event(new AgentMessageEvent($request->message,$id,Auth::id(),Auth::user()->name,null,null,Auth::user()->image,$request->usersId,'image'));
        }else{
            event(new AgentMessageEvent($request->message,$id,Auth::id(),Auth::user()->name,null,null,Auth::user()->image,$request->usersId));
        }
        $group->save();


    }

    public function groupconversiondelete($id){
        $groupConversations = AgentGroupConversation::where('unique_id', $id)->get();

        $authId = Auth::id();

        foreach ($groupConversations as $key => $conversation) {
            if(!$conversation->delete_status){
                $conversation->delete_status = [Auth::user()->id];
                $conversation->save();
            }else{
                if(!in_array(Auth::id(), json_decode($conversation->delete_status))){
                    $existingData = [];
                    $existingData = json_decode($conversation->delete_status, true);
                    $newUserId = Auth::user()->id;
                    $existingData[] = $newUserId;
                    $conversation->delete_status = json_encode($existingData);
                    $conversation->save();
                }
            }
        }

        return redirect()->route('admin.operators')->with('success', lang('Delete Successfully.', 'alerts'));
    }

    public function ChatFlowSave(Request $req)
    {
        LiveChatFlow::where('active', 1)->update(['active' => 0]);

        $ActiveChat = LiveChatFlow::where('id',$req->chatId)->first();
        if($req->chat){
            $ActiveChat->liveChatFlow = $req->chat;
        }
        $ActiveChat->active = 1;
        $ActiveChat->active_draft = null;
        $ActiveChat->save();

        return response()->json(['success' => lang('Livechat flow saved successfully'), 'flow' => $ActiveChat]);
    }

    public function activeChatFlow(Request $req){
        LiveChatFlow::where('active', 1)->update(['active' => 0]);
        if($req->checked){
            $ActiveChat = LiveChatFlow::where('id',$req->chatId)->first();
            $ActiveChat->active = 1;
            $ActiveChat->save();
        }
        return response()->json(['success' => lang('Livechat flow saved')]);
    }

    function deleteChatFlow(Request $req,$id){
        $flow = LiveChatFlow::find($id);
        if($req->query('active-draft-delete')){
            $flow->active_draft = null ;
            $flow->save();
        }else{
            $flow->delete();
        }
        return redirect()->route('admin.chatResponses')->with('success', lang('Chat Flow Deleted', 'alerts'));
    }

    public function ChatFlowData(Request $request,$id)
    {
        $requaredchart = null;
        if($id != 'null'){
            $requaredchart = LiveChatFlow::find($id);
            if($requaredchart->active_draft){
                $requaredchart->liveChatFlow = $requaredchart->active_draft;
            }
            $requaredchart->onlineUsers = Setting::where("key",'All_Online_Users')->first();
            $requaredchart->isonlineoroffline = $this->isonlineoroffline();
            $requaredchart->OnlineStatusMessage = setting('OnlineStatusMessage');
            $requaredchart->OfflineStatusMessage = setting('OfflineStatusMessage');
            $requaredchart->OfflineMessage = setting('OfflineMessage');
            $requaredchart->offlineDisplayLiveChat = setting('offlineDisplayLiveChat');
            $requaredchart->livechatIconSize = setting('livechatIconSize');
            $requaredchart->livechatPosition = setting('livechatPosition');
            $requaredchart->liveChatHidden = setting('liveChatHidden');
            $requaredchart->LivechatCustWelcomeMsg = setting('LivechatCustWelcomeMsg');
        }

        $activeChatData = LiveChatFlow::where('active', 1)->first();

        $activeChatDataArray = [
            'onlineUsers' => Setting::where("key",'All_Online_Users')->first(),
            'isonlineoroffline' => $this->isonlineoroffline(),
            'OnlineStatusMessage' => setting('OnlineStatusMessage'),
            'OfflineStatusMessage' => setting('OfflineStatusMessage'),
            'OfflineMessage' => setting('OfflineMessage'),
            'offlineDisplayLiveChat' => setting('offlineDisplayLiveChat'),
            'livechatIconSize' => setting('livechatIconSize'),
            'livechatPosition' => setting('livechatPosition'),
            'liveChatHidden' => setting('liveChatHidden'),
            'LivechatCustWelcomeMsg' => setting('LivechatCustWelcomeMsg'),
        ];

        if ($activeChatData) {
            $activeChatDataArray = array_merge($activeChatDataArray, $activeChatData->toArray());
        }

        $flow = $id != 'null' ? $requaredchart : $activeChatDataArray;
        return response()->json(['success' => $flow]);
    }

    public function chatResponses()
    {

        $flow = LiveChatFlow::where('active', 1)->first();
        $allDraftflow = LiveChatFlow::where('active', 0)->get();

        return view('managers.views.livechat.livechat.responses')->with([
            'flow' => $flow,
            'allDraftflow' => $allDraftflow,
        ]);

    }


    public function testItOut($id){

        $flowChatId = $id;

        return view('managers.views.livechat.livechat.testitout')->with([
            'flowChatId' => $flowChatId,
        ]);

    }

    public function engageConversation(Request $request){

        $livecust = LiveChatCustomers::find($request->custId);

        $existingEngageConversation = json_decode($livecust->engage_conversation, true);
        if ($existingEngageConversation === null) {
            $existingEngageConversation = [];
        }
        $newUsers = is_array($request->users) ? $request->users : json_decode($request->users, true);
        $existingIds = array_column($existingEngageConversation, 'id');
        foreach ($newUsers as $user) {
            if (!in_array($user['id'], $existingIds)) {
                $existingEngageConversation[] = $user;
            }
        }
        $livecust->engage_conversation = json_encode($existingEngageConversation);
        $livecust->save();

        $livechatdata = LiveChatConversations::where('unique_id',$livecust->cust_unique_id)->get();
        $currentUserId = Auth::id();
        $filteredLiveChatData = $livechatdata->reject(function ($message) use ($currentUserId){
            return $message->delete != null;
        });
        if($currentUserId && strpos($request->header('referer'), 'livechat-flow/test-it-out') == false){
            foreach ($filteredLiveChatData as $message) {
                if ($message->livechat_cust_id !== null && $message->status != "comment") {
                    $message->status = 'seen';
                    $message->save();
                }
            }
        }

        // For the Joined comment
        $user = Auth::user();
        $newconversation = new LiveChatConversations();
        $newconversation->unique_id = $livecust->cust_unique_id;
        $newconversation->livechat_user_id = $user->id;
        $newconversation->livechat_username = $user->name;
        $newconversation->message = $user->name. ' joined the discussion at';
        $newconversation->status = 'comment';
        $newconversation->save();


        event(new ChatMessageEvent(null,null,$user->id,$request->custId,null,null,true,$livecust->engage_conversation,$newconversation->message));
    }

    public function solvedChats()
    {

        $user = User::where('id','!=',Auth::id())->get();

        $livecust = LiveChatCustomers::latest('updated_at')->get();

        $filteredLiveCust = $livecust->filter(function ($item) {
            return !$item->engage_conversation && $item->status == 'solved';
        });

        foreach ($filteredLiveCust as $customer) {
            $livechatdata = LiveChatConversations::where('unique_id', $customer->cust_unique_id)->get();
            $unreadIndexNumber = 0;
            foreach ($livechatdata as $message) {
                if ($message->livechat_cust_id !== null && $message->status != "seen" && !$message->delete) {
                    $unreadIndexNumber = $unreadIndexNumber + 1;
                }
            }
            $customer->unreadIndexNumber = $unreadIndexNumber;
            $customer->lastMessage = $livechatdata->isNotEmpty() ? $livechatdata->last() : null;
        }

        $filteredLiveCust = $filteredLiveCust->sortByDesc(function ($customer) {
            return optional($customer->lastMessage)->created_at->timestamp;
        });


        $categories = TicketCategorie::whereIn('display', ['ticket', 'both'])->where('status', '1')->get();

        return view('managers.views.livechat.livechat.solvedchat')->with([
            'categories' => $categories,
            'filteredLiveCust' => $filteredLiveCust,
            'user' => $user,
        ]);


    }

    public function myOpenedChats()
    {

        $livecust = LiveChatCustomers::all();
        $filteredLiveCust = $livecust->filter(function ($customer) {
            $engageConversation = json_decode($customer->engage_conversation, true);
            return collect($engageConversation)->contains('id', auth()->id());
        });

        foreach ($filteredLiveCust as $customer) {
            $livechatdata = LiveChatConversations::where('unique_id', $customer->cust_unique_id)->get();
            $unreadIndexNumber = 0;
            foreach ($livechatdata as $message) {
                if ($message->livechat_cust_id !== null && $message->status != "seen" && !$message->delete) {
                    $unreadIndexNumber = $unreadIndexNumber + 1;
                }
            }
            $customer->unreadIndexNumber = $unreadIndexNumber;
            $customer->lastMessage = $livechatdata->isNotEmpty() ? $livechatdata->last() : null;
        }

        $filteredLiveCust = $filteredLiveCust->sortByDesc(function ($customer) {
            return optional($customer->lastMessage)->created_at->timestamp;
        });

        $user = User::where('id','!=',Auth::id())->get();

        $userCurrent = User::find(Auth::user()->id);
        $userCurrent->getRoleNames()[0] == "superadmin";

        $categories = TicketCategorie::whereIn('display', ['ticket', 'both'])->where('status', '1')->get();


        return view('managers.views.livechat.livechat.myopenedchats')->with([
            'userCurrent' => $userCurrent,
            'categories' => $categories,
            'filteredLiveCust' => $filteredLiveCust,
            'user' => $user,
        ]);

    }


    public function getCannedmessages(){
        $cannedmessages = Cannedmessages::latest()->where('responsetype','livechat')->get();
        $data['cannedmessages'] = $cannedmessages;
        return response()->json(['success' => true, 'message' => $data]);
    }


    public function conversationLeave(Request $request){
        $livecust = LiveChatCustomers::find($request->id);
        $engageConversation = json_decode($livecust->engage_conversation, true);
        $engageConversation = array_filter($engageConversation, function ($conversation) use ($request) {
            return $conversation['id'] != auth()->id();
        });
        $livecust->engage_conversation = json_encode(array_values($engageConversation));
        $livecust->save();

        // For the Joined comment
        $user = Auth::user();
        $newconversation = new LiveChatConversations();
        $newconversation->unique_id = $livecust->cust_unique_id;
        $newconversation->livechat_user_id = $user->id;
        $newconversation->livechat_username = $user->name;
        $newconversation->message = $user->name. ' left the discussion at';
        $newconversation->status = 'comment';
        $newconversation->save();
        event(new ChatMessageEvent(null,null,$user->id,$request->id,null,null,true,$livecust->engage_conversation,$newconversation->message));
        return redirect()->route('admin.myOpenedChats');

    }

    public function conversationReassign(Request $request){
        $livecust = LiveChatCustomers::find($request->custId);
        $user = User::find($request->assignUser);
        $authUser  = Auth::user();

        if ($livecust) {
            $engageConversation = $livecust->engage_conversation
                ? json_decode($livecust->engage_conversation, true)
                : [];
            $engageConversation = array_filter($engageConversation, function ($participant) use ($authUser) {
                return $participant['id'] !== $authUser->id;
            });
            $userInArray = collect($engageConversation)->first(function ($participant) use ($user) {
                return $participant['id'] === $user->id;
            });
            if (!$userInArray) {
                $engageConversation[] = $user->toArray();
            }
            $livecust->engage_conversation = json_encode(array_values($engageConversation));
            $livecust->save();

            // For the Joined comment
            $newconversation = new LiveChatConversations();
            $newconversation->unique_id = $livecust->cust_unique_id;
            $newconversation->livechat_user_id = $authUser->id;
            $newconversation->livechat_username = $authUser->name;
            $newconversation->message = $authUser->name. ' Reassign discussion to '.$user->name.' at ';
            $newconversation->status = 'comment';
            $newconversation->save();
        }

        event(new ChatMessageEvent(null,null,$user->id,$request->custId,null,null,true,$livecust->engage_conversation,$newconversation->message));
    }

    function livechatConversationDelete(Request $request) {
        $unqid = $request->unqid;
        $userId = Auth::id();
        $liveChatMessages = LiveChatConversations::where('unique_id', $unqid)->get();
        $livecust = LiveChatCustomers::where('cust_unique_id', $liveChatMessages[0]->unique_id)->first();
        $livecust->update(['engage_conversation' => null]);

        foreach ($liveChatMessages as $message) {
            $deleteArray = json_decode($message->delete, true) ?? [];
            if (!in_array($userId, $deleteArray)) {
                $deleteArray[] = $userId;
                $message->update(['delete' => json_encode($deleteArray)]);
            }
        }

        return Redirect::back()->with('success', lang('Updated successfully', 'alerts'));
    }
    function markAsSolved(Request $request) {
        $livecust = LiveChatCustomers::find($request->id);
        if(json_decode($livecust->engage_conversation) != null){
            foreach(json_decode($livecust->engage_conversation) as $engageusers ){
                // For the Left User comment
                $user = $engageusers;
                $newconversation = new LiveChatConversations();
                $newconversation->unique_id = $livecust->cust_unique_id;
                $newconversation->livechat_user_id = $user->id;
                $newconversation->livechat_username = $user->name;
                $newconversation->message = $user->name. ' left the discussion at';
                $newconversation->status = 'comment';
                $newconversation->save();
                event(new ChatMessageEvent(null,null,$user->id,$request->id,null,null,true,$livecust->engage_conversation,$newconversation->message));
            }
        }

        $solvedUser = Auth::user();
        $newconversation = new LiveChatConversations();
        $newconversation->unique_id = $livecust->cust_unique_id;
        $newconversation->livechat_user_id = $solvedUser->id;
        $newconversation->livechat_username = $solvedUser->name;
        $newconversation->message = $solvedUser->name. ' Solved the discussion at';
        $newconversation->status = 'comment';
        $newconversation->save();
        event(new ChatMessageEvent(null,null,$solvedUser->id,$request->id,null,null,true,$livecust->engage_conversation,$newconversation->message));

        $livecust->engage_conversation = '';
        $livecust->status = 'solved';
        $livecust->save();

        if($request->solvedsourcefrom == 'directsolved'){
            return response()->json(['success' => true, 'message' => 'Added as solved chat.']);
        }else{
            return redirect()->route('admin.solvedChats');
        }
    }

    function liveChatNotificationsSetting(Request $request){
        $data['notificationsSounds']  =  $request->notificationsSounds ? true : false;
        $data['newMessageWebNot'] = $request->newMessageWebNot ? true : false;
        $data['newMessageSound'] = $request->newMessageSound;
        $data['newChatRequestWebNot'] = $request->newChatRequestWebNot ? true : false;
        $data['newChatRequestSound'] = $request->newChatRequestSound;
        $data['notificationType'] = $request->notificationType;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function liveChatNotificationsSound(Request $request){
        $request->validate([
            'uploadSound' => 'required|mimes:mp3,audio/*',
        ]);
        $path = public_path('uploads/livechatsounds');
        $file = $request->file('uploadSound');
        $filename = $file->getClientOriginalName();
        $file->move($path, $filename);
        return back()->with('success', lang('Sound uploaded successfully', 'alerts'));
    }

    function liveChatFlowSettings (Request $request){
        $data['liveChatFlowload']  =  $request->liveChatFlowload ;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }


    function liveChatImageUpload(Request $request){
        $validator = Validator::make($request->all(), [
            'chatFileUpload' => 'required|file',
        ]);

        if ($validator->passes()) {
            $path = public_path('uploads/livechat');
            $file = $request->file('chatFileUpload');
            $filename = time() . '' . $file->getClientOriginalName();
            $file->move($path, $filename);
            return response()->json(['success' => true, 'message' => 'Image Upload', 'uploadedfilename' => $filename]);
        }
        return response()->json(['uploadError' => lang('The given file is invalid.')]);
    }

    function removeChatImage(Request $request){
        $path = public_path('uploads/livechat');
        $filepath = $path . '/' . $request->filename;

        if (File::exists($filepath)){
            File::delete($filepath);
            return response()->json(['success' => true, 'message' => 'File removed']);
        }else{
            return response()->json(['success' => false, 'message' => 'File not found']);
        }
    }

    function liveChatFileSettings(Request $request){
        $userfileuploadtypes = explode(',',$request->livechatFileUploadTypes);
        $fileuploadtypes = explode(',',$request->AgentlivechatFileUploadTypes);
        $allowedFormats = ['.xlsx', '.csv', '.docx', '.pdf', '.jpg', '.jpeg', '.png', '.mp3', '.wav', '.mp4', '.zip', '.webp'];
        if($request->livechatFileUploadTypes != null){
            foreach($userfileuploadtypes as $userfileuploadtype){
                if(!in_array($userfileuploadtype, $allowedFormats)){
                    return back()->with('error', lang('You are enter wrong file formats please enter correct format.', 'alerts'));
                }
            }
        }
        if($request->AgentlivechatFileUploadTypes != null){
            foreach($fileuploadtypes as $fileuploadtype){
                if(!in_array($fileuploadtype, $allowedFormats)){
                    return back()->with('error', lang('You are enter wrong file formats please enter correct format.', 'alerts'));
                }
            }
        }

        if($request->has('liveChatFileUpload')){
            $request->validate([
                'livechatMaxFileUpload' => 'required|numeric|gt:0',
                'livechatFileUploadMax' => 'required|numeric|gt:0',
            ]);
        }

        if($request->has('liveChatAgentFileUpload')){
            $request->validate([
                'AgentlivechatMaxFileUpload' => 'required|numeric|gt:0',
                'AgentlivechatFileUploadMax' => 'required|numeric|gt:0',
            ]);
        }

        $livecust = LiveChatCustomers::all();
        if($request->livechatMaxFileUpload){
            $data['liveChatFileUpload']  =  $request->liveChatFileUpload ? true : false;
            if($request->liveChatFileUpload){
                foreach ($livecust as $cust) {
                    $cust->update(['file_upload_permission'=> $request->liveChatFileUpload ? true : false]);
                }
            }else{
                foreach ($livecust as $cust) {
                    $cust->update(['file_upload_permission'=> false]);
                }
            }
            $data['livechatMaxFileUpload'] = $request->livechatMaxFileUpload;
            $data['livechatFileUploadMax'] = $request->livechatFileUploadMax;
            $data['livechatFileUploadTypes'] = $request->livechatFileUploadTypes;
        }else{
            $data['liveChatAgentFileUpload']  =  $request->liveChatAgentFileUpload ? true : false;
            $data['AgentlivechatMaxFileUpload'] = $request->AgentlivechatMaxFileUpload;
            $data['AgentlivechatFileUploadMax'] = $request->AgentlivechatFileUploadMax;
            $data['AgentlivechatFileUploadTypes'] = $request->AgentlivechatFileUploadTypes;
        }
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function liveChatCustFileUpload(Request $request) {
        $livecust = LiveChatCustomers::find($request->custUser);
        $livecust->file_upload_permission = $request->permission == 'true' ? 1 : 0;
        $livecust->save();
        return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }

    function livechatNotificationsSonds(){

        $flow = LiveChatFlow::where('active', 1)->first();

        $soundPath = public_path('uploads/livechatsounds');
        if(file_exists($soundPath)){
            $soundNames = File::files($soundPath);
        }else{
            $soundNames = [];
        }

        $sounds = [];
        foreach ($soundNames as $soundName) {
            $sounds[] = (object)['name' => $soundName->getFilename()];
        }

        return view('managers.views.livechat.livechat.sonds')->with([
            'sounds' => $sounds,
            'flow' => $flow,
        ]);



    }

    function livechatNotificationsSondsDelete(Request $request){
        $path = public_path('uploads/livechatsounds');
        $filepath = $path . '/' . $request->id;

        if (File::exists($filepath)){
            File::delete($filepath);
        return response()->json(['success' => lang('Updated successfully', 'alerts'), 200]);
        }else{
            return response()->json(['success' => false, 'message' => 'File not found']);
        }
    }

    function livechatNotificationsMassSondsDelete(Request $request) {
        $path = public_path('uploads/livechatsounds');
        foreach($request->id as $soung){
            $filepath = $path . '/' . $soung;
            if (File::exists($filepath)){
                File::delete($filepath);
            }
        }
        return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }

    function liveChatIconSize(Request $request){
        if($request->livechatIconSize){
            $data['livechatIconSize'] = $request->livechatIconSize;
            $this->updateSettings($data);
            return back()->with('success', lang('Updated successfully', 'alerts'));
        }
    }

    function liveChatPosition(Request $request){
        if($request->livechatPosition){
            $data['livechatPosition'] = $request->livechatPosition;
            $this->updateSettings($data);
            return back()->with('success', lang('Updated successfully', 'alerts'));
        }
    }

    function liveChatOfflineSetting(Request $request){
        $data['offlineDisplayLiveChat']  =  $request->offlineDisplayLiveChat ? true : false;
        $data['OfflineStatusMessage']  =  $request->OfflineStatusMessage;
        $data['OnlineStatusMessage']  =  $request->OnlineStatusMessage;
        $data['OfflineMessage']  =  $request->OfflineMessage;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    private function isonlineoroffline()
    {
        $status = null;

        $now = now();
        $holidays = $holidays = \App\Models\Holiday::whereDate('startdate', '<=', $now->toDateString())->whereDate('enddate', '>=', $now->toDateString())->where('status', '1')->get();
        if($holidays->isNotEmpty() && setting('24hoursbusinessswitch') != 'on'){
            $status = 'offline';
        }

        foreach(bussinesshour() as $bussiness){
            if(now()->timezone(setting('default_timezone'))->format('D') == $bussiness->weeks){
                if(strtotime($bussiness->starttime) <= strtotime(now()->timezone(setting('default_timezone'))->format('h:i A')) && strtotime($bussiness->endtime) >= strtotime(now()->timezone(setting('default_timezone'))->format('h:i A'))|| $bussiness->starttime == "24H"){
                    $status = 'online';
                }else{
                    $status = 'offline';
                }
            }

        }

        return $status;
    }

    function operatorsNotificationsSetting(Request $request){

        $data['operatorsNotificationsSounds']  =  $request->operatorsNotificationsSounds ? true : false;
        $data['operatorsAgentToAgentWebNot'] = $request->operatorsAgentToAgentWebNot ? true : false;
        $data['operatorsGroupChatWebNot'] = $request->operatorsGroupChatWebNot ? true : false;
        $data['operatorsAgentToAgentSound'] = $request->operatorsAgentToAgentSound;
        $data['operatorsGroupChatSound'] = $request->operatorsGroupChatSound;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function livechatssldstore(Request $request){

        $domainname = parse_url(url('/'));
        $appurl = url('/');
        if(setting('serverssldomainname') == $appurl){
            return redirect()->back()->with('error', lang('Your ssl certificate details all ready working, do not edit it.', 'alerts'));
        }else{
            file_put_contents(base_path('config/localhost/server.key'), $request->sslkey);
            file_put_contents(base_path('config/localhost/server.crt'), $request->sslcertificate);

            $localCertPath = base_path('config/localhost/server.crt');
            $localKeyPath = base_path('config/localhost/server.key');

            try {

                $remoteCert = SslCertificate::createForHostName($domainname['host']);
                $remoteCertArray = $remoteCert->toArray();

                if($remoteCert->isValid()){
                      $certContent = file_get_contents($localCertPath);
                      $keyContent = file_get_contents($localKeyPath);

                      $certDetails = openssl_x509_parse($certContent);

                      if ($certDetails === false) {
                          return redirect()->back()->with('error', 'Failed to parse certificate: ' . openssl_error_string());
                      }else{
                          $keyValid = openssl_x509_check_private_key($certContent, $keyContent);

                          if ($keyValid) {
                                $data['serversslcertificate'] = $request->sslcertificate;
                                $data['serversslkey'] = $request->sslkey;
                                $data['serverssldomainname'] = $appurl;
                                $this->updateSettings($data);

                                  $response = [
                                      'success' => true,
                                      'message' => 'SSL certificate data has been saved successfully.',
                                  ];

                                return response()->json($response);

                          } else {

                              $response = [
                                  'success' => false,
                                  'message' => 'The private key does not correspond to the given certificate.',
                              ];

                              return response()->json($response);
                          }

                      }
                }else{
                    return redirect()->back()->with('error', 'Your ssl certificate is not valid.');
                }
            } catch (Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
    }

    function livechatCredentials(Request $request){
        if(setting('serverssldomainname') == null || setting('serverssldomainname') != url('/')){
            return response()->json(['code'=>500, 'error'=> lang("First you need to setup ssl cert and key details correctly.", "alerts")], 500);
        }else{
            if($request->has('liveChat_hidden')){
                $request->validate([
                    'liveChatPort' => 'required',
                ]);

                $data['liveChatHidden'] = $request->liveChat_hidden ? 'false' : 'true';
                $data['liveChatPort'] = $request->liveChatPort;
                $this->updateSettings($data);

                if($request->liveChatPort != setting('liveChatPort')){
                    try{

                        $startport = $request->liveChatPort;
                        $serveProcess = new Process(["php", "artisan", "websockets:serve", "--port=$startport"]);
                        $serveProcess->start();

                        sleep(3);

                        return response()->json(["code"=>200, "success"=> lang("WebSocket server started on port $startport.", "alerts")], 200);
                    }catch(\Exception $e){
                        return response()->json(['code'=>500, 'error'=> $e->getMessage()], 500);
                    }
                }

                return response()->json(["code"=>200, "success"=> lang("This websocket is already started, please re-start your server websokcet if it is not work. ", "alerts")], 200);
            }else{
                $data['liveChatHidden'] = $request->liveChat_hidden ? 'false' : 'true';
                $data['liveChatPort'] = $request->liveChatPort;
                $this->updateSettings($data);

                return response()->json(["code"=>200, "success"=> lang("Data updated successfully", "alerts")], 200);
            }
        }

    }

    function livechatAutoSave(Request $request){
        $request->validate([
            'autoSloveEmailTimer' => 'required|numeric|gt:0',
            'autoSloveCloseTimer' => 'required|numeric|gt:0',
        ]);

        $data['enableAutoSlove']  =  $request->enableAutoSlove ? true : false;
        $data['autoSloveEmailTimer'] = $request->autoSloveEmailTimer;
        $data['autoSloveCloseTimer'] = $request->autoSloveCloseTimer;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function livechatAutoDelete(Request $request){
        if($request->has('AUTO_DELETE_LIVECHAT_ENABLE')){
            $request->validate([
                'AUTO_DELETE_LIVECHAT_IN_MONTHS' => 'required|numeric|gt:0',
            ]);
        }

        $data['AUTO_DELETE_LIVECHAT_ENABLE']  =  $request->has('AUTO_DELETE_LIVECHAT_ENABLE') ? 'on' : 'off';
        $data['AUTO_DELETE_LIVECHAT_IN_MONTHS'] = $request->AUTO_DELETE_LIVECHAT_IN_MONTHS;
        $this->updateSettings($data);

        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function livechatTickets(Request $request){

        $flow = LiveChatFlow::where('active', 1)->first();

        $livechatTickets = Ticket::where('cust_id',$request->id)->get();

        // $data['tickets'] = $livechatTickets;

        // $alltickets = Ticket::whereIn('status', ['New'])->latest('updated_at')->get();
        $perPage = request()->input('per_page', 10);
        $currentPage = request()->input('page', 1);
        $finalResult = $livechatTickets->forPage($currentPage, $perPage)->values();
        $data['ticketdata'] = new \Illuminate\Pagination\LengthAwarePaginator(
            $finalResult,
            $livechatTickets->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        $perPage = $perPage;

        if (request()->ajax()) {
            return response()->json([
                'rendereddata' => view('admin.superadmindashboard.tabledatainclude', ['ticketdata' => $data['ticketdata'], 'perPage' => $perPage])->render(),
            ]);
        }


        return view('managers.views.livechat.livechat.tickets')->with([
            'perPage' => $perPage,
            'flow' => $flow,
        ]);

    }

    function livechatTicketMassDelete(Request $request){
        foreach($request->id as $ticketId){
            Ticket::where('ticket_id',$ticketId)->delete();
        }
       return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }

    function livechatTicketDelete(Request $request){
        Ticket::where('ticket_id',$request->id)->delete();
        return response()->json(['success' => true, 'message' => 'Updated successfully']);
    }

    function livechatFeedbackDropdown(Request $request)
    {
        $request->validate([
            'LivechatCustFeedbackQuestion' => 'required',
        ]);

        $data['livechatFeedbackDropdown'] = $request->livechatFeedbackDropdown;
        $data['LivechatCustFeedbackQuestion'] = $request->LivechatCustFeedbackQuestion;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function LivechatCustWelcomeMsg(Request $request)
    {
        $request->validate([
            'LivechatCustWelcomeMsg' => 'required',
        ]);

        $data['LivechatCustWelcomeMsg'] = $request->LivechatCustWelcomeMsg;
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    function livechatAllRatings() {


        $userCurrent = User::all();
        $userCurrent->TotalAnsweredTicket = 0;

        $reviewsData = livechatReviews::all();

        $liveChatConversationData = LiveChatConversations::all();

        foreach ($userCurrent as $user) {
            foreach ($liveChatConversationData as $conversation) {
                if ($conversation->livechat_user_id == $user->id) {
                    $user->TotalAnsweredTicket++;
                }
            }
        }


        return view('managers.views.livechat.livechat.reviews')->with([
            'reviewsData' => $reviewsData,
            'userCurrent' => $userCurrent,
        ]);
    }

    function livechatEmpliyerRatings(Request $req, $id){


        $users = User::find($id);

        $reviewsData = livechatReviews::where('users_id',$id)->get();

        return view('managers.views.livechat.livechat.employereview')->with([
            'reviewsData' => $reviewsData,
            'users' => $users,
        ]);

    }

    function livechatDeleteFeedback($id){
        $review = livechatReviews::find($id);
        $review->delete();
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }

    public function downloadFile(Request $req)
    {
        $file = $req->file('file');
        $email = $req->input('email');
        // return $file;
        try{
            Mail::send([], [], function ($message) use ($email, $file) {
                $message->to($email)
                        ->subject('Your LiveChat backup file')
                        ->attach($file->getRealPath(), [
                            'as' => $file->getClientOriginalName(), // You can set the attachment name here
                        ])
                        ->text('Your LiveChat backup file'); // Use text() or html() to set the body content
            });
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to send email'], 500);
        }

        return response()->json(['message' => 'Email sent successfully'], 200);
        // Mail::send([], [], function ($message) use ($email, $file) {
        //     $message->to($email)
        //             ->subject('Your LiveChat backup file')
        //             ->attach($file->getRealPath(), [
        //                 'as' => $file->getClientOriginalName(), // You can set the attachment name here
        //             ])
        //             ->setBody('Your LiveChat backup file'); // You can set the body of the email here
        // });

        // Mail::send('admin.email.template', ['emailBody' => "This is a test email sent by system"], function ($message) use ($email, $file) {
        //     $message->to($email)
        //     ->subject('Your LiveChat backup file')
        //     ->attach($file->getRealPath(), [
        //         'as' => $file->getClientOriginalName(), // You can set the attachment name here
        //     ])->setBody('text/html');
        // });

        // Check if the email was sent successfully
        // if (count(Mail::failures()) > 0) {
        //     return response()->json(['message' => 'Failed to send email'], 500);
        // }

        // return response()->json(['message' => 'Email sent successfully'], 200);
    }

    function securitySettings(Request $request){
        $data['inspectDisable'] = $request->inspectDisable ? $request->inspectDisable : "off";
        $data['selectDisabled'] = $request->selectDisabled ? $request->selectDisabled : "off";
        $this->updateSettings($data);
        return back()->with('success', lang('Updated successfully', 'alerts'));
    }


    private function updateSettings($data)
    {
        foreach($data as $key => $val){
        	$setting = Setting::where('key', $key);
        	if( $setting->exists() )
        		$setting->first()->update(['value' => $val]);
        }

    }

}
