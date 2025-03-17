<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\IPLIST;
use App\Models\Seosetting;
use DataTables;
use Illuminate\Http\Request;

class IpblockController extends Controller
{
    public function index(){

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $iplists = IPLIST::latest()->get();
        $data['iplists'] = $iplists;

        return view('admin.securitysetting.ipblocklist')->with($data);
    }

    public function show($id){

        $ip = IPLIST::find($id);

        return response()->json($ip);
    }


    public function store(Request $request){

        $request->validate([
            'ip'=> 'required|ip',
            'types' => 'required',

        ]);

        $ipId = $request->IP_id;
        $ipdata =  [
            'ip' => $request->ip,
            'types' => $request->types,

        ];


     $ipdtaa = IPLIST::updateOrCreate(['id' => $ipId], $ipdata);

     $ipdataupdate = IPLIST::find($ipdtaa->id);
     if($ipdataupdate->entrytype != 'Auto'){
        $ipdataupdate->entrytype = 'Manual';
     }
     $ipdataupdate->update();
     return response()->json(['code'=>200, 'success'=> lang('The IP address was successfully created and updated.', 'alerts'),'data' => $ipdtaa], 200);

    }

    public function destroy($id){

        $ipdelete = IPLIST::find($id);
        $ipdelete->delete();
        return response()->json(['success'=> lang('The IP address has been successfully removed.', 'alerts')]);
    }


    public function allipblocklistdelete(Request $request){
        $id_array = $request->input('id');

        $sendmails = IPLIST::whereIn('id', $id_array)->get();

        foreach($sendmails as $sendmail){
            $sendmail->delete();

        }
        return response()->json(['success'=> lang('The IP address has been successfully removed.', 'alerts')]);
    }

    public function resetipblock($id){

        $ipreset = IPLIST::find($id);
        $ipreset->types = 'Unlock';
        $ipreset->update();
        return response()->json(['success'=> lang('Updated Successfully', 'alerts')]);

    }

}
