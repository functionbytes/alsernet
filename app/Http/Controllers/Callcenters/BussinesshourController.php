<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Apptitle;
use App\Models\Footertext;
use App\Models\Seosetting;
use App\Models\Bussinesshours;

class BussinesshourController extends Controller
{
    public function index()
    {
        $this->authorize('Bussiness Hours');
        $basic = Apptitle::first();
        $data['basic'] = $basic;

        $title = Apptitle::first();
        $data['title'] = $title;

        $footertext = Footertext::first();
        $data['footertext'] = $footertext;

        $seopage = Seosetting::first();
        $data['seopage'] = $seopage;

        $bussiness1 = Bussinesshours::where('no_id', '1')->first();
        $data['bussiness1'] = $bussiness1;
        $bussiness2 = Bussinesshours::where('no_id', '2')->first();
        $data['bussiness2'] = $bussiness2;
        $bussiness3 = Bussinesshours::where('no_id', '3')->first();
        $data['bussiness3'] = $bussiness3;
        $bussiness4 = Bussinesshours::where('no_id', '4')->first();
        $data['bussiness4'] = $bussiness4;
        $bussiness5 = Bussinesshours::where('no_id', '5')->first();
        $data['bussiness5'] = $bussiness5;
        $bussiness6 = Bussinesshours::where('no_id', '6')->first();
        $data['bussiness6'] = $bussiness6;
        $bussiness7 = Bussinesshours::where('no_id', '7')->first();
        $data['bussiness7'] = $bussiness7;

        return view('admin.bussinesshour.index')->with($data);
    }

    public function store(Request $request)
    {

        if($request->starttime1 != null || $request->endtime1 != null ||$request->starttime2 != null || $request->endtime2 != null || $request->starttime3 != null || $request->endtime3 != null || $request->starttime4 != null || $request->endtime4 != null || $request->starttime5 != null || $request->endtime5 != null || $request->starttime6 != null || $request->endtime6 != null || $request->starttime7 != null || $request->endtime7 != null)
        {
            if($request->starttime1 != $request->endtime1 ||$request->starttime2 != $request->endtime2 || $request->starttime3 != $request->endtime3 || $request->starttime4 != $request->endtime4 ||$request->starttime5 != $request->endtime5 || $request->starttime6 != $request->endtime6 || $request->starttime7 != $request->endtime7){

                $this->bussinessstore($request);
                return redirect()->back()->with('success', lang('Updated successfully', 'alerts'));
            }
            else{

                return redirect()->back()->with('error', lang('Cannot Update the data', 'alerts'));
            }
        }
        $this->bussinessstore($request);
        return redirect()->back()->with('success', lang('Updated successfully', 'alerts'));
    }

    private function bussinessstore($request)
    {
        $bussinessid1 = $request->bussinessid1;
        $bussiness1 = $request->bussiness1;
        $starttime1 = $request->starttime1;
        $endtime1 = $request->endtime1;
        $status1 = $request->status1;


        $ticket1 = [

            'no_id' => $bussinessid1,
            'weeks' => $bussiness1,
            'starttime' => $starttime1,
            'endtime' => $endtime1,
            'status' => $status1,

        ];
        $buss1 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid1], $ticket1);


        $bussinessid2 = $request->bussinessid2;
        $bussiness2 = $request->bussiness2;
        $starttime2 = $request->starttime2;
        $endtime2 = $request->endtime2;
        $status2 = $request->status2;


        $ticket2 = [

            'no_id' => $bussinessid2,
            'weeks' => $bussiness2,
            'starttime' => $starttime2,
            'endtime' => $endtime2,
            'status' => $status2,

        ];
        $buss2 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid2], $ticket2);



        $bussinessid3 = $request->bussinessid3;
        $bussiness3 = $request->bussiness3;
        $starttime3 = $request->starttime3;
        $endtime3 = $request->endtime3;
        $status3 = $request->status3;


        $ticket3 = [

            'no_id' => $bussinessid3,
            'weeks' => $bussiness3,
            'starttime' => $starttime3,
            'endtime' => $endtime3,
            'status' => $status3,

        ];
        $buss3 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid3], $ticket3);


        $bussinessid4 = $request->bussinessid4;
        $bussiness4 = $request->bussiness4;
        $starttime4 = $request->starttime4;
        $endtime4 = $request->endtime4;
        $status4 = $request->status4;


        $ticket4 = [

            'no_id' => $bussinessid4,
            'weeks' => $bussiness4,
            'starttime' => $starttime4,
            'endtime' => $endtime4,
            'status' => $status4,

        ];
        $buss4 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid4], $ticket4);


        $bussinessid5 = $request->bussinessid5;
        $bussiness5 = $request->bussiness5;
        $starttime5 = $request->starttime5;
        $endtime5 = $request->endtime5;
        $status5 = $request->status5;


        $ticket5 = [

            'no_id' => $bussinessid5,
            'weeks' => $bussiness5,
            'starttime' => $starttime5,
            'endtime' => $endtime5,
            'status' => $status5,

        ];
        $buss5 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid5], $ticket5);



        $bussinessid6 = $request->bussinessid6;
        $bussiness6 = $request->bussiness6;
        $starttime6 = $request->starttime6;
        $endtime6 = $request->endtime6;
        $status6 = $request->status6;


        $ticket6 = [

            'no_id' => $bussinessid6,
            'weeks' => $bussiness6,
            'starttime' => $starttime6,
            'endtime' => $endtime6,
            'status' => $status6,

        ];
        $buss6 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid6], $ticket6);


        $bussinessid7 = $request->bussinessid7;
        $bussiness7 = $request->bussiness7;
        $starttime7 = $request->starttime7;
        $endtime7 = $request->endtime7;
        $status7 = $request->status7;


        $ticket7 = [

            'no_id' => $bussinessid7,
            'weeks' => $bussiness7,
            'starttime' => $starttime7,
            'endtime' => $endtime7,
            'status' => $status7,

        ];
        $buss7 = Bussinesshours::updateOrCreate(['no_id' => $bussinessid7], $ticket7);



    }
}
