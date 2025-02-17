<?php

namespace App\Http\Controllers\Managers\Settings;

use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MantenanceSettingsController extends Controller
{

    public function index(){

        $secret = Str::random(20);

        return view('managers.views.settings.maintenance.setting')->with([
            'secret' => $secret
        ]);

    }


    public function update(Request $request){

        if($request->maintenance_mode == "true"){

            $data['maintenance_mode']  =  $request->maintenance_mode;
            $data['maintenance_mode_value']  =  $request->maintenance_mode_value;
            updateSettings($data);
            Artisan::call('down --secret="'.$request->maintenance_mode_value.'"');

        }else{

            $data['maintenance_mode']  =  $request->maintenance_mode;
            $data['maintenance_mode_value']  =  null;
            updateSettings($data);
            Artisan::call("up");

        }

        return response()->json([
            'success' => true,
            'message' => 'Se actualizo correctamente el modo mantenimiento',
        ]);


    }

}
