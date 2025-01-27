<?php

namespace App\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailsSettingsController extends Controller
{

   public function index(){

        return view('managers.views.settings.emails.setting')->with([
        ]);

   }

    public function update(Request $request){

            $data['imap_status']  =  $request->imap_status;
            $data['imap_host']  =  $request->imap_host;
            $data['imap_port']  =  $request->imap_port;
            $data['imap_protocol']  =  $request->imap_protocol;
            $data['imap_username']  =  $request->imap_username;
            $data['imap_password']  =  $request->imap_password;
            $data['imap_encryption']  =  $request->imap_encryption;

            updateSettings($data);

            $response = [
                'status' => true,
                'message' => 'Se actualizo correctamente configuración de correo',
            ];

            return response()->json($response);

    }

}
