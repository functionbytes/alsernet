<?php


namespace App\Http\Controllers\Callcenters\Users;

use App\Events\Auth\Password\ForgotPasswordCreated;
use App\Events\Auth\Password\ResetPasswordCreated;
use App\Models\Enterprise\EnterpriseUser;
use App\Models\Enterprise\Enterprise;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class UsersController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $role = null ?? $request->role;

        $users = User::descending();

        if ($searchKey) {
            $users = $users->where(function($query) use ($searchKey) {
                $query->where('users.firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('users.lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(users.firstname, ' ', users.lastname)"), 'like', '%' . $searchKey . '%')
                    ->orWhere('users.email', 'like', '%' . $searchKey . '%')->orWhere('users.identification', 'like', '%' . $searchKey . '%');
            });
        }

        if ($request->role != null) {
            $users = $users->where('role', $role);
        }

        $users = $users->paginate(paginationNumber());

        return view('callcenters.views.users.users.index')->with([
            'users' => $users,
            'role' => $role,
            'searchKey' => $searchKey,
        ]);
    }
    public function create(){

        $roles = collect([
            ['id' => 'admin', 'title' => 'Administrador'],
            ['id' => 'customers', 'title' => 'Cliente'],
            ['id' => 'enterprises', 'title' => 'Empresa'],
        ]);

        $roles = $roles->pluck('title', 'id');

        $enterprises = Enterprise::get();
        $enterprises->prepend('', '');
        $enterprises = $enterprises->pluck('title', 'id');

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('callcenters.views.users.users.create')->with([
            'roles' => $roles,
            'enterprises' => $enterprises,
            'availables' => $availables,
        ]);

    }
    public function store(Request $request)
    {

        $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

        if($validates!=null){

            $email =  User::where('email', $request->email)->get();

            if($email!=null){

                return response()->json([
                    'success' => false,
                    'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                ]);

            }

            $identification =  User::where('identification', $request->identification)->get();

            if($identification!=null){

                return response()->json(['success' => false,
                    'message' => 'El nit ya estan regitrada en nuestro sistema',
                ]);

            }

        }else{

            $user = new User;
            $user->uid = $this->generate_uid('users');
            $user->firstname = Str::upper($request->firstname);
            $user->lastname  = Str::upper($request->lastname);
            $user->cellphone = $request->cellphone;
            $user->identification = $request->identification;
            $user->email = $request->email;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->role = $request->role;
            $user->available = 1;
            $user->password = $request->password;
            $user->terms = 1;
            $user->page = 1;
            $user->setting = 1;
            $user->validation = 1;
            $user->email_verified_at = Carbon::now()->setTimezone('America/Bogota');
            $request->roles == 'enterprises' ? $user->enterprise_id = $request->enterprise : $user->enterprise_id = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Se ha actualizado correctamente.'
            ]);

        }

    }
    public function view($uid){

        $user = User::uid($uid);

        $roles = collect([
            ['id' => 'admin', 'title' => 'Administrador'],
            ['id' => 'customers', 'title' => 'Cliente'],
            ['id' => 'enterprises', 'title' => 'Empresa'],
        ]);

        $roles = $roles->pluck('title', 'id');

        $availables = collect([
            ['id' => '1', 'label' => 'Publico'],
            ['id' => '0', 'label' => 'Oculto'],
        ]);

        $availables = $availables->pluck('label','id');

        return view('callcenters.views.users.users.view')->with([
            'user' => $user,
            'roles' => $roles,
            'availables' => $availables,
        ]);

    }
    public function navegation($uid){
        $user = User::uid($uid);

        return view('callcenters.views.users.users.navegation')->with([
            'user' => $user,
        ]);

    }
    public function edit($uid)
    {
        $user = User::uid($uid);

        $roles = collect([
            ['id' => 'admin', 'title' => 'Administrador'],
            ['id' => 'customers', 'title' => 'Cliente'],
            ['id' => 'enterprises', 'title' => 'Empresa'],
        ]);

        $roles = $roles->pluck('title', 'id');

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label','id');

        $enterprises = Enterprise::get();
        $enterprises->prepend('', '');
        $enterprises = $enterprises->pluck('title', 'id');

        $enterprise = $user->relations?->id;
        return view('callcenters.views.users.users.edit')->with([
            'user' => $user,
            'enterprises' => $enterprises,
            'enterprise' => $enterprise,
            'availables' => $availables,
            'roles' => $roles,
        ]);

    }
    public function update(Request $request)
    {


        $user = User::uid($request->uid);

        if($user->email != $request->email || $user->identification != $request->identification){

            $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

            if (count($validates)>0) {

                $email =  User::where('email', $request->email)->get();

                if(count($email)>0){

                    if($user->email != $request->email){

                        return response()->json([
                            'success' => false,
                            'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                        ]);

                    }

                }

                $identification =  User::where('identification', $request->identification)->get();

                if(count($identification)>0){
                    if($user->identification != $request->identification){

                        return response()->json([
                            'success' => false,
                            'message' => 'El nit ya estan regitrada en nuestro sistema',
                        ]);

                    }
                }

                $user = User::uid($request->uid);
                $user->firstname = Str::upper($request->firstname);
                $user->lastname = Str::upper($request->lastname);
                $user->cellphone = $request->cellphone;
                $user->email = $request->email;
                $user->address = $request->address;
                $user->company = $request->company;
                $user->role = $request->role;
                $user->available = $request->available;
                $request->password != null ? $user->password = $request->password : null;

                if ($request->role == "enterprises") {

                    $user->enterprise_id = $request->enterprise;

                } elseif ($user->role == "customers") {

                    $enterprise = $user->relation;

                    if ($enterprise != null) {

                        $enterprise->enterprise_id = $request->enterprises;
                        $enterprise->save();

                    } else {

                        $inscription = new EnterpriseUser;
                        $inscription->user_id = $user->id;
                        $inscription->enterprise_id = $request->enterprises;
                        $inscription->available = 1;
                        $inscription->created_at = Carbon::now()->setTimezone('America/Bogota');
                        $inscription->updated_at = Carbon::now()->setTimezone('America/Bogota');
                        $inscription->save();
                    }

                } else {

                    $user->enterprise_id = null;
                    $relation = $user->relatios;
                    $relation->delete();

                }

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
            $user->cellphone = $request->cellphone;
            $user->email = $request->email;
            $user->address = $request->address;
            $user->company = $request->company;
            $user->role = $request->role;
            $user->available = $request->available;
            $request->password != null ? $user->password = $request->password : null;

            if ($request->role == "enterprises") {

                $user->enterprise_id = $request->enterprise;

            } elseif ($user->role == "customers") {

                $enterprise = $user->relation;

                if ($enterprise != null) {

                    $enterprise->enterprise_id = $request->enterprises;
                    $enterprise->save();

                } else {

                    $inscription = new EnterpriseUser;
                    $inscription->user_id = $user->id;
                    $inscription->enterprise_id = $request->enterprises;
                    $inscription->available = 1;
                    $inscription->created_at = Carbon::now()->setTimezone('America/Bogota');
                    $inscription->updated_at = Carbon::now()->setTimezone('America/Bogota');
                    $inscription->save();
                }

            } else {

                $user->enterprise_id = null;
                $relation = $user->relatios;
                $relation->delete();

            }

            $user->update();

            return response()->json([
                'success' => true,
                'message' => 'Se ha actualizado correctamente.'
            ]);

        }


    }
    public function destroy($uid){
        $user = User::uid($uid);
        $user->delete();
        return redirect()->back();
    }
    public function information(Request $request)
    {

        $user = User::uid($request->uid);

        if($user->email != $request->email || $user->identification != $request->identification){

            $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

            if (count($validates)>0) {

                $email =  User::where('email', $request->email)->get();

                if(count($email)>0){

                    if($user->email != $request->email){

                        return response()->json([
                            'success' => false,
                            'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                        ]);


                    }

                }

                $identification =  User::where('identification', $request->identification)->get();

                if(count($identification)>0){
                    if($user->identification != $request->identification){

                        return response()->json([
                            'success' => false,
                            'message' => 'El nit ya estan regitrada en nuestro sistema',
                        ]);


                    }
                }

                $user = User::uid($request->uid);
                $user->firstname = Str::upper($request->firstname);
                $user->lastname = Str::upper($request->lastname);
                $user->cellphone = $request->cellphone;
                $user->email = $request->email;
                $user->address = $request->address;
                $user->role = $request->role;
                $user->available = $request->available;


                if ($user->isDirty()) {

                    $user->update();

                    activity()
                        ->performedOn($user)
                        ->withProperties($user->getChanges())
                        ->log('updated');
                }


                return response()->json([
                    'success' => true,
                    'message' => 'El nit ya estan regitrada en nuestro sistema',
                ]);


            }
        }else {

            $user = User::uid($request->uid);
            $user->firstname = Str::upper($request->firstname);
            $user->lastname = Str::upper($request->lastname);
            $user->cellphone = $request->cellphone;
            $user->email = $request->email;
            $user->address = $request->address;
            $user->role = $request->role;
            $user->available = $request->available;

            if ($user->isDirty()) {

                $user->update();

                activity()
                    ->performedOn($user)
                    ->withProperties($user->getChanges())
                    ->log('updated');
            }



            return response()->json([
                'success' => true,
                'message' => 'El nit ya estan regitrada en nuestro sistema',
            ]);


        }


    }
    public function resetpassword(Request $request)
    {

        $user = User::uid($request->uid);
        $new_password_hashed_password = bcrypt($request->new_password);
        $new_password_confirmation_hashed_password = bcrypt($request->new_password_confirmation);

        if (Hash::check($new_password_hashed_password, $new_password_confirmation_hashed_password) == false) {

            $user->password = $request->password;
            $user->remember_token = Str::random(60);
            $user->password_reset_token = null;
            $user->password_reset_max_tries = null;
            $user->password_reset_last_tried_on = null;
            $user->sessions()->delete();

            event(new ResetPasswordCreated($user));

            if ($user->isDirty()) {

                $user->update();

                activity()
                    ->performedOn($user)
                    ->withProperties($user->getChanges())
                    ->log('updated');
            }

            return response()->json([
                'success' => true,
                'message' => 'El nit ya estan regitrada en nuestro sistema',
            ]);

        }

    }
    public function forgotpassword(Request $request)
    {

        $user = User::uid($request->uid);

        $reset_tries = 0;

        if ($user->password_reset_last_tried_on != "") {

            $current_date = date("Y-m-d");
            $last_tried_date = date("Y-m-d", strtotime($user->password_reset_last_tried_on));

            if ($last_tried_date == $current_date && $user->password_reset_max_tries >= 3) {

                return response()->json([
                    'success' => false,
                    'message' => 'Ya lo has probado 3 veces hoy. Comuníquese con el administrador para restablecer la contraseña.',
                ]);

            }

            if ($last_tried_date == $current_date && $user->password_reset_max_tries < 3) {
                $reset_tries = $user->password_reset_max_tries + 1;
            } else if ($last_tried_date != $current_date) {
                $reset_tries = $reset_tries + 1;
            }
        } else {
            $reset_tries = $reset_tries + 1;
        }

        $password_token = Str::random(50);

        $user->password_reset_token = $password_token;
        $user->password_reset_max_tries = $reset_tries;
        $user->password_reset_last_tried_on = now();

        event(new ForgotPasswordCreated($user));

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
    public function notification(Request $request){

        $user = User::uid($request->uid);

            $user->subscribers_notification = $request->subscribers_notification == 'true' ? 1 : 0;
            $user->order_notification = $request->order_notification == 'true' ? 1 : 0;
            $user->status_notification = $request->status_notification  == 'true' ? 1 : 0;
            $user->email_notification = $request->email_notification  == 'true' ? 1 : 0;
            $user->cookies_notification = $request->cookies_notification == 'true' ? 1 : 0;


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
