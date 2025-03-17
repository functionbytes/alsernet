<?php

namespace App\Http\Controllers\Shops\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;

class SettingsController extends Controller
{
    public function profile(){

        $user = User::auth();

        return view('shopsviews.settings.profile.setting')->with([
            'user' => $user
        ]);
    }
    public function notifications(){

        $user = User::auth();

        return view('shopsviews.settings.setting.setting')->with([
            'user' => $user,
        ]);

    }
    public function updateProfile(Request $request) {

        $user = User::uid($request->uid);

        if($user->email != $request->email || $user->identification != $request->identification){

            $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

            if (count($validates)>0) {

                $email =  User::where('email', $request->email)->get();

                if(count($email)>0){

                    if($user->email != $request->email){

                        $response = [
                            'success' => false,
                            'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                        ];

                        return response()->json($response);
                    }

                }

                $user = User::uid($request->uid);
                $user->firstname = Str::upper($request->firstname);
                $user->lastname = Str::upper($request->lastname);
                $user->support = Str::upper($request->support);
                $user->email = $request->email;
                $request->password != null ? $user->password = $request->password : null;
                $user->update();

                return response()->json([
                    'success' => true,
                    'message' => 'Se ha actualizado correctamente.'
                ]);

            }

        }else {

            $user = User::uid($request->uid);
            $user->firstname = Str::upper($request->firstname);
            $user->lastname = Str::upper($request->lastname);
            $user->support = Str::upper($request->support);
            $user->email = $request->email;
            $request->password != null ? $user->password = $request->password : null;
            $user->update();

            return response()->json([
                'success' => true,
                'message' => 'Se ha actualizado correctamente.'
            ]);
        }


    }
    public function updateNotifications(Request $request){

                $user = User::uid($request->uid);
                $user->mail_notification = $request->mail_notification == 'true' ? 1 : 0;
                $user->inscription_notification = $request->inscription_notification == 'true' ? 1 : 0;
                $user->invoice_notification = $request->invoice_notification == 'true' ? 1 : 0;

                if ($user->isDirty()) {

                    $user->update();

                    activity()
                        ->performedOn($user)
                        ->withProperties($user->getChanges())
                        ->log('updated');
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Se ha actualizado correctamente.'
                ]);

    }

}
