<?php

namespace App\Http\Controllers\Callcenters\Users;

use App\Exports\Distributors\IncomesExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Supports\Enterprises\UsersExport;
use App\Imports\Managers\UsersImport;
use App\Models\Course\CourseProgress;
use App\Models\Distributor\Distributor;
use App\Models\Distributor\DistributorEnterprise;
use App\Models\Enterprise\Enterprise;
use App\Models\Enterprise\EnterpriseUser;
use App\Models\Inscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ManagementController extends Controller
{

    public function index(Request $request, $uid){

        $inscription = Inscription::uid($uid);
        $course = $inscription->course;

        return view('callcenters.views.users.managements.index')->with([
            'inscription' => $inscription,
            'course' => $course,
        ]);
    }



    public function progressView($uid){

        $inscription = Inscription::uid($uid);
        $progress = $inscription->progress;
        $user = $inscription->user;
        $course = $inscription->course;
        $class = $course->lessons;

        return view('callcenters.views.users.managements.progress')->with([
            'user' => $user,
            'course' => $course,
            'progress' => $progress,
            'class' => $class,
            'inscription' => $inscription,
        ]);

    }

    public function progressRestoreSingle($uid){
        $inscription = null;
        $progress = CourseProgress::id($uid);
        $inscription = $progress->inscription;
        $progress->delete();
        return redirect()->route('support.enterprises.users.managements.progress.view', $inscription->uid);
    }

    public function progressRestore($uid){
        $inscription = Inscription::uid($uid);
        $inscription->progress()->delete(); // Elimina todos los registros relacionados
        return redirect()->route('support.enterprises.users.managements.progress.view', $inscription->uid);
    }



    public function reassign($uid){

        $user = User::uid($uid);
        $enterprise = $user->getEnterprise();

        $distributor = app('distributor');
        $enterprises = $distributor->enterprises;
        $enterprises->prepend('' , '');
        $enterprises = $enterprises->pluck('title', 'uid');

        $enterprises = $enterprises->forget($enterprise->uid);

        return view('callcenters.views.enterprises.users.users.reassign')->with([
            'user' => $user,
            'enterprises' => $enterprises,
            'enterprise' => $enterprise,
        ]);

    }

    public function reassignUser(Request $request) {

        $user = User::uid($request->uid);
        $newEnterprise = Enterprise::uid($request->enterprise);

        if (!$user || !$newEnterprise) {
            return response()->json([
            'success' => false,
            'message' => 'Usuario o empresa no encontrados.']);
        }

        $enterpriseUser = EnterpriseUser::where('user_id', $user->id)->first();

        if ($enterpriseUser) {

            $enterpriseUser->enterprise_id = $newEnterprise->id;
            $enterpriseUser->save();

            return response()->json([
                'success' => true,
                'enterprise' => $newEnterprise->uid,
                'message' => 'Usuario reasignado a la nueva empresa correctamente.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró la relación del usuario con la empresa.'
        ]);

    }

    public function dashboard($uid){

        $enterprise = Enterprise::uid($uid);

        return view('callcenters.views.enterprises.users.users.dashboard')->with([
            'enterprise' => $enterprise,
        ]);

    }

    public function create($uid){

        $enterprise = Enterprise::uid($uid);

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label', 'id');

        return view('callcenters.views.enterprises.users.users.create')->with([
            'enterprise' => $enterprise,
            'availables' => $availables,
        ]);
    }

    public function view($uid){

        $user = User::uid($uid);
        $enterprise = $user->relations;
        return view('callcenters.views.enterprises.users.users.view')->with([
            'user' => $user,
            'enterprise' => $enterprise,
        ]);
    }

    public function edit($uid){

        $user = User::uid($uid);
        $enterprise = $user->relations;

        $availables = collect([
            ['id' => '1', 'label' => 'Activo'],
            ['id' => '0', 'label' => 'Inactivo'],
        ]);

        $availables = $availables->pluck('label', 'id');

        return view('callcenters.views.enterprises.users.users.edit')->with([
            'user' => $user,
            'enterprise' => $enterprise,
            'availables' => $availables,
        ]);

    }

    public function update(Request $request){

        $user = User::uid($request->uid);

        if ($user->email != $request->email || $user->identification != $request->identification) {

            $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

            if (count($validates) > 0) {

                $email =  User::where('email', $request->email)->get();

                if (count($email) > 0) {

                    if ($user->email != $request->email) {

                        return response()->json([
                            'success' => false,
                            'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                        ]);

                    }
                }

                $identification =  User::where('identification', $request->identification)->get();

                if (count($identification) > 0) {
                    if ($user->identification != $request->identification) {

                        return response()->json([
                            'success' => false,
                            'message' => 'El nit ya estan regitrada en nuestro sistema',
                        ]);

                    }
                }

                $user = User::uid($request->uid);
                $user->firstname = Str::upper($request->firstname);
                $user->lastname = Str::upper($request->lastname);
                $user->identification = $request->identification;
                $user->cellphone = $request->cellphone;
                $user->email = $request->email;
                $user->address = $request->address;
                $user->available = $request->available;
                $request->password != null ? $user->password = $request->password : null;
                $user->update();

                return response()->json([
                    'success' => true,
                    'message' => 'Se ha actualizado correctamente el perfil',
                ]);

            }

        } else {

            $user = User::uid($request->uid);
            $user->firstname = Str::upper($request->firstname);
            $user->lastname = Str::upper($request->lastname);
            $user->cellphone = $request->cellphone;
            $user->identification = $request->identification;
            $user->email = $request->email;
            $user->address = $request->address;
            $user->available = $request->available;
            $request->password != null ? $user->password = $request->password : null;
            $user->update();

            return response()->json([
                'success' => true,
                'message' => 'Se ha actualizado correctamente el perfil',
            ]);

        }
    }

    public function store(Request $request){

        $enterprise = Enterprise::uid($request->enterprise);

        $validates = User::where('email', $request->email)->orWhere('identification', $request->identification)->get();

        if (count($validates) > 0) {

            $email =  User::where('email', $request->email)->get();

            if (count($email) > 0) {

                return response()->json([
                    'success' => false,
                    'message' => 'El correo electronico ya estan regitrada en nuestro sistema',
                ]);

            }

            $identification =  User::where('identification', $request->identification)->get();

            if (count($identification) > 0) {

                return response()->json([
                    'success' => false,
                    'message' => 'El nit ya estan regitrada en nuestro sistema',
                ]);

            }
        } else {


            $user = new User;
            $user->uid = $this->generate_uid('users');
            $user->firstname = Str::upper($request->firstname);
            $user->lastname =  Str::upper($request->lastname);
            $user->cellphone = $request->cellphone;
            $user->identification = $request->identification;
            $user->email = $request->email;
            $user->address = $request->address;
            $request->password != null ? $user->password = $request->password : $user->password = $request->identification;
            $user->role = 'customer';
            $user->available = 1;
            $user->terms = 1;
            $user->page = 0;
            $user->setting = 0;
            $user->validation = 1;
            $user->email_verified_at = Carbon::now()->setTimezone('America/Bogota');
            $user->save();

            $inscription = new EnterpriseUser;
            $inscription->user_id = $user->id;
            $inscription->enterprise_id = $enterprise->id;
            $inscription->available = 1;
            $inscription->created_at = Carbon::now()->setTimezone('America/Bogota');
            $inscription->updated_at = Carbon::now()->setTimezone('America/Bogota');
            $inscription->save();

            return response()->json([
                'success' => true,
                'message' => 'Se ha creado correctamente ',
            ]);

        }

    }

    public function users($uid){

        $enterprise = Enterprise::uid($uid);
        $users = $enterprise->users;

        return view('callcenters.views.enterprises.users.users.index')->with([
            'enterprise' => $enterprise,
            'users' => $users,
        ]);
    }

    public function courses($uid){

        $user = User::uid($uid);
        $inscriptions = $user->inscriptions()->with('course');
        $inscriptions = $inscriptions->paginate(paginationNumber());

        return view('callcenters.views.enterprises.users.courses.index')->with([
            'user' => $user,
            'inscriptions' => $inscriptions,
        ]);
    }

    public function report($uid){

        $enterprise = Enterprise::uid($uid);

        $modalities = collect([
            ['id' => '0', 'title' => 'Todos'],
            ['id' => '1', 'title' => 'Publico'],
            ['id' => '2', 'title' => 'Inactivos'],
        ]);

        $modalities = $modalities->pluck('title', 'id');

        return view('callcenters.views.enterprises.users.users.report')->with([
            'modalities' => $modalities,
            'enterprise' => $enterprise,
        ]);
    }

    public function income($uid){

        $enterprise = Enterprise::uid($uid);

        $courses = $enterprise->courses;
        $courses = $courses->pluck('title', 'id');

        return view('callcenters.views.enterprises.users.users.income')->with([
            'courses' => $courses,
            'enterprise' => $enterprise,
        ]);
    }

    public function import($uid){

        $enterprise = Enterprise::uid($uid);

        return view('callcenters.views.enterprises.users.users.import')->with([
            'enterprise' => $enterprise,
        ]);
    }

    public function importation(Request $request){

        $enterprise = Enterprise::uid($request->enterprise);

        if ($request->hasFile('file') && $request->file('file')->isValid()) {

            try {
                Excel::import(new UsersImport($enterprise->uid), request()->file('file'));
            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {

                $failures = $e->failures();
                //dd($failures);
                return view('callcenters.views.enterprises.users.users.response')->with([
                    'error_message' => $e->getMessage(),
                    'failures' => $failures,
                    'enterprise' => $enterprise,
                ]);
            }
        }

        return redirect()->route('support.supports.users', $enterprise->uid);
    }

    public function generate(Request $request){

        $modalitie = $request->modalitie;
        $enterprise = $request->enterprise;

        return Excel::download(new UsersExport($enterprise, $modalitie), 'REPORTE USUARIOS ' . date('Y-m-d') . '.xlsx');
    }

    public function incoming(Request $request){

        $course = $request->course;
        $enterprise = $request->enterprise;
        $date = explode(" - ", $request->range);
        $start = Carbon::parse($date[0])->startOfDay();
        $end = Carbon::parse($date[1])->endOfDay();

        return Excel::download(new IncomesExport($enterprise, $course, $start, $end), 'REPORTE USUARIOS ' . date('Y-m-d') . '.xlsx');
    }

    public function check(Request $request)
    {
        $user = User::where('identification', $request->identification)->first();

        if ($user) {
            $enterpriseUser = EnterpriseUser::where('user_id', $user->id)->first();

            if ($enterpriseUser) {
                $enterprise = Enterprise::find($enterpriseUser->enterprise_id);

                if ($enterprise) {
                    $distributorEnterprise = DistributorEnterprise::where('enterprise_id', $enterprise->id)->first();

                    if ($distributorEnterprise) {

                        $distributor = Distributor::find($distributorEnterprise->distributor_id);

                        return response()->json([
                            'success' => true,
                            'message' => 'Este usuario ya está registrado y asignado a la empresa: ' . $enterprise->title,
                            'enterprise' => $enterprise->title,
                            'distributor' => $distributor->title,
                            'url' => route('distributor.supports.users', ['uid' => $enterprise->uid])
                        ]);


                    } else {

                        return response()->json([
                            'success' => true,
                            'message' => 'Este usuario ya está registrado en la empresa: ' . $enterprise->title . ', pero no está asignado a ningún distribuidor.',
                            'enterprise' => $enterprise->title,
                            'distributor' => null
                        ]);

                    }
                }
            } else {

                return response()->json([ 'success' => true,
                    'message' => 'Este usuario ya está registrado pero no está asignado a ninguna empresa.',
                    'enterprise' => null
                ]);
            }
        } else {

            return response()->json([
                'success' => false,
                'message' => 'Este usuario no está registrado.'
            ]);

        }

    }


}
