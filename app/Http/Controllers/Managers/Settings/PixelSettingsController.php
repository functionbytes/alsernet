<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PixelSettingsController extends Controller
{

   public function index(){

        return view('managers.views.settings.pixel.setting')->with([
        ]);

   }


    public function update(Request $request){

        $data['fb_pixel_enable']  =  $request->fb_pixel_enable;
        $data['fb_pixel'] = $request->fb_pixel;

        updateSettings($data);

        $response = [
          'status' => true,
          'message' => 'Se actualizo el pixel correctamente',
        ];

        return response()->json($response);

    }

}
