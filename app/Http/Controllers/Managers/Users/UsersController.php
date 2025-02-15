<?php


namespace App\Http\Controllers\Managers\Users;

use App\Http\Controllers\Controller;
use App\Models\Enterprise\Enterprise;
use App\Models\Enterprise\EnterpriseUser;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    public function index(Request $request){

        $searchKey = null ?? $request->search;
        $role = null ?? $request->role;

        $users = User::latest();

        if ($searchKey) {
            $users->when(!strpos($searchKey, '-'), function ($query) use ($searchKey) {
                $query->where('users.firstname', 'like', '%' . $searchKey . '%')
                    ->orWhere('users.lastname', 'like', '%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONCAT(users.firstname, ' ', users.lastname)"), 'like', '%' . $searchKey . '%')
                    ->orWhere('users.email', 'like', '%' . $searchKey . '%')
                    ->orWhere('users.identification', 'like', '%' . $searchKey . '%');
            });
        }

        if ($request->role != null) {
            $users = $users->where('role', $role);
        }

        $users = $users->paginate(paginationNumber());

        return view('managers.views.users.users.index')->with([
            'users' => $users,
            'role' => $role,
            'searchKey' => $searchKey,
        ]);
    }
    public function create()
    {

        $shops = Shop::get()->prepend('', '')->pluck('title', 'id');
        $roles = Role::get()->prepend('', '')->pluck('name', 'id');

        return view('managers.views.users.users.create')->with([
            'shops' => $shops,
            'roles' => $roles,
        ]);

    }
    public function store(Request $request)
    {

        $validates = User::where('email', $request->email)->exists();

        if($validates){

            $email =  User::where('email', $request->email)->exists();

            if($email){
                $response = [
                    'success' => false,
                    'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                ];

                return response()->json($response);
            }

        }else{

            $user = new User;
            $user->uid = $this->generate_uid('users');
            $user->firstname = Str::upper($request->firstname);
            $user->lastname  = Str::upper($request->lastname);
            $user->email = $request->email;
            $user->password = $request->password;
            $user->available = $request->available;
            $user->shop_id = $request->shop;
            $user->save();

            $user->roles()->sync([$request->role]);

            $response = [
                'success' => true,
                'message' => '',
            ];

            return response()->json($response);

        }

    }

    public function view($uid)
    {
        $user = User::uid($uid);

        $shops = Shop::get()->prepend('', '')->pluck('title', 'id');
        $roles = Role::get()->prepend('', '')->pluck('name', 'id');

        return view('managers.views.users.users.view')->with([
            'user' => $user,
            'shops' => $shops,
            'roles' => $roles,
        ]);

    }
    public function edit($uid)
    {
        $user = User::uid($uid);

        $shops = Shop::get()->prepend('', '')->pluck('title', 'id');
        $roles = Role::get()->prepend('', '')->pluck('name', 'id');

        return view('managers.views.users.users.edit')->with([
            'user' => $user,
            'shops' => $shops,
            'roles' => $roles,
        ]);

    }
    public function update(Request $request)
    {
        $user = User::uid($request->uid);

        // Validar si el email ya está registrado en otro usuario
        if ($user->email !== $request->email && User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico ya está registrado en nuestro sistema',
            ]);
        }

        // Actualizar los datos del usuario
        $user->firstname = Str::upper($request->firstname);
        $user->lastname  = Str::upper($request->lastname);
        $user->email = $request->email;

        if (!empty($request->password)) {
            $user->password = bcrypt($request->password);
        }

        $user->available = $request->available;
        $user->shop_id = ($request->role == 2) ? $request->shop : null;
        $user->update();

        if (!empty($request->role) && is_numeric($request->role)) {
            $user->roles()->sync([$request->role]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
        ]);
    }

    public function destroy($uid)
    {
        $user = User::uid($uid);
        $user->delete();
        return redirect()->route('manager.users');

    }

}
