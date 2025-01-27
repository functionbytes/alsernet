<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\Hour;
use Illuminate\Http\Request;

class HoursSettingsController extends Controller
{

   public function index()
      {

          $bussiness1 = Hour::where('no_id', '1')->first();
          $data['bussiness1'] = $bussiness1;
          $bussiness2 = Hour::where('no_id', '2')->first();
          $data['bussiness2'] = $bussiness2;
          $bussiness3 = Hour::where('no_id', '3')->first();
          $data['bussiness3'] = $bussiness3;
          $bussiness4 = Hour::where('no_id', '4')->first();
          $data['bussiness4'] = $bussiness4;
          $bussiness5 = Hour::where('no_id', '5')->first();
          $data['bussiness5'] = $bussiness5;
          $bussiness6 = Hour::where('no_id', '6')->first();
          $data['bussiness6'] = $bussiness6;
          $bussiness7 = Hour::where('no_id', '7')->first();
          $data['bussiness7'] = $bussiness7;

          return view('managers.views.settings.hours.setting')->with($data);

      }


    public function update(Request $request)
    {

        if($request->starttime1 != $request->endtime1 ||$request->starttime2 != $request->endtime2 || $request->starttime3 != $request->endtime3 || $request->starttime4 != $request->endtime4 ||$request->starttime5 != $request->endtime5 || $request->starttime6 != $request->endtime6 || $request->starttime7 != $request->endtime7){

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
            $buss1 = Hour::updateOrCreate(['no_id' => $bussinessid1], $ticket1);


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
            $buss2 = Hour::updateOrCreate(['no_id' => $bussinessid2], $ticket2);

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
            $buss3 = Hour::updateOrCreate(['no_id' => $bussinessid3], $ticket3);


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
            $buss4 = Hour::updateOrCreate(['no_id' => $bussinessid4], $ticket4);


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
            $buss5 = Hour::updateOrCreate(['no_id' => $bussinessid5], $ticket5);



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
            $buss6 = Hour::updateOrCreate(['no_id' => $bussinessid6], $ticket6);


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
            $buss7 = Hour::updateOrCreate(['no_id' => $bussinessid7], $ticket7);
            
        }


        $data['hoursswitch']  =  $request->has('hoursswitch') ? 'true' : 'false';
        $data['hourstitle']  =  $request->hourstitle;
        $data['hourssubtitle']  =  $request->hourssubtitle;
        updateSettings($data);

        return response()->json([
            'success' => true,
            'message' => 'Se actualizo correctamente el horario de soporte',
        ]);


    }

}
