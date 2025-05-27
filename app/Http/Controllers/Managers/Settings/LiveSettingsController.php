<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Livechat\LiveChatFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LiveSettingsController extends Controller
{

   public function index()
      {

          $flow = LiveChatFlow::where('active', 1)->first();
          $data['flow'] = $flow;

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

          $domainname = url('/');


        return view('managers.views.settings.lives.setting')->with([
            'sounds' => $sounds,
            'flow' => $flow,
            'domainname' => $domainname,
        ]);
      }

    public function update(Request $request)
    {

        $exp = array("<p class='ql-align-justify'><br></p>", "<p> </p>", "<p></p>", "<p></p>");

        $data['page_title']  =  $request->page_title;
        $data['page_copyright']  =  $request->page_copyright;
        $data['page_email']  =  $request->page_email;
        $data['page_phone']  =  $request->page_phone;
        $data['page_cellphone']  =  $request->page_cellphone;
        $data['page_whatsapp']  =  $request->page_whatsapp;
        $data['page_description']  =  str_replace($exp, '', $request->page_description);
        $data['page_politic']  =  str_replace($exp, '', $request->page_politic);
        $data['page_term']  =  str_replace($exp, '', $request->page_term);
        $data['page_address']  =  $request->page_address;
        $data['page_map']  =  $request->page_map;
        $data['social_media_facebook']  =  $request->social_media_facebook;
        $data['social_media_instagram']  =  $request->social_media_instagram;
        $data['social_media_twitter']  =  $request->social_media_twitter;
        $data['social_media_youtube']  =  $request->social_media_youtube;
        $data['social_media_linkedin']  =  $request->social_media_linkedin;
        $data['page_hour_weekend']  =  $request->page_hour_weekend;
        $data['page_hour_weekends']  =  $request->page_hour_weekends;
        updateSettings($data);

        return response()->json([
            'success' => true,
            'message' => 'Se actualizo correctamente la configuración',
        ]);

    }

}
