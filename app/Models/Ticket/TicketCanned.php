<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use App\Models\User;
use App\Models\Ticket\Ticket;


class TicketCanned extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'ticket_canneds';

    protected $fillable = [
        'title', 'messages', 'available'
    ];

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}


    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }



    public static function getDetailsList(){
	    $return_object=array();
	    $return_object["app_name"]="The Application Name";
	    $return_object["site_url"]="The Site URL";
	    $return_object["ticket_user"]="The Customer who has opened ticket";
	    $return_object["ticket_title"]="The Ticket Title";
	    $return_object["ticket_id"]="The Ticket ID";
	    $return_object["ticket_priority"]="The Ticket Priority";
	    $return_object["user_reply"]="The Employee's who reply to the ticket";
	    $return_object["user_role"]="The Employee's Role";

	    return $return_object;
	}

    public static function getDetailsListClearData(){
	    $return_object=self::getDetailsList();
	    $return_object=array_map(function($value){
	        $value="";
	    }, $return_object);
	    $return_object["app_name"]=env('APP_NAME');
	    $return_object["site_url"]=env('APP_URL');
	    return $return_object;
	}

    static function get_real_message($params,$str){

	    if(count($params)>0){

            $search=array();
    	    $replace=array();

    	    foreach ($params as $key=>$value){
    	        $search[]="{{".$key."}}";
    	        $replace[]=$value;
    	    }

    	    return str_replace($search, $replace, $str);
	    }
	    return $str;
	}


    public static function details($slack)
    {

        $ticket = Ticket::slack($slack);

        if($ticket){

            $response_object = [];
            $cannedmessages = self::where('available', '1')->get();

            if(count($cannedmessages)>0){
                $auth = Auth::user();
                $details=self::getDetailsListClearData();
                $user= User::id($ticket->cust_id);
                $details["ticket_user"]= $user->firstname." ". $user->lastname;
                $details["ticket_title"]= $ticket->subject;
                $details["ticket_id"]= $ticket->ticket_id;
                $details["ticket_priority"]= $ticket->priority->slug;
                $details["user_reply"]= $auth->name;
                $details["user_role"]= $auth->role;

                foreach ($cannedmessages as $msg){
                    $msg->messages=self::get_real_message($details, $msg->messages);
                    $response_object[$msg->id]=$msg;
                }
                return $response_object;
            }
        }

        return [];
    }



}
