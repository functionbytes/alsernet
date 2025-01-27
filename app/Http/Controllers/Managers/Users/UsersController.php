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
        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label','id');

        $shops = Shop::get();
        $shops->prepend('', '');
        $shops = $shops->pluck('title', 'id');

        $roles = Role::get();
        $roles->prepend('', '');
        $roles = $roles->pluck('title', 'id');

        return view('managers.views.users.users.create')->with([
            'shops' => $shops,
            'availables' => $availables,
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

    public function view($slack)
    {
        $user = User::uid($slack);

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label','id');

        $shops = Shop::get();
        $shops->prepend('', '');
        $shops = $shops->pluck('title', 'id');

        $roles = Role::get();
        $roles->prepend('', '');
        $roles = $roles->pluck('title', 'id');

        return view('managers.views.users.users.view')->with([
            'user' => $user,
            'shops' => $shops,
            'availables' => $availables,
            'roles' => $roles,
        ]);

    }
    public function edit($slack)
    {
        $user = User::uid($slack);

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label','id');

        $shops = Shop::get();
        $shops->prepend('', '');
        $shops = $shops->pluck('title', 'id');

        $roles = Role::get();
        $roles->prepend('', '');
        $roles = $roles->pluck('title', 'id');

        return view('managers.views.users.users.edit')->with([
            'user' => $user,
            'shops' => $shops,
            'availables' => $availables,
            'roles' => $roles,
        ]);

    }
    public function update(Request $request)
    {

        $user = User::uid($request->uid);

        if($user->email != $request->email ){

            $validates = User::where('email', $request->email)->get();

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
                $user->lastname  = Str::upper($request->lastname);
                $user->email = $request->email;
                $request->password != null ? $user->password = $request->password : null;
                $user->available = $request->available;
                if ($request->role == 2) {
                    $user->shop_id = $request->shop;
                } else {
                    $user->shop_id = null;
                }

                $user->update();

                if ($user->roles()->where('role_id', $request->role)->doesntExist()) {
                    $user->roles()->sync([$request->role]);
                }

                return response()->json([
                    'success' => true,
                    'message' => '',
                ]);

            }
        }else {

            $user = User::uid($request->uid);
            $user->firstname = Str::upper($request->firstname);
            $user->lastname  = Str::upper($request->lastname);
            $user->email = $request->email;
            $request->password != null ? $user->password = $request->password : null;
            $user->available = $request->available;
            if ($request->role == 2) {
                $user->shop_id = $request->shop;
            } else {
                $user->shop_id = null;
            }

            $user->update();

            if ($user->roles()->where('role_id', $request->role)->doesntExist()) {
                $user->roles()->sync([$request->role]);
            }

            $response = [
                'success' => true,
                'message' => '',
            ];

            return response()->json($response);
        }


    }
    public function destroy($slack)
    {
        $user = User::uid($slack);
        $user->delete();
        return redirect()->route('manager.users');

    }

}
